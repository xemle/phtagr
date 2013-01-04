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

App::uses('Controller', 'Controller');
App::uses('UploadComponent', 'Controller/Component');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('FileManagerComponent', 'Controller/Component');
App::uses('AppModel', 'Model');
App::uses('MyFile', 'Model');
App::uses('Logger', 'Lib');
App::uses('Folder', 'Utility');

if (!defined('RESOURCES')) {
  define('RESOURCES', TESTS . 'Resources' . DS);
}
if (!defined('TEST_FILES')) {
  define('TEST_FILES', TMP);
}
if (!defined('TEST_FILES_TMP')) {
  define('TEST_FILES_TMP', TEST_FILES . 'upload.test.tmp' . DS);
}

if (!is_writeable(TEST_FILES)) {
  trigger_error(__('Test file directory %s must be writeable', TEST_FILES), E_USER_ERROR);
}

class UploadComponentMock extends UploadComponent {
  public function isUploadedFile($filename) {
    return true;
  }

  public function moveUploadedFile($filename, $dst) {
    return rename($filename, $dst);
  }
}

class FileManagerComponentMock extends FileManagerComponent {
  var $canWriteMockValue = true;
  var $uniqueFilenameMockValue = '';
  var $addMockValue = true;

  public function canWrite($size, $user = false) {
    return $this->canWriteMockValue;
  }

  public function delete($file) {
    return true;
  }

  public function createUniqueFilename($path, $filename) {
    return $this->uniqueFilenameMockValue;
  }

  public function add($filename, $user = false) {
    return $this->addMockValue;
  }
}

class UploadComponentTest extends CakeTestCase {

  var $Folder = null;
  var $Controller = null;
  var $FileManager = null;

  /** Upload component */
  var $Upload = null;
  public $fixtures = array();

  public function setUp() {
    parent::setUp();
    $this->Folder = new Folder();
    $this->request = new CakeRequest('controller_posts/index');
    $this->request->params['pass'] = $this->request->params['named'] = array();
    $this->Controller = new Controller($this->request);
    $this->FileManager = new FileManagerComponentMock($this->getMock('ComponentCollection'), array());
    $this->Upload = new UploadComponentMock($this->getMock('ComponentCollection'), array());
    $this->Upload->controller = $this->Controller;
    $this->Upload->FileManager = $this->FileManager;
    $this->_buildUploadFolders();
  }

  /**
   * tearDown method
   *
   * @return void
   */
  public function tearDown() {
    $this->_deleteUploadFolders();
    unset($this->Upload);
    unset($this->Controller);
    unset($this->Folder);
    parent::tearDown();
  }

  public function _buildUploadFolders() {
    $src = TEST_FILES . 'src' . DS;
    $dst = TEST_FILES . 'dst' . DS;
    $this->Folder->create($src);
    $this->Folder->create($dst);
    $this->assertEqual(true, is_dir($src));
    $this->assertEqual(true, is_dir($dst));
  }

  public function _deleteUploadFolders() {
    $src = TEST_FILES . 'src' . DS;
    $dst = TEST_FILES . 'dst' . DS;
    $this->Folder->delete($src);
    $this->Folder->delete($dst);
    $this->assertEqual(false, is_dir($src));
    $this->assertEqual(false, is_dir($dst));
  }

  public function testInitialStates() {
    // initial no upload
    $result = $this->Upload->isUpload();
    $this->assertEqual(false, $result);

    $result = $this->Upload->getSize();
    $this->assertEqual(0, $result);

    $result = $this->Upload->hasErrors();
    $this->assertEqual(false, $result);

    $result = $this->Upload->getErrors();
    $this->assertEqual(false, $result);
  }

  public function testGetUploadsSingle() {
    $data = array(
        'error' => 0,
        'extraField' => 'invisible',
        'name' => 'IMG_4145.JPG',
        'type' => 'image/jpeg',
        'size' => '79666',
        'tmp_name' => TEST_FILES_TMP . 'f0e1d2c3.tmp'
    );
    $expected = array(
        array(
            'error' => 0,
            'name' => 'IMG_4145.JPG',
            'type' => 'image/jpeg',
            'size' => '79666',
            'tmp_name' => TEST_FILES_TMP . 'f0e1d2c3.tmp'
        )
    );
    $result = $this->Upload->getUploads($data);
    $this->assertEqual($expected, $result);

    // Read from the internal upload array
    $result = $this->Upload->getUploads();
    $this->assertEqual($expected, $result);

    // clear test
    $this->Upload->clear();
    $this->Controller->request->data = false;
    $result = $this->Upload->getUploads();
    $this->assertEqual(null, $result);

    // read data from controller
    $this->Controller->request->data = $data;
    $result = $this->Upload->getUploads();
    $this->assertEqual($expected, $result);

    $result = $this->Upload->hasErrors();
    $expected = false;
    $this->assertEqual($expected, $result);
  }

  public function testGetUploadsMulti() {
    $data = array(
        'File' => array(
            'uploads' => array(
                0 => array(
                    'error' => 0,
                    'name' => 'IMG_4145.JPG',
                    'type' => 'image/jpeg',
                    'size' => '79666',
                    'tmp_name' => TEST_FILES_TMP . 'f0e1d2c3.tmp'
                ),
                2 => array(
                    'error' => 0,
                    'name' => 'img_7795.jpg',
                    'type' => 'image/jpeg',
                    'size' => '69683',
                    'tmp_name' => TEST_FILES_TMP . 'f1e2d3c4.tmp'
                )
            ),
            'error' => array(
                'error' => 1,
                'name' => 'IMG_8613.JPG',
                'type' => 'image/jpeg',
                'size' => '8726',
                'tmp_name' => TEST_FILES_TMP . 'e1e2e3e4.tmp'
            ),
            'data' => array(
                'error' => 0,
                'name' => 'IMG_6131.JPG',
                'type' => 'image/jpeg',
                'size' => '72601',
                'tmp_name' => TEST_FILES_TMP . 'f2e3d4c5.tmp'
            ),
            'error2' => array(
                'error' => 1,
                'name' => 'IMG_6131.JPG',
                'type' => 'image/jpeg',
                'size' => '72601',
                'tmp_name' => TEST_FILES_TMP . 'e2e3e4e5.tmp'
            ),
        )
    );
    $expected = array(
        0 => array(
            'error' => 0,
            'name' => 'IMG_4145.JPG',
            'type' => 'image/jpeg',
            'size' => '79666',
            'tmp_name' => TEST_FILES_TMP . 'f0e1d2c3.tmp'
        ),
        1 => array(
            'error' => 0,
            'name' => 'img_7795.jpg',
            'type' => 'image/jpeg',
            'size' => '69683',
            'tmp_name' => TEST_FILES_TMP . 'f1e2d3c4.tmp'
        ),
        2 => array(
            'error' => 0,
            'name' => 'IMG_6131.JPG',
            'type' => 'image/jpeg',
            'size' => '72601',
            'tmp_name' => TEST_FILES_TMP . 'f2e3d4c5.tmp'
        )
    );
    $this->Controller->request->data = $data;
    $result = $this->Upload->getUploads();
    $this->assertEqual($expected, $result);

    $result = $this->Upload->hasErrors();
    $this->assertEqual(true, $result);

    $result = $this->Upload->getSize();
    $expected = 221950;
    $this->assertEqual($expected, $result);
  }

  public function testUpload() {
    $src = TEST_FILES . 'src' . DS;
    $dst = TEST_FILES . 'dst' . DS;
    $this->_buildUploadFolders();

    $data = array(
        'File' => array(
            'uploads' => array(
                0 => array(
                    'error' => 0,
                    'name' => 'IMG_4145.JPG',
                    'type' => 'image/jpeg',
                    'size' => '79666',
                    'tmp_name' => $src . 'f0e1d2c3.tmp'
                ),
                1 => array(
                    'error' => 0,
                    'name' => 'IMG_7795.JPG',
                    'type' => 'image/jpeg',
                    'size' => '69683',
                    'tmp_name' => $src . 'f1e2d3c4.tmp'
                ),
                2 => array(
                    'error' => 0,
                    'name' => 'IMG_6131.JPG',
                    'type' => 'image/jpeg',
                    'size' => '72601',
                    'tmp_name' => $src . 'f2e3d4c5.tmp'
                )
            )
        )
    );
    $this->Controller->request->data = $data;

    $this->assertEqual(true, copy(RESOURCES . 'IMG_4145.JPG', $src . 'f0e1d2c3.tmp'));
    $this->assertEqual(true, copy(RESOURCES . 'IMG_7795.JPG', $src . 'f1e2d3c4.tmp'));
    $this->assertEqual(true, copy(RESOURCES . 'IMG_6131.JPG', $src . 'f2e3d4c5.tmp'));

    $noPath = TEST_FILES_TMP . 'tmp123' . DS;
    $result = $this->Upload->upload($noPath);
    $this->assertEqual($result, false);

    $result = $this->Upload->upload($dst);
    $expected = array(
        0 => 'IMG_4145.JPG',
        1 => 'IMG_7795.JPG',
        2 => 'IMG_6131.JPG'
    );
    $this->assertEqual($expected, $result);

    // overwrite test
    $this->assertEqual(true, copy(RESOURCES . 'IMG_6131.JPG', $src . 'f2e3d4c5.tmp'));
    $data = array(
        'Upload' => array(
            'data' => array(
                'error' => 0,
                'name' => 'IMG_6131.JPG',
                'type' => 'image/jpeg',
                'size' => '72601',
                'tmp_name' => $src . 'f2e3d4c5.tmp'
            )
        )
    );
    $this->Upload->clear();
    $result = $this->Upload->isUpload($data);
    $this->assertEqual(true, $result);

    $result = $this->Upload->upload($dst, array('overwrite' => true));
    $expected = array(0 => 'IMG_6131.JPG');
    $this->assertEqual($expected, $result);

    $this->assertEqual(true, copy(RESOURCES . 'IMG_6131.JPG', $src . 'f2e3d4c5.tmp'));
    $this->FileManager->uniqueFilenameMockValue = 'IMG_6131.0.JPG';
    $result = $this->Upload->upload($dst, array('overwrite' => false));
    $expected = array(0 => 'IMG_6131.0.JPG');
    $this->assertEqual($expected, $result);

    $this->assertEqual(true, copy(RESOURCES . 'IMG_6131.JPG', $src . 'f2e3d4c5.tmp'));
    $this->FileManager->uniqueFilenameMockValue = 'IMG_6131.1.JPG';
    $result = $this->Upload->upload($dst, array('overwrite' => false));
    $expected = array(0 => 'IMG_6131.1.JPG');
    $this->assertEqual($expected, $result);

    $this->_deleteUploadFolders();
  }

  public function testErrors() {
    $src = TEST_FILES . 'src' . DS;
    $dst = TEST_FILES . 'dst' . DS;
    $this->_buildUploadFolders();
    $this->assertEqual(true, copy(RESOURCES . 'IMG_4145.JPG', $src . 'f0e1d2c3.tmp'));

    $data = array(
        'error' => 1,
        'name' => 'IMG_4145.JPG',
        'type' => 'image/jpeg',
        'size' => '85363',
        'tmp_name' => $src . 'f0e1d2c3.tmp'
    );
    $this->Controller->request->data = $data;
    $result = $this->Upload->isUpload();
    $this->assertEqual($result, false);

    $result = $this->Upload->hasErrors();
    $this->assertEqual($result, true);

    $errors = $this->Upload->getErrors();
    $expected = array(
        'IMG_4145.JPG' => array(
            'msg' => 'uploadError',
            'data' => array(
                'error' => 1,
                'name' => 'IMG_4145.JPG',
                'type' => 'image/jpeg',
                'size' => '85363',
                'tmp_name' => $src . 'f0e1d2c3.tmp'
            )
        )
    );
    $this->assertEqual($expected, $errors);
    $data = array(
        'error' => 0,
        'name' => 'IMG_4145.JPG',
        'type' => 'image/jpeg',
        'size' => '79666',
        'tmp_name' => $src . 'f0e1d2c3.tmp'
    );
    $this->Upload->clear();
    $result = $this->Upload->isUpload($data);
    $this->assertEqual(true, $result);
    $this->Upload->upload($dst);

    $this->assertEqual(true, copy(RESOURCES . 'IMG_4145.JPG', $src . 'f0e1d2c3.tmp'));

    // FileManager->canWrite() error - Quota check failed
    $this->FileManager->canWriteMockValue = false;
    $result = $this->Upload->upload($dst);
    $this->assertEqual($result, array());

    $result = $this->Upload->getErrors();
    $expected = array(
        'IMG_4145.JPG' => array(
            'msg' => 'quotaExceed',
            'data' => array(
                'error' => 0,
                'name' => 'IMG_4145.JPG',
                'type' => 'image/jpeg',
                'size' => '79666',
                'tmp_name' => $src . 'f0e1d2c3.tmp'
            )
        )
    );
    $this->assertEqual($expected, $result);

    // FileManager->add() error
    $this->FileManager->canWriteMockValue = true;
    $this->FileManager->addMockValue = false;
    $result = $this->Upload->upload($dst);

    $result = $this->Upload->getErrors();
    $expected = array(
        'IMG_4145.JPG' => array(
            'msg' => 'fileManagerError',
            'data' => array(
                'error' => 0,
                'name' => 'IMG_4145.JPG',
                'type' => 'image/jpeg',
                'size' => '79666',
                'tmp_name' => $src . 'f0e1d2c3.tmp'
            )
        )
    );
    $this->assertEqual($expected, $result);

    $this->_deleteUploadFolders();
  }

}

?>
