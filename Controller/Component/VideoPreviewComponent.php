<?php
/**
 * PHP versions 5
 *
 * phTagr : Organize, Browse, and Share Your Photos.
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

class VideoPreviewComponent extends Component {

  var $controller = null;
  var $components = array('FileCache', 'FileManager', 'Command');
  var $_semaphoreId = false;
  var $bin = false;

  var $createVideoThumbOption = 'filter.video.createThumb';
  var $createVideoThumb = false;


  public function initialize(Controller $controller) {
    $this->controller = $controller;

    $this->bin = $this->controller->getOption('bin.ffmpeg', null);
    $this->createVideoThumb = $this->bin && $this->controller->getOption($this->createVideoThumbOption, false);

    if (function_exists('sem_get')) {
      $this->_semaphoreId = sem_get(4713);
    }
  }

  private function _getDummyPreview() {
    return APP . 'webroot' . DS . 'img' . DS . 'dummy_video_preview.jpg';
  }

  /**
   * Finds the video thumb of a video
   *
   * @param array $video File model data of the video
   * @param boolean $insertIfMissing If true, adds the thumb file to the database. Default is true
   * @return string Filename of the thumb file. False if no thumb file was found
   */
  public function findVideoThumb(&$media) {
    $thumb = $this->controller->Media->getFile($media, FILE_TYPE_VIDEOTHUMB, false);
    if ($thumb) {
      return $this->controller->MyFile->getFilename($thumb);
    }

    $video = $this->controller->Media->getFile($media, FILE_TYPE_VIDEO, false);
    $videoFilename = $this->controller->MyFile->getFilename($video);

    $path = dirname($videoFilename);
    $folder = new Folder($path);
    $pattern = basename($videoFilename);
    $pattern = substr($pattern, 0, strrpos($pattern, '.') + 1) . '[Tt][Hh][Mm]';
    $found = $folder->find($pattern);

    if (count($found) && is_readable(Folder::addPathElement($path, $found[0]))) {
      $thumbFilename = Folder::addPathElement($path, $found[0]);
      $thumb = $this->controller->MyFile->findByFilename($thumbFilename);
      if (!$thumb) {
        $thumbId = $this->FileManager->add($thumbFilename, $video['File']['user_id']);
        CakeLog::debug("Add missing video thumb $thumbFilename to database: $thumbId");
        $thumb = $this->controller->MyFile->findById($thumbId);
      }
      if (!$thumb) {
        CakeLog::error("Could not find thumbnail in database");
        return false;
      }
      if ($thumb['File']['media_id'] != $video['File']['media_id'] &&
        $this->controller->MyFile->setMedia($thumb, $video['File']['media_id'])) {
        CakeLog::debug("Link video thumb {$thumb['File']['id']} to media {$video['File']['media_id']}");
      }
      return $thumbFilename;
    }
    return false;
  }

  /**
   * @param string $videoFilename Video filename
   * @return string THM video thumbnail filename
   */
  private function _getVideoThumbFilename($videoFilename) {
    return substr($videoFilename, 0, strrpos($videoFilename, '.') + 1) . 'thm';
  }

  /**
   * Creates a video preview image using ffmpeg
   *
   * @param string $videoFilename Video file
   * @param string $thumbFilename Optional filename of the thumbnail image file
   * @return string Filename of the video thumbnail. False on failure
   */
  private function _create($videoFilename, $thumbFilename) {
    if (!file_exists($videoFilename) || !is_readable($videoFilename)) {
      CakeLog::error("Video file '$videoFilename' does not exists or is readable");
      return false;
    }
    if (!is_writeable(dirname($thumbFilename))) {
      CakeLog::error("Could not write video thumb. Path '".dirname($thumbFilename)."' is not writable");
      return false;
    }
    if ($this->_semaphoreId) {
      sem_acquire($this->_semaphoreId);
    }
    $result = $this->Command->run($this->bin, array(
      '-i' => $videoFilename,
      '-vframes' => 1,
      '-f' => 'mjpeg',
      '-y', $thumbFilename));
    if ($this->_semaphoreId) {
      sem_release($this->_semaphoreId);
    }
    if ($result != 0) {
      CakeLog::error("Command '{$this->bin}' returned unexcpected $result");
      return false;
    } else {
      CakeLog::info("Created video thumbnail of '$videoFilename'");
    }
    return $thumbFilename;
  }

  /**
   * @param array $media Media model data
   * @return string Filename of main video
   */
  private function _getVideoFile(&$media) {
    $video = $this->controller->Media->getFile($media, FILE_TYPE_VIDEO, false);
    return $this->controller->MyFile->getFilename($video);
  }

  /**
   * Create a THM video thumbnail for given media
   *
   * @param array $media Media model data
   * @return string Filename of video thumbnail. False on error
   */
  public function createVideoThumb(&$media) {
    $video = $this->controller->Media->getFile($media, FILE_TYPE_VIDEO, false);
    $videoFile = $this->controller->MyFile->getFilename($video);
    $filename = $this->_getVideoThumbFilename($videoFile);

    $thumbFilename = $this->_create($videoFile, $filename);
    if ($thumbFilename) {
      $this->FileManager->add($thumbFilename, $media['Media']['user_id']);
      $thumb = $this->controller->MyFile->findByFilename($thumbFilename);
      if ($this->controller->MyFile->setMedia($thumb, $video['File']['media_id'])) {
        CakeLog::debug("Link thumbnail {$thumb['File']['id']} to media {$video['File']['media_id']}");
      }
    }
    return $thumbFilename;
  }

  /**
   * Add play watermark to the video preview file
   *
   * @param type $filename
   */
  private function _addWatermark($filename) {
    $watermarkFile = APP . 'webroot' . DS . 'img' . DS . 'play.icon.png';

    App::uses('WatermarkCreator', 'Lib');
    $watermark = new WatermarkCreator();

    $scaleMode = 'inner';
    $position = ''; // empty for center

    if (!$watermark->create($filename, $watermarkFile, $scaleMode, $position)) {
      CakeLog::error(join(', ', $watermark->errors));
    }
  }

  /**
   * Returns the preview filename of the internal cache
   *
   * @param array $media Media model data
   * @return string Cached preview filename
   */
  private function _getPreviewFilenameCache(&$media) {
    $path = $this->FileCache->getPath($media);
    $file = $this->FileCache->getFilenamePrefix($media['Media']['id']);
    $thumbFilename = $path . $file . 'preview.thm';
    return $thumbFilename;
  }

  /**
   * Copy given video thumbnail to cached location
   *
   * @param string $thumbFilename Filename of video thumbnail
   * @param string $cacheFilename Filename of cached video thumbnail
   * @return string Filename of cache filename. On error returns a dummy preview file
   */
  private function _createCachedFile($thumbFilename, $cacheFilename) {
    if (!is_readable($thumbFilename)) {
      CakeLog::error("Thumbnail file is not readable: $thumbFilename");
      return $this->_getDummyPreview();
    } else if (!is_writable(dirname($cacheFilename))) {
      CakeLog::error("Target directory " . dirname($cacheFilename) . " is not writable for copy");
      return $this->_getDummyPreview();
    }
    if ($thumbFilename != $cacheFilename) {
      @copy($thumbFilename, $cacheFilename);
    }
    $this->_addWatermark($cacheFilename);

    return $cacheFilename;
  }

  /**
   * Validate if given media is a valid video media
   *
   * @param array $media Media model data
   * @return boolean True if media is valid
   */
  private function _validateVideoMedia(&$media) {
    $video = $this->controller->Media->getFile($media, FILE_TYPE_VIDEO, false);
    if (!$video) {
      CakeLog::error("Media {$media['Media']['id']} has no attached video file");
      return false;
    }
    $videoFilename = $this->controller->MyFile->getFilename($video);
    if (!is_readable($videoFilename)) {
      CakeLog::error("Video file of media {$media['Media']['id']} not readable: $videoFilename");
      return false;
    }
    return true;
  }

  /**
   * Gets the thumbnail filename of the a video.
   *
   * @param image Media model data
   */
  public function getPreviewFilename($media) {
    $cache = $this->_getPreviewFilenameCache($media);
    if (is_readable($cache)) {
      return $cache;
    }

    if (!$this->_validateVideoMedia($media)) {
      CakeLog::error("Invalid media {$media['Media']['id']}");
      return $this->_getDummyPreview();
    }

    $thumbFilename = $this->findVideoThumb($media);
    if ($thumbFilename) {
      return $this->_createCachedFile($thumbFilename, $cache);
    }

    if ($this->createVideoThumb) {
      $thumbFilename = $this->createVideoThumb($media);
      return $this->_createCachedFile($thumbFilename, $cache);
    }

    CakeLog::info("Create cached video preview $cache");
    $videoFile = $this->_getVideoFile($media);
    $this->_create($videoFile, $cache);
    return $this->_createCachedFile($cache, $cache);
  }

}
