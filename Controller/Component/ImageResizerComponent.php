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
if (!class_exists('phpThumb') && !App::import('Vendor', 'phpthumb', array('file' => 'phpthumb' .DS . 'phpthumb.class.php'))) {
  debug("Please install phpthumb properly");
}

class ImageResizerComponent extends Component {

  var $controller = null;
  var $components = array('Command', 'Exiftool');
  var $_semaphoreId = false;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
    // allow only to converts at the same time to reduce system load
    if (function_exists('sem_get')) {
      $this->_semaphoreId = sem_get(4711, 2);
    }
  }

  /**
   * Resize an image
   *
   * @param src Source image filename
   * @param dst Destination image filename
   * @param options Options
   *   - height Maximum height of the resized image. Default is 220.
   *   - quality Quality of the resized image. Default is 85
   *   - rotation Rotation in degree. Default i s0
   *   - square Square the image. Default is false. If set, only width is considered.
   *   - clearMetaData Clear all meta data. Default is true
   */
  public function resize($src, $dst, $options = array()) {
    $options = am(array(
      'size' => 220,
      'quality' => 85,
      'rotation' => 0,
      'square' => false,
      'clearMetaData' => true
      ), $options);

    if (!is_readable($src)) {
      Logger::err("Could not read source $src");
      return false;
    }
    if (!is_writeable(dirname($dst))) {
      Logger::err("Could not write to path ".dirname($dst));
      return false;
    }
    if (!isset($options['width']) || !isset($options['height'])) {
      $size = getimagesize($src);
      $options['width'] = $size[0];
      $options['height'] = $size[1];
    }

    $phpThumb = new phpThumb();
    $this->_configurePhpThump($phpThumb, $src, $dst, $options);

    $t0 = microtime(true);
    if ($this->_semaphoreId) {
      sem_acquire($this->_semaphoreId);
    }
    $t1 = microtime(true);
    $result = $phpThumb->GenerateThumbnail();
    $t2 = microtime(true);
    if ($this->_semaphoreId) {
      sem_release($this->_semaphoreId);
    }
    if ($result) {
      Logger::debug("Render {$options['size']}x{$options['size']} image in ".round($t2-$t1, 4)."ms to '{$phpThumb->cache_filename}'");
      $phpThumb->RenderToFile($phpThumb->cache_filename);
    } else {
      Logger::err("Could not generate thumbnail: ".$phpThumb->error);
      Logger::err($phpThumb->debugmessages);
      die('Failed: '.$phpThumb->error);
    }

    if ($options['clearMetaData']) {
      $this->Exiftool->clearMetaData($dst);
    }
    return true;
  }

  /**
   * Configure phpThumb with source, destination and all options
   *
   * @param object $phpThumb
   * @param string $src
   * @param string $dst
   * @param array $options
   */
  private function _configurePhpThump(&$phpThumb, &$src, &$dst, &$options) {
    $phpThumb->src = $src;
    $this->_configureOptions($phpThumb, $options);
    $this->_configureConverter($phpThumb, $src);
    $this->_configureOutput($phpThumb, $dst);
  }

  /**
   * Set phtagrs options to phpThumb
   *
   * @param object $phpThumb
   * @param array $options
   */
  private function _configureOptions(&$phpThumb, &$options) {
    $phpThumb->w = $options['size'];
    $phpThumb->h = $options['size'];
    $phpThumb->q = $options['quality'];
    $phpThumb->ra = $options['rotation'];

    if ($options['square']) {
      $phpThumb->zc = 1;
      $phpThumb->w = $options['size'];
      $phpThumb->h = $options['size'];
    }
  }

  /**
   * Evaluate converter type between ImageMagick's convert and GD.
   * Use ImageMagick to convert large files or uncommon file extions.
   * Convert is faster converting large files (> 5 MB) than GD. GD knows only
   * common file extensions like JPG or PNG. GD is faster for smaller
   * images since phpThumb has some configuration overhead
   *
   * @param object $phpThumb
   * @param string $src Filename of source
   */
  private function _configureConverter(&$phpThumb, &$src) {
    $binConvert = $this->controller->getOption('bin.convert', false);

    $ext = strtolower(substr($src, strrpos($src, '.') + 1));
    $gdExtensions = array('jpg', 'png');
    $preferConvert = @file_exists($binConvert) && (!in_array($ext, $gdExtensions) || @filesize($src) > 5 * 1024 * 1024 || !function_exists('gd_info'));

    if ($preferConvert) {
      $phpThumb->config_imagemagick_path = $binConvert;
      $phpThumb->config_prefer_imagemagick = true;
    } else {
      $phpThumb->config_imagemagick_path = '';
      $phpThumb->config_prefer_imagemagick = false;
      if ($phpThumb->ra) {
        // phpthumb bug: rotation via gd is different as via convert
        //
        // phpThumb option Rotate by Angle: angle of rotation in degrees
        // positive = counterclockwise, negative = clockwise
        //
        // for image magick it is default clockwise. So negate value
        $phpThumb->ra = -1 * $phpThumb->ra;
      }
    }

    $phpThumb->config_imagemagick_use_thumbnail = false;
  }

  /**
   * Configure output options for phpThumb like cache and tmp directories.
   *
   * @param object $phpThumb
   * @param string $dst Destination filename
   */
  public function _configureOutput(&$phpThumb, &$dst) {
    $phpThumb->config_output_format = 'jpg';
    $phpThumb->config_error_die_on_error = true;
    $phpThumb->config_document_root = '';
    $phpThumb->config_temp_directory = APP . 'tmp';
    $phpThumb->config_allow_src_above_docroot = true;

    $phpThumb->config_cache_directory = dirname($dst);
    $phpThumb->config_cache_disable_warning = false;
    $phpThumb->cache_filename = $dst;
  }

}
