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

App::uses('Media', 'Model');
App::uses('MyFile', 'Model');
App::uses('Group', 'Model');
App::uses('User', 'Model');

/**
 * Media Test Case
 *
 */
class MyFileTestCase extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('app.file', 'app.media', 'app.user', 'app.group', 'app.groups_media',
      'app.groups_user', 'app.option', 'app.guest', 'app.comment', 'app.my_file',
      'app.fields_media', 'app.field', 'app.comment');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->Media = ClassRegistry::init('Media');
		$this->MyFile = ClassRegistry::init('MyFile');
		$this->Group = ClassRegistry::init('Group');
		$this->User = ClassRegistry::init('User');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Media);
		unset($this->MyFile);
		unset($this->Group);
		unset($this->User);

		parent::tearDown();
	}

  public function testCanRead() {
    $userA = $this->User->save($this->User->create(array('username' => 'userA', 'role' => ROLE_USER)));
    $userB = $this->User->save($this->User->create(array('username' => 'userB', 'role' => ROLE_USER)));
    $userC = $this->User->save($this->User->create(array('username' => 'userC', 'role' => ROLE_USER)));
    // user 'userB' has guest 'guestA'
    $guestA = $this->User->save($this->User->create(array('username' => 'guestA', 'role' => ROLE_GUEST, 'creator_id' => $userB['User']['id'])));
    $userNone = $this->User->save($this->User->create(array('username' => 'nobody', 'role' => ROLE_NOBODY)));

    // 'userA' has group 'aGroup'. 'userB' and 'guestA' are member of 'aGroup'
    $group = $this->Group->save($this->Group->create(array('name' => 'aGroup', 'user_id' => $userA['User']['id'])));
    $group = $this->Group->findById($this->Group->getLastInsertID());
    $this->Group->subscribe($group, $userB['User']['id']);
    $group = $this->Group->findById($group['Group']['id']);
    $this->Group->subscribe($group, $guestA['User']['id']);
    // Reload users to refresh model data of groups
    $userA = $this->User->findById($userA['User']['id']);
    $userB = $this->User->findById($userB['User']['id']);
    $userC = $this->User->findById($userC['User']['id']);
    $guestA = $this->User->findById($guestA['User']['id']);
    $userNone = $this->User->findById($userNone['User']['id']);

    $base = TMP . 'test' . DS . '2012-05-02' . DS;
    $folder = new Folder();
    $folder->create($base);
    $file1 = new File($base . 'IMG_1234.JPG');
    $file1->append('');
    $file2 = new File($base . 'IMG_2345.JPG');
    $file2->append('');
    $file3 = new File($base . 'IMG_3456.JPG');
    $file3->append('');
    $file4 = new File($base . 'IMG_4567.JPG');
    $file4->append('');

    // media1 is public
    $media1 = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97, 'uacl' => 97, 'oacl' => 97)));
    $file1 = $this->MyFile->save($this->MyFile->create(array('path' => $base, 'file' => $file1->name, 'media_id' => $media1['Media']['id'])));
    // media2 is visible by users
    $media2 = $this->Media->save($this->Media->create(array('name' => 'IMG_2345.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97, 'uacl' => 97)));
    $file2 = $this->MyFile->save($this->MyFile->create(array('path' => $base, 'file' => $file2->name, 'media_id' => $media2['Media']['id'])));
    // media3 is visible by group members of 'aGroup'
    $media3 = $this->Media->save($this->Media->create(array('name' => 'IMG_3456.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97)));
    $this->Media->save(array('Media' => array('id' => $media3['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));
    $file3 = $this->MyFile->save($this->MyFile->create(array('path' => $base, 'file' => $file3->name, 'media_id' => $media3['Media']['id'])));
    // media4 is private
    $media4 = $this->Media->save($this->Media->create(array('name' => 'IMG_4567.JPG', 'user_id' => $userA['User']['id'])));
    $file4 = $this->MyFile->save($this->MyFile->create(array('path' => $base, 'file' => $file4->name, 'media_id' => $media4['Media']['id'])));

    $this->assertEqual($this->MyFile->canRead($base.'IMG_1234.JPG', $userB), true);
    $this->assertEqual($this->MyFile->canRead($base.'IMG_2345.JPG', $userB), true);
    $this->assertEqual($this->MyFile->canRead($base.'IMG_3456.JPG', $userB), true);
    $this->assertEqual($this->MyFile->canRead($base.'IMG_4567.JPG', $userB), false);
    $this->assertEqual($this->MyFile->canRead($base, $userB), true);

    $this->assertEqual($this->MyFile->canRead($base, $userNone), true);
    $this->Media->save(array('id' => $media1['Media']['id'], 'oacl' => 0));
    $this->assertEqual($this->MyFile->canRead($base, $userNone), false);

    // File cleanup
    $folder->delete(TMP . DS . 'test');
  }
}
