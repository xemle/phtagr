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

class TestWriteController extends AppController {

  var $uses = array('Media', 'MyFile', 'User', 'Option');
  var $components = array('FileManager', 'FilterManager');

  public function &getUser() {
    $user =& $this->User->find('first');
    return $user;
  }

}

/**
 * GpsFilterComponent Test Case
 *
 */
class MediaWriteTestCase extends CakeTestCase {

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
      'app.fields_media', 'app.field', 'app.comment');

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

    $this->Option = ClassRegistry::init('Option');
    $this->Option->setValue('bin.ffmpeg', $this->findExecutable('ffmpeg'), 0);
    $this->Option->setValue('bin.exiftool', $this->findExecutable('exiftool'), 0);
    $this->Option->setValue('bin.convert', $this->findExecutable('convert'), 0);

    $CakeRequest = new CakeRequest();
    $CakeResponse = new CakeResponse();
    $this->Controller = new TestWriteController($CakeRequest, $CakeResponse);
    $this->Controller->constructClasses();
    $this->Controller->startupProcess();
    $this->Media = $this->Controller->Media;
    $this->MyFile = $this->Controller->MyFile;


    $this->Folder->create(TEST_FILES_TMP);
  }

  private function findExecutable($command) {
    if (DS != '/') {
      throw new Exception("Non Unix OS are not supported yet");
    }
    $paths = array('/usr/local/bin/', '/usr/bin/');
    foreach ($paths as $path) {
      if (file_exists($path . $command)) {
        return $path . $command;
      }
    }
    $result = array();
    exec('which ' . $command, $result);
    if ($result) {
      return $result[0];
    } else {
      return false;
    }
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
    unset($this->User);
    unset($this->Folder);
    parent::tearDown();
  }

  private function copyResource($resourceName, $dstPath, $dstName = null) {
    $src = dirname(dirname(__FILE__)) . DS . 'Resources' . DS . $resourceName;
    if (is_dir($dstPath) || can_write($dstPath)) {
      throw new Exception("Destination does not exist or is not writeabel: $dstPath");
    }
    $dst = Folder::slashTerm($dstPath);
    $dst .= ($dstName ? $dstName : $resourceName);
    copy($src, $dst);
    return $dst;
  }

  /**
   * Extract metadata of a file via exiftool
   *
   * @param String $filename
   * @return Array Key to value hash map
   */
  private function extractMeta($filename) {
    $option = $this->User->Option->findByName('bin.exiftool');
    if (!$option) {
      return array();
    }
    $cmd = $option['Option']['value'];
    $cmd .= ' ' . escapeshellarg('-n');
    $cmd .= ' ' . escapeshellarg('-S');
    $cmd .= ' ' . escapeshellarg($filename);
    $result = array();
    $exitCode = 0;
    exec($cmd, $result, $exitcode);
    if (!$result) {
      return array();
    }

    $values = array();
    foreach ($result as $line) {
      if (preg_match('/(\S+):\s(.*)/', $line, $m)) {
        $values[$m[1]] = $m[2];
      }
    }
    ksort($values);
    return $values;
  }

  function testThumbnailCreation() {
    $filename = TEST_FILES_TMP . 'MVI_7620.OGG';
    copy(RESOURCES . 'MVI_7620.OGG', $filename);

    // Insert video and add tag 'thailand'
    $this->Controller->FilterManager->read($filename);
    $media = $this->Media->find('first');
    $this->assertNotEqual($media, false);
    $user = $this->Controller->getUser();
    $this->Media->setAccessFlags($media, $user);
    $data = array('Field' => array('keyword' => 'thailand'));
    $tmp = $this->Media->editSingle($media, $data, $user);
    $this->Media->save($tmp);

    $media = $this->Media->findById($media['Media']['id']);
    $result = $this->Controller->FilterManager->write($media);
    $this->assertEqual($result, true);

    $thumb = TEST_FILES_TMP . 'MVI_7620.thm';
    $this->assertEqual(file_exists($thumb), true);
    $values = $this->extractMeta($thumb);
    $this->assertEqual($values['Keywords'], 'thailand');
  }

  function testImageMetaData() {
    $filename = TEST_FILES_TMP . 'IMG_6131.JPG';
    copy(RESOURCES . 'IMG_6131.JPG', $filename);
    clearstatcache(true, $filename);
    $user = $this->Controller->getUser();

    $this->Controller->FilterManager->read($filename);
    $media = $this->Media->find('first');
    $this->assertNotEqual($media, false);
    $this->assertEqual($media['Media']['flag'], 0);
    $this->assertEqual($media['Media']['orientation'], 1);
    $this->assertEqual($media['Media']['latitude'], null);
    $this->assertEqual($media['Media']['longitude'], null);
    $this->assertEqual($media['Field'], array());

    $data = array(
        'Media' => array(
            'name' => 'Mosque Taj Mahal, India',
            'geo' => '27.175,78.0416',
            'rotation' => 90
        ),
        'Field' => array(
            'keyword' => 'sunset',
            'category' => 'vacation, sightseeing',
            'sublocation' => 'taj mahal',
            'city' => 'agra',
            'state' => 'uttar pradesh',
            'country' => 'india')
        );
    $tmp = $this->Media->editSingle($media, $data, $user);
    $this->Media->save($tmp);

    $media = $this->Media->findById($media['Media']['id']);
    // test if meta data has changed
    $this->assertEqual($media['Media']['flag'], MEDIA_FLAG_DIRTY);
    $result = $this->Controller->FilterManager->write($media);
    $this->assertEqual($result, true);
    $media = $this->Media->findById($media['Media']['id']);
    // test if all media are written and clean
    $this->assertEqual($media['Media']['flag'], 0);

    // Verify written meta data
    $values = $this->extractMeta($filename);
    $this->assertEqual($values['ObjectName'], 'Mosque Taj Mahal, India');
    $this->assertEqual($values['Orientation'], '6');
    $this->assertEqual($values['GPSLatitudeRef'], 'N');
    $this->assertEqual($values['GPSLatitude'], '27.175');
    $this->assertEqual($values['GPSLongitudeRef'], 'E');
    $this->assertEqual($values['GPSLongitude'], '78.0416');
    $this->assertEqual($values['Keywords'], 'sunset');
    $this->assertEqual($values['SupplementalCategories'], 'vacation, sightseeing');
    $this->assertEqual($values['Sub-location'], 'taj mahal');
    $this->assertEqual($values['City'], 'agra');
    $this->assertEqual($values['Province-State'], 'uttar pradesh');
    $this->assertEqual($values['Country-PrimaryLocationName'], 'india');
  }

  function testImageWithChangedLocation() {
    $filename = TEST_FILES_TMP . 'IMG_6131.JPG';
    copy(RESOURCES . 'IMG_6131.JPG', $filename);
    clearstatcache(true, $filename);
    $user = $this->Controller->getUser();

    $this->Controller->FilterManager->read($filename);
    $media = $this->Media->find('first');

    $data = array(
        'Media' => array(
            'name' => 'Mosque Taj Mahal, India',
            'geo' => '27.175,78.0416'
        ),
        'Field' => array(
            'keyword' => 'sunset',
            'category' => 'vacation, sightseeing',
            'sublocation' => 'taj mahal',
            'city' => 'agra',
            'state' => 'uttar pradesh',
            'country' => 'india')
        );
    $tmp = $this->Media->editSingle($media, $data, $user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $result = $this->Controller->FilterManager->write($media);

    // Verify written meta data
    $values = $this->extractMeta($filename);
    $this->assertEqual($values['ObjectName'], 'Mosque Taj Mahal, India');
    $this->assertTrue(!isset($values['Comment']));
    $this->assertEqual($values['Orientation'], '1');
    $this->assertEqual($values['GPSLatitudeRef'], 'N');
    $this->assertEqual($values['GPSLatitude'], '27.175');
    $this->assertEqual($values['GPSLongitudeRef'], 'E');
    $this->assertEqual($values['GPSLongitude'], '78.0416');
    $this->assertEqual($values['Keywords'], 'sunset');
    $this->assertEqual($values['SupplementalCategories'], 'vacation, sightseeing');
    $this->assertEqual($values['Sub-location'], 'taj mahal');
    $this->assertEqual($values['City'], 'agra');
    $this->assertEqual($values['Province-State'], 'uttar pradesh');
    $this->assertEqual($values['Country-PrimaryLocationName'], 'india');

    $data = array(
        'Media' => array(
            'name' => 'IMG_6131.JPG',
            'rotation' => 90,
            'geo' => '10.461,-12.674',      // value change
            'date' => '2012-10-03 10:10:43',
            'name' => 'Mosque Taj Mahal, India',
            'caption' => 'Temple of love'
        ),
        'Field' => array(
            'keyword' => 'sunset, mosque', // list addition +mosque
            'category' => 'sightseeing',   // list removal -vacation
            'city' => 'agra city',         // value change
            'state' => ''                  // value removal
        ));
    $tmp = $this->Media->editSingle($media, $data, $user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $result = $this->Controller->FilterManager->write($media);

    // Verify written meta data
    $values = $this->extractMeta($filename);
    $this->assertTrue(!isset($values['ObjectName']));
    $this->assertEqual($values['Comment'], 'Temple of love');
    $this->assertEqual($values['DateCreated'], '2012:10:03');
    $this->assertEqual($values['TimeCreated'], '10:10:43+02:00');
    $this->assertEqual($values['Orientation'], '6');
    $this->assertEqual($values['GPSLatitudeRef'], 'N');
    $this->assertEqual($values['GPSLatitude'], '10.461');
    $this->assertEqual($values['GPSLongitudeRef'], 'W');
    $this->assertEqual($values['GPSLongitude'], '-12.674');
    $this->assertEqual($values['Keywords'], 'sunset, mosque');
    $this->assertEqual($values['SupplementalCategories'], 'sightseeing');
    $this->assertEqual($values['Sub-location'], 'taj mahal');
    $this->assertEqual($values['City'], 'agra city');
    $this->assertTrue(!isset($values['Province-State']));
    $this->assertEqual($values['Country-PrimaryLocationName'], 'india');
  }
}
