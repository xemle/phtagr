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
App::uses('PluploadComponent', 'Controller/Component');
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

class PluploadComponentMock extends PluploadComponent {
  public function isUploadedFile($filename) {
    return true;
  }

  public function moveUploadedFile($filename, $dst) {
    return rename($filename, $dst);
  }
  
  public function _getContentType() {
    return 'multipart';
  }
}

class FileManagerComponentMockB extends FileManagerComponent {
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
  
  public function move($src, $dst) {
    rename($src, $dst);
    return true;
  }
}

class PluploadComponentTest extends CakeTestCase {

  var $Folder = null;
  var $Controller = null;
  var $FileManager = null;

  /** Upload component */
  var $Plupload = null;
  public $fixtures = array();

  public function setUp() {
    parent::setUp();
    $this->Folder = new Folder();
    $this->request = new CakeRequest('controller_posts/index');
    $this->request->params['pass'] = $this->request->params['named'] = array();
    $this->Controller = new Controller($this->request);
    $this->FileManager = new FileManagerComponentMockB($this->getMock('ComponentCollection'), array());
    $this->Plupload = new PluploadComponentMock($this->getMock('ComponentCollection'), array());
    $this->Plupload->controller = $this->Controller;
    $this->Plupload->FileManager = $this->FileManager;
    $this->_buildUploadFolders();
  }

  /**
   * tearDown method
   *
   * @return void
   */
  public function tearDown() {
    $this->_deleteUploadFolders();
    unset($this->Plupload);
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

  public function testIsPlupload() {
    $result = $this->Plupload->isPlupload();
    $this->assertEqual(false, $result);
    
    $this->request->data = array('name' => 'small.jpg');
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'tmp_name' => '/tmp/12345', 'error' => 0, 'size' => 31)));
    $result = $this->Plupload->isPlupload();
    $this->assertEqual(true, $result);

    $this->request->data = array('name' => 'small.jpg', 'chunk' => 0, 'chunks' => 2);
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'tmp_name' => '/tmp/12345', 'error' => 0, 'size' => 31)));
    $result = $this->Plupload->isPlupload();
    $this->assertEqual(true, $result);

    // upload error should return false
    $this->request->data = array('name' => 'small.jpg', 'chunk' => 0, 'chunks' => 2);
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'tmp_name' => '/tmp/12345', 'error' => 1, 'size' => 31)));
    $result = $this->Plupload->isPlupload();
    $this->assertEqual(false, $result);
  }

  public function testUploadSingle() {
    $src = TEST_FILES . 'src' . DS;
    $dst = TEST_FILES . 'dst' . DS;
    $this->_buildUploadFolders();
    
    $filename = 'gps.log';
    $content = 'GPS LOG';
    $tmpFilename = $src . '1234.tmp';
    file_put_contents($tmpFilename, $content);
    $this->request->data = array('name' => $filename);
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'size' => strlen($content), 'tmp_name' => $tmpFilename, 'error' => 0)));

    $result = $this->Plupload->upload($dst);
    $this->assertEqual($result, $filename);
    $this->assertEqual(true, file_exists($dst . $filename));
    $this->assertEqual($content, file_get_contents($dst . $filename));
    
    // test overwrite
    $content = 'GPS LOG overwrite';
    $tmpFilename = $src . '2345.tmp';
    file_put_contents($tmpFilename, $content);
    $this->request->data = array('name' => $filename);
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'size' => strlen($content), 'tmp_name' => $tmpFilename, 'error' => 0)));

    $result = $this->Plupload->upload($dst, array('overwrite' => true));
    $this->assertEqual($result, $filename);
    $this->assertEqual(true, file_exists($dst . $filename));
    $this->assertEqual($content, file_get_contents($dst . $filename));

    $content = 'GPS LOG no overwrite';
    $tmpFilename = $src . '3456.tmp';
    file_put_contents($tmpFilename, $content);
    $this->request->data = array('name' => $filename);
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'size' => strlen($content), 'tmp_name' => $tmpFilename, 'error' => 0)));

    $newFilename = 'gps.0.log';
    $this->FileManager->uniqueFilenameMockValue = $newFilename;
    $result = $this->Plupload->upload($dst, array('overwrite' => false));
    $this->assertEqual($result, $newFilename);
    $this->assertEqual(true, file_exists($dst . $newFilename));
    $this->assertEqual($content, file_get_contents($dst . $newFilename));

    $this->_deleteUploadFolders();
  }


  public function testUploadChunks() {
    $src = TEST_FILES . 'src' . DS;
    $dst = TEST_FILES . 'dst' . DS;
    $this->_buildUploadFolders();
    
    $filename = 'gps.log';
    $content1 = 'GPS';
    $tmpFilename = $src . '1234.tmp';
    file_put_contents($tmpFilename, $content1);
    $this->request->data = array('name' => $filename, 'chunk' => 0, 'chunks' => 2);
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'size' => strlen($content1), 'tmp_name' => $tmpFilename, 'error' => 0)));

    $result = $this->Plupload->upload($dst);
    $this->assertEqual(false, $result);
    $partFilename = $filename . '.part';
    $this->assertEqual(true, file_exists($dst . $partFilename));
    $tmp = file_get_contents($dst . $partFilename); 
    $this->assertEqual($content1, file_get_contents($dst . $partFilename));

    $filename = 'gps.log';
    $content2 = ' LOG';
    $tmpFilename = $src . '1234.tmp';
    file_put_contents($tmpFilename, $content2);
    $this->request->data = array('name' => $filename, 'chunk' => 1, 'chunks' => 2);
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'size' => strlen($content2), 'tmp_name' => $tmpFilename, 'error' => 0)));

    $result = $this->Plupload->upload($dst);
    $this->assertEqual($filename, $result);
    $this->assertEqual(true, file_exists($dst . $filename));
    $this->assertEqual($content1 . $content2, file_get_contents($dst . $filename));
    
    // Test overwrite
    $filename = 'gps.log';
    $content1 = 'Overwrite ';
    $tmpFilename = $src . '1234.tmp';
    file_put_contents($tmpFilename, $content1);
    $this->request->data = array('name' => $filename, 'chunk' => 0, 'chunks' => 2);
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'size' => strlen($content1), 'tmp_name' => $tmpFilename, 'error' => 0)));

    $result = $this->Plupload->upload($dst, array('overwrite' => true));
    $this->assertEqual(false, $result);
    $partFilename = $filename . '.part';
    $this->assertEqual(true, file_exists($dst . $partFilename));
    $this->assertEqual($content1, file_get_contents($dst . $partFilename));

    $filename = 'gps.log';
    $content2 = ' GPS LOG';
    $tmpFilename = $src . '1234.tmp';
    file_put_contents($tmpFilename, $content2);
    $this->request->data = array('name' => $filename, 'chunk' => 1, 'chunks' => 2);
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'size' => strlen($content2), 'tmp_name' => $tmpFilename, 'error' => 0)));

    $result = $this->Plupload->upload($dst, array('overwrite' => true));
    $this->assertEqual($filename, $result);
    $this->assertEqual(true, file_exists($dst . $filename));
    $this->assertEqual($content1 . $content2, file_get_contents($dst . $filename));

    
    // Test no overwrite
    $filename = 'gps.log';
    $content1 = 'Overwrite ';
    $tmpFilename = $src . '1234.tmp';
    file_put_contents($tmpFilename, $content1);
    $this->request->data = array('name' => $filename, 'chunk' => 0, 'chunks' => 2);
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'size' => strlen($content1), 'tmp_name' => $tmpFilename, 'error' => 0)));

    $result = $this->Plupload->upload($dst, array('overwrite' => false));
    $this->assertEqual(false, $result);
    $partFilename = $filename . '.part';
    $this->assertEqual(true, file_exists($dst . $partFilename));
    $this->assertEqual($content1, file_get_contents($dst . $partFilename));

    $filename = 'gps.log';
    $content2 = ' GPS LOG';
    $tmpFilename = $src . '1234.tmp';
    file_put_contents($tmpFilename, $content2);
    $this->request->data = array('name' => $filename, 'chunk' => 1, 'chunks' => 2);
    $this->request->params = array('form' => array('file' => array('name' => 'blob', 'type' => 'application/octet', 'size' => strlen($content2), 'tmp_name' => $tmpFilename, 'error' => 0)));

    $newFilename = 'gps.0.log';
    $this->FileManager->uniqueFilenameMockValue = $newFilename;
    $result = $this->Plupload->upload($dst, array('overwrite' => false));
    $this->assertEqual($newFilename, $result);
    $this->assertEqual(true, file_exists($dst . $newFilename));
    $this->assertEqual($content1 . $content2, file_get_contents($dst . $newFilename));

    $this->_deleteUploadFolders();
  }

}
