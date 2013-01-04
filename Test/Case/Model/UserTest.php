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
App::uses('User', 'Model');
App::uses('Option', 'Model');
App::uses('Group', 'Model');

/**
 * Media Test Case
 *
 */
class UserTestCase extends CakeTestCase {

  /**
   * Fixtures
   *
   * @var array
   */
  public $fixtures = array('app.file', 'app.media', 'app.user', 'app.group',
      'app.groups_media', 'app.groups_user', 'app.option', 'app.guest', 'app.comment',
      'app.my_file', 'app.fields_media', 'app.field');

  /**
   * setUp method
   *
   * @return void
   */
  public function setUp() {
    parent::setUp();

    $this->User = ClassRegistry::init('User');
    $this->Option = ClassRegistry::init('Option');
    $this->Group = ClassRegistry::init('Group');
  }

  /**
   * tearDown method
   *
   * @return void
   */
  public function tearDown() {
    unset($this->User);
    unset($this->Option);
    unset($this->Group);

    parent::tearDown();
  }

  public function testDefaultOptions() {
    // test only default
    $this->Option->save($this->Option->create(array('user_id' => 0, 'name' => 'bin.exiftool', 'value' => '/usr/bin/exiftool')));
    $this->User->save($this->User->create(array('username' => 'xemle')));
    $user = $this->User->findById($this->User->getLastInsertID());
    $this->assertSame(1, count($user['Option']));

    // test overwrite of default values
    $userId = $user['User']['id'];
    $this->Option->save($this->Option->create(array('user_id' => 0, 'name' => 'lang', 'value' => 'en_EN')));
    $this->Option->save($this->Option->create(array('user_id' => 0, 'name' => 'path[]', 'value' => '/tmp')));
    $this->Option->save($this->Option->create(array('user_id' => $userId, 'name' => 'lang', 'value' => 'de_DE')));
    $this->Option->save($this->Option->create(array('user_id' => $userId, 'name' => 'path[]', 'value' => '/home/xemle')));

    $user = $this->User->findById($userId);
    $this->assertSame(4, count($user['Option']));
    // test Option.name
    $expected = array('bin.exiftool', 'lang', 'path[]', 'path[]');
    $result = Set::extract('/Option/name', $user);
    sort($expected);
    sort($result);
    $this->assertSame($expected, $result);
    // test Option.value
    $expected = array('/usr/bin/exiftool', 'de_DE', '/tmp', '/home/xemle');
    $result = Set::extract('/Option/value', $user);
    sort($expected);
    sort($result);
    $this->assertSame($expected, $result);
  }

  public function testFindVisibleUsers() {
    $this->User->save($this->User->create(array('username' => 'admin', 'role' => ROLE_ADMIN, 'visible_level' => PROFILE_LEVEL_USER)));
    $admin = $this->User->findById($this->User->getLastInsertID());
    $this->User->save($this->User->create(array('username' => 'sysop', 'role' => ROLE_SYSOP, 'visible_level' => PROFILE_LEVEL_PUBLIC)));
    $sysop = $this->User->findById($this->User->getLastInsertID());
    $this->User->save($this->User->create(array('username' => 'userA', 'role' => ROLE_USER, 'visible_level' => PROFILE_LEVEL_GROUP)));
    $userA = $this->User->findById($this->User->getLastInsertID());
    $this->User->save($this->User->create(array('username' => 'userB', 'role' => ROLE_USER, 'visible_level' => PROFILE_LEVEL_PRIVATE)));
    $userB = $this->User->findById($this->User->getLastInsertID());

    // sysop creates group 'friends' with 'UserA'
    $this->Group->save($this->Group->create(array('name' => 'friends', 'user_id' => $sysop['User']['id'])));
    $group = $this->Group->findById($this->Group->getLastInsertID());
    $this->Group->subscribe($group, $userA['User']['id']);
    $sysop = $this->User->findById($sysop['User']['id']);

    // Test all users for admin
    $users = $this->User->findVisibleUsers($admin);
    $this->assertEqual(array('admin', 'sysop', 'userA', 'userB'), Set::extract('/User/username', $users));
    // Test explicit username
    $users = $this->User->findVisibleUsers($admin, 'userA');
    $this->assertEqual(array('userA'), Set::extract('/User/username', $users));
    // Test like usernames
    $users = $this->User->findVisibleUsers($admin, 'user', true);
    $this->assertEqual(array('userA', 'userB'), Set::extract('/User/username', $users));

    // Test group visibility. userA is in group of sysop
    $users = $this->User->findVisibleUsers($sysop);
    $this->assertEqual(array('admin', 'sysop', 'userA'), Set::extract('/User/username', $users));
    // userB is not in any group. Sees only public and user level
    $users = $this->User->findVisibleUsers($userB);
    $this->assertEqual(array('admin', 'sysop'), Set::extract('/User/username', $users));
  }

  public function testGenerateKey() {
    $user = $this->User->save($this->User->create(array('username' => 'user')));
    $user = $this->User->findById($user['User']['id']);

    $this->User->generateKey($user, 20, '0123456789abcdef');
    $key = $user['User']['key'];
    $this->assertEqual(20, strlen($key));
    $this->assertEqual(true, preg_match('/[0123456789abcdef]+/', $key));

    $this->User->generateKey($user);
    $this->assertEqual(true, $user['User']['key'] != $key);
  }

}
