<?php
App::import('Component', array('Upload', 'FileManager'));
App::import('Model', 'MyFile');
App::import('File', 'Logger', array('file' => APP.'logger.php'));

if (!defined('TEST_FILES')) {
  define('TEST_FILES', TESTS . 'files' . DS);
}
if (!defined('TEST_FILES_TMP')) {
  define('TEST_FILES_TMP', TEST_FILES . 'tmp' . DS);
}

if (!is_writeable(TEST_FILES)) {
  trigger_error(sprintf(__('Test file directory %s must be writeable', true), TEST_FILES), E_USER_ERROR);
}

class FakeUploadController {}

class UploadComponentTest extends CakeTestCase {

  var $Folder = null;

  var $Controller = null;

  /** Upload component */
  var $Upload = null;

  function startcase() {
    $this->Folder = new Folder();

    $this->Controller = new FakeUploadController();
    $this->Controller->data = false;
    Mock::generatePartial('MyFile', 'MockMyFile', array('findByFilename'));
    $this->Controller->MyFile = new MockMyFile();

    $this->Upload = new UploadComponent();
    $this->Upload->_testRun = true;

    Mock::generate('FileManagerComponent', 'MockFileManagerComponent');
    $FileManager = new MockFileManagerComponent();
    $FileManager->setReturnValue('add', true);
    $FileManager->setReturnValue('canWrite', true);
    $this->Upload->FileManager =& $FileManager;

    $this->Controller->Upload =& $this->Upload;
    $this->Upload->startup(&$this->Controller);
  }

  /** Reset all upload data before new test function */
  function starttest($method) {
    $this->Controller->data = null;
    $this->Upload->clear();
  }

  function endtest($method) {
  }

  function testInitialStates() {
    // initial no upload
    $result = $this->Upload->isUpload();
    $this->assertEqual($result, false);

    $result = $this->Upload->getSize();
    $this->assertEqual($result, 0);

    $result = $this->Upload->hasErrors();
    $this->assertEqual($result, false);

    $result = $this->Upload->getErrors();
    $this->assertEqual($result, false);
  }

  function testGetUploadsSingle() {
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
    $this->assertEqual($result, $expected);

    // Read from the internal upload array
    $result = $this->Upload->getUploads();
    $this->assertEqual($result, $expected);

    // clear test
    $this->Upload->clear();
    $this->Controller->data = false;
    $result = $this->Upload->getUploads();
    $this->assertEqual($result, null);

    // read data from controller
    $this->Controller->data =& $data;
    $result = $this->Upload->getUploads();
    $this->assertEqual($result, $expected);

    $result = $this->Upload->hasErrors();
    $expected = false;
    $this->assertEqual($result, $expected);
  }

  function testGetUploadsMulti() {
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
    $this->Controller->data =& $data;
    $result = $this->Upload->getUploads();
    $this->assertEqual($result, $expected);

    $result = $this->Upload->hasErrors();
    $this->assertEqual($result, true);

    $result = $this->Upload->getSize();
    $expected = 221950;
    $this->assertEqual($result, $expected);
  }

  function _buildUploadFolders() {
    $src = TEST_FILES . 'src' . DS;
    $dst = TEST_FILES . 'dst' . DS;
    $this->Folder->create($src);
    $this->Folder->create($dst);
    $this->assertEqual(is_dir($src), true);
    $this->assertEqual(is_dir($dst), true);
  }

  function _deleteUploadFolders() {
    $src = TEST_FILES . 'src' . DS;
    $dst = TEST_FILES . 'dst' . DS;
    $this->Folder->delete($src);
    $this->Folder->delete($dst);
    $this->assertEqual(is_dir($src), false);
    $this->assertEqual(is_dir($dst), false);
  }

  function testUpload() {
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
            'name' => 'img_7795.jpg',
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
    $this->Controller->data =& $data;

    $this->assertEqual(copy(TEST_FILES_TMP . 'f0e1d2c3.tmp', $src . 'f0e1d2c3.tmp'), true);
    $this->assertEqual(copy(TEST_FILES_TMP . 'f1e2d3c4.tmp', $src . 'f1e2d3c4.tmp'), true);
    $this->assertEqual(copy(TEST_FILES_TMP . 'f2e3d4c5.tmp', $src . 'f2e3d4c5.tmp'), true);

    $noPath = TEST_FILES_TMP . 'tmp123' . DS;
    $result = $this->Upload->upload($noPath);
    $this->assertEqual($result, false);

    $result = $this->Upload->upload($dst);
    $expected = array(
      0 => 'IMG_4145.JPG',
      1 => 'img_7795.jpg',
      2 => 'IMG_6131.JPG'
      );
    $this->assertEqual($result, $expected);

    // overwrite test
    $this->assertEqual(copy(TEST_FILES_TMP . 'f2e3d4c5.tmp', $src . 'f2e3d4c5.tmp'), true);
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
    $this->assertEqual($result, true);

    $result = $this->Upload->upload($dst, array('overwrite' => true));
    $expected = array(0 => 'IMG_6131.JPG');
    $this->assertEqual($result, $expected);

    $this->assertEqual(copy(TEST_FILES_TMP . 'f2e3d4c5.tmp', $src . 'f2e3d4c5.tmp'), true);
    $result = $this->Upload->upload($dst, array('overwrite' => false));
    $expected = array(0 => 'IMG_6131.0.JPG');
    $this->assertEqual($result, $expected);

    $this->assertEqual(copy(TEST_FILES_TMP . 'f2e3d4c5.tmp', $src . 'f2e3d4c5.tmp'), true);
    $result = $this->Upload->upload($dst, array('overwrite' => false));
    $expected = array(0 => 'IMG_6131.1.JPG');
    $this->assertEqual($result, $expected);

    $this->_deleteUploadFolders(); 
  }

  function testErrors() {
    $src = TEST_FILES . 'src' . DS;
    $dst = TEST_FILES . 'dst' . DS;
    $this->_buildUploadFolders();
    $this->assertEqual(copy(TEST_FILES_TMP . 'f0e1d2c3.tmp', $src . 'f0e1d2c3.tmp'), true);

    $data = array(
      'error' => 1,
      'name' => 'IMG_4145.JPG',
      'type' => 'image/jpeg',
      'size' => '85363',
      'tmp_name' => $src . 'f0e1d2c3.tmp'
      );
    $this->Controller->data =& $data;
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
          'size' => '79666',
          'tmp_name' => $src . 'f0e1d2c3.tmp'
          )
        )
      );
    $this->assertEqual($result, $expected);

    $data = array(
      'error' => 0,
      'name' => 'IMG_4145.JPG',
      'type' => 'image/jpeg',
      'size' => '79666',
      'tmp_name' => $src . 'f0e1d2c3.tmp'
      );
    $this->Upload->clear();
    $result = $this->Upload->isUpload($data);
    $this->assertEqual($result, true);
    $this->Upload->upload($dst);

    $this->assertEqual(copy(TEST_FILES_TMP . 'f0e1d2c3.tmp', $src . 'f0e1d2c3.tmp'), true);

    // FileManager->canWrite() error - Quota check failed
    $count = $this->Upload->FileManager->_mock->getCallCount('canWrite');
    $this->Upload->FileManager->setReturnValueAt($count, 'canWrite', false);
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
    $this->assertEqual($result, $expected);

    // FileManager->add() error
    $count = $this->Upload->FileManager->_mock->getCallCount('add');
    $this->Upload->FileManager->setReturnValueAt($count, 'add', false);
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
    $this->assertEqual($result, $expected);

    $this->_deleteUploadFolders(); 
  }
}
?>
