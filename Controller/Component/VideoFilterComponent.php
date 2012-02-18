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

App::uses('BaseFilter', 'Component');

class VideoFilterComponent extends BaseFilterComponent {

  var $controller = null;
  var $components = array('VideoPreview', 'FileManager', 'Command');

  function initialize(&$controller) {
    $this->controller =& $controller;
  }

  function getName() {
    return "Video";
  }

  function _getVideoExtensions() {
    if ($this->controller->getOption('bin.exiftool') || $this->controller->getOption('bin.ffmpeg')) {
      return array('avi', 'mov', 'mpeg', 'mpg', 'mts', 'mp4', 'flv');
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
    $filename = $this->controller->MyFile->getFilename($file);
    if (!$media) {
      $video = $this->_findVideo($file);
      if (!$video) {
        $this->FilterManager->addError($filename, "VideoNotFound");
        Logger::err("Could not find video for video thumb $filename");
        return false;
      }
      $media = $this->controller->Media->findById($video['File']['media_id']);
      if (!$media) {
        $this->FilterManager->addError($filename, "MediaNotFound");
        Logger::err("Could not find media for video file. Maybe import it first");
        return false;
      }
    }
    $ImageFilter = $this->FilterManager->getFilter('Image');
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
    if (!$this->controller->Media->save($media)) {
      $this->FilterManager->addError($filename, "MediaSaveError");
      Logger::err("Could not save media");
      return false;
    } 
    $this->controller->MyFile->setMedia($file, $media['Media']['id']);
    $this->controller->MyFile->updateReaded($file);
    Logger::verbose("Updated media from thumb file");
    return $this->controller->Media->findById($media['Media']['id']);
  }

  /** Read the video data from the file 
   * @param image Media model data
   * @return True, false on error */
  function read(&$file, &$media, $options = array()) {
    $filename = $this->controller->MyFile->getFilename($file);

    if ($this->controller->MyFile->isType($file, FILE_TYPE_VIDEOTHUMB)) {
      return $this->_readThumb($file, &$media);
    } elseif (!$this->controller->MyFile->isType($file, FILE_TYPE_VIDEO)) {
      $this->FilterManager->addError($filename, "FileNotSupported");
      Logger::err("File type is not supported: ".$this->controller->MyFile->getFilename($file));
      return false;
    }

    $isNew = false;
    if (!$media) {
      $media = $this->controller->Media->create(array(
            'type' => MEDIA_TYPE_VIDEO,
            'date' => date('Y-m-d H:i:s', time()),
            'name' => basename($filename),
            'orientation' => 1
            ), true);
      if ($this->controller->getUserId() != $file['File']['user_id']) {
        $user = $this->controller->Media->User->findById($file['File']['user_id']);
      } else {
        $user = $this->controller->getUser();
      }
      $media = $this->controller->Media->addDefaultAcl(&$media, &$user);

      $isNew = true;
    }

    if ($this->controller->getOption('bin.exiftool')) {
      $media = $this->_readExiftool(&$media, $filename);
    } elseif ($this->controller->getOption('bin.ffmpeg')) {
      $media = $this->_readFfmpeg(&$media, $filename);
    } else {
      $media = $this->_readGetId3(&$media, $filename);
    }
    if (!$media || !$this->controller->Media->save($media)) {
      $this->FilterManager->addError($filename, "MediaSaveError");
      Logger::err("Could not save media");
      return false;
    }
     
    $mediaId = $media['Media']['id'];
    if ($isNew || !$this->controller->MyFile->hasMedia($file)) {
      $mediaId = $isNew ? $this->controller->Media->getLastInsertID() : $data['id'];
      if (!$this->controller->MyFile->setMedia($file, $mediaId)) {
        Logger::err("File was not saved: " . $filename);
        $this->FilterManager->addError($filename, "FileSaveError");
        $this->controller->Media->delete($mediaId);
        return false;
      }
      $media = $this->controller->Media->findById($mediaId);
    }

    $this->controller->MyFile->updateReaded($file);
    $this->controller->MyFile->setFlag($file, FILE_FLAG_DEPENDENT);

    return $this->controller->Media->findById($mediaId);
  }

  function _readExiftool(&$media, $filename) {
    $data =& $media['Media'];

    $bin = $this->controller->getOption('bin.exiftool', 'exiftool');
    $this->Command->redirectError = true;
    $result = $this->Command->run($bin, array('-n', '-S', $filename));
    $output = $this->Command->output;
 
    if ($result != 0) {
      Logger::err("Command '$bin' returned unexcpected $result");
      return false;
    } elseif (!count($output)) {
      Logger::err("Command returned no output!");
      return false;
    } else {
      foreach ($output as $line) {
        if (!preg_match('/^(\w+): (.*)$/', $line, $m)) {
          Logger::warn('Could not parse line: '.$line);
          continue;
        }
        if ($m[1] == 'ImageWidth') {
          $data['width'] = intval($m[2]);
          Logger::trace("Extract video width of '$filename': ".$data['width']);
        } else if ($m[1] == 'ImageHeight') {
          $data['height'] = intval($m[2]);
          Logger::trace("Extract video height of '$filename': ".$data['height']);
        } else if ($m[1] == 'Duration') {
          $data['duration'] = ceil(intval($m[2]));
          Logger::trace("Extract duration of '$filename': ".$data['duration']."s");
        }
      } 
      if (!$data['width'] || !$data['height'] || !$data['duration']) {
        Logger::warn("Could not extract width, height, or durration from '$filename'. Width is {$data['width']}, height is {$data['height']}, duration is {$data['duration']}");
      }
    }
    return $media;
  }

  function _readFfmpeg(&$media, $filename) {
    $data =& $media['Media'];

    $bin = $this->controller->getOption('bin.ffmpeg', 'ffmpeg');
    $this->Command->redirectError = true;
    $result = $this->Command->run($bin, array('-i' => $filename, '-t', 0.0));
    $output = $this->Command->output;
 
    if ($result != 1) {
      Logger::err("Command '$bin' returned unexcpected $result");
      return false;
    } elseif (!count($output)) {
      Logger::err("Command returned no output!");
      return false;
    } else {
      Logger::trace($output);

      foreach ($output as $line) {
        $words = preg_split("/[\s,]+/", trim($line));
        if (count($words) >= 2 && $words[0] == "Duration:") {
          $times = preg_split("/:/", $words[1]);
          $time = $times[0] * 3600 + $times[1] * 60 + intval($times[2]);
          $data['duration'] = $time;
          Logger::trace("Extract duration of '$filename': $time");
        } elseif (count($words) >= 6 && $words[2] == "Video:") {
          $words = preg_split("/,+/", trim($line));
          $data = preg_split("/\s+/", trim($words[2]));
          list($width, $height) = split("x", trim($data[0]));
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
    $thumb = $this->controller->Media->getFile($media, FILE_TYPE_VIDEOTHUMB);
    if ($thumb) {
      return true;
    } else {
      return false;
    }
  }

  function _createThumb($media) {
    $video = $this->controller->Media->getFile($media, FILE_TYPE_VIDEO);
    if (!$video) {
      Logger::err("Media {$media['Media']['id']} has no video");
      return false;
    }
    if (!is_writable(dirname($this->controller->MyFile->getFilename($video)))) {
      Logger::warn("Cannot create video thumb. Directory of video is not writeable");
    }
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
        $file = $this->controller->MyFile->findById($id);
        $this->controller->MyFile->setMedia($file, $media['Media']['id']);
        $media = $this->controller->Media->findById($media['Media']['id']);
        $this->write($file, $media);
      }
    }
    if ($this->controller->MyFile->isType($file, FILE_TYPE_VIDEOTHUMB)) {
      $imageFilter = $this->FilterManager->getFilter('Image');
      if (!$imageFilter) {
        Logger::err("Could not get filter Image");
        return false;
      }
      $filename = $this->controller->MyFile->getFilename($file);
      Logger::debug("Write video thumbnail by ImageFilter: $filename");
      return $imageFilter->write(&$file, &$media);
    }
    return true;
  }

}

?>
