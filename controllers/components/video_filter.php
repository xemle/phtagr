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

class VideoFilterComponent extends BaseFilterComponent {

  var $controller = null;
  var $components = array('Logger', 'VideoPreview', 'FileManager');

  function startup(&$controller) {
    $this->controller =& $controller;
  }

  function getName() {
    return "Video";
  }

  function getExtensions() {
    return array('avi', 'mov', 'mpeg', 'mpg', 'thm');
  }

  /** Read the video data from the file 
   * @param image Medium model data
   * @return True, false on error */
  function read(&$file, &$medium, $options = array()) {
    $filename = $this->MyFile->getFilename(&$file);

    $isNew = false;
    if (!$medium) {
      $medium = $this->Medium->create(array(
            'user_id' => $file['File']['user_id'],
            'type' => MEDIUM_TYPE_VIDEO,
            'date' => date('Y-m-d H:i:s', time()),
            'name' => basename($filename),
            'orientation' => 1
            ), true);
      $isNew = true;
    }

    if ($this->MyFile->isType($file, FILE_TYPE_VIDEOTHUMB)) {
      $ImageFilter = $this->Manager->getFilter('Image');
      $this->Logger->debug("Read video thumbnail by ImageFilter: $filename");
      foreach (array('name', 'width', 'height', 'flag', 'duration') as $column) {
        $tmp[$column] = $medium['Medium'][$column];
      }
      $ImageFilter->read(&$file, &$medium, array('noSave' => true));
      // accept different name except filename
      if ($medium['Medium']['name'] != basename($filename)) {
        unset($tmp['name']);
      }
      // restore overwritten values
      $medium['Medium'] = am($medium['Medium'], $tmp);
      if (!$this->Medium->save($medium)) {
        $this->Logger->err("Could not save medium");
        return -1;
      } else {
        $this->Logger->verbose("Updated medium from thumb file");
        return 1;
      }
    } elseif (!$this->MyFile->isType($file, FILE_TYPE_VIDEO)) {
      $this->Logger->err("File type is not supported: ".$this->MyFile->getFilename($file));
      return -1;
    }

    $data =& $medium['Medium'];

    $bin = $this->controller->getOption('bin.ffmpeg', 'ffmpeg');
    $command = "$bin -i ".escapeshellarg($filename)." -t 0.0 2>&1";
    $output=array();
    $result=-1;
    $t1 = getMicrotime();
    exec($command, &$output, &$result);
    $t2 = getMicrotime();
    $this->Logger->debug("Command '$command' returnd $result and required ".round($t2-$t1, 4)."ms");
    
    if ($result != 1) {
      $this->Logger->err("Command '$command' returned unexcpected $result");
      return -1;
    } elseif (!count($output)) {
      $this->Logger->err("Command returned no output!");
      return -1;
    } else {
      $this->Logger->debug("Command '$command' returned $result");
      $this->Logger->trace($output);

      foreach ($output as $line) {
        $words=preg_split("/[\s,]+/", trim($line));
        if ($words[0]=="Duration:") {
          $times=preg_split("/:/", $words[1]);
          $time=$times[0]*3600+$times[1]*60+intval($times[2]);
          $data['duration'] = $time;
          $this->Logger->trace("Extract duration of '$filename': $time");
        } elseif ($words[2]=="Video:") {
          list($width, $height)=split("x", $words[5]);
          $data['width'] = $width;
          $data['height'] = $height;
          $this->Logger->trace("Extract video size of '$filename': $width x $height");
        }
      }
    }
    if (!$this->Medium->save($medium)) {
      $this->Logger->err("Could not save medium");
      return -1;
    } elseif ($isNew || !$this->MyFile->hasMedium($file)) {
      $mediumId = $isNew ? $this->Medium->getLastInsertID() : $data['id'];
      if (!$this->MyFile->setMedium($file, $mediumId)) {
        $this->Medium->delete($mediumId);
        return -1;
      }
    }

    $this->MyFile->updateReaded($file);
    $this->MyFile->setFlag($file, FILE_FLAG_DEPENDENT);

    return 1;
  }

  // Check for video thumb
  function _hasThumb($medium) {
    $thumb = $this->Medium->getFile($medium, FILE_TYPE_VIDEOTHUMB);
    if ($thumb) {
      return true;
    } else {
      return false;
    }
  }

  function _createThumb($medium) {
    $video = $this->Medium->getFile($medium, FILE_TYPE_VIDEO);
    if (!$video) {
      $this->Logger->err("Medium {$medium['Medium']['id']} has no video");
      return false;
    }
    if (!is_writable(dirname($this->MyFile->getFilename($video)))) {
      $this->Logger->warn("Cannot create video thumb. Directory of video is not writeable");
    }
    //$this->VideoPreview->controller =& $this->controller;
    $this->Logger->debug($this->VideoPreview->controller->MyFile->alias);
    $thumb = $this->VideoPreview->create($video);
    if (!$thumb) {
      return false;
    }
    return $this->FileManager->add($thumb);
  }

  function write($file, $medium, $options = array()) {
    if (!$this->_hasThumb($medium)) {
      $id = $this->_createThumb($medium);
      if ($id) {
        $file = $this->MyFile->findById($id);
        $this->MyFile->setMedium($file, $medium['Medium']['id']);
        $medium = $this->Medium->findById($medium['Medium']['id']);
        $this->write($file, $medium);
      }
    }
    if ($this->MyFile->isType($file, FILE_TYPE_VIDEOTHUMB)) {
      $imageFilter = $this->Manager->getFilter('Image');
      if (!$imageFilter) {
        $this->Logger->err("Could not get filter Image");
        return false;
      }
      $filename = $this->MyFile->getFilename($file);
      $this->Logger->debug("Write video thumbnail by ImageFilter: $filename");
      return $imageFilter->write(&$file, &$medium);
    }
    return true;
  }

}

?>
