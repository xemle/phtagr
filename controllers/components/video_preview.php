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

class VideoPreviewComponent extends Object {

  var $controller = null;
  var $components = array('Logger', 'FileCache', 'FileManager');

  function startup(&$controller) {
    $this->controller =& $controller;
  }

  /** Creates a video preview image using ffmpeg 
    @param video File model data of a video
    @param thumbFilename Optional filename of the thumbnail image file
    @return Filename of the video thumbnail. False on failure */
  function create($video, $thumbFilename = '', $overwrite = false) {
    $videoFilename = $this->controller->MyFile->getFilename(&$video);
    $isNew = false;
    if (!file_exists($videoFilename) || !is_readable($videoFilename)) {
      $this->Logger->err("Video file '$videoFilename' does not exists or is readable");
      return false;
    }
    if ($thumbFilename == '') {
      $thumbFilename = substr($videoFilename, 0, strrpos($videoFilename, '.')+1).'thm';
      $isNew = true;
    }
    if (!$overwrite && file_exists($thumbFilename)) {
      $this->Logger->warn("Video thumbnail file '$thumbFilename' already exists");
      return $thumbFilename;
    }
    if (!is_writeable(dirname($videoFilename))) {
      $this->Logger->err("Could not write video thumb. Dir is not writable");
      return false;
    }
    $bin = $this->controller->getOption('bin.ffmpeg', 'ffmpeg');
    $command = "$bin -i ".escapeshellarg($videoFilename)." -t 0.001 -f mjpeg -y ".escapeshellarg($thumbFilename);
    $output = array();
    $result = -1;
    $t1 = getMicrotime();
    exec($command, &$output, &$result);
    $t2 = getMicrotime();
    $this->Logger->debug("Command '$command' returnd $result and required ".round($t2-$t1, 4)."ms");
    if ($result != 0) {
      $this->Logger->err("Command '$command' returned unexcpected $result");
      return false;  
    } else {
      $this->Logger->info("Created video thumbnail of '$videoFilename'");
      if ($isNew) {
        $this->FileManager->add($thumbFilename);
      }
    }
    return $thumbFilename;
  }

  /** Returns the preview filename of the internal cache
    @param image Medium model data
    @return Cached preview filename */
  function getPreviewFilenameCache($medium) {
    $path = $this->FileCache->getPath($medium['Medium']['user_id'], $medium['Medium']['id']);
    $file = $this->FileCache->getFilenamePrefix($medium['Medium']['id']);
    $thumbFilename = $path.$file.'preview.thm';
    return $thumbFilename;
  }

  /** Gets the thumbnail filename of the a video. If it not exists, build it 
    @param image Medium model data
    @param options Array of options. Set 'create' to false to disable automaitc
    thumbnail creations. Default is true. Set 'noCache' to true to disable
    thumbnail creation in the cache directory. Default is false. */
  function getPreviewFilename($medium, $options = array()) {
    $options = am($options, array('create' => true, 'noCache' => false));

    $thumb = $this->controller->Medium->getFile($medium, FILE_TYPE_VIDEOTHUMB, false);
    if ($thumb) {
      return $this->controller->MyFile->getFilename($thumb);
    }
  
    if (!$options['noCache']) {
      $cache = $this->getPreviewFilenameCache($medium);
      if (file_exists($cache)) {
        return $cache;
      }
    }
    $thumbFilename = false;
    if ($options['create']) {
      $video = $this->controller->Medium->getFile($medium, FILE_TYPE_VIDEO, false);
      if (!$video) {
        $this->Logger->err("No video file found for medium {$medium['Medium']['id']}");
        return false;
      }
      $videoFile = $this->controller->MyFile->getFilename($video);
      if (is_writeable(dirname($videoFile))) {
        $thumbFilename = $this->create($video);
      } elseif (!$options['noCache']) {
        $this->Logger->info("Origination directory of video is not writable. Use cache directory ($cache)");
        $thumbFilename = $this->create($video, $cache);
      }
    }
    return $thumbFilename;
  }

}

?>
