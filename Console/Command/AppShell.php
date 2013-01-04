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

App::import('File', 'ShellControllerMock', array('file' => dirname(__FILE__) . DS . 'ShellControllerMock.php'));
if (!class_exists('Logger')) {
  App::import('File', 'Logger', array('file' => APP . 'logger.php'));
}

class AppShell extends Shell {
  var $uses = array();
  var $components = array();

  var $ControllerMock = null;

  function initialize() {
    if (function_exists('ini_set') && !ini_set('include_path', ROOT . DS . APP_DIR . DS . 'Vendor' . DS . 'Pear' . DS . PATH_SEPARATOR . ini_get('include_path'))) {
      $this->out("Could not set include_path");
      exit(1);
    }
    parent::initialize();
    $this->loadControllerMock();
    $this->bindCompontents();
  }
  /**
   * Load ShellControllerMock with models and components
   */
  function loadControllerMock() {
    $this->ControllerMock = new ShellControllerMock();
    $this->ControllerMock->setRequest(new CakeRequest());
    $this->ControllerMock->response = new CakeResponse();
    $this->ControllerMock->uses = $this->uses;
    $this->ControllerMock->components = $this->components;
    $this->ControllerMock->constructClasses();
    $this->ControllerMock->startupProcess();
  }

  /**
   * Bind controller's components to shell
   */
  function bindCompontents() {
    foreach($this->ControllerMock->components as $key => $component) {
      if (!is_numeric($key)) {
        $component = $key;
      }
      if (empty($this->ControllerMock->{$component})) {
        $this->out("Could not load component $component");
        exit(1);
      }
      $this->{$component} = $this->ControllerMock->{$component};
    }
  }

  function mockUser($user) {
    $this->ControllerMock->mockUser($user);
  }
}
