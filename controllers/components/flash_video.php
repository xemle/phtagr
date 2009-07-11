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

class FlashVideoComponent extends Object {

  var $controller = null;
  var $components = array('FileCache');

  function startup(&$controller) {
    $this->controller =& $controller;
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
    return array($width, $height);
  }


  function create($media, $options = array()) {
    $options = am(array('size' => OUTPUT_SIZE_VIDEO, 'bitrate' => OUTPUT_BITRATE_VIDEO), $options);
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
    Logger::debug($src);
    $cache = $this->FileCache->getFilename($media);

    if (!$cache) {
      Logger::fatal("Precondition of cache directory failed: $cacheDir");
      die("Precondition of cache directory failed");
    }

    $flashFilename = $cache.$options['size'].'.flv';

    if (!file_exists($flashFilename)) {
      $bin = $this->controller->getOption('bin.ffmpeg', 'ffmpeg');
      list($width, $height) = $this->_scaleSize($media, $options['size']);
      $command = "$bin -i ".escapeshellarg($src)." -s {$width}x{$height} -r 15 -b {$options['bitrate']} -ar 22050 -ab 48 -y ".escapeshellarg($flashFilename);
      $output = array();
      $result = -1;
      $t1 = getMicrotime();
      exec($command, &$output, &$result);
      $t2 = getMicrotime();
      Logger::debug("Command '$command' returnd $result and required ".round($t2-$t1, 4)."ms");
      if ($result != 0) {
        Logger::err("Command '$command' returned unexcpected $result");
        @unlink($flashFilename);
        $this->controller->redirect(null, 500);
      } else {
        Logger::info("Created flash video '$flashFilename' of '$src'");
      }
      
      $bin = $this->controller->getOption('bin.flvtool2', 'flvtool2');
      $command = "$bin -U ".escapeshellarg($flashFilename);
      $output = array();
      $result = -1;
      $t1 = getMicrotime();
      exec($command, &$output, &$result);
      $t2 = getMicrotime();
      Logger::debug("Command '$command' returnd $result and required ".round($t2-$t1, 4)."ms");
      if ($result != 0) {
        Logger::err("Command '$command' returned unexcpected $result");
        $this->redirect(null, 500);
      } else {
        Logger::info("Updated flash video '$flashFilename' with meta tags");
      }
    }
    if (!is_file($flashFilename)) { 
      Logger::err("Could not create preview file {$flashFilename}");
      $this->redirect(null, 500);
    }
 
    return $flashFilename;
  }
}

?>
