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
  var $paths = array();
  var $config = null; 
  var $db = null;
  var $Schema = null;
  var $commands = array('exiftool', 'convert', 'ffmpeg', 'flvtool2');

  function beforeFilter() {
    Configure::write('Cache.disable', true);
    $this->config = ROOT.DS.APP_DIR.DS.'config'.DS.'database.php';
    $this->paths = array(TMP, USER_DIR);
    return false;
  }

  function beforeRender() {
  }

  /** Checks the current database for missing tables. If tables are missing, it
   * returns an array of creation statements 
    @param Schema Current Schema
    @param Create statements or false if all required tables are in the
    database */
  function __getMissingTables($Schema) {
    $db =& ConnectionManager::getDataSource($this->Schema->connection);

    // Check for required tables and create missing tables
    $sources = $db->listSources();
    $requiredTables = array();
    $create = array();
    foreach ($Schema->tables as $table => $fields) {
      $tableName = $db->fullTableName($table, false);
      $requiredTables[] = $tableName;
      if (!in_array($tableName, $sources)) {
        $create[$table] = $db->createSchema($Schema, $table);
      }
    }
    // set tables sources only to the required tables. This overwrites current
    // list and hides not required tables
    $db->_sources = $requiredTables;

    if (!count($create)) {
      return false;
    }
    return $create;
  }
  
  /** Create tables according to create statements
    @param Schema Current table schema
    @param Array of creation statements
    @return On success it returns false. If error occurs, it returns the
    creation statements */
  function __createTables($Schema, $create) {
    $db =& ConnectionManager::getDataSource($this->Schema->connection);
  
    $errors = array();
    foreach ($create as $table => $sql) {
      if (!$db->_execute($create[$table])) {
        $errors[$table] = $sql;
        $tableName = $db->fullTableName($table, false);
        $this->Logger->err("Could not create table '$tableName'");
        $this->Logger->debug($create[$table]);
      }
    }
    if (!count($errors)) {
      return false;
    }
    return $errors;
  }

  function __getAlteredColumns($Schema) {
    $db =& ConnectionManager::getDataSource($this->Schema->connection);

    $Old = $this->Schema->read();
    $compare = $this->Schema->compare($Old, $Schema);

    // Check changes
    $columns = array();
    foreach ($compare as $table => $changes) {
      $columns[$table] = $db->alterSchema(array($table => $changes), $table);
    }
    
    if (!count($columns)) {
      return false;
    }
    return $columns;
  }

  function __alterColumns($Schema, $columns) {
    $db =& ConnectionManager::getDataSource($Schema->connection);
    $errors = array();
    foreach ($columns as $table => $changes) {
      if (!$db->_execute($changes)) {
        $this->Logger->err("Could not update table $table");
        $this->Logger->debug($changes);
        $errors[$table] = $changes;
      }
    }
    if (!count($errors)) {
      return false;
    }
    return $errors;
  }

  function __hasSession() {
    return $this->Session->check('setup');
  }

  function __checkSession() {
    if (!$this->__hasSession()) {
      $this->Logger->warn('Setup is disabled. Setup session variable is not set.');
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
    return is_readable($this->config);
  }

  function __hasConnection() {
    if (!$this->__hasConfig())
      return false;
    $this->Logger->debug("Check for database connection");

    App::import('Core', 'ConnectionManager');
    $this->db =& ConnectionManager::getDataSource('default');
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
    App::import('model', 'User');

    $this->User =& new User();

    return $this->User->hasAdmins();
  }

  function __hasCommands($commands = null) {
    if (!$this->__hasAdmin())
      return false;

    if ($commands === null) {
      $commands = $this->commands;
    }

    App::import('model', 'Preference');
    $this->Preference =& new Preference();

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
      $this->Logger->warn("Setup is disabled. phTagr is already configured!");
      $this->redirect('/');
    }

    $this->Session->write('setup', true);
    $this->Logger->info("Start Setup of phTagr!");
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

    if (!is_writeable(dirname($this->config)))
      $this->redirect('configreadonly');

    $this->__checkSession();

    if (!empty($this->data)) {
      $output = "<?php 
/** Automatic generated database configuration file */
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
      $file =& new File($this->config, true, 644);
      if ($file->write($output)) {
        $this->Logger->info("Database configuration file '{$this->config}' was written successfully");
        $file->close();
        $this->redirect('database');
      } else {
        $this->Logger->err("Could not write database configuration file '{$this->config}'");
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

  function configreadonly() {
    if ($this->__hasConfig())
      $this->redirect('database');

    // nothing to do
    $this->set('config', $this->config);
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

    App::import('Core', 'CakeSchema');
    $this->Schema =& new CakeSchema(array('connection' => 'default', 'file' => null, 'path' => null, 'name' => null));
    $Schema = $this->Schema->load(array());

    $create = $this->__getMissingTables($Schema);
    if ($create) {
      $errors = $this->__createTables($Schema, $create);
      if ($errors) {
        $tables = array();
        foreach ($errors as $table => $sql) {
          $tables[$table] = $db->fullTableName($table, false);
        }
        $this->Session->write('tables', $tables);
        $this->redirect('tableerror');
      }
      $this->Logger->warn("All missing tables are created: ".implode(', ', array_keys($create)));
    }
    $this->Logger->warn("All tables exists");

    $columns = $this->__getAlteredColumns($Schema);
    if ($columns) {
      $errors = $this->__alterColumns($Schema, $columns);
      if ($errors) {
        $this->Logger->err("Could not allter table: ".implode(', ', array_keys($errors)));
        /*
        $this->Session->write('errors', $errors);
        $this->redirect('columnerror');
        */
      }
      $this->Logger->info("All tables are updated: ".implode(', ', array_keys($columns)));
    }
    $this->Logger->info("All tables are correct");
    $this->Session->setFlash("All required tables are created");
    $this->redirect('user');
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
}
?>
