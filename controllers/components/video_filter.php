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

class VideoFilterComponent extends BaseFilterComponent {

  var $controller = null;
  var $components = array('VideoPreview', 'FileManager');

  function startup(&$controller) {
    $this->controller =& $controller;
  }

  function getName() {
    return "Video";
  }

  function _getVideoExtensions() {
    if ($this->controller->getOption('bin.ffmpeg')) {
      return array('avi', 'mov', 'mpeg', 'mpg', 'flv');
    } else {
      return array('flv');
    }
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
    if (!count($found)) {
      return false;
    }
    foreach ($found as $file) {
      if (is_readable(Folder::addPathElement($path, $file))) {
        $videoFilename = Folder::addPathElement($path, $file);
        $video = $this->controller->MyFile->findByFilename($videoFilename);
        if ($video) {
          return $video;
        }
      }
    } 
    return false;
  }
  
  function _readThumb($file, &$media) {
    $filename = $this->MyFile->getFilename($file);
    if (!$media) {
      $video = $this->_findVideo($file);
      if (!$video) {
        Logger::err("Could not find video for video thumb $filename");
        return -1;
      }
      $media = $this->Media->findById($video['File']['media_id']);
      if (!$media) {
        Logger::err("Could not find media for video file. Maybe import it first");
        return -1;
      }
    }
    $ImageFilter = $this->Manager->getFilter('Image');
    Logger::debug("Read video thumbnail by ImageFilter: $filename");
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
      Logger::err("Could not save media");
      return -1;
    } 
    $this->MyFile->setMedia($file, $media['Media']['id']);
    $this->MyFile->updateReaded($file);
    Logger::verbose("Updated media from thumb file");
    return 1;
  }

  /** Read the video data from the file 
   * @param image Media model data
   * @return True, false on error */
  function read(&$file, &$media, $options = array()) {
    $filename = $this->MyFile->getFilename($file);

    if ($this->MyFile->isType($file, FILE_TYPE_VIDEOTHUMB)) {
      return $this->_readThumb($file, &$media);
    } elseif (!$this->MyFile->isType($file, FILE_TYPE_VIDEO)) {
      Logger::err("File type is not supported: ".$this->MyFile->getFilename($file));
      return -1;
    }

    $isNew = false;
    if (!$media) {
      $media = $this->Media->create(array(
            'type' => MEDIA_TYPE_VIDEO,
            'date' => date('Y-m-d H:i:s', time()),
            'name' => basename($filename),
            'orientation' => 1
            ), true);
      if ($this->controller->getUserId() != $file['File']['user_id']) {
        $user = $this->Media->User->findById($file['File']['user_id']);
      } else {
        $user = $this->controller->getUser();
      }
      $media = $this->Media->addDefaultAcl(&$media, &$user);

      $isNew = true;
    }

    $data =& $media['Media'];

    if ($this->controller->getOption('bin.ffmpeg')) {
      $media = $this->_readFfmpeg(&$media, $filename);
    } else {
      $media = $this->_readGetId3(&$media, $filename);
    }
    if (!$media || !$this->Media->save($media)) {
      Logger::err("Could not save media");
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

  function _readFfmpeg(&$media, $filename) {
    $data =& $media['Media'];

    $bin = $this->controller->getOption('bin.ffmpeg', 'ffmpeg');
    $command = "$bin -i ".escapeshellarg($filename)." -t 0.0 2>&1";
    $output = array();
    $result = -1;
    $t1 = getMicrotime();
    exec($command, &$output, &$result);
    $t2 = getMicrotime();
    Logger::debug("Command '$command' returnd $result and required ".round($t2-$t1, 4)."ms");
    
    if ($result != 1) {
      Logger::err("Command '$command' returned unexcpected $result");
      return false;
    } elseif (!count($output)) {
      Logger::err("Command returned no output!");
      return false;
    } else {
      Logger::debug("Command '$command' returned $result");
      Logger::trace($output);

      foreach ($output as $line) {
        $words = preg_split("/[\s,]+/", trim($line));
        if (count($words) >= 2 && $words[0] == "Duration:") {
          $times = preg_split("/:/", $words[1]);
          $time = $times[0] * 3600 + $times[1] * 60 + intval($times[2]);
          $data['duration'] = $time;
          Logger::trace("Extract duration of '$filename': $time");
        } elseif (count($words) >= 6 && $words[2] == "Video:") {
          list($width, $height) = split("x", $words[5]);
          $data['width'] = $width;
          $data['height'] = $height;
          Logger::trace("Extract video size of '$filename': $width x $height");
        }
      }
    }
    return $media;
  }

  function _readGetId3(&$media, $filename) {
    App::import('vendor', 'getid3/getid3');
    $getId3 = new getId3();
    // disable not required modules
    $getId3->option_tag_id3v1 = false;
    $getId3->option_tag_id3v2 = false;
    $getId3->option_tag_lyrics3 = false;
    $getId3->option_tag_apetag = false;

    $data = $getId3->analyze($filename);
    if (isset($data['error'])) {
      Logger::err("GetId3 analyzing error: {$data['error'][0]}");
      Logger::debug($data);
      return false;
    }

    $media['Media']['duration'] = $data['meta']['onMetaData']['duration'];
    $media['Media']['width'] = $data['meta']['onMetaData']['width'];
    $media['Media']['height'] = $data['meta']['onMetaData']['height'];
    
    return $media;    
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
      Logger::err("Media {$media['Media']['id']} has no video");
      return false;
    }
    if (!is_writable(dirname($this->MyFile->getFilename($video)))) {
      Logger::warn("Cannot create video thumb. Directory of video is not writeable");
    }
    //$this->VideoPreview->controller =& $this->controller;
    //Logger::debug($this->VideoPreview->controller->MyFile->alias);
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
        Logger::err("Could not get filter Image");
        return false;
      }
      $filename = $this->MyFile->getFilename($file);
      Logger::debug("Write video thumbnail by ImageFilter: $filename");
      return $imageFilter->write(&$file, &$media);
    }
    return true;
  }

}

?>
