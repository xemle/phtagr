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

App::uses('SearchComponent', 'Controller/Component');
if (!class_exists('TestControllerMock')) {
  App::import('File', 'TestControllerMock', array('file' => dirname(dirname(__FILE__)) . DS . 'TestControllerMock.php'));
}
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

class FilterManagerController extends AppController {

	var $uses = array('Media', 'MyFile', 'User', 'Option');

	var $components = array('FileManager', 'FilterManager', 'Exiftool');

	public function &getUser() {
    $user = $this->User->find('first');
    return $user;
	}

}
class FilterManagerComponentTest  extends CakeTestCase {

	var $controller;

  var $User;
  var $Media;
  var $Option;
  var $userId;

  var $Folder;

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

    $this->Option = ClassRegistry::init('Option');
    $this->Option->setValue('bin.ffmpeg', $this->findExecutable('ffmpeg'), 0);
    $this->Option->setValue('bin.exiftool', $this->findExecutable('exiftool'), 0);
    $this->Option->setValue('bin.convert', $this->findExecutable('convert'), 0);

    $CakeRequest = new CakeRequest();
		$CakeResponse = new CakeResponse();
		$this->Controller = new FilterManagerController($CakeRequest, $CakeResponse);
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

    $this->Controller->Exiftool->exitExiftool();
    unset($this->Controller);
    unset($this->Media);
    unset($this->Option);
    unset($this->Group);
    unset($this->User);
    unset($this->Folder);

		parent::tearDown();
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

  public function testReadFilesRecursivly() {
    $subdir = TEST_FILES_TMP . 'subdir' . DS;
    $subsubdir = $subdir . 'subdir' . DS;
    mkdir($subdir);
    mkdir($subsubdir);
    copy(RESOURCES . 'IMG_4145.JPG', TEST_FILES_TMP . 'IMG_4145.JPG');
    copy(RESOURCES . 'IMG_6131.JPG', $subdir . 'IMG_6131.JPG');
    copy(RESOURCES . 'IMG_7795.JPG', $subsubdir . 'IMG_7795.JPG');

    $options = array('recursive' => false);
    $this->Controller->FilterManager->readFiles(TEST_FILES_TMP, $options);
    $count = $this->Media->find('count');
    $this->assertEqual($count, 1);

    $media = $this->Media->find('all');
    $names = Set::extract('/Media/name', $media);
    sort($names);
    $this->assertEqual($names, array('IMG_4145.JPG'));

    $options = array('recursive' => true);
    $this->Controller->FilterManager->readFiles(TEST_FILES_TMP, $options);
    $count = $this->Media->find('count');
    $this->assertEqual($count, 3);

    $media = $this->Media->find('all');
    $names = Set::extract('/Media/name', $media);
    sort($names);
    $this->assertEqual($names, array('IMG_4145.JPG', 'IMG_6131.JPG', 'IMG_7795.JPG'));
  }
}
?>
