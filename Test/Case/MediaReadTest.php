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

App::uses('Router', 'Routing');
App::uses('Controller', 'Controller');
App::uses('AppController', 'Controller');
App::uses('ComponentCollection', 'Controller');
App::uses('BaseFilterComponent', 'Controller/Component');
App::uses('GpsFilterComponent', 'Controller/Component');
App::uses('Logger', 'Lib');

class TestController extends AppController {
	
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
	
  /**
   * Fixtures
   *
   * @var array
   */
	public $fixtures = array('app.file', 'app.media', 'app.user', 'app.group', 
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
		$CakeRequest = new CakeRequest();
		$CakeResponse = new CakeResponse();
		$this->Controller = new TestController($CakeRequest, $CakeResponse);
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();
    $this->Media =& $this->Controller->Media;
    $this->MyFile = & $this->Controller->MyFile;
    $this->Option = & $this->Controller->Option;
    $this->User =& $this->Controller->User;

    $this->User->save($this->User->create(array('username' => 'admin', 'role' => ROLE_ADMIN)));
    $this->userId = $this->User->getLastInsertID();
  }

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Controller);

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
		$filename = dirname(dirname(__FILE__)) . DS . 'Resources' . DS . 'example.gpx';
		$result = $this->Controller->FilterManager->read($filename);
		$this->assertEqual($result, false);
	}
  
  public function testGpx() {
    // 2 hour time shift
    $this->Option->setValue('filter.gps.offset', 120, $this->userId);
    $this->Media->save($this->Media->create(array('user_id' => $this->userId, 'date' => '2007-10-14T12:12:39')));
    $mediaId = $this->Media->getLastInsertID();
		$filename = dirname(dirname(__FILE__)) . DS . 'Resources' . DS . 'example.gpx';
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
    $filename = dirname(dirname(__FILE__)) . DS . 'Resources' . DS . 'example.log';
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

    $filename = dirname(dirname(__FILE__)) . DS . 'Resources' . DS . 'example.gpx';
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

    $filename = dirname(dirname(__FILE__)) . DS . 'Resources' . DS . 'example.gpx';
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

}
