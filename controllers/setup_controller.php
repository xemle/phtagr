<?php 
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2008 Sebastian Felis, sebastian@phtagr.org
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the 
 * License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

uses('file', 'model' . DS . 'schema');

class SetupController extends AppController {

  //var $autoRender = false;
  var $uses = null;
  var $helpers = array('form', 'html');
  var $core = null; 
  var $dbConfig = null; 
  var $paths = array();
  var $db = null;
  var $Schema = null;
  var $User = null;
  var $Preference = null;
  var $commands = array('exiftool', 'convert', 'ffmpeg', 'flvtool2');

  function beforeFilter() {
    Configure::write('Cache.disable', true);
    $this->core = CONFIGS.'core.php';
    $this->dbConfig = CONFIGS.'database.php';
    $this->paths = array(TMP, USER_DIR);
    if (isset($this->params['admin']) && $this->params['admin'] && $this->__hasAdmin()) {
      parent::beforeFilter();
    } else {
      return false;
    }
  }

  function beforeRender() {
  }

  function __initDatabase() {
    if (!$this->__hasConfig()) {
      return false;
    }

    App::import('Core', 'CakeSchema');
    $this->Schema =& new CakeSchema(array('connection' => 'default', 'file' => null, 'path' => null, 'name' => null));

    App::import('Core', 'ConnectionManager');
    $this->db =& ConnectionManager::getDataSource($this->Schema->connection);

    return true;
  }

  /** Checks the current database for missing tables. If tables are missing, it
   * returns an array of creation statements 
    @param Schema Current Schema
    @param Create statements or false if all required tables are in the
    database */
  function __getMissingTables($Schema) {
    // Check for required tables and create missing tables
    $sources = $this->db->listSources();
    $requiredTables = array();
    $missingTables = array();
    foreach ($Schema->tables as $table => $fields) {
      $tableName = $this->db->fullTableName($table, false);
      $requiredTables[] = $tableName;
      if (!in_array($tableName, $sources)) {
        $missingTables[$table] = $this->db->createSchema($Schema, $table);
      }
    }
    // set tables sources only to the required tables. This overwrites current
    // list and hides not required tables
    $this->db->_sources = $requiredTables;

    if (!count($missingTables)) {
      return false;
    }
    return $missingTables;
  }
  
  /** Create tables according to create statements
    @param Schema Current table schema
    @param Array of creation statements
    @return On success it returns false. If error occurs, it returns the
    creation statements */
  function __createTables($Schema, $newTables) {
    if (!$newTables)
      return false;

    $errors = array();
    foreach ($newTables as $table => $sql) {
      if (!$this->db->_execute($sql)) {
        $errors[$table] = $sql;
        $tableName = $this->db->fullTableName($table, false);
        $this->Logger->err("Could not create table '$tableName'");
        $this->Logger->debug($sql);
      }
    }
    if (!count($errors)) {
      return false;
    }
    return $errors;
  }

  function __getAlteredColumns($Schema) {
    $Old = $this->Schema->read();
    $compare = $this->Schema->compare($Old, $Schema);

    // Check changes
    $columns = array();
    $sources = $this->db->listSources();
    foreach ($compare as $table => $changes) {
      $tableName = $this->db->fullTableName($table, false);
      if (!in_array($tableName, $sources))
        continue;
      $columns[$table] = $this->db->alterSchema(array($table => $changes), $table);
    }
    
    if (!count($columns)) {
      return false;
    }
    return $columns;
  }

  function __alterColumns($Schema, $columns) {
    if (!$columns) 
      return false;

    $errors = array();
    foreach ($columns as $table => $sql) {
      if (!$this->db->_execute($sql)) {
        $errors[$table] = $sql;
        $tableName = $this->db->fullTableName($table, false);
        $this->Logger->err("Could not update table '$tableName'");
        $this->Logger->debug($sql);
      }
    }
    if (!count($errors)) {
      return false;
    }
    return $errors;
  }

  function __requireUpgrade($Schema) {
    $missingTables = $this->__getMissingTables($Schema);
    if ($missingTables) {
      return true;
    }
    $alterColumns = $this->__getAlteredColumns($Schema);
    if ($alterColumns) {
      return true;
    }
    return false;
  }

  function __createMissingTables($Schema) {
    $missingTables = $this->__getMissingTables($Schema);
    return $this->__createTables($Schema, $missingTables);
  }

  function __upgradeTables($Schema) {
    $alterColumns = $this->__getAlteredColumns($Schema);
    return $this->__alterColumns($Schema, $alterColumns);
  }

  function __upgradeDatabase($Schema) {
    $errorTables = $this->__createMissingTables($Schema);
    $errorColumns = $this->__upgradeTables($Schema);
    if ($errorTables || $errorColumns) {
      return array('tables' => $errorTables, 'columns' => $errorColumns);
    } else {
      return false;
    }
  }

  function __loadModel($models) {
    if (!$this->__hasConnection()) {
      return false;
    }

    if (!is_array($models)) {
      $models = array($models);
    }
    $success = true;
    foreach($models as $model) {
      if (isset($this->$model))
        continue;

      if (!App::import('model', $model)) {
        $this->Logger->err("Could not load model '$model'");
        $success = false;
        continue;
      }
      $this->$model =& new $model();
    }
    return $success;
  }

  function __hasSalt() {
    $this->Logger->debug("Check for settings in core");
    if (Configure::read('Security.salt') == 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi') {
      $this->Logger->warn("Detecting unsecure security salt");
      return false;
    }

    return true;
  }

  function __hasSession() {
    return $this->Session->check('setup');
  }

  function __checkSession() {
    if (!$this->__hasSession()) {
      $this->Logger->warn('Setup is disabled. Setup session variable is not set.');
      $this->Logger->bt();
      $this->redirect('/');
    }
  }

  function __hasPaths() {
    $this->Logger->debug("Check for paths");
    foreach ($this->paths as $path) {
      if (!is_dir($path) || !is_writeable($path))
        return false;
    }
    return true;
  }

  function __hasConfig() {
    if (!$this->__hasPaths())
      return false;
    $this->Logger->debug("Check for database configuration");
    return is_readable($this->dbConfig);
  }

  function __hasConnection() {
    if (!$this->__hasConfig())
      return false;
    $this->Logger->debug("Check for database connection");

    if (!$this->__initDatabase())
      return false;

    return $this->db->connected;
  }

  function __hasTables($tables = array('users')) {
    if (!$this->__hasConnection())
      return false;

    $this->Logger->debug("Check for initial required tables");
    $sources = $this->db->listSources();
    foreach ($tables as $table) {
      $tableName = $this->db->fullTableName($table, false);
      if (!in_array($tableName, $sources))
        return false;
    }
    return true;
  }

  function __hasAdmin() {
    if (!$this->__hasTables())
      return false;

    $this->Logger->debug("Check for admin account");
    if (!$this->__loadModel('User')) {
      return false;
    }

    return $this->User->hasAdmins();
  }

  function __hasCommands($commands = null) {
    if (!$this->__hasAdmin())
      return false;

    if ($commands === null) {
      $commands = $this->commands;
    }

    if (!$this->__loadModel('Preference')) {
      return false;
    }

    if (!count($commands)) {
      return $this->Preference->hasAny(array('user_id' => 0, 'name' => 'LIKE bin.%'));
    } else {
      foreach ($commands as $command) {
        if (!$this->Preference->hasAny(array('user_id' => 0, 'name' => 'bin.'.$command))) {
          $this->Logger->trace("Command '$command' is missing");
          return false;
        }
      }
      return true;
    }
  }

  function index() {
    if ($this->__hasAdmin()) {
      if ($this->hasRole(ROLE_ADMIN)) {
        $this->redirect('/admin/setup/upgrade');
      } else {
        $this->Logger->warn("Setup is disabled. phTagr is already configured!");
        $this->redirect('/');
      }
    } elseif (!$this->__hasSalt()) {
      $this->redirect('salt');
    }

    $this->Session->write('setup', true);
    $this->Logger->info("Start Setup of phTagr!");
  }

  function __generateSalt() {
    $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $chars .= 'abcdefghijklmnopqrstuvwxyz';
    $chars .= '0123456789';
    $len = strlen($chars);

    srand(getMicrotime()*1000);
    $salt = '';
    for($i = 0; $i < 40; $i++) {
      $salt .= $chars[rand(0, $len-1)];
    }

    return $salt;
  }

  function salt() {
    if ($this->__hasSalt())
      $this->redirect('index');

    if (!is_writeable(dirname($this->core))) {
      $this->redirect('saltro');
    }

    $oldSalt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9m';
    $salt = $this->__generateSalt();

    $file =& new File($this->core);
    $content = $file->read();
    $newContent = preg_replace("/$oldSalt/", $salt, $content);
    if (!$file->write($newContent)) {
      $this->Session->setFlash("Could not write configureation to '$this->core'");
      $this->Logger->err("Could not write configuration to '$this->core'");
    } else {
      Configure::write('Security.salt', $salt);
      $this->Session->destroy();
      $this->Session->renew();

      $this->Session->setFlash("Update core settings");
      $this->Logger->info("Set new security salt to '$this->core'");
      $this->redirect('index');
    }
  }

  function saltro() {
    if ($this->__hasSalt())
      $this->redirect('index');

    if (is_writeable(dirname($this->core))) {
      $this->redirect('salt');
    } 

    $oldSalt = Configure::read('Security.salt');

    $file =& new File($this->core);
    $content = $file->read();
    $lines = preg_split('/\n/', $content);
    $c = count($lines);
    for($i = 0; $i < $c; $i++) {
      if (preg_match("/'$oldSalt'/", $lines[$i])) {
        $this->set('line', $i+1);
        break;
      }
    }

    $this->set('oldSalt', $oldSalt);
    $this->set('salt', $this->__generateSalt());
    $this->set('file', $this->core);
  }

  function path() {
    // Check temporary directory for sessions, logs, etc.
    if ($this->__hasPaths()) 
      $this->redirect('config');

    $this->__checkSession();

    $missing = array();
    $readonly = array();
    foreach ($this->paths as $path) {
      if (!is_dir($path))
        $missing[] = $path;
      elseif (!is_writeable($path))
        $readonly[] = $path;
    }

    $this->Logger->info("Missing directories: ".implode(', ', $missing).", readonly Directories: ".implode(', ', $readonly));
    $this->set('missing', $missing);
    $this->set('readonly', $readonly);
  }

  function config() {
    if (!$this->__hasPaths())
      $this->redirect('path');

    if ($this->__hasConfig())
      $this->redirect('database');

    if (!is_writeable(dirname($this->dbConfig)))
      $this->redirect('configro');

    $this->__checkSession();

    if (!empty($this->data)) {
      $output = "<?php 
/** 
 * Automatic generated database configuration file by phTagr
 *
 * Date: ".date("Y-m-d H:i:s")."
 */
class DATABASE_CONFIG
{
  var \$default = array('driver' => 'mysql',
                'connect' => 'mysql_connect',
                'persistent' => true,
                'host' => '{$this->data['db']['host']}',
                'login' => '{$this->data['db']['login']}',
                'password' => '{$this->data['db']['password']}',
                'database' => '{$this->data['db']['database']}',
                'encoding' => 'utf8',
                'prefix' => '{$this->data['db']['prefix']}');
}
?>";
      $file =& new File($this->dbConfig, true, 644);
      if ($file->write($output)) {
        $this->Logger->info("Database configuration file '{$this->dbConfig}' was written successfully");
        $file->close();
        $this->redirect('database');
      } else {
        $this->Logger->err("Could not write database configuration file '{$this->dbConfig}'");
        $this->Session->setFlash("Could not write database configuration file");
      }
      $file->close();
    } else {
      $this->data['db']['host'] = 'localhost';
      $this->data['db']['database'] = 'phtagr';
      $this->data['db']['login'] = 'phtagr';
    }
    $this->Logger->info("Request database configuration");
  }

  function configro() {
    if ($this->__hasConfig())
      $this->redirect('database');

    // nothing to do
    $this->set('dbConfig', $this->dbConfig);
    $this->Logger->info("Request database configuration (readonly)");
  }

  function database() {
    if (!$this->__hasConfig())
      $this->redirect('config');

    if (!$this->__hasConnection()) {
      $this->Session->setFlash('Could not connect to database. Please check your database configuration!');
      $this->redirect('config');
    }

    if ($this->__hasAdmin())
      $this->redirect('system');

    $this->__checkSession();

    $this->Logger->info("Check current database schema");

    if (!$this->__initDatabase()) {
      $this->Logger->err("Could not setup database connection");
      $this->redirect('config');
    }

    $Schema = $this->Schema = $this->Schema->load(array());
    $errors = $this->__upgradeDatabase($Schema);

    $check = false;
    $this->Logger->trace($errors);
    if ($errors['tables']) {
      $check = true;
      $this->Logger->err("Not all tables could be created: ".array_keys($errors['tables']));
    } else {
      $this->Logger->info("All tables exists");
    }
    if ($errors['columns']) {
      $check = true;
      $this->Logger->err("Not all columngs could be altered: ".array_keys($errors['columns']));
    } else {
      $this->Logger->info("All tables are correct");
    }

    if (!$check) {
      $this->Session->setFlash("All required tables are created");
      $this->redirect('user');
    } else {
      $this->Session->setFlash("Could not create tables correctly. Please see logfile for details");
    }
  }

  function user() {
    if (!$this->__hasTables()) 
      $this->redirect('database');

    if ($this->__hasAdmin())
      $this->redirect('system');

    $this->__checkSession();

    if (!empty($this->data)) {
      $this->data['User']['role'] = ROLE_ADMIN;
      $this->User->create($this->data);
      if (empty($this->data['User']['confirm']))
        $this->User->invalidate('confirm', 'Password confirmation is missing');
      if ($this->User->save()) {
        $userId = $this->User->getLastInsertID();
        $this->Session->write('User.id', $userId);
        $this->Session->write('User.role', ROLE_ADMIN);
        $this->Session->write('User.username', $this->data['User']['username']);
        $this->Logger->info("Admin account '{$this->data['User']['username']}' was created");
        $this->Session->setFlash("Admin account was successfully created");
        $this->redirect('system');
      } else {
        $this->Logger->err("Admin account '{$this->data['User']['username']}' could not be created");
        $this->Session->setFlash("Could not create admin account. Please retry");
      }
    } elseif (!isset($this->data['User']['username'])) {
      $this->data['User']['username'] = 'admin';
    }
    $this->Logger->info("Request account data for the admin");
  }
  
  function __findCommand($command) {
    $paths = array('/usr/local/bin/', '/usr/bin/');
    foreach ($paths as $path) {
      $file = new File($path.$command);
      if ($file->executable()) {
        return $file->pwd();
      }
    }
    return false;
  }

  function __checkMp3Support($bin) {
    $file = new File($bin);
    if (!$file->executable()) {
      return false;
    }
    $command = $file->pwd()." -version";
    if (substr(PHP_OS, 0, 3) != "WIN") {
      $command .= ' 2>&1';
    }

    $output = array();
    $result = -1;
    exec($command, &$output, &$result);
    $output = implode(' ', $output);

    if (preg_match('/--enable-libmp3lame/', $output)) {
      return true;
    } else {
      return false;
    }
  }

  function system() {
    if (!$this->__hasAdmin())
      $this->redirect('user');

    $this->requireRole(ROLE_ADMIN);

    $this->__checkSession();

    // If a command is missing, we should not redirect
    if ($this->__hasCommands())
      $this->redirect('/');

    $this->Logger->info("Check for required external programs");
    $missing = array();
    if (!empty($this->data)) {
      foreach ($this->commands as $command) {
        $bin = Set::extract($this->data, 'bin.'.$command);
        $file = new File($bin);
        if ($file->executable()) {
          $this->Preference->setValue('bin.'.$command, $bin, 0);
          $this->Logger->debug("Write 'bin.$command'='$bin'");
        } else {
          $missing[] = $command;    
          $this->Logger->err("Command for '$command': '$bin' is missing or not executeable!");
        }
      }
      if (!count($missing))
        $this->redirect('finish');
    } else {
      foreach ($this->commands as $command) {
        $bin = $this->__findCommand($command);
        $this->data['bin'][$command] = $bin;
        if (!$bin) {
          $missing[] = $command;
        }
      }
    }
    if (isset($this->data['bin']['ffmpeg'])) {
      $this->set('mp3Support', $this->__checkMp3Support($this->data['bin']['ffmpeg']));
    }
    $this->set('missing', $missing);
  }
  
  function finish() {
    if (!$this->__hasCommands()) {
      $this->redirect('system');
    }

    $this->requireRole(ROLE_ADMIN);

    $this->__checkSession();

    $this->Logger->info("Setup complete");

    // cleanup
    $this->Session->delete('setup');
  }

  function admin_upgrade($action = null) {
    if (!$this->__hasAdmin()) 
      $this->redirect('/setup');
    $this->requireRole(ROLE_ADMIN);

    $Schema = $this->Schema->load(array());
    if (!$this->__requireUpgrade($Schema))
      $this->redirect('/admin/setup/uptodate');

    $errors = false;
    if ($action == 'run') {
      $errors = $this->__upgradeDatabase($Schema);
      if ($errors == false) {
        $this->Session->setFlash("Database was upgraded successfully");
        $this->redirect('/admin/setup/uptodate');
      } else {
        $this->Session->setFlash("The database could not upgraded completely. The log file might discover the issue");
      }
    }
    $this->set('errors', $errors);
  }

  function admin_uptodate() {
    if (!$this->__hasAdmin()) 
      $this->redirect('/setup');
    $this->requireRole(ROLE_ADMIN);

    $Schema = $this->Schema->load(array());
    if ($this->__requireUpgrade($Schema))
      $this->redirect('/admin/setup/upgrade');
  }
}
?>
