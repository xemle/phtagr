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

App::uses('Media', 'Model');
App::uses('Group', 'Model');
App::uses('User', 'Model');

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
      'app.tag', 'app.media_tag', 'app.category', 'app.categories_media', 
      'app.location', 'app.locations_media', 'app.comment');

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

		parent::tearDown();
	}

  public function testRotate() {
    $data = array();
    $this->Media->rotate(&$data, 1, 90);
    $this->assertSame(6, $data['Media']['orientation']);
    $this->Media->rotate(&$data, 1, 180);
    $this->assertSame(3, $data['Media']['orientation']);
    $this->Media->rotate(&$data, 1, 270);

    $this->Media->rotate(&$data, 6, 90);
    $this->assertSame(3, $data['Media']['orientation']);
    $this->Media->rotate(&$data, 3, 90);
    $this->assertSame(8, $data['Media']['orientation']);
    $this->Media->rotate(&$data, 8, 90);
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
    $this->Media->setAccessFlags(&$media, &$admin);
    
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
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(0, $media['Media']['canReadPreview']);
    $this->assertEqual(0, $media['Media']['canReadHigh']);
    $this->assertEqual(0, $media['Media']['canReadOriginal']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_PREVIEW)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(1, $media['Media']['canReadPreview']);
    $this->assertEqual(0, $media['Media']['canReadHigh']);
    $this->assertEqual(0, $media['Media']['canReadOriginal']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_HIGH)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(1, $media['Media']['canReadPreview']);
    $this->assertEqual(1, $media['Media']['canReadHigh']);
    $this->assertEqual(0, $media['Media']['canReadOriginal']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_ORIGINAL)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(1, $media['Media']['canReadPreview']);
    $this->assertEqual(1, $media['Media']['canReadHigh']);
    $this->assertEqual(1, $media['Media']['canReadOriginal']);

    // Test canWrite
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(0, $media['Media']['canWriteTag']);
    $this->assertEqual(0, $media['Media']['canWriteMeta']);
    $this->assertEqual(0, $media['Media']['canWriteCaption']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_PREVIEW | ACL_WRITE_TAG)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(1, $media['Media']['canWriteTag']);
    $this->assertEqual(0, $media['Media']['canWriteMeta']);
    $this->assertEqual(0, $media['Media']['canWriteCaption']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_PREVIEW | ACL_WRITE_META)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(1, $media['Media']['canWriteTag']);
    $this->assertEqual(1, $media['Media']['canWriteMeta']);
    $this->assertEqual(0, $media['Media']['canWriteCaption']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => ACL_READ_PREVIEW | ACL_WRITE_CAPTION)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(1, $media['Media']['canWriteTag']);
    $this->assertEqual(1, $media['Media']['canWriteMeta']);
    $this->assertEqual(1, $media['Media']['canWriteCaption']);
    
    // Test visiblility
    $this->Media->save(array('Media' => array('id' => $mediaId, 'oacl' => 0)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(ACL_LEVEL_PRIVATE, $media['Media']['visibility']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'gacl' => ACL_READ_PREVIEW)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(ACL_LEVEL_GROUP, $media['Media']['visibility']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'gacl' => ACL_READ_PREVIEW, 'uacl' => ACL_READ_PREVIEW)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(ACL_LEVEL_USER, $media['Media']['visibility']);

    $this->Media->save(array('Media' => array('id' => $mediaId, 'gacl' => ACL_READ_PREVIEW, 'uacl' => ACL_READ_PREVIEW, 'oacl' => ACL_READ_PREVIEW)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(ACL_LEVEL_OTHER, $media['Media']['visibility']);  

    // Test owner
    $this->Media->setAccessFlags(&$media, &$admin);
    $this->assertEqual(1, $media['Media']['isOwner']);  
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(0, $media['Media']['isOwner']);  

    // Test canWriteAcl
    $this->Media->setAccessFlags(&$media, &$admin);
    $this->assertEqual(1, $media['Media']['canWriteAcl']);  
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(0, $media['Media']['canWriteAcl']);  

    // Set image owner from 'admin' to 'user'. Admin can write Acl
    $this->Media->save(array('Media' => array('id' => $mediaId, 'user_id' => $user['User']['id'])));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$admin);
    $this->assertEqual(1, $media['Media']['canWriteAcl']);  
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(1, $media['Media']['canWriteAcl']);
    
    // Test access gaining over group
    $this->Media->save(array('Media' => array('id' => $mediaId, 'user_id' => $admin['User']['id'], 'gacl' => ACL_READ_PREVIEW | ACL_WRITE_META, 'uacl' => 0, 'oacl' => 0)));
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(0, $media['Media']['canReadPreview']);
    $this->assertEqual(0, $media['Media']['canReadHigh']);
    $this->assertEqual(0, $media['Media']['canReadOriginal']);
    $this->assertEqual(0, $media['Media']['canWriteTag']);
    $this->assertEqual(0, $media['Media']['canWriteMeta']);
    $this->assertEqual(0, $media['Media']['canWriteCaption']);
    
    // Add 'group1' and 'group2', to media. User 'user' will gain rights via group 'group2'
    $requestData = array('Group' => array('names' => 'group1, group2'));
    $this->Media->setAccessFlags(&$media, &$admin);
    $tmp = $this->Media->editSingle(&$media, &$requestData, &$admin);
    $this->Media->save($tmp);
    $media = $this->Media->findById($mediaId);
    $this->Media->setAccessFlags(&$media, &$user);
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
    $this->Media->setAccessFlags(&$media, &$admin);
    
    $requestData = array('Group' => array('names' => 'group1'));
    $data = $this->Media->prepareMultiEditData($requestData, &$admin);
    $tmp = $this->Media->editMulti(&$media, &$data);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags(&$media, &$admin);
    $this->assertEqual(array('group1'), Set::extract('/Group/name', $media));
    
    $requestData = array('Group' => array('names' => ' group2 , -group1'));
    $data = $this->Media->prepareMultiEditData($requestData, &$admin);
    $tmp = $this->Media->editMulti(&$media, &$data);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags(&$media, &$admin);
    $this->assertEqual(array('group2'), Set::extract('/Group/name', $media));
    
    // Admin can use every group
    $requestData = array('Group' => array('names' => ' group 3 , group1'));
    $data = $this->Media->prepareMultiEditData($requestData, &$admin);
    $tmp = $this->Media->editMulti(&$media, &$data);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->assertEqual(array('group2', 'group1', 'group 3'), Set::extract('/Group/name', $media));

    // Test group set for user
    $this->Media->save($this->Media->create(array('name' => 'IMG_2345.JPG', 'user_id' => $user['User']['id'])));
    $media = $this->Media->findById($this->Media->getLastInsertID());
    $this->Media->setAccessFlags(&$media, &$user);
    
    $requestData = array('Group' => array('names' => ' group 3 , group1'));
    $data = $this->Media->prepareMultiEditData($requestData, &$user);
    $tmp = $this->Media->editMulti(&$media, &$data);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(array('group 3'), Set::extract('/Group/name', $media));
    
    $requestData = array('Group' => array('names' => ' group2 , -group 3'));
    $data = $this->Media->prepareMultiEditData($requestData, &$user);
    $tmp = $this->Media->editMulti(&$media, &$data);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(array('group2'), Set::extract('/Group/name', $media));
    
    $requestData = array('Group' => array('names' => 'group 3, fake Group'));
    $data = $this->Media->prepareMultiEditData($requestData, &$user);
    $tmp = $this->Media->editMulti(&$media, &$data);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags(&$media, &$user);
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
    $this->Media->setAccessFlags(&$media, &$admin);
    
    $requestData = array('Group' => array('names' => 'group1'));
    $tmp = $this->Media->editSingle(&$media, &$requestData, &$admin);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags(&$media, &$admin);
    $this->assertEqual(array('group1'), Set::extract('/Group/name', $media));
    
    $requestData = array('Group' => array('names' => ' group2 , -group1'));
    $tmp = $this->Media->editSingle(&$media, &$requestData, &$admin);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags(&$media, &$admin);
    $this->assertEqual(array('group2'), Set::extract('/Group/name', $media));
    
    // Admin can use every group
    $requestData = array('Group' => array('names' => ' group 3 , group1'));
    $tmp = $this->Media->editSingle(&$media, &$requestData, &$admin);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->assertEqual(array('group1', 'group 3'), Set::extract('/Group/name', $media));

    // Test group set for user
    $this->Media->save($this->Media->create(array('name' => 'IMG_2345.JPG', 'user_id' => $user['User']['id'])));
    $media = $this->Media->findById($this->Media->getLastInsertID());
    $this->Media->setAccessFlags(&$media, &$user);
    
    $requestData = array('Group' => array('names' => ' group 3 , group1'));
    $tmp = $this->Media->editSingle(&$media, &$requestData, &$user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(array('group 3'), Set::extract('/Group/name', $media));
    
    $requestData = array('Group' => array('names' => ' group2 , -group 3'));
    $tmp = $this->Media->editSingle(&$media, &$requestData, &$user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(array('group2'), Set::extract('/Group/name', $media));
    
    $requestData = array('Group' => array('names' => 'group 3, fake Group'));
    $tmp = $this->Media->editSingle(&$media, &$requestData, &$user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $this->Media->setAccessFlags(&$media, &$user);
    $this->assertEqual(array('group 3'), Set::extract('/Group/name', $media));
  }
}
