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
	
	var $uses = array('Media', 'MyFile');
	var $components = array('FileManager', 'FilterManager');
	
	function getUser() {
		return array(
				'User' => array(
					'id' => 1,
					'role' => ROLE_ADMIN,
					'username' => 'admin'
				),
				'Option' => array());
	}
}

/**
 * GpsFilterComponent Test Case
 *
 */
class MediaReadTestCase extends CakeTestCase {

	var $controller;
	
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
    $this->Media->save($this->Media->create(array('user_id' => 1, 'date' => '2007-10-14T12:12:39')));
    $mediaId = $this->Media->getLastInsertID();
		$filename = dirname(dirname(__FILE__)) . DS . 'Resources' . DS . 'example.gpx';
    $result = $this->Controller->FilterManager->read($filename);
		$this->assertEqual($result, 1);
    $media = $this->Media->findById($mediaId);
    $this->assertEqual($media['Media']['latitude'], 46.5764);
    $this->assertEqual($media['Media']['longitude'], 8.89267);
  }

  public function testNmeaLog() {
    // 2 hour time shift
    $this->Media->save($this->Media->create(array('user_id' => 1, 'date' => '2011-08-08T20:46:37')));
    $mediaId = $this->Media->getLastInsertID();
    $filename = dirname(dirname(__FILE__)) . DS . 'Resources' . DS . 'example.log';
    $result = $this->Controller->FilterManager->read($filename);
    $this->assertEqual($result, 1);
    $media = $this->Media->findById($mediaId);
    $this->assertEqual($media['Media']['latitude'], 49.0074);
    $this->assertEqual($media['Media']['longitude'], 8.42879);
  }

}
