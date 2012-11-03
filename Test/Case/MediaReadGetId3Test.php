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

class TestNativeReadController extends AppController {

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
class MediaReadGetId3TestCase extends CakeTestCase {

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

    $this->Group = ClassRegistry::init('Group');

    $CakeRequest = new CakeRequest();
		$CakeResponse = new CakeResponse();
		$this->Controller = new TestNativeReadController($CakeRequest, $CakeResponse);
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();
    $this->Media = $this->Controller->Media;
    $this->MyFile = $this->Controller->MyFile;

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

	public function testNativeRead() {
		$filename = RESOURCES . 'IMG_7795.JPG';
    // Read file via GetID3 PHP library
		$result = $this->Controller->FilterManager->read($filename);
		$this->assertNotEqual($result, false);

    $media = $this->Media->find('first');
    $this->assertEqual($media['Media']['name'], 'Temple, Ayutthaya');
    // Caption is not supported by GetID3
    $this->assertTrue(!isset($media['Media']['caption']));
    $this->assertEqual($media['Media']['date'], '2009-02-14 14:36:34');
    $this->assertEqual($media['Media']['width'], 800);
    $this->assertEqual($media['Media']['height'], 600);
    $this->assertEqual($media['Media']['orientation'], 6);
    $this->assertEqual($media['Media']['model'], 'Canon PowerShot A570 IS');
    $this->assertEqual($media['Media']['iso'], '80');
    $this->assertEqual($media['Media']['duration'], -1);
    $this->assertEqual($media['Media']['aperture'], 5.65625);
    $this->assertEqual($media['Media']['shutter'], 0.0666963);
    $this->assertEqual($media['Media']['latitude'], 14.3593);
    $this->assertEqual($media['Media']['longitude'], 100.567);
    $this->assertEqual(Set::extract('/Field[name=keyword]/data', $media), array('light', 'night', 'temple'));
    $this->assertEqual(Set::extract('/Field[name=category]/data', $media), array('vacation', 'asia'));
    $this->assertEqual(Set::extract('/Field[name=sublocation]/data', $media), array('wat ratburana'));
    $this->assertEqual(Set::extract('/Field[name=city]/data', $media), array('ayutthaya'));
    $this->assertEqual(Set::extract('/Field[name=state]/data', $media), array('ayutthaya'));
    $this->assertEqual(Set::extract('/Field[name=country]/data', $media), array('thailand'));
	}
}
