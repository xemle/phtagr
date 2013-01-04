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
App::uses('Group', 'Model');
App::uses('User', 'Model');
App::uses('Field', 'Model');

/**
 * Media Test Case
 *
 */
class MediaTestCase extends CakeTestCase {
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
		$this->Group = ClassRegistry::init('Group');
		$this->User = ClassRegistry::init('User');
		$this->Field = ClassRegistry::init('Field');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Media);
		unset($this->Group);
		unset($this->User);
		unset($this->Field);

		parent::tearDown();
	}

  public function testRotate() {
    $data = array();
    $this->Media->rotate($data, 1, 90);
    $this->assertSame(6, $data['Media']['orientation']);
    $this->Media->rotate($data, 1, 180);
    $this->assertSame(3, $data['Media']['orientation']);
    $this->Media->rotate($data, 1, 270);

    $this->Media->rotate($data, 6, 90);
    $this->assertSame(3, $data['Media']['orientation']);
    $this->Media->rotate($data, 3, 90);
    $this->assertSame(8, $data['Media']['orientation']);
    $this->Media->rotate($data, 8, 90);
    $this->assertSame(1, $data['Media']['orientation']);

    $this->Media->rotate($data, 6, 'reset');
    $this->assertSame(1, $data['Media']['orientation']);
  }

  public function testSetAccessFlags() {
    $this->User->save($this->User->create(array('username' => 'admin', 'role' => ROLE_ADMIN)));
    $admin = $this->User->findById($this->User->getLastInsertID());
    $this->User->save($this->User->create(array('username' => 'user', 'role' => ROLE_USER)));
    $user = $this->User->findById($this->User->getLastInsertID());

    $this->Group->save($this->Group->create(array('name' => 'group1', 'user_id' => $admin['User']['id'])));
    $group1 = $this->Group->findById($this->Group->getLastInsertID());
    // user 'user' is member of 'group2'
    $this->Group->save($this->Group->create(array('name' => 'group2', 'user_id' => $admin['User']['id'], 'is_shared' => '1')));
    $group2 = $this->Group->findById($this->Group->getLastInsertID());
    $this->Group->subscribe($group2, $user['User']['id']);
    // Group3 belongs to user 'user'
    $this->Group->save($this->Group->create(array('name' => 'group 3', 'user_id' => $user['User']['id'])));
    $group3 = $this->Group->findById($this->Group->getLastInsertID());

    // reload users
    $admin = $this->User->findById($admin['User']['id']);
    $user = $this->User->findById($user['User']['id']);

    $this->Media->save($this->Media->create(array(
        'name' => 'IMG_1234.JPG',
        'user_id' => $admin['User']['id'],
        'gacl' => 0,
        'uacl' => 0,
        'oacl' => 0)));
    $mediaId = $this->Media->getLastInsertID();
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $admin);

    $this->assertEqual(1, $media['Media']['canReadPreview']);
    $this->assertEqual(1, $media['Media']['canReadHigh']);
    $this->assertEqual(1, $media['Media']['canReadOriginal']);

    $this->assertEqual(1, $media['Media']['canWriteTag']);
    $this->assertEqual(1, $media['Media']['canWriteMeta']);
    $this->assertEqual(1, $media['Media']['canWriteCaption']);

    $this->assertEqual(ACL_LEVEL_PRIVATE, $media['Media']['visibility']);
    $this->assertEqual(1, $media['Media']['isOwner']);
    $this->assertEqual(1, $media['Media']['canWriteAcl']);
    $this->assertEqual(0, $media['Media']['isDirty']);

    // Test canRead
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(0, $media['Media']['canReadPreview']);
    $this->assertEqual(0, $media['Media']['canReadHigh']);
    $this->assertEqual(0, $media['Media']['canReadOriginal']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_PREVIEW)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(1, $media['Media']['canReadPreview']);
    $this->assertEqual(0, $media['Media']['canReadHigh']);
    $this->assertEqual(0, $media['Media']['canReadOriginal']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_HIGH)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(1, $media['Media']['canReadPreview']);
    $this->assertEqual(1, $media['Media']['canReadHigh']);
    $this->assertEqual(0, $media['Media']['canReadOriginal']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_ORIGINAL)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(1, $media['Media']['canReadPreview']);
    $this->assertEqual(1, $media['Media']['canReadHigh']);
    $this->assertEqual(1, $media['Media']['canReadOriginal']);

    // Test canWrite
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(0, $media['Media']['canWriteTag']);
    $this->assertEqual(0, $media['Media']['canWriteMeta']);
    $this->assertEqual(0, $media['Media']['canWriteCaption']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_PREVIEW | ACL_WRITE_TAG)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(1, $media['Media']['canWriteTag']);
    $this->assertEqual(0, $media['Media']['canWriteMeta']);
    $this->assertEqual(0, $media['Media']['canWriteCaption']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_PREVIEW | ACL_WRITE_META)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(1, $media['Media']['canWriteTag']);
    $this->assertEqual(1, $media['Media']['canWriteMeta']);
    $this->assertEqual(0, $media['Media']['canWriteCaption']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_PREVIEW | ACL_WRITE_CAPTION)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(1, $media['Media']['canWriteTag']);
    $this->assertEqual(1, $media['Media']['canWriteMeta']);
    $this->assertEqual(1, $media['Media']['canWriteCaption']);

    // Test visiblility
    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => 0)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(ACL_LEVEL_PRIVATE, $media['Media']['visibility']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'gacl' => ACL_READ_PREVIEW)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(ACL_LEVEL_GROUP, $media['Media']['visibility']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'gacl' => ACL_READ_PREVIEW, 'uacl' => ACL_READ_PREVIEW)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(ACL_LEVEL_USER, $media['Media']['visibility']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'gacl' => ACL_READ_PREVIEW, 'uacl' => ACL_READ_PREVIEW, 'oacl' => ACL_READ_PREVIEW)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(ACL_LEVEL_OTHER, $media['Media']['visibility']);

    // Test owner
    $this->Media->setAccessFlags($media, $admin);
    $this->assertEqual(1, $media['Media']['isOwner']);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(0, $media['Media']['isOwner']);

    // Test canWriteAcl
    $this->Media->setAccessFlags($media, $admin);
    $this->assertEqual(1, $media['Media']['canWriteAcl']);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(0, $media['Media']['canWriteAcl']);

    // Set image owner from 'admin' to 'user'. Admin can write Acl
    $this->Media->save(array('Media' => array('id' => $mediaId, 'user_id' => $user['User']['id'])));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $admin);
    $this->assertEqual(1, $media['Media']['canWriteAcl']);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(1, $media['Media']['canWriteAcl']);

    // Test access gaining over group
    $this->Media->save(array('Media' => array('id' => $mediaId, 'user_id' => $admin['User']['id'], 'gacl' => ACL_READ_PREVIEW | ACL_WRITE_META, 'uacl' => 0, 'oacl' => 0)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(0, $media['Media']['canReadPreview']);
    $this->assertEqual(0, $media['Media']['canReadHigh']);
    $this->assertEqual(0, $media['Media']['canReadOriginal']);
    $this->assertEqual(0, $media['Media']['canWriteTag']);
    $this->assertEqual(0, $media['Media']['canWriteMeta']);
    $this->assertEqual(0, $media['Media']['canWriteCaption']);

    // Add 'group1' and 'group2', to media. User 'user' will gain rights via group 'group2'
    $requestData = array('Group' => array('names' => 'group1, group2'));
    $this->Media->setAccessFlags($media, $admin);
    $tmp = $this->Media->editSingle($media, $requestData, $admin);
    $this->Media->save($tmp);
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(1, $media['Media']['canReadPreview']);
    $this->assertEqual(0, $media['Media']['canReadHigh']);
    $this->assertEqual(0, $media['Media']['canReadOriginal']);
    $this->assertEqual(1, $media['Media']['canWriteTag']);
    $this->assertEqual(1, $media['Media']['canWriteMeta']);
    $this->assertEqual(0, $media['Media']['canWriteCaption']);
  }

  /**
   * Test Media->editMulti for group
   */
  public function testEditMultiGroups() {
    $this->User->save($this->User->create(array('username' => 'admin', 'role' => ROLE_ADMIN)));
    $admin = $this->User->findById($this->User->getLastInsertID());
    $this->User->save($this->User->create(array('username' => 'user', 'role' => ROLE_USER)));
    $user = $this->User->findById($this->User->getLastInsertID());

    $this->Group->save($this->Group->create(array('name' => 'group1', 'user_id' => $admin['User']['id'])));
    $group1 = $this->Group->findById($this->Group->getLastInsertID());
    // user 'user' is member of 'group2'
    $this->Group->save($this->Group->create(array('name' => 'group2', 'user_id' => $admin['User']['id'], 'is_shared' => '1')));
    $group2 = $this->Group->findById($this->Group->getLastInsertID());
    $this->Group->subscribe($group2, $user['User']['id']);
    // Group3 belongs to user 'user'
    $this->Group->save($this->Group->create(array('name' => 'group 3', 'user_id' => $user['User']['id'])));
    $group3 = $this->Group->findById($this->Group->getLastInsertID());

    // reload users
    $admin = $this->User->findById($admin['User']['id']);
    $user = $this->User->findById($user['User']['id']);

    $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $admin['User']['id'])));
    $media = $this->Media->findById($this->Media->getLastInsertID());
    $this->Media->setAccessFlags($media, $admin);

    $requestData = array('Group' => array('names' => 'group1'));
    $data = $this->Media->prepareMultiEditData($requestData, $admin);
    $tmp = $this->Media->editMulti($media, $data, $admin);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags($media, $admin);
    $this->assertEqual(array('group1'), Set::extract('/Group/name', $media));

    $requestData = array('Group' => array('names' => ' group2 , -group1'));
    $data = $this->Media->prepareMultiEditData($requestData, $admin);
    $tmp = $this->Media->editMulti($media, $data, $admin);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags($media, $admin);
    $this->assertEqual(array('group2'), Set::extract('/Group/name', $media));

    // Admin can use every group
    $requestData = array('Group' => array('names' => ' group 3 , group1'));
    $data = $this->Media->prepareMultiEditData($requestData, $admin);
    $tmp = $this->Media->editMulti($media, $data, $admin);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->assertEqual(array('group2', 'group1', 'group 3'), Set::extract('/Group/name', $media));

    // Test group set for user
    $this->Media->save($this->Media->create(array('name' => 'IMG_2345.JPG', 'user_id' => $user['User']['id'])));
    $media = $this->Media->findById($this->Media->getLastInsertID());
    $this->Media->setAccessFlags($media, $user);

    $requestData = array('Group' => array('names' => ' group 3 , group1'));
    $data = $this->Media->prepareMultiEditData($requestData, $user);
    $tmp = $this->Media->editMulti($media, $data, $user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(array('group 3'), Set::extract('/Group/name', $media));

    $requestData = array('Group' => array('names' => ' group2 , -group 3'));
    $data = $this->Media->prepareMultiEditData($requestData, $user);
    $tmp = $this->Media->editMulti($media, $data, $user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(array('group2'), Set::extract('/Group/name', $media));

    $requestData = array('Group' => array('names' => 'group 3, fake Group'));
    $data = $this->Media->prepareMultiEditData($requestData, $user);
    $tmp = $this->Media->editMulti($media, $data, $user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(array('group2', 'group 3'), Set::extract('/Group/name', $media));
  }

  /**
   * Test Media->editMulti for group
   */
  public function testEditSingleGroups() {
    $this->User->save($this->User->create(array('username' => 'admin', 'role' => ROLE_ADMIN)));
    $admin = $this->User->findById($this->User->getLastInsertID());
    $this->User->save($this->User->create(array('username' => 'user', 'role' => ROLE_USER)));
    $user = $this->User->findById($this->User->getLastInsertID());

    $this->Group->save($this->Group->create(array('name' => 'group1', 'user_id' => $admin['User']['id'])));
    $group1 = $this->Group->findById($this->Group->getLastInsertID());
    // user 'user' is member of 'group2'
    $this->Group->save($this->Group->create(array('name' => 'group2', 'user_id' => $admin['User']['id'], 'is_shared' => '1')));
    $group2 = $this->Group->findById($this->Group->getLastInsertID());
    $this->Group->subscribe($group2, $user['User']['id']);
    // Group3 belongs to user 'user'
    $this->Group->save($this->Group->create(array('name' => 'group 3', 'user_id' => $user['User']['id'])));
    $group3 = $this->Group->findById($this->Group->getLastInsertID());

    // reload users
    $admin = $this->User->findById($admin['User']['id']);
    $user = $this->User->findById($user['User']['id']);

    $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $admin['User']['id'])));
    $media = $this->Media->findById($this->Media->getLastInsertID());
    $this->Media->setAccessFlags($media, $admin);

    $requestData = array('Group' => array('names' => 'group1'));
    $tmp = $this->Media->editSingle($media, $requestData, $admin);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags($media, $admin);
    $this->assertEqual(array('group1'), Set::extract('/Group/name', $media));

    $requestData = array('Group' => array('names' => ' group2 , -group1'));
    $tmp = $this->Media->editSingle($media, $requestData, $admin);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags($media, $admin);
    $this->assertEqual(array('group2'), Set::extract('/Group/name', $media));

    // Admin can use every group
    $requestData = array('Group' => array('names' => ' group 3 , group1'));
    $tmp = $this->Media->editSingle($media, $requestData, $admin);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->assertEqual(array('group1', 'group 3'), Set::extract('/Group/name', $media));

    // Test group set for user
    $this->Media->save($this->Media->create(array('name' => 'IMG_2345.JPG', 'user_id' => $user['User']['id'])));
    $media = $this->Media->findById($this->Media->getLastInsertID());
    $this->Media->setAccessFlags($media, $user);

    $requestData = array('Group' => array('names' => ' group 3 , group1'));
    $tmp = $this->Media->editSingle($media, $requestData, $user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(array('group 3'), Set::extract('/Group/name', $media));

    $requestData = array('Group' => array('names' => ' group2 , -group 3'));
    $tmp = $this->Media->editSingle($media, $requestData, $user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(array('group2'), Set::extract('/Group/name', $media));

    $requestData = array('Group' => array('names' => 'group 3, fake Group'));
    $tmp = $this->Media->editSingle($media, $requestData, $user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags($media, $user);
    $this->assertEqual(array('group 3'), Set::extract('/Group/name', $media));
  }

  public function testCloud() {
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

    // media1 is public
    $media1 = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97, 'uacl' => 97, 'oacl' => 97)));
    // media2 is visible by users
    $media2 = $this->Media->save($this->Media->create(array('name' => 'IMG_2345.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97, 'uacl' => 97)));
    // media3 is visible by group members of 'aGroup'
    $media3 = $this->Media->save($this->Media->create(array('name' => 'IMG_3456.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97)));
    $this->Media->save(array('Media' => array('id' => $media3['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));
    // media4 is private
    $media4 = $this->Media->save($this->Media->create(array('name' => 'IMG_4567.JPG', 'user_id' => $userA['User']['id'])));

    $skyField = $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'sky')));
    $vacationField = $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'vacation')));
    $natureField = $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'nature')));
    $otherField = $this->Field->save($this->Field->create(array('name' => 'other', 'data' => 'otherdata')));

    $this->Media->save(array('Media' => array('id' => $media1['Media']['id']), 'Field' => array('Field' => array($skyField['Field']['id'], $vacationField['Field']['id'], $otherField['Field']['id']))));
    $this->Media->save(array('Media' => array('id' => $media2['Media']['id']), 'Field' => array('Field' => array($skyField['Field']['id'], $vacationField['Field']['id'], $natureField['Field']['id'], $otherField['Field']['id']))));
    $this->Media->save(array('Media' => array('id' => $media3['Media']['id']), 'Field' => array('Field' => array($vacationField['Field']['id'], $natureField['Field']['id'], $otherField['Field']['id']))));
    $this->Media->save(array('Media' => array('id' => $media4['Media']['id']), 'Field' => array('Field' => array($vacationField['Field']['id'], $otherField['Field']['id']))));

    $result = $this->Media->cloud($userA, array('conditions' => array('Field.name' => 'keyword')));
    $this->assertEqual($result, array('vacation' => 4, 'sky' => 2, 'nature' => 2));
    $result = $this->Media->cloud($userB, array('conditions' => array('Field.name' => 'keyword')));
    $this->assertEqual($result, array('vacation' => 3, 'sky' => 2, 'nature' => 2));
    $result = $this->Media->cloud($userC, array('conditions' => array('Field.name' => 'keyword')));
    $this->assertEqual($result, array('vacation' => 2, 'sky' => 2, 'nature' => 1));
    $result = $this->Media->cloud($guestA, array('conditions' => array('Field.name' => 'keyword')));
    $this->assertEqual($result, array('vacation' => 2, 'sky' => 1, 'nature' => 1));
    $result = $this->Media->cloud($userNone, array('conditions' => array('Field.name' => 'keyword')));
    $this->assertEqual($result, array('vacation' => 1, 'sky' => 1));
   }

  function testEditSingleWithFields() {
    $userA = $this->User->save($this->User->create(array('username' => 'UserA', 'role' => ROLE_USER)));
    $userB = $this->User->save($this->User->create(array('username' => 'UserB', 'role' => ROLE_USER)));
    $userC = $this->User->save($this->User->create(array('username' => 'UserC', 'role' => ROLE_USER)));
    $userD = $this->User->getNobody();

    // UserA has a group and UserB is a member
    $group = $this->Group->save($this->Group->create(array('name' => 'Group', 'user_id' => $userA['User']['id'])));
    $this->Group->save(array('Group' => array('id' => $group['Group']['id']), 'Member' => array('Member' => array($userB['User']['id']))));

    $keyword = $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'flower')));
    $category = $this->Field->save($this->Field->create(array('name' => 'category', 'data' => 'vacation')));
    $country = $this->Field->save($this->Field->create(array('name' => 'country', 'data' => 'swiss')));
    $custom = $this->Field->save($this->Field->create(array('name' => 'custom', 'data' => 'someValue')));
    $fieldIds = Set::extract("/Field/id", $this->Field->find('all'));

    $media = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.jpg', 'user_id' => $userA['User']['id'], 'gacl' => ACL_WRITE_META, 'uacl' => ACL_WRITE_TAG)));
    $this->Media->save(array('Media' => array('id' => $media['Media']['id']), 'Group' => array('Group' => array($group['Group']['id'])), 'Field' => array('Field'=> $fieldIds)));

    // Allow custom2 field
    $this->Media->Field->singleFields[] = 'custom';

    // UserD is not allowed to change anything
    $media = $this->Media->findById($media['Media']['id']);
    $data = array('Field' => array('keyword' => 'rose'));
    $tmp = $this->Media->editSingle($media, $data, $userD);
    $this->assertEqual($tmp, false);

    // UserC can change only keywords
    $userC = $this->User->findById($userC['User']['id']);
    $media = $this->Media->findById($media['Media']['id']);
    $data = array('Field' => array('keyword' => '-flower, rose', 'category' => 'people'));
    $tmp = $this->Media->editSingle($media, $data, $userC);
    $this->assertEqual(true, ($tmp['Media']['flag'] & MEDIA_FLAG_DIRTY) > 0);
    $this->assertEqual(count($tmp['Field']['Field']), 4);
    $fields = $this->Field->find('all', array('conditions' => array('id' => $tmp['Field']['Field'])));
    $fieldValues = Set::extract('/Field/data', $fields);
    sort($fieldValues);
    $this->assertEqual($fieldValues, array('rose', 'someValue', 'swiss', 'vacation'));

    // UserB can change meta data
    $userB = $this->User->findById($userB['User']['id']);
    $media = $this->Media->findById($media['Media']['id']);
    $data = array('Field' => array('keyword' => 'tulip', 'category' => 'people, locals ', 'country' => 'italy', 'custom' => 'overwritten'));
    $tmp = $this->Media->editSingle($media, $data, $userB);
    $this->assertEqual(true, ($tmp['Media']['flag'] & MEDIA_FLAG_DIRTY) > 0);
    $this->assertEqual(count($tmp['Field']['Field']), 5);
    $fields = $this->Field->find('all', array('conditions' => array('id' => $tmp['Field']['Field'])));
    $fieldValues = Set::extract('/Field/data', $fields);
    sort($fieldValues);
    $this->assertEqual($fieldValues, array('italy', 'locals', 'people', 'someValue', 'tulip'));

    // UserA can change all fields
    $userA = $this->User->findById($userA['User']['id']);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->Field->singleFields[] = 'custom2';
    $data = array('Field' => array('keyword' => 'john', 'category' => 'people', 'custom' => 'overwritten', 'custom2' => 'newValue'));
    $tmp = $this->Media->editSingle($media, $data, $userA);
    $this->assertEqual(true, ($tmp['Media']['flag'] & MEDIA_FLAG_DIRTY) > 0);
    $this->assertEqual(count($tmp['Field']['Field']), 5);
    $fields = $this->Field->find('all', array('conditions' => array('id' => $tmp['Field']['Field'])));
    $fieldValues = Set::extract('/Field/data', $fields);
    sort($fieldValues);
    $this->assertEqual($fieldValues, array('john', 'newValue', 'overwritten', 'people', 'swiss'));
  }

  function testEditSingleWithEmptyFieldValues() {
    $user = $this->User->save($this->User->create(array('username' => 'UserA', 'role' => ROLE_USER)));
    $user = $this->User->findById($user['User']['id']);
    $media = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.jpg', 'user_id' => $user['User']['id'])));
    $media = $this->Media->findById($media['Media']['id']);

    // Precondition: No fields should be exist
    $this->assertEqual(0, $this->Media->Field->find('count'));

    // empty category and city should not trigger new fields
    $data = array('Field' => array('keyword' => 'rose', 'category' => '', 'city' => ''));
    $tmp = $this->Media->editSingle($media, $data, $user);

    $allFields = $this->Media->Field->find('all');
    $this->assertEqual(1, count($allFields));
    $this->assertEqual(Set::extract('/Field/id', $allFields), Set::extract('/Field/Field', $tmp));
  }

  function testEditMultiWithFields() {
    $userA = $this->User->save($this->User->create(array('username' => 'UserA', 'role' => ROLE_USER)));
    $userB = $this->User->save($this->User->create(array('username' => 'UserB', 'role' => ROLE_USER)));
    $userC = $this->User->save($this->User->create(array('username' => 'UserC', 'role' => ROLE_USER)));
    $userD = $this->User->getNobody();

    // UserA has a group and UserB is a member
    $group = $this->Group->save($this->Group->create(array('name' => 'Group', 'user_id' => $userA['User']['id'])));
    $this->Group->save(array('Group' => array('id' => $group['Group']['id']), 'Member' => array('Member' => array($userB['User']['id']))));

    $keyword = $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'flower')));
    $category = $this->Field->save($this->Field->create(array('name' => 'category', 'data' => 'vacation')));
    $country = $this->Field->save($this->Field->create(array('name' => 'country', 'data' => 'swiss')));
    $custom = $this->Field->save($this->Field->create(array('name' => 'custom', 'data' => 'someValue')));
    $fieldIds = Set::extract("/Field/id", $this->Field->find('all'));

    $media = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.jpg', 'user_id' => $userA['User']['id'], 'gacl' => ACL_WRITE_META, 'uacl' => ACL_WRITE_TAG)));
    $this->Media->save(array('Media' => array('id' => $media['Media']['id']), 'Group' => array('Group' => array($group['Group']['id'])), 'Field' => array('Field'=> $fieldIds)));

    // Allow custom fields
    $this->Media->Field->singleFields[] = 'custom';
    $this->Media->Field->singleFields[] = 'custom2';

    // UserD is not allowed to change anything
    $media = $this->Media->findById($media['Media']['id']);
    $data = array('Field' => array('keyword' => 'rose'));
    $data = $this->Media->prepareMultiEditData($data, $userD);
    $tmp = $this->Media->editMulti($media, $data, $userD);
    $this->assertEqual($tmp, false);

    // UserC can change only keywords
    $userC = $this->User->findById($userC['User']['id']);
    $media = $this->Media->findById($media['Media']['id']);
    $data = array('Field' => array('keyword' => '-flower, rose', 'category' => 'people'));
    $data = $this->Media->prepareMultiEditData($data, $userC);
    $tmp = $this->Media->editMulti($media, $data, $userC);
    $this->assertEqual(true, ($tmp['Media']['flag'] & MEDIA_FLAG_DIRTY) > 0);
    $this->assertEqual(count($tmp['Field']['Field']), 4);
    $fields = $this->Field->find('all', array('conditions' => array('id' => $tmp['Field']['Field'])));
    $fieldValues = Set::extract('/Field/data', $fields);
    sort($fieldValues);
    $this->assertEqual($fieldValues, array('rose', 'someValue', 'swiss', 'vacation'));

    // UserB can change meta data
    $userB = $this->User->findById($userB['User']['id']);
    $media = $this->Media->findById($media['Media']['id']);
    $data = array('Field' => array('keyword' => 'tulip', 'category' => 'people, locals ', 'country' => 'italy', 'custom' => 'overwritten'));
    $data = $this->Media->prepareMultiEditData($data, $userB);
    $tmp = $this->Media->editMulti($media, $data, $userB);
    $this->assertEqual(true, ($tmp['Media']['flag'] & MEDIA_FLAG_DIRTY) > 0);
    $this->assertEqual(count($tmp['Field']['Field']), 7);
    $fields = $this->Field->find('all', array('conditions' => array('id' => $tmp['Field']['Field'])));
    $fieldValues = Set::extract('/Field/data', $fields);
    sort($fieldValues);
    $this->assertEqual($fieldValues, array('flower', 'italy', 'locals', 'people', 'someValue', 'tulip', 'vacation'));

    // UserA can change all fields
    $userA = $this->User->findById($userA['User']['id']);
    $media = $this->Media->findById($media['Media']['id']);
    $data = array('Field' => array('keyword' => 'john', 'category' => 'people', 'custom' => 'overwritten', 'custom2' => 'newValue'));
    $data = $this->Media->prepareMultiEditData($data, $userA);
    $tmp = $this->Media->editMulti($media, $data, $userA);
    $this->assertEqual(true, ($tmp['Media']['flag'] & MEDIA_FLAG_DIRTY) > 0);
    $this->assertEqual(count($tmp['Field']['Field']), 7);
    $fields = $this->Field->find('all', array('conditions' => array('id' => $tmp['Field']['Field'])));
    $fieldValues = Set::extract('/Field/data', $fields);
    sort($fieldValues);
    $this->assertEqual($fieldValues, array('flower', 'john', 'newValue', 'overwritten', 'people', 'swiss', 'vacation'));
  }

  public function testMarkDirtyByGroup() {
    $userA = $this->User->save($this->User->create(array('username' => 'UserA', 'role' => ROLE_USER)));
    $userB = $this->User->save($this->User->create(array('username' => 'UserB', 'role' => ROLE_USER)));

    $group = $this->Group->save($this->Group->create(array('name' => 'Group', 'user_id' => $userA['User']['id'])));

    $media1 = $this->Media->save($this->Media->create(array('Media' => array('user_id' => $userA['User']['id']))));
    $this->Media->save(array('Media' => array('id' => $media1['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));
    $media2 = $this->Media->save($this->Media->create(array('Media' => array('user_id' => $userB['User']['id']))));
    $this->Media->save(array('Media' => array('id' => $media2['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));

    // Test precondition
    $media1 = $this->Media->findById($media1['Media']['id']);
    $this->assertEqual($media1['Media']['flag'], 0);
    $media2 = $this->Media->findById($media2['Media']['id']);
    $this->assertEqual($media2['Media']['flag'], 0);

    $result = $this->Media->markDirtyByGroup($group);
    $this->assertEqual($result, true);

    $media1 = $this->Media->findById($media1['Media']['id']);
    $this->assertEqual($media1['Media']['flag'], MEDIA_FLAG_DIRTY);
    $media2 = $this->Media->findById($media2['Media']['id']);
    $this->assertEqual($media2['Media']['flag'], MEDIA_FLAG_DIRTY);
  }

  public function testMarkDirtyByGroupAndUser() {
    $userA = $this->User->save($this->User->create(array('username' => 'UserA', 'role' => ROLE_USER)));
    $userB = $this->User->save($this->User->create(array('username' => 'UserB', 'role' => ROLE_USER)));

    $group = $this->Group->save($this->Group->create(array('name' => 'Group', 'user_id' => $userA['User']['id'])));

    $media1 = $this->Media->save($this->Media->create(array('Media' => array('user_id' => $userA['User']['id']))));
    $this->Media->save(array('Media' => array('id' => $media1['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));
    $media2 = $this->Media->save($this->Media->create(array('Media' => array('user_id' => $userB['User']['id']))));
    $this->Media->save(array('Media' => array('id' => $media2['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));

    // Test precondition
    $media1 = $this->Media->findById($media1['Media']['id']);
    $this->assertEqual($media1['Media']['flag'], 0);
    $media2 = $this->Media->findById($media2['Media']['id']);
    $this->assertEqual($media2['Media']['flag'], 0);

    $result = $this->Media->markDirtyByGroupAndUser($group, $userB);
    $this->assertEqual($result, true);

    $media1 = $this->Media->findById($media1['Media']['id']);
    $this->assertEqual($media1['Media']['flag'], 0);
    $media2 = $this->Media->findById($media2['Media']['id']);
    $this->assertEqual($media2['Media']['flag'], MEDIA_FLAG_DIRTY);
  }

  public function testDeleteGroup() {
    $userA = $this->User->save($this->User->create(array('username' => 'UserA', 'role' => ROLE_USER)));
    $userB = $this->User->save($this->User->create(array('username' => 'UserB', 'role' => ROLE_USER)));

    $group = $this->Group->save($this->Group->create(array('name' => 'Group', 'user_id' => $userA['User']['id'])));

    $media1 = $this->Media->save($this->Media->create(array('Media' => array('user_id' => $userA['User']['id']))));
    $this->Media->save(array('Media' => array('id' => $media1['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));
    $media2 = $this->Media->save($this->Media->create(array('Media' => array('user_id' => $userB['User']['id']))));
    $this->Media->save(array('Media' => array('id' => $media2['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));

    // Test precondition
    $media1 = $this->Media->findById($media1['Media']['id']);
    $this->assertEqual(count(Set::extract('/Group/id', $media1)), 1);
    $media2 = $this->Media->findById($media2['Media']['id']);
    $this->assertEqual(count(Set::extract('/Group/id', $media2)), 1);

    // Delete group from all media
    $result = $this->Media->deleteGroup($group);
    $this->assertEqual($result, true);

    $media1 = $this->Media->findById($media1['Media']['id']);
    $this->assertEqual(count(Set::extract('/Group/id', $media1)), 0);
    $media2 = $this->Media->findById($media2['Media']['id']);
    $this->assertEqual(count(Set::extract('/Group/id', $media2)), 0);
  }

  public function testDeleteGroupByUser() {
    $userA = $this->User->save($this->User->create(array('username' => 'UserA', 'role' => ROLE_USER)));
    $userB = $this->User->save($this->User->create(array('username' => 'UserB', 'role' => ROLE_USER)));

    $group = $this->Group->save($this->Group->create(array('name' => 'Group', 'user_id' => $userA['User']['id'])));

    $media1 = $this->Media->save($this->Media->create(array('Media' => array('user_id' => $userA['User']['id']))));
    $this->Media->save(array('Media' => array('id' => $media1['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));
    $media2 = $this->Media->save($this->Media->create(array('Media' => array('user_id' => $userB['User']['id']))));
    $this->Media->save(array('Media' => array('id' => $media2['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));

    // Test precondition
    $media1 = $this->Media->findById($media1['Media']['id']);
    $this->assertEqual(count(Set::extract('/Group/id', $media1)), 1);
    $media2 = $this->Media->findById($media2['Media']['id']);
    $this->assertEqual(count(Set::extract('/Group/id', $media2)), 1);

    // Delete group from all media of user UserB
    $result = $this->Media->deleteGroupByUser($group, $userB);
    $this->assertEqual($result, true);

    $media1 = $this->Media->findById($media1['Media']['id']);
    $this->assertEqual(count(Set::extract('/Group/id', $media1)), 1);
    $media2 = $this->Media->findById($media2['Media']['id']);
    $this->assertEqual(count(Set::extract('/Group/id', $media2)), 0);
  }
}
