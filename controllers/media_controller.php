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
if (!App::import('Vendor', "phpthumb", true, array(), "phpthumb.class.php")) {
  debug("Please install phpthumb properly");
}

class MediaController extends AppController
{
  var $name = 'Media';
  var $uses = array('Media', 'MyFile');
  var $layout = null;
  var $_outputMap = array(
                      OUTPUT_TYPE_MINI => array('size' => OUTPUT_SIZE_MINI, 'square' => true),
                      OUTPUT_TYPE_THUMB => array('size' => OUTPUT_SIZE_THUMB),
                      OUTPUT_TYPE_PREVIEW => array('size' => OUTPUT_SIZE_PREVIEW),
                      OUTPUT_TYPE_HIGH => array('size' => OUTPUT_SIZE_HIGH, 'quality' => 90),
                      OUTPUT_TYPE_VIDEO => array('size' => OUTPUT_SIZE_VIDEO, 'bitrate' => OUTPUT_BITRATE_VIDEO)
                    );
  var $components = array('ImageResizer', 'VideoPreview', 'FlashVideo', 'FileCache', 'FilterManager');

  function beforeFilter() {
    // Reduce security level for this controller if required. Security level
    // 'high' allows only 10 concurrent requests
    if (Configure::read('Security.level') === 'high') {
      Configure::write('Security.level', 'media');
    }
    // Disable sql output
    if (Configure::read('debug') === 2) {
      Configure::write('debug', 1);
    }
    parent::beforeFilter();
    $this->Media->setUser($this->getUser());
  }
  
  function _getCacheDir($data) {
    if (!isset($data['Media']['id'])) {
      Logger::debug("Data does not contain id of the image");
      return false;
    }

    $cacheDir = $this->FileCache->getPath($data['Media']['user_id'], $data['Media']['id']);
    return $cacheDir;
  }

  function _getCacheFilename($id, $size, $ext='jpg') {
    $prefix = $this->FileCache->getFilenamePrefix($id);
    return $prefix.sprintf("%d.%s", $size, $ext);
  }

  /** Fetch the request headers. Getting headers sent by the client. Convert
   * header to lower case since it is case insensitive.
    @return Array of request header */
  function _getRequestHeaders() {
    $headers = array();
    if (function_exists('apache_request_headers')) {
      $headers = apache_request_headers();
      foreach($headers as $h => $v) {
        $headers[strtolower($h)] = $v;
      }
    } else {
      $headers = array();
      foreach($_SERVER as $h => $v) {
        if(ereg('HTTP_(.+)', $h, $hp)) {
          $headers[strtolower($hp[1])] = $v;
        }
      }
    }
    return $headers;
  }

  /** Checking if the client is validating his cache and the cache file is the
    * concurrent one. If clients file is OK, it will respond '304 Not Modified'
    * @param filename filename of cache file
    */
  function _handleClientCache($filename) {
    $cacheTime = filemtime($filename);
    $headers = $this->_getRequestHeaders();
    if (isset($headers['if-modified-since']) &&
        (strtotime($headers['if-modified-since']) == $cacheTime)) {
      header('Last-Modified: '.
        gmdate('D, d M Y H:i:s', $cacheTime).' GMT', true, 304);
      // Allow further caching for 30 days
      header('Cache-Control: max-age=2592000, must-revalidate');
      exit;
    }

    // following line will disallow caching
    //header('Cache-Control: max-age=0');
  }

  /** Fetch image from database and checks access 
    @param id Media id
    @param outputType 
    @return Media data array. If no image is found or access is denied it responses 404 */
  function _getMedia($id, $outputType) {
    if (!$this->Media->hasAny("Media.id = $id")) {
      Logger::debug("No Media with id $id exists");
      $this->redirect(null, 404);
    }

    $user = $this->getUser();
    $media = $this->Media->find("Media.id = $id");
    if (!$media) {
      Logger::debug("Deny access to image $id");
      $this->redirect(null, 403);
    }

    $media = $this->Media->setMediaAccess($media);
    Logger::debug($media);
    if ($outputType == OUTPUT_TYPE_HIGH && $media['Media']['media_view'] < GROUP_MEDIAVIEW_FULL) {
      Logger::debug("Deny access to high output for media $id");
      $this->redirect(null, 403);
    }        
    
    return $media;
  }

  /** Fetches the source filename and checks it for read permission. If
   * filename is not readable it responses with 404
    @param image Media data
    @return filename of image */
  function _getSourceFile($media) {
    if ($this->Media->isType($media, MEDIA_TYPE_VIDEO)) {
      $sourceFilename = $this->VideoPreview->getPreviewFilename($media);
    } elseif (count($media['File']) > 0) {
      $sourceFilename = $this->Media->File->getFilename($media['File'][0]);
    } else {
      Logger::err("No file attached to media {$media['Media']['id']}");
      Logger::debug($media);
      $this->redirect(null, 500);
    }
    if(!is_readable($sourceFilename)) {
      Logger::debug("Media file (id {$media['Media']['id']}) is not readable: $sourceFilename");
      $this->redirect(null, 500); 
    }
    return $sourceFilename;
  }

  /**  */
  function _createPreview($id, $outputType) {
    $id = intval($id);
    if (!isset($this->_outputMap[$outputType])) {
      Logger::err("Unknown ouput type $outputType");
      die("Internal error");
    }
    
    $media = $this->_getMedia($id, $outputType);
    $options = am(array('size' => 220, 'square' => false, 'quality' => OUTPUT_QUALITY), $this->_outputMap[$outputType]);
    $dst = $this->_getCacheDir($media).$this->_getCacheFilename($id, $options['size']);

    if (!file_exists($dst)) {
      $src = $this->_getSourceFile($media);
      $options['width'] = $media['Media']['width'];
      $options['height'] = $media['Media']['height'];

      switch ($media['Media']['orientation']) {
        case 1: break;
        case 3: $options['rotation'] = 180; break;
        case 6: $options['rotation'] = 90; break;
        case 8: $options['rotation'] = 270; break;
        default: 
          Logger::warn("Unsupported rotation flag: ".$media['Media']['orientation']);
          break;
      }

      if (!$this->ImageResizer->resize($src, $dst, $options)) {
        Logger::err("Could not create image preview");
        $this->redirect(null, 500);
      }
    }

    $this->_handleClientCache($dst);

    $mediaOptions = $this->MyFile->getMediaViewOptions($dst);
    $this->view = 'Media';
    $this->set($mediaOptions);
  }

  function _createFlashVideo($id, $outputType) {
    $id = intval($id);
    if (!isset($this->_outputMap[$outputType])) {
      Logger::err("Unknown ouput type $outputType");
      die("Internal error");
    }

    $media = $this->_getMedia($id, $outputType);
    $flashFilename = $this->FlashVideo->create($media, $this->_outputMap[$outputType]);

    if (!is_file($flashFilename)) { 
      Logger::err("Could not create preview file {$flashFilename}");
      $this->redirect(null, 500);
    }
 
    return $flashFilename;
  }

  function mini($id) {
    $this->_createPreview($id, OUTPUT_TYPE_MINI);
  }

  function thumb($id)	{
    $this->_createPreview($id, OUTPUT_TYPE_THUMB);
  }

  function preview($id) {
    Logger::info("Request of image $id: preview");
    $this->_createPreview($id, OUTPUT_TYPE_PREVIEW);
  }

  function high($id) {
    Logger::info("Request of image $id: high");
    $this->_createPreview($id, OUTPUT_TYPE_HIGH);
  }

  function video($id) {
    Logger::info("Request of image $id: video");
    $filename = $this->_createFlashVideo($id, OUTPUT_TYPE_VIDEO);

    $mediaOptions = $this->MyFile->getMediaViewOptions($filename);
    $mediaOptions['download'] = true;
    $this->view = 'Media';
    $this->set($mediaOptions);
  }

  function file($id) {
    $id = intval($id);
    $file = $this->MyFile->findById($id);
    $user = $this->getUser();
    if (!$this->MyFile->hasMedia($file)) {
      Logger::warn("User {$user['User']['id']} requested file {$file['File']['id']} without media");
      $this->redirect(null, 404);
    }
    $media = $this->Media->find("Media.id = " . $file['Media']['id']);
    $media = $this->Media->setMediaAccess($media);
    if ($media['Media']['media_view'] < GROUP_MEDIAVIEW_FULL) {
      Logger::warn("User {$user['User']['id']} has no previleges to access image ".$file['Media']['id']);
      $this->redirect(null, 404);
    }
    if ($this->Media->hasFlag($media, MEDIA_FLAG_DIRTY)) {
      $this->FilterManager->write($media);
    }
    Logger::info("Request of media {$file['Media']['id']}: file $id '{$file['File']['file']}'");
    $filename = $this->MyFile->getFilename($file);  

    $mediaOptions = $this->MyFile->getMediaViewOptions($filename);
    $mediaOptions['download'] = true;
    $this->view = 'Media';
    $this->set($mediaOptions);
  }
}
?>