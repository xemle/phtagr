<?php 
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2009 Sebastian Felis, sebastian@phtagr.org
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the 
 * License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

class UploadComponent extends Object {
  
  var $name = 'UploadComponent';
  var $controller = null;
  var $components = array('FileCache', 'FileManager');
  
  function startup(&$controller) {
    $this->controller = $controller;
  }
  
  function isUpload() {
    return $this->getUploadData() ? true : false;
  }

  function getUploadData() {
    if (isset($this->controller->data['File']['Filedata'])) {
      return $this->controller->data['File']['Filedata'];
    }
    return false;
  } 

  function upload($options = array()) {
    $options = am(array('root' => false, 'path' => '', 'overwrite' => true), $options);
    if (!isset($options['root'])) {
      Logger::err("Option for root directory is missing");
      return false;
    } 
    if (!$this->validate()) {
      return false;
    }

    $path = Folder::slashTerm($options['root']).$options['path'];
    $path = Folder::slashTerm($path);
    if (!is_dir($path) || !is_writeable($path)) {
      Logger::err("Upload path '$path' does not exists or is not writeable");
      return false;
    }
    $folder = new Folder($path);
    $uploadData = $this->getUploadData();
    $filename = $uploadData['name'];

    if ($folder->find($filename)) {
      if (!$options['overwrite']) {
        $filename = $this->createUniqueFilename($path, $filename);
      } else {
        $this->_deleteOldFile($path.$filename);
      }
    }

    $tmpFile = $uploadData['tmp_name'];
    if (!move_uploaded_file($tmpFile, $path.$filename)) {
      Logger::err("Could not write uploaded file");
      return false;
    }
    // Check users quota
    if (!$this->FileManager->canWrite($uploadData['size'])) {
      Logger::warn("Quota exceed. Deny upload of {$uploadData['size']} Bytes");
      return false;
    }

    if (!$this->FileManager->add($path.$filename)) {
      unlink($path.$filename);
      Logger::err("Could not insert $path$filename to database");
      return false;
    }

    return $path.$filename;
  }
  
  function _deleteOldFile($file) {
    $data = $this->controller->MyFile->findByFilename($file);
    if (!$data) {
      Logger::warn("Could not find file '$file' in database");
    } else {
      // @TODO delete only media if media requires file
      $this->FileCache->delete($data['File']['user_id'], $data['Media']['id']);
      $this->controller->Media->delete($data['Media']['id']);
      Logger::info("Delete existsing file '$file' data for overwrite");
    }
  }

  function createUniqueFilename($path, $filename) {
    $path = Folder::slashTerm($path);
    $name = substr($filename, 0, strrpos($filename, '.'));
    $ext = substr($filename, strrpos($filename, '.') + 1);
    $found = false;
    $count = 0;
    while (!$found) {
      $new = $name.'.'.$count.'.'.$ext;
      if (!file_exists($path.$new)) {
        return $new;
      }
      $count++;
    }
  }

  function validate() {
    $data = $this->getUploadData();
    if (!$data ||
      $data['error'] ||
      !is_uploaded_file($data['tmp_name'])) {
      Logger::warn('Upload data is invalid');
      Logger::trace($data);
      return false;
    }
    return true;
  }
  
}
?>
