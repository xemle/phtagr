<?php

/**
 * PHP versions 5
 *
 * phTagr : Organize, Browse, and Share Your Photos.
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

App::uses('AppControllerTestCase', 'Test/Case');
App::uses('AppController', 'Controller');

class UserControllerTest extends AppControllerTestCase {

  var $sessionKeys = array('user', 'userId', 'userIdStack');

  public function setUp() {
    parent::setUp();
    foreach ($this->sessionKeys as $name) {
      CakeSession::delete($name);
    }
  }

  public function tearDown() {
    foreach ($this->sessionKeys as $name) {
      CakeSession::delete($name);
    }
    parent::tearDown();
  }

  public function testLogin() {
    $user = $this->Factory->createUser('user', ROLE_USER, array('password' => 'securepassword'));

    $data = array('User' => array('username' => 'user', 'password' => 'securepassword'));
    $this->testAction('/users/login', array('data' => $data));

    $userId = $this->controller->Session->read('userId');
    $this->assertEqual($userId, $user['User']['id']);
  }

  public function testLogout() {
    $user = $this->Factory->createUser('user', ROLE_USER, array('password' => 'securepassword'));

    $data = array('User' => array('username' => 'user', 'password' => 'securepassword'));
    $this->testAction('/users/login', array('data' => $data));
    $this->testAction('/users/logout');

    $userId = $this->controller->Session->read('userId');
    $this->assertEqual($userId, null);
  }

  public function testAuthKeyShouldOverwriteCurrentLogin() {
    $user1 = $this->Factory->createUser('user1', ROLE_USER, array('password' => 'securepassword'));
    $user2 = $this->Factory->createUser('user2', ROLE_USER, array('password' => 'securepassword', 'key' => '3KZImXQr2W'));

    $data = array('User' => array('username' => 'user1', 'password' => 'securepassword'));
    $this->testAction('/users/login', array('data' => $data));
    $userId = $this->controller->Session->read('userId');
    $this->assertEqual($userId, $user1['User']['id']);

    $this->testAction('/users/index/key:3KZImXQr2W');
    $userId = $this->controller->Session->read('userId');
    $this->assertEqual($userId, $user2['User']['id']);
  }

  public function testLogoutShouldSwitchToPreviousUser() {
    $user1 = $this->Factory->createUser('user1', ROLE_USER, array('password' => 'securepassword'));
    $user2 = $this->Factory->createUser('user2', ROLE_USER, array('password' => 'securepassword', 'key' => '3KZImXQr2W'));
    $user3 = $this->Factory->createUser('user3', ROLE_USER, array('password' => 'securepassword', 'key' => 'MaUEnAhJdg'));

    $data = array('User' => array('username' => 'user1', 'password' => 'securepassword'));
    $this->testAction('/users/login', array('data' => $data));
    $userId = $this->controller->Session->read('userId');
    $this->assertEqual($userId, $user1['User']['id']);

    $this->testAction('/users/index/key:MaUEnAhJdg');
    $userId = $this->controller->Session->read('userId');
    $this->assertEqual($userId, $user3['User']['id']);

    $this->testAction('/users/index/key:3KZImXQr2W');
    $userId = $this->controller->Session->read('userId');
    $this->assertEqual($userId, $user2['User']['id']);

    $this->testAction('/users/logout');
    $userId = $this->controller->Session->read('userId');
    $this->assertEqual($userId, $user3['User']['id']);

    $this->testAction('/users/logout');
    $userId = $this->controller->Session->read('userId');
    $this->assertEqual($userId, $user1['User']['id']);

    $this->testAction('/users/logout');
    $userId = $this->controller->Session->read('userId');
    $this->assertEqual($userId, null);
  }

}
