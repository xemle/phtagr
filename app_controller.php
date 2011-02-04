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

App::import('File', 'Logger', array('file' => APP.'logger.php'));

class AppController extends Controller
{
  var $helpers = array('Html', 'Form', 'Session', 'Menu', 'Option');
  var $components = array('Session', 'Cookie', 'Feed', 'RequestHandler', 'Menu');
  var $uses = array('User', 'Option');
  
  var $_nobody = null;
  var $_user = null;

  /** Calls _checkSession() to check the credentials of the user 
    @see _checkSession() */
  function beforeFilter() {
    parent::beforeFilter();
    $this->_checkSession();
    $this->Feed->add('/explorer/rss', array('title' => __('Recent photos', true)));
    $this->Feed->add('/explorer/media', array('title' =>  __('Media RSS of recent photos', true), 'id' => 'gallery'));
    $this->Feed->add('/comment/rss', array('title' => __('Recent comments', true)));
    
    $this->_configureEmail();

    $this->Menu->setCurrentMenu('top-menu');
    $role = $this->getUserRole();
    if ($role == ROLE_NOBODY) {
      $this->Menu->addItem(__('Login', true), array('controller' => 'users', 'action' => 'login'));
      if ($this->getOption('user.register.enable', 0)) {
        $this->Menu->addItem(__('Sign Up', true), array('controller' => 'users', 'action' => 'register'));
      }
    } else {
      $this->Menu->addItem(__('Logout', true), array('controller' => 'users', 'action' => 'logout'));
      $this->Menu->addItem(__('Dashboard', true), array('controller' => 'options'));
    }
  }

  /** Configure email component on any SMTP configuration values in core.php */
  function _configureEmail() {
    if (isset($this->Email)) {
      if (Configure::read('Mail.from')) {
        $this->Email->from = Configure::read('Mail.from');
      } else {
        $this->Email->from = "phTagr <noreply@{$_SERVER['SERVER_NAME']}>";
      }
      if (Configure::read('Mail.replyTo')) {
        $this->Email->replyTo = Configure::read('Mail.replyTo');
      } else {
        $this->Email->replyTo = "noreply@{$_SERVER['SERVER_NAME']}";
      }
      $names = array('host', 'port', 'username', 'password');
      foreach($names as $name) {
        $value = Configure::read("Smtp.$name");
        if ($value) {
          $this->Email->smtpOptions[$name] = $value;
        }
      }
      if (!empty($this->Email->smtpOptions['host'])) {
        $this->Email->delivery = 'smtp';
      }
    }
 }
  function beforeRender() {
    parent::beforeRender();
    if ($this->getUserId() > 0) {
      // reread user for updated options
      $user = $this->User->findById($this->getUserId());
    } else {
      $user = $this->getUser();
    }
    $this->params['options'] = $this->Option->getOptions($user);
    $this->set('currentUser', $user);

    if ($this->RequestHandler->isMobile()) {
      $this->view = "Theme";
      $this->theme = "mobile";
    }
  }

  function _checkCookie() {
    $this->Cookie->name = 'phTagr';
    return $this->Cookie->read('user');
  }

  function _checkKey() {
    if (!isset($this->params['named']['key'])) {
      return false;
    }

    // fetch and delete key from passed parameters
    $key = $this->params['named']['key'];
    unset($this->params['named']['key']);

    $data = $this->User->findByKey($key, array('User.id'));
    if ($data) {
      $this->Session->write('Authentication.key', $key);
      return $data['User']['id'];
    }
    return false;
  }

  /** Checks a cookie for a valid user id. If a id found, the user is load to
   * the session 
   * @todo Check expired user */
  function _checkSession() {
    $this->Session->activate();
    if (!$this->Session->check('Session.requestCount')) {
      $this->Session->write('Session.requestCount', 1);
      $this->Session->write('Session.start', time());
    } else {
      $count = $this->Session->read('Session.requestCount');
      $this->Session->write('Session.requestCount', $count + 1);
    }

    if ($this->Session->check('User.id')) {
      return true;
    }

    $authType = 'Cookie';
    $id = $this->_checkCookie();
    if (!$id) {
      $id = $this->_checkKey();
      $authType = 'Key';
    }

    if (!$id) {
      return false;
    }

    // Fetch User
    $user = $this->User->findById($id);
    if (!$user) {
      return false;
    }

    if ($this->User->isExpired($user)) {
      Logger::warn("User account of '{$user['User']['username']}' (id {$user['User']['id']}) is expired!");
      return false;
    }

    $this->User->writeSession($user, &$this->Session);
    Logger::info("User '{$user['User']['username']}' (id {$user['User']['id']}) authenticated via $authType!");

    return true;
  }

  /** Checks the session for valid user. If no user is found, it checks for a
   * valid cookie
   * @return True if the correct session correspond to an user */ 
  function _checkUser() {
    if (!$this->_checkSession()) {
      return false;
    }

    if ($this->_user) {
      return true;
    }

    $userId = $this->Session->read('User.id');
    $user = $this->User->findById($userId);
    if (!$user) {
      return false;
    }

    $this->_user = $user;
    return true;
  }
 
  function getUser() {
    if (!$this->_checkUser() || !$this->_user) {
      if (!$this->_nobody) {
        $this->_nobody = $this->User->getNobody();
      }
      return $this->_nobody;
    }
    return $this->_user;
  }

  function getUserRole() {
    $user =& $this->getUser();
    return $user['User']['role'];
  }
  
  function getUserId() {
    $user =& $this->getUser();
    return $user['User']['id'];
  }

  function hasRole($requiredRole = ROLE_NOBODY) {
    if ($requiredRole <= $this->getUserRole()) {
      return true;
    }
    return false;
  }

  function requireRole($requiredRole=ROLE_NOBODY, $options = null) {
    $options = am(array(
      'redirect' => '/users/login', 
      'loginRedirect' => false, 
      'flash' => false), 
      $options);
    if (!$this->hasRole($requiredRole)) {
      if ($options['loginRedirect']) {
        $this->Session->write('loginRedirect', $options['loginRedirect']);
      }
      if ($options['flash']) {
        $this->Session->setFlash($options['flash']);
      }
      $this->redirect($options['redirect']);
      exit();
    }
    return true;
  }
  
  function getOption($name, $default=null) {
    $user = $this->getUser();
    return $this->Option->getValue($user, $name, $default);
  }

  /** Load a component
    */
  function loadComponent($componentName, &$parent = null) {
    if (is_array($componentName)) {
      $loaded = true;
      foreach ($componentName as $name) {
        $loaded &= $this->loadComponent($name);
      }
      return $loaded;
    }
    
    if (!$parent) {
      $parent = &$this;
    }
    if (isset($parent->{$componentName})) {
      return true;
    }
    if (!in_array($componentName, $parent->components)) {
      $parent->components[] = $componentName;
    }
    $this->Component->_loadComponents($parent);
    $this->Component->initialize($this);

    if (isset($parent->{$componentName})) {
      return true;
    } else {
      Logger::warn("Could not load component $componentName");
      return false;
    }
  }
 
}
?>
