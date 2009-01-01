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

class VideoFilterComponent extends Object {

  var $controller = null;
  var $components = array('Logger', 'FileCache');

  function startup(&$controller) {
    $this->controller =& $controller;
  }

  /** Insert file to the database if it is a internal file
    @param filename Filename */
  function _insertFile($filename) {
    $path = Folder::slashTerm(dirname($filename));
    $file = basename($filename);
    $user = $this->controller->getUser();
    $image = array('Image' => array('path' => $path, 'file' => $file));
    if (!$this->controller->Image->isExternal(&$image, &$user)) {
      if (!$this->controller->Image->insertFile($filename, &$user)) {
        $this->Logger->err("Could not insert file '$filename' to the database");
      } else {
        $this->Logger->info("Insert file '$filename' to the database");
      }
    }
  }

  /** Creates a video preview image using ffmpeg 
    @param image Image model data
    @param thumbFilename Optional filename of the thumbnail image file
    @return Filename of the video thumbnail. False on failure */
  function createVideoPreview($image, $thumbFilename = '', $overwrite = false) {
    $videoFilename = $this->controller->Image->getFilename(&$image);
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
        $this->_insertFile($thumbFilename);
      }
    }
    return $thumbFilename;
  }

  /** Returns the preview filename of the internal cache
    @param image Image model data
    @return Cached preview filename */
  function getVideoPreviewFilenameCache($image) {
    $path = $this->FileCache->getPath($image['Image']['user_id'], $image['Image']['id']);
    $file = $this->FileCache->getFilenamePrefix($image['Image']['id']);
    $thumbFilename = $path.$file.'preview.thm';
    return $thumbFilename;
  }

  /** Gets the thumbnail filename of the a video. If it not exists, build it 
    @param image Image model data
    @param options Array of options. Set 'create' to false to disable automaitc
    thumbnail creations. Default is true. Set 'noCache' to true to disable
    thumbnail creation in the cache directory. Default is false. */
  function getVideoPreviewFilename($image, $options = array()) {
    $options = am($options, array('create' => true, 'noCache' => false));
    $folder = new Folder($image['Image']['path']);
    $file = $image['Image']['file'];
    $base = substr($file, 0, strrpos($file, '.')+1);
    $found = $folder->find($base."[Tt][Hh][Mm]");
    if (count($found)) {
      return Folder::slashTerm($folder->path).$found[0];
    }
    if (!$options['noCache']) {
      $cache = $this->getVideoPreviewFilenameCache($image);
      if (file_exists($cache)) {
        return $cache;
      }
    }
    $thumbFilename = false;
    if ($options['create']) {
      if (is_writeable($folder->path)) {
        $thumbFilename = $this->createVideoPreview($image);
      } elseif (!$options['noCache']) {
        $this->Logger->info("Origination directory of video is not writable. Use cache directory ($cache)");
        $thumbFilename = $this->createVideoPreview($image, $cache);
      }
    }
    return $thumbFilename;
  }

  /** Read the video data from the file 
   * @param image Image model data
   * @return True, false on error */
  function readFile(&$image) {
    $filename = $this->controller->Image->getFilename(&$image);
    if (!file_exists($filename) || !is_readable($filename)) {
      $this->Logger->warn("File: $filename does not exists nor is readable");
      return false;
    }

    $image['Image']['duration'] = 0;
    $image['Image']['width'] = 0;
    $image['Image']['height'] = 0;

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
      return false;
    } elseif (!count($output)) {
      $this->Logger->err("Command returned no output!");
      return false;
    } else {
      $this->Logger->debug("Command '$command' returned $result");
      $this->Logger->trace($output);

      foreach ($output as $line) {
        $words=preg_split("/[\s,]+/", trim($line));
        if ($words[0]=="Duration:") {
          $times=preg_split("/:/", $words[1]);
          $time=$times[0]*3600+$times[1]*60+intval($times[2]);
          $image['Image']['duration'] = $time;
          $this->Logger->trace("Extract duration of '$filename': $time");
        } elseif ($words[2]=="Video:") {
          list($width, $height)=split("x", $words[5]);
          $image['Image']['width'] = $width;
          $image['Image']['height'] = $height;
          $this->Logger->trace("Extract video size of '$filename': $width x $height");
        }
      }
    }
    return true;
  }

}

?>
