<?php 
/*
 * phtagr.
 * 
 * social photo gallery for your community.
 * 
 * Copyright (C) 2006-2010 Sebastian Felis, sebastian@phtagr.org
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


class SetupController extends AppController {

  //var $autoRender = false;
  var $components = array('UpgradeSchema');
  var $uses = null;
  var $helpers = array('form', 'html');
  var $core = null; 
  var $dbConfig = null; 
  var $paths = array();
  var $User = null;
  var $Option = null;
  var $commands = array('exiftool', 'convert', 'ffmpeg', 'flvtool2');
  var $checks = array();

  function beforeFilter() {
    Configure::write('Cache.disable', true);
    $this->UpgradeSchema->modelMapping = array('files' => 'MyFile');
    $this->core = CONFIGS.'core.php';
    $this->dbConfig = CONFIGS.'database.php';
    $this->paths = array(TMP, USER_DIR);
    if (isset($this->params['admin']) && $this->params['admin'] && $this->__hasSysOp()) {
      parent::beforeFilter();
    } else {
      return false;
    }
  }

  function beforeRender() {
  }

  /** Initialize the database schema and data source
    @return True if the database source could be loaded */
  function __initDataSource() {
    if (isset($this->checks['initDataSource'])) {
      return $this->checks['initDataSource'];
    }

    if (!$this->__hasConfig()) {
      $this->checks['initDataSource'] = true;
      return false;
    }

    $this->checks['initDataSource'] = $this->UpgradeSchema->initDataSource();
    return $this->checks['initDataSource'];
  }

  function __loadModel($models) {
    if (!$this->UpgradeSchema->isConnected()) {
      Logger::warn("Not connected");
      return false;
    }

    if (!is_array($models)) {
      $models = array($models);
    }
    $success = true;
    foreach($models as $model) {
      if (isset($this->$model)) {
        continue;
      }

      if (!App::import('model', $model)) {
        Logger::err("Could not load model '$model'");
        $success = false;
        continue;
      }
      $this->$model =& new $model();
    }
    return $success;
  }

  function __hasSalt() {
    if (isset($this->checks['hasSalt'])) {
      return $this->checks['hasSalt'];
    }

    Logger::debug("Check for settings in core");
    if (Configure::read('Security.salt') == 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi') {
      Logger::warn("Detecting unsecure security salt");
      $this->checks['hasSalt'] = false;
      return false;
    }

    $this->checks['hasSalt'] = true;
    return true;
  }

  function __hasSession() {
    if (isset($this->checks['hasSession'])) {
      return $this->checks['hasSession'];
    }

    $this->checks['hasSession'] = $this->Session->check('setup');
    return $this->checks['hasSession'];
  }

  function __checkSession() {
    if (!$this->__hasSession()) {
      Logger::warn('Setup is disabled. Setup session variable is not set.');
      Logger::bt();
      $this->redirect('/');
    }
  }

  /** Checks for required writable paths */
  function __hasPaths() {
    if (isset($this->checks['hasPaths'])) {
      return $this->checks['hasPaths'];
    }

    Logger::debug("Check for writable paths");
    foreach ($this->paths as $path) {
      if (!is_dir($path) || !is_writeable($path)) {
        $this->checks['hasPaths'] = false;
        return false;
      }
    }
    $this->checks['hasPaths'] = true;
    return true;
  }

  /** Checks the existence of the database configuration */
  function __hasConfig() {
    if (isset($this->checks['hasConfig'])) {
      return $this->checks['hasConfig'];
    }

    if (!$this->__hasPaths()) {
      return false;
    }

    Logger::debug("Check for database configuration");
    $this->checks['hasConfig'] = is_readable($this->dbConfig);
    return $this->checks['hasConfig'];
  }

  /** Checks the database connection */
  function __hasConnection() {
    if (isset($this->checks['hasConnection'])) {
      return $this->checks['hasConnection'];
    }

    if (!$this->__hasConfig()) {
      $this->checks['hasConnection'] = false;
      return false;
    }
    Logger::debug("Check for database connection");

    if (!$this->UpgradeSchema->initDataSource()) {
      $this->checks['hasConnection'] = false;
      return false;
    }

    $this->checks['hasConnection'] = $this->UpgradeSchema->isConnected();
    return $this->checks['hasConnection'];
  }

  /** Checks for existing tables
    @param tables. Array of tables names. Default array('users')
    @return True if all given tables exists */
  function __hasTables($tables = array('users')) {
    if (isset($this->checks['hasTables'])) {
      return $this->checks['hasTables'];
    }

    if (!$this->__hasConnection()) {
      $this->checks['hasTables'] = false;
      return false;
    }

    Logger::debug("Check for initial required tables");
    if (!$this->UpgradeSchema->hasTables($tables)) {
      Logger::debug("require tables");
      $this->checks['hasTables'] = false;
      return false;
    } else {
      $this->checks['hasTables'] = true;
      return true;
    }
  }

  /** Check for administration account */
  function __hasSysOp() {
    if (isset($this->checks['hasSysOp'])) {
      return $this->checks['hasSysOp'];
    }
    if (!$this->__hasTables(array('users'))) {
      $this->checks['hasSysOp'] = false;
      return false;
    }

    Logger::debug("Check for admin account");
    if (!$this->__loadModel('User')) {
      $this->checks['hasSysOp'] = false;
      return false;
    }

    $this->checks['hasSysOp'] = $this->User->hasAnyWithRole(ROLE_SYSOP);
    return $this->checks['hasSysOp'];
  }

  function __hasCommands($commands = null) {
    if (!$this->__hasSysOp()) {
      return false;
    }

    if ($commands === null) {
      $commands = $this->commands;
    }

    if (!$this->__loadModel('Option')) {
      return false;
    }

    if (!count($commands)) {
      return $this->Option->hasAny(array('user_id' => 0, 'name' => 'LIKE bin.%'));
    } else {
      foreach ($commands as $command) {
        if (!$this->Option->hasAny(array('user_id' => 0, 'name' => 'bin.'.$command))) {
          Logger::trace("Command '$command' is missing");
          return false;
        }
      }
      return true;
    }
  }

  /** Setup entry. Dispatches preparation, installation or upgrade */
  function index() {
    if ($this->__hasSysOp()) {
      if ($this->hasRole(ROLE_SYSOP)) {
        Logger::verbose("Redirect to upgrade");
        $this->redirect('/admin/setup/upgrade');
      } else {
        Logger::warn("Setup is disabled. phTagr is already configured!");
        $this->Session->write('loginRedirect', '/setup');
        $this->redirect('/users/login');
      }
    } elseif (!$this->__hasSalt()) {
      $this->redirect('salt');
    }

    $this->Session->write('setup', true);
    Logger::info("Start Setup of phTagr!");
  }

  /** Generate a random salt string 
    @return Random salt string */
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
      $this->Session->setFlash(sprintf(__("Could not write configuration to '%s'", true), $this->core));
      Logger::err("Could not write configuration to '$this->core'");
    } else {
      Configure::write('Security.salt', $salt);
      $this->Session->destroy();
      $this->Session->renew();

      $this->Session->setFlash(__("Update core settings", true));
      Logger::info("Set new security salt to '$this->core'");
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

    Logger::info("Missing directories: ".implode(', ', $missing).", readonly Directories: ".implode(', ', $readonly));
    $this->set('missing', $missing);
    $this->set('readonly', $readonly);
  }

  function config() {
    if (!$this->__hasPaths()) {
      $this->redirect('path');
    }

    $error = $this->Session->read('configError');
    if ($this->__hasConfig() && !$error) {
      $this->redirect('database');
    }

    if (!is_writeable(dirname($this->dbConfig))) {
      $this->redirect('configro');
    }

    $this->__checkSession();
    $this->Session->delete('configError');

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
        Logger::info("Database configuration file '{$this->dbConfig}' was written successfully");
        $file->close();
        $this->redirect('database');
      } else {
        Logger::err("Could not write database configuration file '{$this->dbConfig}'");
        $this->Session->setFlash(__("Could not write database configuration file", true));
      }
      $file->close();
    } else {
      $this->data['db']['host'] = 'localhost';
      $this->data['db']['database'] = 'phtagr';
      $this->data['db']['login'] = 'phtagr';
    }
    Logger::info("Request database configuration");
  }

  function configro() {
    $error = $this->Session->read('configError');
    if ($this->__hasConfig() && !$error) {
      $this->redirect('database');
    }

    $this->__checkSession();
    $this->Session->delete('configError');

    // nothing to do
    $this->set('dbConfig', $this->dbConfig);
    Logger::info("Request database configuration (readonly)");
  }

  function database() {
    if (!$this->__hasConfig()) {
      $this->redirect('config');
    }

    if (!$this->__hasConnection()) {
      $this->Session->setFlash(__('Could not connect to database. Please check your database configuration!', true));
      $this->Session->write('configError', true);
      $this->redirect('config');
    }

    if ($this->__hasSysOp()) {
      $this->redirect('system');
    }

    $this->__checkSession();

    $Schema = $this->UpgradeSchema->loadSchema();
    Logger::info("Check current database schema");
    $errors = $this->UpgradeSchema->upgrade();

    $check = false;
    if ($errors['tables']) {
      $check = true;
      Logger::err("Not all tables could be created: ".array_keys($errors['tables']));
    } else {
      Logger::info("All tables exists");
    }
    if ($errors['columns']) {
      $check = true;
      Logger::err("Not all columngs could be altered: ".array_keys($errors['columns']));
    } else {
      Logger::info("All tables are correct");
    }

    if (!$check) {
      $this->Session->setFlash(__("All required tables are created", true));
      $this->redirect('user');
    } else {
      Logger::trace($errors);
      $this->Session->setFlash(__("Could not create tables correctly. Please see logfile for details", true));
    }
  }

  function user() {
    if (!$this->__hasTables()) {
      $this->redirect('database');
    }

    if ($this->__hasSysOp()) {
      $this->redirect('system');
    }

    $this->__checkSession();

    if (!empty($this->data)) {
      $this->data['User']['role'] = ROLE_ADMIN;
      $this->data['User']['quota'] = '100 MB';
      $this->User->create($this->data);
      if (empty($this->data['User']['confirm'])) {
        $this->User->invalidate('confirm', 'Password confirmation is missing');
      }
      if ($this->User->save()) {
        $userId = $this->User->getLastInsertID();
        $this->Session->write('User.id', $userId);
        $this->Session->write('User.role', ROLE_ADMIN);
        $this->Session->write('User.username', $this->data['User']['username']);
        Logger::info("Admin account '{$this->data['User']['username']}' was created");
        $this->Session->setFlash(__("Admin account was successfully created", true));
        $this->redirect('system');
      } else {
        Logger::err("Admin account '{$this->data['User']['username']}' could not be created");
        $this->Session->setFlash(__("Could not create admin account. Please retry", true));
      }
    } elseif (!isset($this->data['User']['username'])) {
      $this->data['User']['username'] = 'admin';
    }
    Logger::info("Request account data for the admin");
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
    if (!$this->__hasSysOp()) {
      $this->redirect('user');
    }

    $this->requireRole(ROLE_SYSOP);

    $this->__checkSession();

    // If a command is missing, we should not redirect
    if ($this->__hasCommands()) {
      $this->redirect('/');
    }

    Logger::info("Check for required external programs");
    $missing = array();
    if (!empty($this->data)) {
      foreach ($this->commands as $command) {
        $bin = Set::extract($this->data, 'bin.'.$command);
        $file = new File($bin);
        if ($file->executable()) {
          $this->Option->setValue('bin.'.$command, $bin, 0);
          Logger::debug("Write 'bin.$command'='$bin'");
        } else {
          $missing[] = $command;    
          Logger::err("Command for '$command': '$bin' is missing or not executeable!");
        }
      }
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
    if (!$this->__hasSysOp()) {
      $this->redirect('user');
    }

    $this->requireRole(ROLE_SYSOP);

    $this->__checkSession();

    Logger::info("Setup complete");

    // cleanup
    $this->Session->delete('setup');
  }

  function __deleteModelCache() {
    $modelCacheDir = TMP.'cache'.DS.'models'.DS;
    $folder = new Folder($modelCacheDir);
    $modelCacheFiles = $folder->find('cake_model_.*');
    foreach ($modelCacheFiles as $file) {
      if (!@unlink($folder->addPathElement($modelCacheDir, $file))) {
        Logger::err("Could not delete model cache file '$file' in '$modelCacheDir'");
      }
    }
  }

  function admin_upgrade($action = null) {
    if (!$this->__hasSysOp()) 
      $this->redirect('/setup');
    $this->requireRole(ROLE_SYSOP);

    if (!$this->UpgradeSchema->loadSchema()) {
      Logger::err("Could not load schema");
    }
    if (!$this->UpgradeSchema->requireUpgrade()) {
      $this->redirect('/admin/setup/uptodate');
    }

    $errors = false;
    if ($action == 'run') {
      $errors = $this->UpgradeSchema->upgrade();
      if ($errors == false) {
        $this->UpgradeSchema->deleteModelCache();
        $this->Session->setFlash(__("Database was upgraded successfully", true));
        $this->redirect('/admin/setup/uptodate');
      } else {
        $this->Session->setFlash(__("The database could not upgraded completely. The log file might discover the issue", true));
      }
    }
    $this->set('errors', $errors);
  }

  function admin_uptodate() {
    if (!$this->__hasSysOp()) {
      $this->redirect('/setup');
    }
    $this->requireRole(ROLE_SYSOP);

    $this->UpgradeSchema->loadSchema();
    if ($this->UpgradeSchema->requireUpgrade()) {
      $this->redirect('/admin/setup/upgrade');
    }
  }
}
?>
