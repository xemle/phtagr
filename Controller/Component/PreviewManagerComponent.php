<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
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

class PreviewManagerComponent extends Component {

  var $controller = null;
  var $components = array('FileCache');

  /**
   * Defauls for a preview configuration
   */
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

  /**
   * Different preview configurations
   */
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

  public function initialize(Controller $controller) {
    $this->controller = $controller;
    if (!isset($controller->Media)) {
      Logger::err("Model MyFile and Media is not found");
      return false;
    }
  }

  /**
   * Return image source file of the media
   */
  public function _getImageSoureFilename($media) {
    $type = $this->controller->Media->getType($media);
    if ($type != MEDIA_TYPE_IMAGE && $type != MEDIA_TYPE_VIDEO) {
      Logger::err("Media type not supported: {$this->controller->Media->getType($media)}");
      return false;
    }
    if ($type == MEDIA_TYPE_VIDEO) {
      $this->controller->loadComponent('VideoPreview', $this);
      return $this->VideoPreview->getPreviewFilename($media);
    }
    $file = $this->controller->Media->getFile($media, FILE_TYPE_IMAGE, false);
    if (!$file) {
      Logger::err("No files are attached to media {$media['Media']['id']}");
      return false;
    }
    return $this->controller->Media->File->getFilename($file);
  }

  /**
   * Fetches the preview of a given media.
   *
   * @param media Media model data
   * @param name Configuration name
   * @param config (Optional) configuration for the preview generation
   * @return Full path to the preview file
   */
  public function getPreview(&$media, $name, $config = array()) {
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
      $config['rotation'] = $this->controller->Media->getRotationInDegree($media);
    }
    $dst = $this->FileCache->getFilePath($media, $name);
    if (!$dst) {
      Logger::err("Could not get cache file path for media {$this->controller->Media->toString($media)}");
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
    $this->controller->loadComponent('ImageResizer', $this);
    if (!$this->ImageResizer->resize($src, $dst, $config)) {
      Logger::err("Resize of '$src' to '$dst' failed");
      //Logger::debug($config);
      return false;
    }
    return $dst;
  }
}