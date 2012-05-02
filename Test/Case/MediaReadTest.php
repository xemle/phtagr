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

App::uses('Option', 'Model');
App::uses('User', 'Model');
App::uses('Group', 'Model');
App::uses('Media', 'Model');

App::uses('Router', 'Routing');
App::uses('Controller', 'Controller');
App::uses('AppController', 'Controller');
App::uses('Logger', 'Lib');
App::uses('Folder', 'Utility');

if (!defined('RESOURCES')) {
  define('RESOURCES', TESTS . 'Resources' . DS);
}
if (!defined('TEST_FILES')) {
  define('TEST_FILES', TMP);
}
if (!defined('TEST_FILES_TMP')) {
  define('TEST_FILES_TMP', TEST_FILES . 'write.test.tmp' . DS);
}

if (!is_writeable(TEST_FILES)) {
  trigger_error(__('Test file directory %s must be writeable', TEST_FILES), E_USER_ERROR);
}

class TestReadController extends AppController {
	
	var $uses = array('Media', 'MyFile', 'User', 'Option');

	var $components = array('FileManager', 'FilterManager');
	
	function getUser() {
    return $this->User->find('first');
	}

}

/**
 * GpsFilterComponent Test Case
 *
 */
class MediaReadTestCase extends CakeTestCase {

	var $controller;

  var $User;
  var $Media;
  var $Option;
  var $userId;
	
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

    $this->User = ClassRegistry::init('User');
    $this->User->save($this->User->create(array('username' => 'admin', 'role' => ROLE_ADMIN)));
    $this->userId = $this->User->getLastInsertID();

    $this->Group = ClassRegistry::init('Group');

    $this->Option = ClassRegistry::init('Option');
    if (file_exists('/usr/bin/ffmpeg')) {
      $this->Option->setValue('bin.ffmpeg', '/usr/bin/ffmpeg', 0);
    }
    if (file_exists('/usr/bin/exiftool')) {
      $this->Option->setValue('bin.exiftool', '/usr/bin/exiftool', 0);
    }
    if (file_exists('/usr/bin/convert')) {
      $this->Option->setValue('bin.convert', '/usr/bin/convert', 0);
    }

    $CakeRequest = new CakeRequest();
		$CakeResponse = new CakeResponse();
		$this->Controller = new TestReadController($CakeRequest, $CakeResponse);
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();
    $this->Media =& $this->Controller->Media;
    $this->MyFile = & $this->Controller->MyFile;

    $this->Folder->create(TEST_FILES_TMP);
  }

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
    $this->Folder->delete(TEST_FILES_TMP);

    unset($this->Controller);
    unset($this->Media);
    unset($this->Option);
    unset($this->Group);
    unset($this->User);
    unset($this->Folder);
		parent::tearDown();
	}

  public function testTimeZones() {
    $s = '1970-01-01T00:00:00Z';
    $utc = new DateTime($s, new DateTimeZone('UTC'));
    $time = $utc->format('U');
    $this->assertEquals($time, 0);
    $s2 = $utc->format('Y-m-d H:i:s');
    $this->assertEqual($s2, '1970-01-01 00:00:00');

    $s = '1970-01-01T00:00:00';
    $utc = new DateTime($s, new DateTimeZone('Etc/GMT+2'));
    $time = $utc->format('U');
    $this->assertEquals($time, 7200);
    $s2 = $utc->format('Y-m-d H:i:s');
    $this->assertEqual($s2, '1970-01-01 00:00:00');
  }

/**
 * testReadFile method
 *
 * @return void
 */
	public function testRead() {
		$filename = RESOURCES . 'example.gpx';
		$result = $this->Controller->FilterManager->read($filename);
		$this->assertEqual($result, false);
	}

  public function testReadWithDefaultRights() {
    $user = $this->User->find('first');
    $group = $this->Group->save($this->Group->create(array('name' => 'Group1', 'user_id' => $user['User']['id'])));
    $this->Option->setValue('acl.write.tag', ACL_LEVEL_OTHER, $user['User']['id']);
    $this->Option->setValue('acl.write.meta', ACL_LEVEL_USER, $user['User']['id']);
    $this->Option->setValue('acl.read.preview', ACL_LEVEL_OTHER, $user['User']['id']);
    $this->Option->setValue('acl.read.original', ACL_LEVEL_GROUP, $user['User']['id']);
    $this->Option->setValue('acl.group', $group['Group']['id'], $user['User']['id']);

    $filename = TEST_FILES_TMP . 'IMG_4145.JPG';
    copy(RESOURCES . 'IMG_4145.JPG', $filename);
    $this->Controller->FilterManager->read($filename);
    $media = $this->Media->find('first');

    $this->assertEqual($media['Media']['gacl'], ACL_READ_ORIGINAL | ACL_WRITE_META);
    $this->assertEqual($media['Media']['uacl'], ACL_READ_PREVIEW | ACL_WRITE_META);
    $this->assertEqual($media['Media']['oacl'], ACL_READ_PREVIEW | ACL_WRITE_TAG);
    $this->assertEqual(Set::extract('/Group/name', $media), array('Group1'));
  }

  public function testGpx() {
    // 2 hour time shift
    $this->Option->setValue('filter.gps.offset', 120, $this->userId);
    $this->Media->save($this->Media->create(array('user_id' => $this->userId, 'date' => '2007-10-14T12:12:39')));
    $mediaId = $this->Media->getLastInsertID();
		$filename = RESOURCES . 'example.gpx';
    $result = $this->Controller->FilterManager->read($filename);
		$this->assertEqual($result, 1);
    $media = $this->Media->findById($mediaId);
    $this->assertEqual($media['Media']['latitude'], 46.5764);
    $this->assertEqual($media['Media']['longitude'], 8.89267);
  }

  public function testNmeaLog() {
    // -2 hour time shift
    $this->Option->setValue('filter.gps.offset', -120, $this->userId);
    $this->Media->save($this->Media->create(array('user_id' => $this->userId, 'date' => '2011-08-08T16:46:37')));
    $mediaId = $this->Media->getLastInsertID();
    $filename = RESOURCES . 'example.log';
    $result = $this->Controller->FilterManager->read($filename);
    $this->assertEqual($result, 1);
    $media = $this->Media->findById($mediaId);
    $this->assertEqual($media['Media']['latitude'], 49.0074);
    $this->assertEqual($media['Media']['longitude'], 8.42879);
  }

  public function testGpsOptionOverwrite() {
    $this->Option->setValue('filter.gps.overwrite', 1, $this->userId);
    $this->Media->save($this->Media->create(array('user_id' => $this->userId, 'date' => '2007-10-14T10:12:39', 'latitude' => 34.232, 'longitude' => -23.423)));
    $mediaId = $this->Media->getLastInsertID();
    $media = $this->Media->findById($mediaId);
    $this->assertEqual($media['Media']['latitude'], 34.232);
    $this->assertEqual($media['Media']['longitude'], -23.423);

    $filename = RESOURCES . 'example.gpx';
    $result = $this->Controller->FilterManager->read($filename);
    $this->assertEqual($result, 1);
    $media = $this->Media->findById($mediaId);
    $this->assertEqual($media['Media']['latitude'], 46.5764);
    $this->assertEqual($media['Media']['longitude'], 8.89267);
  }

  public function testGpsOptionRange() {
    $this->Media->save($this->Media->create(array('user_id' => $this->userId, 'date' => '2007-10-14T09:59:57')));
    $mediaId = $this->Media->getLastInsertID();
    $media = $this->Media->findById($mediaId);

    $filename = RESOURCES . 'example.gpx';
    $this->Option->setValue('filter.gps.range', 0, $this->userId);

    // Time 09:59:57 does not fit. GPS log starts at 10:09:57
    $result = $this->Controller->FilterManager->read($filename);
    $this->assertEqual($result, false);
    $media = $this->Media->findById($mediaId);
    $this->assertEqual($media['Media']['latitude'], null);
    $this->assertEqual($media['Media']['longitude'], null);

    // Set time range of GPS log to 15 minues
    $this->Option->setValue('filter.gps.range', 15, $this->userId);
    $result = $this->Controller->FilterManager->read($filename);
    $this->assertEqual($result, 1);
    $media = $this->Media->findById($mediaId);
    $this->assertEqual($media['Media']['latitude'], 46.5761);
    $this->assertEqual($media['Media']['longitude'], 8.89242);
  }

  public function testVideoRead() {
    copy(RESOURCES . 'MVI_7620.OGG', TEST_FILES_TMP . 'MVI_7620.OGG');
    copy(RESOURCES . 'MVI_7620.THM', TEST_FILES_TMP . 'MVI_7620.THM');
    copy(RESOURCES . 'example.gpx', TEST_FILES_TMP . 'example.gpx');

    $this->Controller->FilterManager->readFiles(TEST_FILES_TMP);
    $count = $this->Media->find('count');
    $this->assertEqual($count, 1);

    $media = $this->Media->find('first');
    $keywords = Set::extract('/Tag/name', $media);
    $this->assertEqual($keywords, array('thailand'));

    $this->assertEqual($media['Media']['date'], '2007-10-14 10:09:57');
    $this->assertEqual($media['Media']['latitude'], 46.5761);
    $this->assertEqual($media['Media']['longitude'], 8.89242);
  }
}
