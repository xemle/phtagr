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
App::uses('Component', 'Controller');

class PluploadComponent extends Component {

  var $name = 'PluploadComponent';

  var $controller = null;

  var $components = array('FileCache', 'FileManager');

  var $maxAgeFile = 18000; // 5 hours

  var $response = array('jsonrpc' => '2.0', 'result' => null);

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  public function isUploadedFile($filename) {
    return is_uploaded_file($filename);
  }

  public function moveUploadedFile($filename, $dst) {
    return move_uploaded_file($filename, $dst);
  }

  public function isPlupload() {
    $request = $this->controller->request;
    if (!isset($request->data['name'])) {
      return false;
    }
    if (!isset($request->params['form']) || !isset($request->params['form']['file'])) {
      return false;
    }
    $paramNames = array('name', 'type', 'tmp_name', 'error', 'size');
    $file = $request->params['form']['file'];
    foreach ($paramNames as $name) {
      if (!isset($file[$name])) {
        return false;
      }
    }
    if ($file['error'] != 0) {
      return false;
    }
    if (!$this->isUploadedFile($file['tmp_name'])) {
      return false;
    }
    return true;
  }

  /**
   * Upload a single chunk file
   *
   * @param String $path Target directory
   * @param Array $upload Upload
   * @param Arry $options
   * @return mixed Returns filename without the directory. false on error.
   */
  public function _handleCompleteFile($path, $upload, $options) {
    $folder = new Folder($path);

    $filename = $upload['name'];
    if ($folder->find($filename)) {
      if (!$options['overwrite']) {
        $filename = $this->FileManager->createUniqueFilename($path, $filename);
      } else {
        $this->FileManager->delete($path . $filename);
      }
    }

    $targetFilename = $path . $filename;
    if (!$this->moveUploadedFile($upload['tmp_name'], $targetFilename)) {
      $this->_addError($upload['name'], 'uploadMoveError', $upload);
      Logger::err("Could not write uploaded file");
      return false;
    }
    return $filename;
  }

  /**
   * Copy one file to another
   *
   * @param file handle $in Input stream handle
   * @param file handle $out Output stream handle
   * @return Returns count of written bytes
   */
  public function _copyFile($in, $out) {
    if (!$in) {
      $this->response['error'] = array('code' => 103, 'message' => "Failed to open input stream.");
      return false;
    }
    if (!$out) {
      $this->response['error'] = array('code' => 104, 'message' => "Failed to open output stream.");
      return false;
    }
    $count = 0;
    while ($buf = fread($in, 4096)) {
      $count += fwrite($out, $buf);
    }
    return $count;
  }

  public function _getContentType() {
    if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
      return $_SERVER["HTTP_CONTENT_TYPE"];
    } else if (isset($_SERVER["CONTENT_TYPE"])) {
      return $_SERVER["CONTENT_TYPE"];
    }
    return '';
  }
  
  public function _handleMultiPartFile($path, $upload, $chunk, $chunks, $options) {
    $folder = new Folder($path);

    $filename = $upload['name'] . '.part';
    $absoluteFilename = $path . $filename;
    if ($folder->find($filename)) {
      if ($chunk == 0 && filemtime($absoluteFilename) < time() - $this->maxFileAge) {
        // delete old part file
        $this->FileManager->delete($absoluteFilename);
      }
    } else if ($chunk > 0) {
      $this->_addError($upload['name'], 'chunkMissing', $upload);
      Logger::err("Could not find chunk");
      return false;
    }
    // Look for the content type header
    $contentType = $this->_getContentType();

    $out = fopen($absoluteFilename, $chunk == 0 ? "wb" : "ab");
    if (strpos($contentType, "multipart") !== false) {
      $in = fopen($upload['tmp_name'], "rb");
    } else {
      $in = fopen("php://input", "rb");
    }
    $count = $this->_copyFile($in, $out);
    @fclose($in);
    @fclose($out);
    @unlink($upload['tmp_name']);

    if ($count && $chunk == $chunks - 1) {
      $filename = $upload['name'];
      if ($folder->find($filename)) {
        if (!$options['overwrite']) {
          $filename = $this->FileManager->createUniqueFilename($path, $filename);
        } else {
          $this->FileManager->delete($path . $filename);
        }
      }
      $this->FileManager->move($absoluteFilename, $path . $filename);
      Logger::verbose("Uploaded complete of " . $upload['name']);
      return $filename;
    } else {
      $this->FileManager->add($absoluteFilename);
      Logger::verbose("Uploaded chunk $chunk/$chunks (" . sprintf('%.2f', $chunk / $chunks) . '%) of ' . $upload['name']);
    }
    return false;
  }

  /**
   * Upload the data to a given directory
   *
   * @param path Destination directory
   * @param options
   *   - overwrite - If true overwrite file with same filename. If false create a unique filename if a file with same filename exists
   * @return array of uploaded files (without the path)
   */
  public function upload($path, $options = array()) {
    if (!$this->isPlupload()) {
      $this->response['error'] = array('code' => 100, 'message' => "Invalid data");
      return array();
    }
    $options = am(array('overwrite' => true), $options);

    if (!is_dir($path) || !is_writeable($path)) {
      $this->response['error'] = array('code' => 101, 'message' => "Cound not write to target directory");
      Logger::err("Upload path '$path' does not exists or is not writeable");
      return false;
    }
    $request = $this->controller->request;

    $upload = $request->params['form']['file'];
    $name = $request->data['name'];
    $upload['name'] = preg_replace('/[^\w\._]+/', '_', $name);

    $chunk = 0;
    $chunks = 1;
    if (isset($request->data['chunk']) && isset($request->data['chunks'])) {
      $chunk = intval($request->data['chunk']);
      $chunks = intval($request->data['chunks']);
    }

    if (!$this->FileManager->canWrite($upload['size'])) {
      $this->response['error'] = array('code' => 102, 'message' => "Upload limit exceeded");
      Logger::warn("Quota exceed. Deny upload of {$upload['size']} Bytes");
      return false;
    }

    $path = Folder::slashTerm($path);
    $filename = false;
    if ($chunks < 2) {
      $filename = $this->_handleCompleteFile($path, $upload, $options);
    } else {
      $filename = $this->_handleMultiPartFile($path, $upload, $chunk, $chunks, $options);
    }

    if ($filename) {
      if (!$this->FileManager->add($path . $filename)) {
        $this->_addError($upload['name'], 'fileManagerError', $upload);
        @unlink($path . $filename);
        Logger::err("Could not insert $path$filename to database");
      } else {
        return $filename;
      }
    }
    return false;
  }

}
