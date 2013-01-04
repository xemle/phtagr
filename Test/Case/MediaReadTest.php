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
 *
 */
class MediaReadTestCase extends PhtagrTestCase {

  var $uses = array('Media', 'MyFile', 'User', 'Option');
  var $components = array('FileManager', 'FilterManager', 'Exiftool');

  var $testDir;
  var $admin;
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

    $this->Option->addValue($this->FilterManager->writeEmbeddedEnabledOption, 1, 0);
    $this->Option->addValue($this->FilterManager->writeSidecarEnabledOption, 1, 0);
    $this->Option->addValue($this->FilterManager->createSidecarOption, 0, 0);

    $this->admin = $this->Factory->createUser('admin', ROLE_ADMIN);
    $this->mockUser($this->admin);

    $this->Controller->startupProcess();
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
    $filename = $this->getResource('example.gpx');
    $result = $this->Controller->FilterManager->read($filename);
    $this->assertEqual($result, false);
  }

  public function testReadWithDefaultRights() {
    $group = $this->Factory->createGroup('Group1', $this->admin);
    $this->Option->setValue('acl.write.tag', ACL_LEVEL_OTHER, $this->admin['User']['id']);
    $this->Option->setValue('acl.write.meta', ACL_LEVEL_USER, $this->admin['User']['id']);
    $this->Option->setValue('acl.read.preview', ACL_LEVEL_OTHER, $this->admin['User']['id']);
    $this->Option->setValue('acl.read.original', ACL_LEVEL_GROUP, $this->admin['User']['id']);
    $this->Option->setValue('acl.group', $group['Group']['id'], $this->admin['User']['id']);
    $this->mockUser($this->User->find('first'));

    $filename = $this->copyResource('IMG_4145.JPG', $this->testDir);
    $this->Controller->FilterManager->read($filename);
    $media = $this->Media->find('first');

    $this->assertEqual($media['Media']['gacl'], ACL_READ_ORIGINAL | ACL_WRITE_META);
    $this->assertEqual($media['Media']['uacl'], ACL_READ_PREVIEW | ACL_WRITE_META);
    $this->assertEqual($media['Media']['oacl'], ACL_READ_PREVIEW | ACL_WRITE_TAG);
    $this->assertEqual(Set::extract('/Group/name', $media), array('Group1'));
  }

  public function testGpx() {
    // 2 hour time shift
    $this->Option->setValue('filter.gps.offset', 120, $this->admin['User']['id']);
    $this->mockUser($this->User->find('first'));

    $media = $this->Factory->createMedia('IMG', $this->admin, array('date' => '2007-10-14T12:12:39'));
    $filename = $this->getResource('example.gpx');
    $result = $this->Controller->FilterManager->read($filename);
    $this->assertEqual($result, $media['Media']['id']);
    $media = $this->Media->findById($media['Media']['id']);
    $this->assertEqual($media['Media']['latitude'], 46.5764);
    $this->assertEqual($media['Media']['longitude'], 8.89267);
  }

  public function testNmeaLog() {
    // -2 hour time shift
    $this->Option->setValue('filter.gps.offset', -120, $this->admin['User']['id']);
    $this->mockUser($this->User->find('first'));

    $media = $this->Factory->createMedia('IMG', $this->admin, array('date' => '2011-08-08T16:46:37'));
    $filename = $this->getResource('example.log');
    $result = $this->Controller->FilterManager->read($filename);
    $this->assertEqual($result, $media['Media']['id']);
    $media = $this->Media->findById($media['Media']['id']);
    $this->assertEqual($media['Media']['latitude'], 49.0074);
    $this->assertEqual($media['Media']['longitude'], 8.42879);
  }

  public function testGpsOptionOverwrite() {
    $this->Option->setValue('filter.gps.overwrite', 1, $this->admin['User']['id']);
    $this->mockUser($this->User->find('first'));

    $media = $this->Factory->createMedia('IMG', $this->admin, array('date' => '2007-10-14T10:12:39', 'latitude' => 34.232, 'longitude' => -23.423));
    $this->assertEqual($media['Media']['latitude'], 34.232);
    $this->assertEqual($media['Media']['longitude'], -23.423);

    $filename = $this->getResource('example.gpx');
    $result = $this->Controller->FilterManager->read($filename);
    $this->assertEqual($result, $media['Media']['id']);
    $media = $this->Media->findById($media['Media']['id']);
    $this->assertEqual($media['Media']['latitude'], 46.5764);
    $this->assertEqual($media['Media']['longitude'], 8.89267);
  }

  public function testGpsOptionRange() {
    $this->Option->setValue('filter.gps.range', 0, $this->admin['User']['id']);
    $this->mockUser($this->User->find('first'));

    $media = $this->Factory->createMedia('IMG', $this->admin, array('date' => '2007-10-14T09:59:57'));
    $filename = $this->getResource('example.gpx');

    // Time 09:59:57 does not fit. GPS log starts at 10:09:57
    $result = $this->Controller->FilterManager->read($filename);
    $this->assertEqual($result, false);
    $media = $this->Media->findById($media['Media']['id']);
    $this->assertEqual($media['Media']['latitude'], null);
    $this->assertEqual($media['Media']['longitude'], null);

    // Set time range of GPS log to 15 minues
    $this->Option->setValue('filter.gps.range', 15, $this->admin['User']['id']);
    $this->mockUser($this->User->find('first'));
    $result = $this->Controller->FilterManager->read($filename);
    $this->assertEqual($result, $media['Media']['id']);
    $media = $this->Media->findById($media['Media']['id']);
    $this->assertEqual($media['Media']['latitude'], 46.5761);
    $this->assertEqual($media['Media']['longitude'], 8.89242);
  }

  public function testImageRead() {
    $this->copyResource('IMG_7795.JPG', $this->testDir);
    // Precondition: There are not groups yet and will be created on import
    $this->assertEqual($this->Media->Group->find('count'), 0);

    $this->Controller->FilterManager->readFiles($this->testDir);
    $this->assertEqual($this->Media->find('count'), 1);

    $media = $this->Media->find('first');
    $this->assertEqual($media['Media']['date'], '2009-02-14 14:36:34');
    $this->assertEqual($media['Media']['orientation'], 6);
    $this->assertEqual($media['Media']['duration'], -1);
    $this->assertEqual($media['Media']['model'], 'Canon PowerShot A570 IS');
    $this->assertEqual($media['Media']['iso'], 80);
    $this->assertEqual($media['Media']['shutter'], 15);
    $this->assertEqual($media['Media']['aperture'], 7.1);
    $this->assertEqual($media['Media']['latitude'], 14.3593);
    $this->assertEqual($media['Media']['longitude'], 100.567);

    $this->assertEqual(Set::extract('/Field[name=keyword]/data', $media), array('light', 'night', 'temple'));
    $this->assertEqual(Set::extract('/Field[name=category]/data', $media), array('vacation', 'asia'));
    $this->assertEqual(Set::extract('/Field[name=sublocation]/data', $media), array('wat ratburana'));
    $this->assertEqual(Set::extract('/Field[name=city]/data', $media), array('ayutthaya'));
    $this->assertEqual(Set::extract('/Field[name=state]/data', $media), array('ayutthaya'));
    $this->assertEqual(Set::extract('/Field[name=country]/data', $media), array('thailand'));

    $groupNames = Set::extract('/Group/name', $media);
    sort($groupNames);
    $this->assertEqual($groupNames, array('family', 'friends'));

    // Check auto created groups
    $groups = $this->Media->Group->find('all');
    $this->assertEqual(count($groups), 2);
    $this->assertEqual($groups[0]['Group']['user_id'], $media['User']['id']);
    $this->assertEqual($groups[1]['Group']['user_id'], $media['User']['id']);
    $this->assertEqual($groups[0]['Group']['is_moderated'], 1);
    $this->assertEqual($groups[1]['Group']['is_moderated'], 1);
    $this->assertEqual($groups[0]['Group']['is_shared'], 0);
    $this->assertEqual($groups[1]['Group']['is_shared'], 0);
    $this->assertEqual($groups[0]['Group']['is_hidden'], 1);
    $this->assertEqual($groups[1]['Group']['is_hidden'], 1);
  }

  /**
   * Test correct GPS position for negative values
   */
  public function testImageReadGps() {
    $this->copyResource('IMG_1721.JPG', $this->testDir);
    $this->Controller->FilterManager->readFiles($this->testDir);

    $media = $this->Media->find('first');
    $this->assertEqual($media['Media']['latitude'], -13.5698);
    $this->assertEqual($media['Media']['longitude'], -71.7819);
  }

  public function testVideo() {
    date_default_timezone_set('Europe/Belgrade');
    $this->copyResource('MVI_7620.MOV', $this->testDir);
    $this->Controller->FilterManager->readFiles($this->testDir);

    $media = $this->Media->find('first');
    $this->assertEqual($media['Media']['date'], '2007-10-14 08:09:57');
    $this->assertEqual($media['Media']['width'], '640');
    $this->assertEqual($media['Media']['height'], '480');
    $this->assertEqual($media['Media']['duration'], '5');
  }

  public function testVideoWithThumb() {
    date_default_timezone_set('Europe/Belgrade');
    $this->copyResource(array('MVI_7620.MOV','MVI_7620.THM', 'example.gpx'), $this->testDir);

    $this->Controller->FilterManager->readFiles($this->testDir);
    $count = $this->Media->find('count');
    $this->assertEqual($count, 1);

    $media = $this->Media->find('first');
    $keywords = Set::extract('/Field[name=keyword]/data', $media);
    $this->assertEqual($keywords, array('thailand'));

    $this->assertEqual($media['Media']['width'], '640');
    $this->assertEqual($media['Media']['height'], '480');
    $this->assertEqual($media['Media']['duration'], '5');
    $this->assertEqual($media['Media']['date'], '2007-10-14 10:09:57');
    $this->assertEqual($media['Media']['latitude'], 46.5761);
    $this->assertEqual($media['Media']['longitude'], 8.89242);
  }

  public function testGroupAutoSubscription() {
    $this->copyResource('IMG_7795.JPG', $this->testDir);
    // Precondition: There are not groups yet and will be created on import
    $this->assertEqual($this->Media->Group->find('count'), 0);

    $userA = $this->Factory->createUser('User');
    $this->mockUser($userA);

    $userB = $this->Factory->createUser('Another User');
    $this->Factory->createGroup('friends', $userB, array('is_moderated' => false, 'is_shared' => true));
    $this->Factory->createGroup('family', $userB, array('is_moderated' => true, 'is_shared' => true));

    $this->Controller->FilterManager->readFiles($this->testDir);
    $media = $this->Media->find('first');
    // Test auto subscription. Exclude group family which is moderated
    $this->assertEqual(Set::extract('/Group/name', $media), array('friends'));
    // Check subscription to group friends
    $user = $this->User->findById($media['User']['id']);
    $this->assertEqual(Set::extract('/Member/name', $user), array('friends'));
  }

  // Image has two orienations: Main file is 6, embedded thumbnail is 1
  public function testOrientationWithEmbeddedThumbnail() {
    $this->copyResource('IMG_7795.JPG', $this->testDir);

    $this->Controller->FilterManager->readFiles($this->testDir);
    $media = $this->Media->find('first');
    $this->assertEqual($media['Media']['orientation'], 6);
  }
}
