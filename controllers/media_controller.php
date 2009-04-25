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
  var $components = array('ImageResizer', 'VideoPreview', 'FlashVideo', 'FileCache');

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
  }
  
  function _getCacheDir($data) {
    if (!isset($data['Media']['id'])) {
      $this->Logger->debug("Data does not contain id of the image");
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

  /** Get options for Media View of a file
    @param filename Filename of the media
    @return Array of Media Option for the view. */
  function _getMediaOptions($filename) {
    $path = substr($filename, 0, strrpos($filename, DS) + 1);
    $file = substr($filename, strrpos($filename, DS) + 1);
    $ext = strtolower(substr($file, strrpos($file, '.') + 1));
    $name = substr($file, 0, strrpos($file, '.'));
    $modified = date("Y-m-d H:i:s", filemtime($filename));

    $options = array(
      'id' => $file,
      'name' => $name,
      'extension' => $ext,
      'path' => $path,
      'modified' => $modified);

    return $options;
  }

  /** Fetch image from database and checks access 
    @param id Media id
    @param outputType 
    @return Media data array. If no image is found or access is denied it responses 404 */
  function _getMedia($id, $outputType) {
    if (!$this->Media->hasAny("Media.id = $id")) {
      $this->Logger->debug("No Media with id $id exists");
      $this->redirect(null, 404);
    }

    $user = $this->getUser();
    switch ($outputType) {
      case OUTPUT_TYPE_VIDEO:
        $flag = ACL_READ_PREVIEW; break;
      case OUTPUT_TYPE_HIGH:
        $flag = ACL_READ_HIGH; break;
      default:
        $flag = ACL_READ_PREVIEW; break;
    }
    //$conditions = "Media.id = $id AND Media.flag & ".MEDIA_FLAG_ACTIVE.$this->Media->buildWhereAcl($user, 0, $flag);
    $conditions = "Media.id = $id".$this->Media->buildWhereAcl($user, 0, $flag);
    $media = $this->Media->find($conditions);
    if (!$media) {
      $this->Logger->debug("Deny access to image $id");
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
    } else {
      $sourceFilename = $this->Media->File->getFilename($media['File'][0]);
    }
    if(!is_readable($sourceFilename)) {
      $this->Logger->debug("Media file (id {$media['Media']['id']}) is not readable: $sourceFilename");
      $this->redirect(null, 500); 
    }
    return $sourceFilename;
  }

  /**  */
  function _createPreview($id, $outputType) {
    $id = intval($id);
    if (!isset($this->_outputMap[$outputType])) {
      $this->Logger->err("Unknown ouput type $outputType");
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
          $this->Logger->warn("Unsupported rotation flag: ".$media['Media']['orientation']);
          break;
      }

      if (!$this->ImageResizer->resize($src, $dst, $options)) {
        $this->Logger->err("Could not create image preview");
        $this->redirect(null, 500);
      }
    }

    $this->_handleClientCache($dst);

    $mediaOptions = $this->_getMediaOptions($dst);
    $this->view = 'Media';
    $this->set($mediaOptions);
  }

  function _createFlashVideo($id, $outputType) {
    $id = intval($id);
    if (!isset($this->_outputMap[$outputType])) {
      $this->Logger->err("Unknown ouput type $outputType");
      die("Internal error");
    }

    $media = $this->_getMedia($id, $outputType);
    $flashFilename = $this->FlashVideo->create($media, $this->_outputMap[$outputType]);

    if (!is_file($flashFilename)) { 
      $this->Logger->err("Could not create preview file {$flashFilename}");
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
    $this->Logger->info("Request of image $id: preview");
    $this->_createPreview($id, OUTPUT_TYPE_PREVIEW);
  }

  function high($id) {
    $this->Logger->info("Request of image $id: high");
    $this->_createPreview($id, OUTPUT_TYPE_HIGH);
  }

  function video($id) {
    $this->Logger->info("Request of image $id: video");
    $filename = $this->_createFlashVideo($id, OUTPUT_TYPE_VIDEO);

    $mediaOptions = $this->_getMediaOptions($filename);
    $mediaOptions['download'] = true;
    $this->view = 'Media';
    $this->set($mediaOptions);
  }

  function original($id) {
    $id = intval($id);
    $media = $this->Media->findById($id);
    $user = $this->getUser();
    if (!$this->Media->checkAccess(&$media, $user, ACL_READ_ORIGINAL, ACL_READ_MASK)) {
      $this->Logger->warn("User {$user['User']['id']} has no previleges to access image ".$media['Media']['id']);
      $this->redirect(null, 404);
    }
    $this->Logger->info("Request of image $id: original");
    $filename = $this->Media->getFilename($media);  

    $mediaOptions = $this->_getMediaOptions($filename);
    $mediaOptions['download'] = true;
    $this->view = 'Media';
    $this->set($mediaOptions);
  }
}
?>
