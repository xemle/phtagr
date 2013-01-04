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

App::uses('PhtagrTestCase', 'Test/Case');

/**
 * GpsFilterComponent Test Case
 */
class MediaWriteTestCase extends PhtagrTestCase {

  var $uses = array('Media', 'Option');
  var $components = array('FilterManager', 'VideoPreview', 'Exiftool');

  var $testDir;
  var $autostartController = false;

  /**
   * setUp method
   *
   * @return void
   */
  public function setUp() {
    parent::setUp();

    $this->testDir = $this->createTestDir();
    $this->setOptionsForExternalTools();
    $this->Option->setValue($this->Exiftool->stayOpenOption, 1, 0);
    $this->Option->setValue($this->VideoPreview->createVideoThumbOption, 1, 0);

    $this->Option->addValue($this->FilterManager->writeEmbeddedEnabledOption, 1, 0);
    $this->Option->addValue($this->FilterManager->writeSidecarEnabledOption, 1, 0);
    $this->Option->addValue($this->FilterManager->createSidecarOption, 0, 0);

    $admin = $this->Factory->createUser('admin', ROLE_ADMIN);
    $this->mockUser($admin);

    $this->Controller->startupProcess();
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
    $cmd .= ' -config ' . escapeshellarg(APP . 'Config' . DS . 'ExifTool-phtagr.conf');
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

  /**
   * Test for video thumbnail THM creation without xmp sidecar file
   */
  function testVideoThumbnailCreation() {
    $filename = $this->copyResource('MVI_7620.OGG', $this->testDir);
    $this->FilterManager->VideoFilter->createVideoThumb = true;
    $this->FilterManager->createSidecarForNonEmbeddableFile = true;

    // Insert video and add tag 'thailand'
    $this->FilterManager->read($filename);
    $media = $this->Media->find('first');
    $this->assertNotEqual($media, false);
    $user = $this->getUser();
    $this->Media->setAccessFlags($media, $user);
    $data = array('Field' => array('keyword' => 'thailand'));
    $tmp = $this->Media->editSingle($media, $data, $user);
    $this->Media->save($tmp);

    $media = $this->Media->findById($media['Media']['id']);
    $result = $this->FilterManager->write($media);
    $this->assertEqual($result, true);

    $thumb = dirname($filename) . DS . 'MVI_7620.thm';
    $this->assertEqual(file_exists($thumb), true);
    $values = $this->extractMeta($thumb);
    $this->assertEqual($values['Keywords'], 'thailand');

    $Folder = new Folder($this->testDir);
    $files = $Folder->find();
    $this->assertEqual(count($files), 2);
  }

  /**
   * Test for xmp sidecar file creation without video thumbnail THM
   */
  function testVideoSidecarCreation() {
    $filename = $this->copyResource('MVI_7620.OGG', $this->testDir);
    $this->FilterManager->VideoFilter->createVideoThumb = false;
    $this->FilterManager->createSidecarForNonEmbeddableFile = true;

    // Insert video and add tag 'thailand'
    $this->FilterManager->read($filename);
    $media = $this->Media->find('first');
    $this->assertNotEqual($media, false);
    $user = $this->getUser();
    $this->Media->setAccessFlags($media, $user);
    $data = array('Field' => array('keyword' => 'thailand'));
    $tmp = $this->Media->editSingle($media, $data, $user);
    $this->Media->save($tmp);

    $media = $this->Media->findById($media['Media']['id']);
    $result = $this->FilterManager->write($media);
    $this->assertEqual($result, true);

    $xmp = dirname($filename) . DS . 'MVI_7620.xmp';
    $this->assertEqual(file_exists($xmp), true);
    $values = $this->extractMeta($xmp);
    $this->assertEqual($values['Subject'], 'thailand');

    $Folder = new Folder($this->testDir);
    $files = $Folder->find();
    $this->assertEqual(count($files), 2);
  }

  function testImageMetaData() {
    //use for testing the same time zone as initial values (+02:00)
    date_default_timezone_set('Europe/Belgrade');//GMT+2 = Europe/Belgrade is 1 hrs behind Europe/Helsinki.
    $filename = $this->copyResource('IMG_6131.JPG', $this->testDir);

    $user = $this->getUser();

    $this->FilterManager->read($filename);
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
    $result = $this->FilterManager->write($media);
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
    //use the same time zone +02:00
    date_default_timezone_set('Europe/Belgrade');//Europe/Belgrade is 1 hrs behind Europe/Helsinki.
    //$d = date_default_timezone_get();
    $filename = $this->copyResource('IMG_6131.JPG', $this->testDir);

    $user = $this->getUser();

    $this->FilterManager->read($filename);
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
    $result = $this->FilterManager->write($media);
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
    $result = $this->FilterManager->write($media);

    // Verify written meta data
    $values = $this->extractMeta($filename);
    $this->assertTrue(!isset($values['ObjectName']));
    $this->assertEqual($values['Comment'], 'Temple of love');
    $this->assertEqual(substr($values['DateTimeOriginal'], 0, 19), "2012:10:03 10:10:43");
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

  function testGroupWrite() {
    $filename = $this->copyResource('IMG_6131.JPG', $this->testDir);

    $user = $this->getUser();
    $group1 = $this->Factory->createGroup('family', $user);
    $group2 = $this->Factory->createGroup('friends', $user);

    $this->FilterManager->read($filename);
    $media = $this->Media->find('first');

    $data = array('Group' => array('names' => 'family,friends'));

    $tmp = $this->Media->editSingle($media, $data, $user);
    $this->Media->save($tmp);
    $media = $this->Media->findById($media['Media']['id']);
    $result = $this->FilterManager->write($media);
    $this->assertEqual($result, true);

    // Verify written meta data
    $values = $this->extractMeta($filename);
    $this->assertEqual($values['PhtagrGroups'], 'family, friends');
  }

  public function testKeepFileGroupIfSubscriptionIsMissing() {
    $filename = $this->copyResource('IMG_7795.JPG', $this->testDir);

    // Precondition: There are not groups yet and will be created on import
    $this->assertEqual($this->Media->Group->find('count'), 0);

    $userA = $this->Factory->createUser('User');
    $this->Factory->createGroup('worker', $userA, array('is_moderated' => true, 'is_shared' => false));
    $this->mockUser($userA);

    $userB = $this->Factory->createUser('Another User');
    $this->Factory->createGroup('friends', $userB, array('is_moderated' => false, 'is_shared' => true));
    $this->Factory->createGroup('family', $userB, array('is_moderated' => true, 'is_shared' => true));

    $this->FilterManager->readFiles($this->testDir);
    $userA = $this->User->findById($userA['User']['id']);
    $media = $this->Media->find('first');
    // Test auto subscription. Exclude group family which is moderated
    $this->assertEqual(Set::extract('/Group/name', $media), array('friends'));

    $data = array('Group' => array('names' => 'friends, worker'));
    $tmp = $this->Media->editSingle($media, $data, $userA);
    $this->Media->save($tmp);
    $media = $this->Media->find('first');
    // Test auto subscription. Exclude group family which is moderated
    $this->assertEqual(Set::extract('/Group/name', $media), array('friends', 'worker'));

    $media = $this->Media->findById($media['Media']['id']);
    $result = $this->FilterManager->write($media);
    $this->assertEqual($result, true);

    // Verify written meta data
    $values = $this->extractMeta($filename);
    $this->assertEqual($values['PhtagrGroups'], 'family, friends, worker');
  }

}
