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
      'app.tag', 'app.media_tag', 'app.category', 'app.categories_media',
      'app.location', 'app.locations_media', 'app.comment');

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
    $this->User = ClassRegistry::init('User');
    $this->Option = ClassRegistry::init('Option');
  }

  /**
   * tearDown method
   *
   * @return void
   */
  public function tearDown() {
    unset($this->Media);
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

    $data = array('import' => 'import', 'Browser' => array('import' => array('IMG_4145.JPG', 'subdir'), 'recursive' => 1));
    $this->testAction('/browser/import/' . basename(TEST_FILES_TMP), array('data' => $data));
    $media = $this->Media->find('all');
    $this->assertEqual(count($media), 3);
    // Check names
    $names = Set::extract('/Media/name', $media);
    sort($names);
    $this->assertEqual($names, array('IMG_4145.JPG', 'IMG_6131.JPG', 'IMG_7795.JPG'));
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

    $data = array('import' => 'import', 'Browser' => array('import' => array('IMG_4145.JPG', 'subdir'), 'recursive' => 1));
    $this->testAction('/browser/import/' . basename(TEST_FILES_TMP), array('data' => $data));
    $media = $this->Media->find('all');
    $this->assertEqual(count($media), 3);

    $data = array('unlink' => 'unlink', 'Browser' => array('import' => array('IMG_4145.JPG', 'subdir'), 'recursive' => 1));
    $Browser = $this->generate('Browser', array('methods' => array('getUser')));
    $Browser->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $this->testAction('/browser/import/' . basename(TEST_FILES_TMP), array('data' => $data));
    $media = $this->Media->find('all');
    $this->assertEqual(count($media), 0);
  }

}