<?php 
/*
 * phtagr.
 * 
 * social photo gallery for your community.
 * 
 * Copyright (C) 2006-2010 Sebastian Felis, sebastian@phtagr.org
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

class ZipComponent extends Object {
  
  var $name = 'ZipComponent';
  var $controller = null;
  var $components = array('FileManager');
  var $zip = null;
  var $_stats = null;

  function startup(&$controller) {
    $this->controller = $controller;
    if (class_exists('ZipArchive')) {
      $this->zip = new ZipArchive();
    }
  }

  function unzip($file, $dst = false) {
    if (!$this->zip) {
      Logger::err("Missing plugin for class ZipArchive");
      return false;
    }
    if (!is_readable($file)) {
      Logger::err("File $file is not readable");
      return false;
    }

    if (!$dst) {
      $dst = dirname($file);
    } elseif (!is_dir($dst)) {
      Logger::err("Destination $dst is not a directory");
      return false;
    }
    $dst = Folder::slashTerm($dst);

    if (!is_writeable($dst)) {
      Logger::err("Destiantion $dst is not writeable");
      return false;
    }

    if ($this->zip->open($file) !== true) {
      Logger::err("Could not open file $file");
      return false;
    } else {
      Logger::info("Open $file with {$this->zip->numFiles} file(s)");
    }

    $this->_stats = array();
    $bytes = 0;
    for ($i = 0; $i < $this->zip->numFiles; $i++) {
      $stat = $this->zip->statIndex($i);
      $bytes += $stat['size'];
      $this->_stats[] = $stat;
    }
    if (!$this->FileManager->canWrite($bytes)) {
      Logger::warn("Extracted data exceeds user's quota");
      return array();
    }

    $newFiles = array();
    foreach ($this->_stats as $file) {
      $newFile = $this->_extract($file, $dst);
      if ($newFile) {
        $newFiles[] = $newFile;
      }
    }
    $this->zip->close();
    return $newFiles;
  }

  /** Exract file from zip file 
    @param file Array of file stat
    @param dst Destination of filei
    @result filename on success */
  function _extract($file, $dst) {
    $fp = $this->zip->getStream($file['name']);
    if (!$fp) {
      Logger::err("Could not extract {$file['name']}");
      return false;
    }
    if (dirname($file['name']) != '') {
      $folder = new Folder();
      $fileDir = $dst.dirname($file['name']);
      if (!is_dir($fileDir)) {
        if (!$folder->mkdir($fileDir)) {
          Logger::err("Could not create directory $fileDir");
          return false;
        } else {
          Logger::verbose("Create directory $fileDir");
        }
      }
    }

    // skip directories, which have zero size
    if ($file['size'] === 0) {
      Logger::debug("Skip directory {$file['name']}");
      return false;
    }

    $newFile = $dst.$file['name'];
    $tp = fopen($newFile, 'w');
    if (!$tp) {
      fclose($fp);
      Logger::err("Could not open file $newFile");
      return false;
    }

    $written = 0;
    while (!feof($fp)) {
      $buf = fread($fp, 1024);
      $written += fwrite($tp, $buf);
    }
    fclose($fp);
    fclose($tp);
    if ($written != $file['size']) {
      Logger::warn("Extraction error: File has {$file['size']} Bytes but $written Bytes were written");
      unlink($newFile);
      return false;
    }
    if (!$this->FileManager->add($newFile)) {
      unlink($newFile);
      Logger::err("Could not insert $newFile to database");
      return false;
    }
    Logger::verbose("Extracted {$file['name']} ($written Bytes)");
    return $newFile;
  }
}

?>
