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

App::uses('Logger', 'Lib');
App::uses('Controller', 'Controller');

class AppController extends Controller
{
  var $helpers = array('Html', 'Js', 'Form', 'Session', 'Menu', 'Option');
  var $components = array('Session', 'Cookie', 'Feed', 'RequestHandler', 'Menu');
  var $uses = array('User', 'Option');

  var $_nobody = null;
  var $_user = null;

  /** Calls _checkSession() to check the credentials of the user
    @see _checkSession() */
  public function beforeFilter() {
    parent::beforeFilter();
    $this->_checkSession();
    $this->Feed->add('/explorer/rss', array('title' => __('Recent photos')));
    $this->Feed->add('/explorer/media', array('title' =>  __('Media RSS of recent photos'), 'id' => 'gallery'));
    $this->Feed->add('/comment/rss', array('title' => __('Recent comments')));

    $this->_setMainMenu();
    $this->_setTopMenu();

    if (isset($this->request->params['named']['mobile'])) {
      // Allow 0, false, off as parameter
      $param = $this->request->params['named']['mobile'];
      $disable = in_array(strtolower(substr($param, 0, 5)), array('0', 'false', 'off'));
      $this->Session->write('mobile', !$disable);
    }
  }

  public function _setMainMenu() {
    $this->Menu->setCurrentMenu('main-menu');
    $this->Menu->addItem(__('Home'), "/");
    $this->Menu->addItem(__('Explorer'), array('controller' => 'explorer', 'action' => 'index'));
    if ($this->hasRole(ROLE_GUEST)) {
      $user = $this->getUser();
      $this->Menu->addItem(__('My Photos'), array('controller' => 'explorer', 'action' => 'user', $user['User']['username']));
    }
    if ($this->hasRole(ROLE_USER)) {
      $this->Menu->addItem(__('Upload'), array('controller' => 'browser', 'action' => 'quickupload'));
    }
  }

  public function _setTopMenu() {
    $this->Menu->setCurrentMenu('top-menu');
    $role = $this->getUserRole();
    if ($role == ROLE_NOBODY) {
      $this->Menu->addItem(__('Login'), array('controller' => 'users', 'action' => 'login'));
      if ($this->getOption('user.register.enable', 0)) {
        $this->Menu->addItem(__('Sign Up'), array('controller' => 'users', 'action' => 'register'));
      }
    } else {
      $user = $this->getUser();
      $this->Menu->addItem(__('Howdy, %s!', $user['User']['username']), false);
      $this->Menu->addItem(__('Logout'), array('controller' => 'users', 'action' => 'logout'));
      $this->Menu->addItem(__('Dashboard'), array('controller' => 'options'));
    }
  }

  public function beforeRender() {
    parent::beforeRender();
    if ($this->getUserId() > 0) {
      // reread user for updated options
      $user = $this->User->findById($this->getUserId());
    } else {
      $user = $this->getUser();
    }
    $this->request->params['options'] = $this->Option->getOptions($user);
    $this->set('currentUser', $user);

    if ($this->Session->read('mobile')) {
      $this->viewClass = "Theme";
      $this->theme = "Mobile";
    }
  }

  public function _checkCookie() {
    $this->Cookie->name = 'phTagr';
    $id = $this->Cookie->read('user');
    if (is_numeric($id)) {
      return (int) $id;
    } else {
      return false;
    }
  }

  public function _checkKey() {
    if (!isset($this->request->params['named']['key'])) {
      return false;
    }

    // fetch and delete key from passed parameters
    $key = $this->request->params['named']['key'];
    unset($this->request->params['named']['key']);

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
  public function _checkSession() {
    //$this->Session->activate();
    if (!$this->Session->check('Session.requestCount')) {
      $this->Session->write('Session.requestCount', 1);
      $this->Session->write('Session.start', time());
      $this->Session->write('mobile', $this->RequestHandler->isMobile());
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
      Logger::err("Could not find user with given id '$id' (via $authType)");
      return false;
    }

    if ($this->User->isExpired($user)) {
      Logger::warn("User account of '{$user['User']['username']}' (id {$user['User']['id']}) is expired!");
      return false;
    }

    $this->User->writeSession($user, $this->Session);
    Logger::info("User '{$user['User']['username']}' (id {$user['User']['id']}) authenticated via $authType!");

    return true;
  }

  /** Checks the session for valid user. If no user is found, it checks for a
   * valid cookie
   * @return True if the correct session correspond to an user */
  public function _checkUser() {
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

  public function &getUser() {
    if (!$this->_checkUser() || !$this->_user) {
      if (!$this->_nobody && isset($this->User)) {
        $this->_nobody = $this->User->getNobody();
      } elseif (!$this->_nobody) {
        $this->_nobody = array('User' => array('username' => '', 'password' => '', 'role' => ROLE_NOBODY));
      }
      return $this->_nobody;
    }
    return $this->_user;
  }

  public function getUserRole() {
    $user = $this->getUser();
    return $user['User']['role'];
  }

  public function getUserId() {
    $user = $this->getUser();
    return $user['User']['id'];
  }

  public function hasRole($requiredRole = ROLE_NOBODY) {
    if ($requiredRole <= $this->getUserRole()) {
      return true;
    }
    return false;
  }

  public function requireRole($requiredRole=ROLE_NOBODY, $options = null) {
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

  public function getOption($name, $default=null) {
    $user = $this->getUser();
    return $this->Option->getValue($user, $name, $default);
  }

  /** Load a component
    */
  public function loadComponent($componentName, &$parent = null) {
    if (is_array($componentName)) {
      $loaded = true;
      foreach ($componentName as $name) {
        $loaded &= $this->loadComponent($name, $parent);
      }
      return $loaded;
    }

    if (!$parent) {
      $parent = $this;
    }
    if (isset($parent->{$componentName})) {
      return true;
    }
    if (!in_array($componentName, $parent->components)) {
      $parent->components[] = $componentName;
    }
    $component = $this->Components->load($componentName);
    if (!$component) {
      Logger::warn("Could not load component $componentName");
      return false;
    }
    $parent->{$componentName} = $component;
    // Load components recusivly
    if (is_array($component->components)) {
      $this->loadComponent($component->components, $component);
    }
    $component->initialize($this);

    return true;
  }
  
  /**
   * Log User (username, Ip, path) if admin 'users logging option' enabled 
   * localhost and internal network not logged
   */
  public function logUser()  {
    if (!$this->getOption('user.logging.enable', 0)) {
      return false;
    }
    $user = $this->getUser();
    $IP = $this->request->clientIp();
    $path = $this->request->here;
    if (($IP !== '127.0.0.1') and (substr($IP,0,7) !== '192.168')) {
      $this->log("User '{$user['User']['username']}' ({$user['User']['id']}), IP ".$IP.", path: ".$path, 'IP_log');
    }
  }
  
}
?>
