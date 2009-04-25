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

  function _getVideoExtensions() {
    return array('avi', 'mov', 'mpeg', 'mpg');
  }
  function getExtensions() {
    return am($this->_getVideoExtensions(), array('thm' => array('priority' => 5)));
  }

  /** Finds the video thumb of a video 
    @param video File model data of the video
    @param insertIfMissing If true, adds the thumb file to the database. Default is true
    @return Filename of the thumb file. False if no thumb file was found */
  function _findVideo($thumb) {
    $thumbFilename = $this->controller->MyFile->getFilename($thumb);
    $path = dirname($thumbFilename);
    $folder =& new Folder($path);
    $pattern = basename($thumbFilename);
    $pattern = substr($pattern, 0, strrpos($pattern, '.')+1).'('.implode($this->_getVideoExtensions(), '|').')';
    $found = $folder->find($pattern);
    if (count($found) && is_readable(Folder::addPathElement($path, $found[0]))) {
      $videoFilename = Folder::addPathElement($path, $found[0]);
      $video = $this->controller->MyFile->findByFilename($videoFilename);
      if ($video) {
        return $video;
      } 
    } 
    return false;
  }
  
  function _readThumb($file, &$media) {
    $filename = $this->MyFile->getFilename($file);
    if (!$media) {
      $video = $this->_findVideo($file);
      if (!$video) {
        $this->Logger->err("Could not find video for video thumb $filename");
        return -1;
      }
      $media = $this->Media->findById($video['File']['media_id']);
      if (!$media) {
        $this->Logger->err("Could not find media for video file. Maybe import it first");
        return -1;
      }
    }
    $ImageFilter = $this->Manager->getFilter('Image');
    $this->Logger->debug("Read video thumbnail by ImageFilter: $filename");
    foreach (array('name', 'width', 'height', 'flag', 'duration') as $column) {
      if (isset($media['Media'][$column])) {
        $tmp[$column] = $media['Media'][$column];
      }
    }
    $ImageFilter->read(&$file, &$media, array('noSave' => true));
    // accept different name except filename
    if ($media['Media']['name'] != basename($filename)) {
      unset($tmp['name']);
    }
    // restore overwritten values
    $media['Media'] = am($media['Media'], $tmp);
    if (!$this->Media->save($media)) {
      $this->Logger->err("Could not save media");
      return -1;
    } 
    $this->MyFile->setMedia($file, $media['Media']['id']);
    $this->MyFile->updateReaded($file);
    $this->Logger->verbose("Updated media from thumb file");
    return 1;
  }

  /** Read the video data from the file 
   * @param image Media model data
   * @return True, false on error */
  function read(&$file, &$media, $options = array()) {
    $filename = $this->MyFile->getFilename(&$file);

    if ($this->MyFile->isType($file, FILE_TYPE_VIDEOTHUMB)) {
      return $this->_readThumb($file, &$media);
    } elseif (!$this->MyFile->isType($file, FILE_TYPE_VIDEO)) {
      $this->Logger->err("File type is not supported: ".$this->MyFile->getFilename($file));
      return -1;
    }

    $isNew = false;
    if (!$media) {
      $media = $this->Media->create(array(
            'type' => MEDIUM_TYPE_VIDEO,
            'date' => date('Y-m-d H:i:s', time()),
            'name' => basename($filename),
            'orientation' => 1
            ), true);
      if ($this->controller->getUserId() != $file['File']['user_id']) {
        $user = $this->Media->User->findById($file['File']['user_id']);
      } else {
        $user = $this->controller->getUser();
      }
      $this->Media->addDefaultAcl(&$media, &$user);

      $isNew = true;
    }

    $data =& $media['Media'];

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
    if (!$this->Media->save($media)) {
      $this->Logger->err("Could not save media");
      return -1;
    } elseif ($isNew || !$this->MyFile->hasMedia($file)) {
      $mediaId = $isNew ? $this->Media->getLastInsertID() : $data['id'];
      if (!$this->MyFile->setMedia($file, $mediaId)) {
        $this->Media->delete($mediaId);
        return -1;
      }
    }

    $this->MyFile->updateReaded($file);
    $this->MyFile->setFlag($file, FILE_FLAG_DEPENDENT);

    return 1;
  }

  // Check for video thumb
  function _hasThumb($media) {
    $thumb = $this->Media->getFile($media, FILE_TYPE_VIDEOTHUMB);
    if ($thumb) {
      return true;
    } else {
      return false;
    }
  }

  function _createThumb($media) {
    $video = $this->Media->getFile($media, FILE_TYPE_VIDEO);
    if (!$video) {
      $this->Logger->err("Media {$media['Media']['id']} has no video");
      return false;
    }
    if (!is_writable(dirname($this->MyFile->getFilename($video)))) {
      $this->Logger->warn("Cannot create video thumb. Directory of video is not writeable");
    }
    //$this->VideoPreview->controller =& $this->controller;
    //$this->Logger->debug($this->VideoPreview->controller->MyFile->alias);
    $thumb = $this->VideoPreview->create($video);
    if (!$thumb) {
      return false;
    }
    return $this->FileManager->add($thumb);
  }

  function write($file, $media, $options = array()) {
    if (!$this->_hasThumb($media)) {
      $id = $this->_createThumb($media);
      if ($id) {
        $file = $this->MyFile->findById($id);
        $this->MyFile->setMedia($file, $media['Media']['id']);
        $media = $this->Media->findById($media['Media']['id']);
        $this->write($file, $media);
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
      return $imageFilter->write(&$file, &$media);
    }
    return true;
  }

}

?>
