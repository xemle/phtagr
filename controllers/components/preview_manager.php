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

class PreviewManagerComponent extends Object {

  var $controller = null;
  var $components = array('FileCache');

  /** Defauls for a preview configuration */
  var $defaults = array(
    'type' => 'image',
    'square' => false,
    'size' => 1280,
    'requires' => false,
    'extension' => 'jpg',
    'quality' => 85,
    'force' => false,
    'clearMetaData' => false,
    'rotation' => 0
    );

  /** Different preview configurations */
  var $config = array(
    'mini' => array(
      'size' => OUTPUT_SIZE_MINI,
      'square' => true,
      'requires' => 'thumb'),
    'thumb' => array(
      'size' => OUTPUT_SIZE_THUMB,
      'requires' => 'preview'),
    'preview' => array(
      'size' => OUTPUT_SIZE_PREVIEW,
      'requires' => 'high'),
    'high' => array(
      'quality' => 90,
      'size' => OUTPUT_SIZE_HIGH),
    'hd' => array(
      'size' => OUTPUT_SIZE_HD),
    );

  var $errors = 0;

  function initialize(&$controller) {
    $this->controller =& $controller;
    if (!isset($controller->MyFile) || !isset($controller->Media)) {
      Logger::err("Model MyFile and Media is not found");
      return false;
    }
    $this->MyFile =& $controller->MyFile;
    $this->Media =& $controller->Media;
  }

  /** Return image source file of the media */
  function _getImageSoureFilename($media) {
    $type = $this->Media->getType($media);
    if ($type != MEDIA_TYPE_IMAGE && $type != MEDIA_TYPE_VIDEO) {
      Logger::err("Media type not supported: {$this->Media->getType($media)}");
      return false;
    } 
    if ($type == MEDIA_TYPE_VIDEO) {
      $this->controller->loadComponent('VideoPreview', &$this);
      return $this->VideoPreview->getPreviewFilename($media);
    } 
    $file = $this->Media->getFile($media, FILE_TYPE_IMAGE, false);
    if (!$file) {
      Logger::err("No files are attached to media {$media['Media']['id']}");
      return false;
    }
    return $this->Media->File->getFilename($file);
  }

  /** Fetches the preview of a given media.
    @param media Media model data
    @param name Configuration name
    @param config (Optional) configuration for the preview generation
    @return Full path to the preview file */
  function getPreview(&$media, $name, $config = array()) {
    $config = am($this->defaults, $config);
    if (isset($this->config[$name])) {
      $config = am($config, $this->config[$name]);
    }
    if ($config['requires']) {
      $src = $this->getPreview($media, $config['requires']);
      if (!$src) {
        Logger::err("Could not get preview of {$config['requires']}");
        return false;
      }
    } else {
      $src = $this->_getImageSoureFilename($media);
      $config['clearMetaData'] = true;
      $config['rotation'] = $this->Media->getRotationInDegree($media);
    }
    $dst = $this->FileCache->getFilePath($media, $name);
    if (!$dst) {
      Logger::err("Could not get cache file path for media {$this->Media->toString($media)}");
      return false;
    }
    
    if (file_exists($dst) && !$config['force']) {
      if (is_readable($dst)) {
        return $dst;
      } else {
        Logger::err("Cachefile not readable: $dst");
        return false;
      }
    }
    $this->controller->loadComponent('ImageResizer', &$this);
    if (!$this->ImageResizer->resize($src, $dst, $config)) {
      Logger::err("Resize of '$src' to '$dst' failed");
      Logger::debug($config);
      return false;
    }
    return $dst;
  }
}
?>
