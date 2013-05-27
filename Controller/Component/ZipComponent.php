<?php
/**
 * PHP versions 5
 *
 * phTagr : Organize, Browse, and Share Your Photos.
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

App::uses('Folder', 'Utility');

class ZipComponent extends Component {

  var $name = 'ZipComponent';
  var $controller = null;
  var $components = array('FileManager');
  var $Zip = null;
  var $Folder = null;
  var $_stats = array();

  public function initialize(Controller $controller) {
    $this->controller = $controller;
    $this->Folder = new Folder();
    if (class_exists('ZipArchive')) {
      $this->Zip = new ZipArchive();
    } else {
      CakeLog::error("Missing plugin for class ZipArchive");
    }
  }

  public function _readZip($file) {
    if (isset($this->_stats[$file])) {
      return $this->_stats[$file];
    }

    if (!$this->Zip) {
      return false;
    }

    if (!is_readable($file)) {
      CakeLog::error("File $file is not readable");
      $this->_stats[$file] = false;
      return false;
    }

    if ($this->Zip->open($file) !== true) {
      CakeLog::error("Could not open file $file");
      $this->_stats[$file] = false;
      return false;
    } else {
      CakeLog::info("Open $file with {$this->Zip->numFiles} file(s)");
    }

    $stat = array();
    $stat['count'] = $this->Zip->numFiles;
    $stat['files'] = array();
    $size = 0;
    for ($i = 0; $i < $this->Zip->numFiles; $i++) {
      $fileInfo = $this->Zip->statIndex($i);
      $size += $fileInfo['size'];
      $stat['files'][$i] = $fileInfo;
    }
    $this->Zip->close();
    $stat['size'] = $size;
    $this->_stats[$file] = $stat;

    return $stat;
  }

  public function getExtractedSize($file) {
    if (!$this->Zip) {
      return false;
    }

    $stat = $this->_readZip($file);
    $sizes = Set::extract('/size', $stat);
    return array_sum($sizes);
  }

  public function unzip($file, $dst = false) {
    if (!$this->Zip) {
      return false;
    }

    $stat = $this->_readZip($file);
    if (!$stat) {
      return false;
    }

    if (!$dst) {
      $dst = dirname($file);
    } elseif (!is_dir($dst)) {
      CakeLog::error("Destination $dst is not a directory");
      return false;
    }
    $dst = Folder::slashTerm($dst);

    if (!is_writeable($dst)) {
      CakeLog::error("Destiantion $dst is not writeable");
      return false;
    }

    if (!$this->FileManager->canWrite($stat['size'])) {
      CakeLog::warning("Extracted data exceeds user's quota");
      return array();
    }
    if ($this->Zip->open($file) !== true) {
      CakeLog::error("Could not open file $file");
      return false;
    }

    $newFiles = array();
    foreach ($stat['files'] as $file) {
      $newFile = $this->_extract($file, $dst);
      if ($newFile) {
        $newFiles[] = $newFile;
      }
    }
    $this->Zip->close();
    CakeLog::debug($newFiles);
    return $newFiles;
  }

  /** Exract file from zip file
    @param file Array of file stat
    @param dst Destination of file
    @result filename on success */
  public function _extract($file, $dst) {
    $fp = $this->Zip->getStream($file['name']);
    if (!$fp) {
      CakeLog::error("Could not extract {$file['name']}");
      return false;
    }
    if (dirname($file['name']) != '') {
      $dirname = dirname($file['name']);
      if ($dirname != '.') {
        $dst .= dirname($file['name']);
      }
      if (!is_dir($dst)) {
        if (!$this->Folder->create($dst)) {
          CakeLog::error("Could not create directory $dst");
          return false;
        } else {
          CakeLog::debug("Create directory $dst");
        }
      }
    }
    $this->Folder->cd($dst);
    $dst = Folder::slashTerm($dst);

    // skip directories, which have zero size
    if ($file['size'] === 0) {
      CakeLog::debug("Skip directory {$file['name']}");
      return false;
    }

    $newFile = $dst . $this->FileManager->createUniqueFilename($dst, basename($file['name']));
    $tp = fopen($newFile, 'w');
    if (!$tp) {
      fclose($fp);
      CakeLog::error("Could not open file $newFile");
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
      CakeLog::warning("Extraction error: File has {$file['size']} Bytes but $written Bytes were written");
      unlink($newFile);
      return false;
    }

    if (!$this->FileManager->add($newFile)) {
      unlink($newFile);
      CakeLog::error("Could not insert $newFile to database");
      return false;
    }
    CakeLog::debug("Extracted {$file['name']} ($written Bytes)");
    return $newFile;
  }

}

?>
