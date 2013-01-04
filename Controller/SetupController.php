<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

App::uses('File', 'Utility');

class SetupController extends AppController {

  //var $autoRender = false;
  var $components = array('UpgradeSchema');
  var $uses = false;
  var $helpers = array('Form', 'Html');
  var $core = null;
  var $dbConfig = null;
  var $paths = array();
  var $User = null;
  var $Option = null;
  var $commands = array('exiftool', 'convert', 'ffmpeg', 'flvtool2');
  var $checks = array();
  var $Migration = null;
  var $Version = null;

  public function beforeFilter() {
    $this->layout = 'backend';
    Configure::write('Cache.disable', true);
    $this->UpgradeSchema->modelMapping = array('files' => 'MyFile');
    $this->core = CONFIGS.'core.php';
    $this->dbConfig = CONFIGS.'database.php';
    $this->paths = array(TMP, USER_DIR);
    if (isset($this->request->params['admin']) && $this->request->params['admin'] && $this->__hasSysOp()) {
      parent::beforeFilter();
    } else {
      $this->layout = 'default';
      return false;
    }
  }

  public function beforeRender() {
  }

  /**
   * Initialize the database schema and data source
   *
   * @return True if the database source could be loaded
   */
  public function __initDataSource() {
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

  /**
   * Load models of given array
   *
   * @param models array of models
   * @return true on success
   */
  public function __loadModel($models) {
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

      if (!App::import('Model', $model)) {
        Logger::err("Could not load model '$model'");
        $success = false;
        continue;
      }
      $this->$model = new $model();
    }
    return $success;
  }

  public function __hasSalt() {
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

  public function __hasSession() {
    if (isset($this->checks['hasSession'])) {
      return $this->checks['hasSession'];
    }

    $this->checks['hasSession'] = $this->Session->check('setup');
    return $this->checks['hasSession'];
  }

  public function __checkSession() {
    if (!$this->__hasSession()) {
      Logger::warn('Setup is disabled. Setup session variable is not set.');
      Logger::bt();
      $this->redirect('/');
    }
  }

  /**
   * Checks for required writable paths
   */
  public function __hasPaths() {
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
  public function __hasConfig() {
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

  /**
   * Checks the database connection
   */
  public function __hasConnection() {
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

  /**
   * Checks for existing tables
   *
   * @param tables. Array of tables names. Default array('users')
   * @return True if all given tables exists
   */
  public function __hasTables($tables = array('users')) {
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

  /**
   * Check for administration account
   */
  public function __hasSysOp() {
    if (isset($this->checks['hasSysOp'])) {
      return $this->checks['hasSysOp'];
    }
    if (!$this->__hasTables(array('users'))) {
      $this->checks['hasSysOp'] = false;
      return false;
    }

    Logger::debug("Check for admin account");
    if (!$this->__loadModel(array('User', 'Option'))) {
      $this->checks['hasSysOp'] = false;
      return false;
    }

    $this->checks['hasSysOp'] = $this->User->hasAnyWithRole(ROLE_SYSOP);
    return $this->checks['hasSysOp'];
  }

  public function __hasCommands($commands = null) {
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

  public function &getUser() {
    if (!$this->__hasSysOp() && $this->Session->read('setup')) {
      $user = array('User' => array('id' => 0, 'username' => '', 'role' => ROLE_ADMIN));
      return $user;
    }
    return parent::getUser();
  }

  /**
   * Setup entry. Dispatches preparation, installation or upgrade
   */
  public function index() {
    if ($this->__hasSysOp()) {
      if ($this->hasRole(ROLE_SYSOP)) {
        Logger::verbose("Redirect to upgrade");
        $this->redirect('/system/upgrade');
      } else {
        Logger::warn("Setup is disabled. phTagr is already configured!");
        $this->Session->write('loginRedirect', '/setup');
        $this->redirect('/users/login');
      }
    }
    $this->Session->write('setup', true);
    Logger::info("Start Setup of phTagr!");

    if (!$this->__hasSalt()) {
      $this->redirect('salt');
    }
  }

  /** Generate a random salt string
    @return Random salt string */
  public function __generateSalt() {
    $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $chars .= 'abcdefghijklmnopqrstuvwxyz';
    $chars .= '0123456789';
    $max = strlen($chars) - 1;

    srand(microtime(true)*1000);
    $salt = '';
    for($i = 0; $i < 40; $i++) {
      $salt .= $chars[rand(0, $max)];
    }

    return $salt;
  }

  public function salt() {
    if ($this->__hasSalt())
      $this->redirect('index');

    if (!is_writeable(dirname($this->core)) || !is_writeable($this->core)) {
      $this->redirect('saltro');
    }

    $oldSalt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9m';
    $salt = $this->__generateSalt();

    $file = new File($this->core);
    $content = $file->read();
    $newContent = preg_replace("/$oldSalt/", $salt, $content);
    if (!$file->write($newContent)) {
      $this->Session->setFlash(__("Could not write configuration to '%s'", $this->core));
      Logger::err("Could not write configuration to '$this->core'");
    } else {
      Configure::write('Security.salt', $salt);
      $this->Session->destroy();
      $this->Session->renew();

      $this->Session->setFlash(__("Update core settings"));
      Logger::info("Set new security salt to '$this->core'");
      $this->redirect('index');
    }
  }

  public function saltro() {
    if ($this->__hasSalt()) {
      $this->redirect('index');
		}

    if (is_writeable(dirname($this->core)) && is_writeable($this->core)) {
      $this->redirect('salt');
    }

    $oldSalt = Configure::read('Security.salt');

    $file = new File($this->core);
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

  public function path() {
    if ($this->__hasPaths()) {
      $this->redirect('config');
		}

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

  public function config() {
    if (!$this->__hasPaths()) {
      $this->redirect('path');
    }

    $error = $this->Session->check('configError') ? $this->Session->read('configError') : true;
    if ($this->__hasConfig() && !$error) {
      $this->redirect('database');
    }

    if (!is_writeable(dirname($this->dbConfig))) {
      $this->redirect('configro');
    }

    $this->__checkSession();
    $this->Session->delete('configError');

    if (!empty($this->request->data)) {
      $output = "<?php
/**
 * Automatic generated database configuration file by phTagr setup
 *
 * Creation date: ".date("Y-m-d H:i:s")."
 */
class DATABASE_CONFIG {

  public \$default = array(
    'datasource' => 'Database/Mysql',
    'persistent' => true,
    'host' => '{$this->request->data['host']}',
    'login' => '{$this->request->data['login']}',
    'password' => '{$this->request->data['password']}',
    'database' => '{$this->request->data['database']}',
    'prefix' => '{$this->request->data['prefix']}',
    'encoding' => 'utf8'
  );

  public \$test = array(
    'datasource' => 'Database/Mysql',
    'persistent' => true,
    'host' => '{$this->request->data['host']}',
    'login' => '{$this->request->data['login']}',
    'password' => '{$this->request->data['password']}',
    'database' => '{$this->request->data['database']}',
    'prefix' => '{$this->request->data['prefix']}test_',
    'encoding' => 'utf8'
  );
}
";
      $file = new File($this->dbConfig, true, 644);
      if ($file->write($output)) {
        Logger::info("Database configuration file '{$this->dbConfig}' was written successfully");
        $file->close();
        $this->redirect('database');
      } else {
        Logger::err("Could not write database configuration file '{$this->dbConfig}'");
        $this->Session->setFlash(__("Could not write database configuration file"));
      }
      $file->close();
      unset($this->request->data['password']);
      $this->Session->write('configData', $this->request->data);
    } else {
      if ($this->Session->check('configData')) {
        $this->request->data = $this->Session->read('configData');
      } else {
        $this->request->data('host', 'localhost');
        $this->request->data('database', 'phtagr');
        $this->request->data('login', 'phtagr');
      }
    }
    Logger::info("Request database configuration");
  }

  public function configro() {
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

  public function database() {
    if (!$this->__hasConfig()) {
      $this->redirect('config');
    }

    if (!$this->__hasConnection()) {
      $this->Session->setFlash(__('Could not connect to database. Please check your database configuration!'));
      $this->Session->write('configError', true);
      $this->redirect('config');
    }

    if ($this->__hasSysOp()) {
      $this->redirect('system');
    }

    $this->__checkSession();

    try {
      if (!$this->__loadMigration()) {
        $this->Session->setFlash(__('Could not initialize database migration'));
        Logger::error("Initialization of database migration failed");
        return;
      }
      $maxVersion = max(array_keys($this->Migration->getMapping('app')));
      if (!$this->Migration->run(array('type' => 'app', 'direction' => 'up', 'version' => $maxVersion))) {
        $this->Session->setFlash(__('Could not initialize database'));
        Logger::error("Initial database migration failed");
        return;
      }
      Logger::info("Successful database migration to verion " . $this->Migration->getVersion('app'));
      $this->Session->setFlash(__("All required tables are created"));
      $this->redirect('user');
    } catch (MigrationVersionException $errors) {
      Logger::trace($errors->getMessage());
      $this->Session->setFlash(__("Could not create tables correctly. Please see logfile for details"));
    }
  }

  public function user() {
    if (!$this->__hasTables()) {
      $this->redirect('database');
    }

    if ($this->__hasSysOp()) {
      $this->redirect('system');
    }

    $this->__checkSession();

    if (!empty($this->request->data)) {
      $this->request->data['User']['role'] = ROLE_ADMIN;
      $this->request->data['User']['quota'] = '100 MB';
      $this->User->create($this->request->data);
      if (empty($this->request->data['User']['confirm'])) {
        $this->User->invalidate('confirm', 'Password confirmation is missing');
      }
      if ($this->User->save()) {
        $userId = $this->User->getLastInsertID();
        $this->Session->write('User.id', $userId);
        $this->Session->write('User.role', ROLE_ADMIN);
        $this->Session->write('User.username', $this->request->data['User']['username']);
        Logger::info("Admin account '{$this->request->data['User']['username']}' was created");
        $this->Session->setFlash(__("Admin account was successfully created"));
        $this->redirect('system');
      } else {
        Logger::err("Admin account '{$this->request->data['User']['username']}' could not be created");
        $this->Session->setFlash(__("Could not create admin account. Please retry"));
      }
    } elseif (!isset($this->request->data['User']['username'])) {
      $this->request->data['User']['username'] = 'admin';
    }
    Logger::info("Request account data for the admin");
  }

  public function __findCommand($command) {
    $paths = array('/usr/local/bin/', '/usr/bin/');
    foreach ($paths as $path) {
      $file = new File($path.$command);
      if ($file->executable()) {
        return $file->pwd();
      }
    }
    return false;
  }

  public function __checkMp3Support($bin) {
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
    exec($command, $output, $result);
    $output = implode(' ', $output);

    if (preg_match('/--enable-libmp3lame/', $output)) {
      return true;
    } else {
      return false;
    }
  }

  public function system() {
    if (!$this->__hasSysOp()) {
      $this->redirect('user');
    }

    $this->requireRole(ROLE_SYSOP);

    $this->__checkSession();

    // If a command is missing, we should not redirect
    if ($this->__hasCommands()) {
      $this->redirect('/');
    }
    if (!isset($this->Option->User)) {
      Logger::warn("Could not associate User model to Option model");
    }

    Logger::info("Check for required external programs");
    $missing = array();
    if (!empty($this->request->data)) {
      foreach ($this->commands as $command) {
        $bin = Set::extract($this->request->data, 'bin.'.$command);
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
        $this->request->data['bin'][$command] = $bin;
        if (!$bin) {
          $missing[] = $command;
        }
      }
    }
    if (isset($this->request->data['bin']['ffmpeg'])) {
      $this->set('mp3Support', $this->__checkMp3Support($this->request->data['bin']['ffmpeg']));
    }
    $this->set('missing', $missing);
  }

  public function finish() {
    if (!$this->__hasSysOp()) {
      $this->redirect('user');
    }

    $this->requireRole(ROLE_SYSOP);

    $this->__checkSession();

    Logger::info("Setup complete");

    // cleanup
    $this->Session->delete('setup');
  }

  public function __deleteModelCache() {
    $modelCacheDir = TMP.'cache'.DS.'models'.DS;
    $folder = new Folder($modelCacheDir);
    $modelCacheFiles = $folder->find('cake_model_.*');
    foreach ($modelCacheFiles as $file) {
      if (!@unlink($folder->addPathElement($modelCacheDir, $file))) {
        Logger::err("Could not delete model cache file '$file' in '$modelCacheDir'");
      }
    }
  }

  /**
   * Load Migration plugin
   *
   * @return True on success
   */
  public function __loadMigration() {
    if (!empty($this->Migration)) {
      return true;
    }
    CakePlugin::load('Migrations');
    if (!App::import('Lib', 'Migrations.MigrationVersion')) {
      Logger::err("Could not import Migrations plugin");
      return false;
    }
    $this->Migration = new MigrationVersion(array('connection' => 'default'));
    if (empty($this->Migration)) {
      Logger::err("Could not load class Migrations.MigrationVersion");
      return false;
    }
    Logger::debug('Loaded Migration plugin');
    return true;
  }

}
?>
