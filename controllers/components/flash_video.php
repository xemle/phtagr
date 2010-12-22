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

class FlashVideoComponent extends Object {

  var $controller = null;
  var $components = array('FileCache', 'Command');
  var $config = array(
    'size' => OUTPUT_SIZE_VIDEO, 
    'bitrate' => OUTPUT_BITRATE_VIDEO,
    'framerate' => 30
    );
  var $_semaphoreId = false;  

  function initialize(&$controller) {
    $this->controller =& $controller;
    if (function_exists('sem_get')) {
      $this->_semaphoreId = sem_get(4712);
    }
  }

  function _scaleSize($media, $size) {
    $width = $media['Media']['width'];
    $height = $media['Media']['height'];
    if ($width > $size && $width > $height) {
      $height = intval($size * $height / $width);
      $width = $size;
    } elseif ($height > $size) {
      $width = intval($size * $width / $height);
      $height = $size;
    }
    // fix for ffmpeg: even frame size
    $width += ($width & 1);
    $height += ($height & 1);
    return $width . 'x' . $height;
  }

  /** Evaluates if the media is a flash movie 
    @param media Current media
    @param file Video file of the media
    @return True if current media is a flash movie */
  function isValidFlash($media, $file) {
    if ($this->controller->MyFile->getExtension($file) == 'flv' &&
      $media['Media']['width'] <= $this->config['size'] &&
      $media['Media']['height'] <= $this->config['size']) {
      return true;
    }
    return false;
  }

  function create($media, $config = array()) {
    $config = am($this->config, $config);
    if (!$this->controller->Media->isType($media, MEDIA_TYPE_VIDEO)) {
      Logger::err("Media {$media['Media']['id']} is not a video");
      return false;
    }
    $video = $this->controller->Media->getFile($media, FILE_TYPE_VIDEO);
    if (!$video) {
      Logger::err("Could not find video for media {$media['Media']['id']}");
      return false;
    }

    $src = $this->controller->MyFile->getFilename($video);
    if ($this->isValidFlash($media, $video)) {
      Logger::verbose("Use media's flash video as source: $src");
      return $src;
    }

    $flashFilename = $this->FileCache->getFilePath($media, 'flashmovie', 'flv');
    if (!$flashFilename) {
      Logger::fatal("Precondition of cache directory failed: $cacheDir");
      return false;
    }

    if (!file_exists($flashFilename) && !$this->convertVideo($media, $src, $flashFilename, $config)) {
      Logger::err("Could not create preview file {$flashFilename}");
      return false;
    }
 
    return $flashFilename;
  }

  function convertVideo($media, $src, $dst, $config = array()) {
    $config = am($this->config, $config);
    $bin = $this->controller->getOption('bin.ffmpeg');
    if (!$bin) {
      Logger::warn("Path to external program ffmpeg is missing");
      return false;
    }
    $args = array(
      '-i' => $src,
      '-s' => $this->_scaleSize($media, $config['size']),
      '-r' => $config['framerate'],
      '-b' => $config['bitrate'],
      '-ar' => 22050,
      '-ab' => 48,
      '-y', $dst);
    if ($this->_semaphoreId) {
      sem_acquire($this->_semaphoreId);
    }
    $result = $this->Command->run($bin, $args);
    if ($this->_semaphoreId) {
      sem_release($this->_semaphoreId);
    }
    if ($result != 0) {
      Logger::err("Command '$bin' returned unexcpected with $result");
      @unlink($dst);
      return false;
    }
    Logger::info("Created flash video '$dst' of '$src'");
    $this->_addCuePoints($dst);
    return true;
  }

  function _addCuePoints($filename) {
    $bin = $this->controller->getOption('bin.flvtool2');
    if (!$bin) {
      return;
    }
    if ($this->Command->run($bin, array('-U' => $filename))) {
      Logger::err("Command '$bin' returned unexcpected $result");
      return false;
    }
    Logger::info("Updated flash video '$filename' with meta tags");
    return true;
  }
}

?>
