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
    $this->ControllerMock =& new ShellControllerMock();
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
