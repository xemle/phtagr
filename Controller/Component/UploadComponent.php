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

class UploadComponent extends Component {

  var $name = 'UploadComponent';

  var $controller = null;

  var $components = array('FileCache', 'FileManager');

  var $_uploads = null;

  var $_errors = null;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  public function clear() {
    $this->_uploads = null;
    $this->_errors = null;
  }

  /**
   *  Add error for upload file
   *
   * @param file Upload filename
   * @param msg Error message / Error code
   * @param data Arbitrary data. Optional
   */
  public function _addError($file, $msg, $data = null) {
    if ($this->_errors === null) {
      $this->_errors = array();
    }
    $this->_errors[$file] = array('msg' => $msg);
    if ($data) {
      $this->_errors[$file]['data'] = $data;
    }
    return true;
  }

  /**
   * Extract upload data recursivly from a data array
   *
   * @param data Data array
   * @return array of upload data
   */
  public function _extractUploads($data) {
    if (!$data || !is_array($data)) {
      return array();
    } elseif (isset($data['error']) && isset($data['name']) &&
      isset($data['type']) && isset($data['size']) &&
      isset($data['tmp_name'])) {
      // handle errors
      if ($data['error']) {
        $this->_addError($data['name'], 'uploadError', $data);
        return array();
      // uploaded files
      } elseif ($this->isUploadedFile($data['tmp_name'])) {
        extract($data);
        return array(compact('error', 'name', 'type', 'size', 'tmp_name'));
      } else {
        $this->_addError($data['name'], 'noUploadFile', $data);
        return array();
      }
    } else {
      $result = array();
      foreach ($data as $d) {
        $result = am($result, $this->_extractUploads($d));
      }
      return $result;
    }
  }

  /**
   * Returns the upload data
   *
   * @param data Upload data. If null the controler's default data is used
   * @return array of upload data
   * @note Before using new data call clear() otherwise the data will be cached
   */
  public function getUploads($data = null) {
    if ($this->_uploads !== null) {
      return $this->_uploads;
    }

    if (!$data && $this->controller->request->data) {
      $data = $this->controller->request->data;
    }

    $uploads = $this->_extractUploads($data);
    if (count($uploads)) {
      $this->_uploads = $uploads;
    } else {
      $uploads = $this->_extractUploads($this->controller->request->params);
      if (count($uploads)) {
        $this->_uploads = $uploads;
      } else {
        $this->_uploads = null;
      }
    }
    return $this->_uploads;
  }

  /**
   * Evaluates if upload data is true
   *
   * @return true if upload data is available. false otherwise
   */
  public function isUpload($data = null) {
    if (count($this->getUploads($data))) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Returns true if one of the upload files has errors
   *
   * @return True if upload data contains error
   */
  public function hasErrors() {
    $this->getUploads();
    if (is_array($this->_errors) && count($this->_errors)) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Returns the error array.
   *
   * @return Array of error
   */
  public function getErrors() {
    if ($this->hasErrors()) {
      return $this->_errors;
    } else {
      return false;
    }
  }

  /**
   * Returns the sum uploaded data size
   *
   * @return Size of all uploded files
   */
  public function getSize() {
    $uploads = $this->getUploads();
    if (!$uploads || count($uploads) == 0) {
      return 0;
    }
    return array_sum(Set::extract('/size', $uploads));
  }

  public function isUploadedFile($filename) {
    return is_uploaded_file($filename);
  }

  public function moveUploadedFile($filename, $dst) {
    return move_uploaded_file($filename, $dst);
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
    $options = am(array('overwrite' => true), $options);

    if (!is_dir($path) || !is_writeable($path)) {
      Logger::err("Upload path '$path' does not exists or is not writeable");
      return false;
    }
    $path = Folder::slashTerm($path);
    $folder = new Folder($path);
    $uploads = $this->getUploads();
    $uploaded = array();
    foreach ($uploads as $upload) {
      $filename = $upload['name'];

      if ($folder->find($filename)) {
        if (!$options['overwrite']) {
          $filename = $this->FileManager->createUniqueFilename($path, $filename);
        } else {
          $this->FileManager->delete($path . $filename);
        }
      }
      // Check users quota
      if (!$this->FileManager->canWrite($upload['size'])) {
        $this->_addError($upload['name'], 'quotaExceed', $upload);
        Logger::warn("Quota exceed. Deny upload of {$upload['size']} Bytes");
        continue;
      }

      if (!$this->moveUploadedFile($upload['tmp_name'], $path . $filename)) {
        $this->_addError($upload['name'], 'uploadMoveError', $upload);
        Logger::err("Could not write uploaded file");
        continue;
      }

      if (!$this->FileManager->add($path . $filename)) {
        $this->_addError($upload['name'], 'fileManagerError', $upload);
        @unlink($path . $filename);
        Logger::err("Could not insert $path$filename to database");
        continue;
      }

      $uploaded[] = $filename;
    }
    return $uploaded;
  }
}
?>
