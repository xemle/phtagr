<?php

/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */
App::uses('User', 'Model');
App::uses('Option', 'Model');

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
  public $fixtures = array('app.file', 'app.media', 'app.user', 'app.group', 'app.groups_user', 'app.option', 'app.guest', 'app.comment', 'app.my_file', 'app.tag', 'app.media_tag', 'app.category', 'app.categories_media', 'app.location', 'app.locations_media');

  /**
   * setUp method
   *
   * @return void
   */
  public function setUp() {
    parent::setUp();

    $this->User = ClassRegistry::init('User');
    $this->Option = ClassRegistry::init('Option');
  }

  /**
   * tearDown method
   *
   * @return void
   */
  public function tearDown() {
    unset($this->User);
    unset($this->Option);

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

}
