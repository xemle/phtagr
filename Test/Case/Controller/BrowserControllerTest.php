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
App::uses('User', 'Model');
App::uses('Option', 'Model');

if (!defined('RESOURCES')) {
  define('RESOURCES', TESTS . 'Resources' . DS);
}
if (!defined('TEST_FILES')) {
  define('TEST_FILES', TMP);
}
if (!defined('TEST_FILES_TMP')) {
  define('TEST_FILES_TMP', TEST_FILES . 'filter.manager.tmp' . DS);
}

if (!is_writeable(TEST_FILES)) {
  trigger_error(__('Test file directory %s must be writeable', TEST_FILES), E_USER_ERROR);
}

class BrowserControllerTest extends ControllerTestCase {
  var $Folder;

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
    $this->Folder = new Folder();
    $this->Folder->create(TEST_FILES_TMP);

    $this->Media = ClassRegistry::init('Media');
    $this->Field = $this->Media->Field;
    $this->Group = $this->Media->Group;
    $this->User = $this->Media->User;
    $this->Option = $this->User->Option;
  }

  /**
   * tearDown method
   *
   * @return void
   */
  public function tearDown() {
    unset($this->Media);
    unset($this->Field);
    unset($this->Group);
    unset($this->User);
    unset($this->Option);
    $this->Folder->delete(TEST_FILES_TMP);

    parent::tearDown();
  }

  public function testImportRecursivly() {
    $subdir = TEST_FILES_TMP . 'subdir' . DS;
    $subsubdir = $subdir . 'subdir' . DS;
    $this->Folder->create($subsubdir);
    copy(RESOURCES . 'IMG_4145.JPG', TEST_FILES_TMP . 'IMG_4145.JPG');
    copy(RESOURCES . 'IMG_6131.JPG', $subdir . 'IMG_6131.JPG');
    copy(RESOURCES . 'IMG_7795.JPG', $subsubdir . 'IMG_7795.JPG');

    $user = $this->User->save($this->User->create(array('role' => ROLE_USER, 'username' => 'user')));
    $this->Option->setValue('path.fsroot[]', TEST_FILES_TMP, $user['User']['id']);
    $user = $this->User->findById($user['User']['id']);

    $Browser = $this->generate('Browser', array('methods' => array('getUser')));
    $Browser->expects($this->any())->method('getUser')->will($this->returnValue($user));

    $options = array('recursive' => true);
    $data = array('import' => 'import', 'Browser' => array('import' => array('IMG_4145.JPG', 'subdir'), 'options' => $options));
    $this->testAction('/browser/import/' . basename(TEST_FILES_TMP), array('data' => $data));
    $media = $this->Media->find('all');
    $this->assertEqual(count($media), 3);
    // Check names
    $names = Set::extract('/Media/name', $media);
    sort($names);
    $this->assertEqual($names, array('IMG_4145.JPG', 'IMG_6131.JPG', 'Temple, Ayutthaya'));
    // Check user Ids
    $userIds = Set::extract('/User/id', $media);
    $userIds = array_unique($userIds);
    $this->assertEqual($userIds, array($user['User']['id']));
  }

  public function testUnlinkRecursivly() {
    $subdir = TEST_FILES_TMP . 'subdir' . DS;
    $subsubdir = $subdir . 'subdir' . DS;
    $this->Folder->create($subsubdir);
    copy(RESOURCES . 'IMG_4145.JPG', TEST_FILES_TMP . 'IMG_4145.JPG');
    copy(RESOURCES . 'IMG_6131.JPG', $subdir . 'IMG_6131.JPG');
    copy(RESOURCES . 'IMG_7795.JPG', $subsubdir . 'IMG_7795.JPG');

    $user = $this->User->save($this->User->create(array('role' => ROLE_USER, 'username' => 'user')));
    $this->Option->setValue('path.fsroot[]', TEST_FILES_TMP, $user['User']['id']);
    $user = $this->User->findById($user['User']['id']);

    $Browser = $this->generate('Browser', array('methods' => array('getUser')));
    $Browser->expects($this->any())->method('getUser')->will($this->returnValue($user));

    $options = array('recursive' => true);
    $data = array('import' => 'import', 'Browser' => array('import' => array('IMG_4145.JPG', 'subdir'), 'options' => $options));
    $this->testAction('/browser/import/' . basename(TEST_FILES_TMP), array('data' => $data));
    $media = $this->Media->find('all');
    $this->assertEqual(count($media), 3);

    $options = array('recursive' => true);
    $data = array('unlink' => 'unlink', 'Browser' => array('import' => array('IMG_4145.JPG', 'subdir'), 'options' => $options));
    $Browser = $this->generate('Browser', array('methods' => array('getUser')));
    $Browser->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $this->testAction('/browser/import/' . basename(TEST_FILES_TMP), array('data' => $data));
    $media = $this->Media->find('all');
    $this->assertEqual(count($media), 0);
  }


  public function testEasyAcl() {
    $user = $this->User->save($this->User->create(array('role' => ROLE_USER, 'username' => 'user')));

    $media1 = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $user['User']['id'])));
    $media2 = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $user['User']['id'])));
    $media3 = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $user['User']['id'])));
    $media4 = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $user['User']['id'])));

    $familyGroup = $this->Group->save($this->Group->create(array('name' => 'family', 'user_id' => $user['User']['id'])));
    $friendsGroup = $this->Group->save($this->Group->create(array('name' => 'friends', 'user_id' => $user['User']['id'])));

    $vacation = $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'vacation')));
    $work = $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'work')));

    // groups: family keywords: vacation
    $this->Media->save(array('Media' => array('id' => $media1['Media']['id']), 'Group' => array('Group' => array($familyGroup['Group']['id'])), 'Field' => array('Field' => array($vacation['Field']['id']))));
    // groups: friends keywords: work
    $this->Media->save(array('Media' => array('id' => $media2['Media']['id']), 'Group' => array('Group' => array($friendsGroup['Group']['id'])), 'Field' => array('Field' => array($work['Field']['id']))));
    // groups: friends keywords: vacation, work
    $this->Media->save(array('Media' => array('id' => $media3['Media']['id']), 'Group' => array('Group' => array($friendsGroup['Group']['id'])), 'Field' => array('Field' => array($vacation['Field']['id'], $work['Field']['id']))));
    // groups: family, friends keywords: vacation, work
    $this->Media->save(array('Media' => array('id' => $media4['Media']['id']), 'Group' => array('Group' => array($familyGroup['Group']['id'], $friendsGroup['Group']['id'])), 'Field' => array('Field' => array($vacation['Field']['id'], $work['Field']['id']))));

    $user = $this->User->findById($user['User']['id']);


    // Test empty result on no filter criteria
    $data = array(
        'Group' => array('names' => ''),
        'Field' => array('keyword' => ''),
        'Media' => array(
            'readPreview' => ACL_LEVEL_GROUP,
            'readOriginal' => ACL_LEVEL_KEEP,
            'writeTag' => ACL_LEVEL_KEEP,
            'writeMeta' => ACL_LEVEL_KEEP));
    $Browser = $this->generate('Browser', array('methods' => array('getUser')));
    $Browser->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $vars = $this->testAction('/browser/easyacl', array('data' => $data, 'return' => 'vars'));
    $mediaIds = $vars['mediaIds'];
    $this->assertEqual(count($mediaIds), 0);

    // Test empty result on no acl change
    $data = array(
        'Group' => array('names' => 'family'),
        'Field' => array('keyword' => ''),
        'Media' => array(
            'readPreview' => ACL_LEVEL_KEEP,
            'readOriginal' => ACL_LEVEL_KEEP,
            'writeTag' => ACL_LEVEL_KEEP,
            'writeMeta' => ACL_LEVEL_KEEP));
    $Browser = $this->generate('Browser', array('methods' => array('getUser')));
    $Browser->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $vars = $this->testAction('/browser/easyacl', array('data' => $data, 'return' => 'vars'));
    $mediaIds = $vars['mediaIds'];
    $this->assertEqual(count($mediaIds), 0);

    // Test empty result on no matching
    $data = array(
        'Group' => array('names' => 'unknown'),
        'Field' => array('keyword' => ''),
        'Media' => array(
            'readPreview' => ACL_LEVEL_KEEP,
            'readOriginal' => ACL_LEVEL_KEEP,
            'writeTag' => ACL_LEVEL_KEEP,
            'writeMeta' => ACL_LEVEL_KEEP));
    $Browser = $this->generate('Browser', array('methods' => array('getUser')));
    $Browser->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $vars = $this->testAction('/browser/easyacl', array('data' => $data, 'return' => 'vars'));
    $mediaIds = $vars['mediaIds'];
    $this->assertEqual(count($mediaIds), 0);

    // Test with valid input
    $data = array(
        'Group' => array('names' => 'family'),
        'Field' => array('keyword' => ''),
        'Media' => array(
            'readPreview' => ACL_LEVEL_GROUP,
            'readOriginal' => ACL_LEVEL_KEEP,
            'writeTag' => ACL_LEVEL_KEEP,
            'writeMeta' => ACL_LEVEL_KEEP));
    $Browser = $this->generate('Browser', array('methods' => array('getUser')));
    $Browser->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $vars = $this->testAction('/browser/easyacl', array('data' => $data, 'return' => 'vars'));
    $mediaIds = $vars['mediaIds'];
    sort($mediaIds);
    $this->assertEqual($mediaIds, array($media1['Media']['id'], $media4['Media']['id']));

    // Test with two groups
    $data = array(
        'Group' => array('names' => 'family, friends'),
        'Field' => array('keyword' => ''),
        'Media' => array(
            'readPreview' => ACL_LEVEL_USER,
            'readOriginal' => ACL_LEVEL_GROUP,
            'writeTag' => ACL_LEVEL_KEEP,
            'writeMeta' => ACL_LEVEL_KEEP));
    $Browser = $this->generate('Browser', array('methods' => array('getUser')));
    $Browser->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $vars = $this->testAction('/browser/easyacl', array('data' => $data, 'return' => 'vars'));
    $mediaIds = $vars['mediaIds'];
    sort($mediaIds);
    $this->assertEqual($mediaIds, array($media4['Media']['id']));

    // Test with keyword and group with exclusion
    $data = array(
        'Group' => array('names' => 'family, -friends'),
        'Field' => array('keyword' => 'vacation'),
        'Media' => array(
            'readPreview' => ACL_LEVEL_OTHER,
            'readOriginal' => ACL_LEVEL_GROUP,
            'writeTag' => ACL_LEVEL_GROUP,
            'writeMeta' => ACL_LEVEL_KEEP));
    $Browser = $this->generate('Browser', array('methods' => array('getUser')));
    $Browser->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $vars = $this->testAction('/browser/easyacl', array('data' => $data, 'return' => 'vars'));
    $mediaIds = $vars['mediaIds'];
    sort($mediaIds);
    $this->assertEqual($mediaIds, array($media1['Media']['id']));

  }

}