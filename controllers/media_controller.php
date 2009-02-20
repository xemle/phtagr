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
  var $uses = array('Medium');
  var $layout = null;
  var $_outputMap = array(
                      OUTPUT_TYPE_MINI => array('size' => OUTPUT_SIZE_MINI, 'square' => true),
                      OUTPUT_TYPE_THUMB => array('size' => OUTPUT_SIZE_THUMB),
                      OUTPUT_TYPE_PREVIEW => array('size' => OUTPUT_SIZE_PREVIEW),
                      OUTPUT_TYPE_HIGH => array('size' => OUTPUT_SIZE_HIGH, 'quality' => 90),
                      OUTPUT_TYPE_VIDEO => array('size' => OUTPUT_SIZE_VIDEO, 'bitrate' => OUTPUT_BITRATE_VIDEO)
                    );
  var $components = array('ImageResizer', 'FileCache');

  function beforeFilter() {
    // Reduce security level for this controller if required. Security level
    // 'high' allows only 10 concurrent requests
    if (Configure::read('Security.level') === 'high') {
      Configure::write('Security.level', 'medium');
    }
    // Disable sql output
    if (Configure::read('debug') === 2) {
      Configure::write('debug', 1);
    }
    parent::beforeFilter();
  }
  
  function _getCacheDir($data) {
    if (!isset($data['Medium']['id'])) {
      $this->Logger->debug("Data does not contain id of the image");
      return false;
    }

    $cacheDir = $this->FileCache->getPath($data['Medium']['user_id'], $data['Medium']['id']);
    return $cacheDir;
  }

  function _getCacheFilename($id, $size, $ext='jpg') {
    $prefix = $this->FileCache->getFilenamePrefix($id);
    return $prefix.sprintf("%d.%s", $size, $ext);
  }

  /** Checking if the client is validating his cache and the cache file is the
    * concurrent one. If clients file is OK, it will respond '304 Not Modified'
    * @param filename filename of cache file
    */
  function _handleClientCache($filename) {
    $cacheTime = filectime($filename);
    if (isset($headers['if-modified-since']) &&
        (strtotime($headers['if-modified-since']) == $cacheTime))
    {
      header('Last-Modified: '.
        gmdate('D, d M Y H:i:s', $cacheTime).' GMT', true, 304);
      // Allow further caching for 30 days
      header('Cache-Control: max-age=2592000, must-revalidate');
      exit;
    }

    // Allow caching
    header('Last-Modified: '.gmdate('D, d M Y H:i:s',
      $cacheTime).' GMT', true, 200);
    header('Cache-Control: max-age=2592000');

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

    $options = array(
      'id' => $file,
      'name' => $name,
      'extension' => $ext,
      'path' => $path);

    return $options;
  }

  /** Fetch image from database and checks access 
    @param id Medium id
    @param outputType 
    @return Medium data array. If no image is found or access is denied it responses 404 */
  function _getMedium($id, $outputType) {
    if (!$this->Medium->hasAny("Medium.id = $id")) {
      $this->Logger->debug("No Medium with id $id exists");
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
    //$conditions = "Medium.id = $id AND Medium.flag & ".MEDIUM_FLAG_ACTIVE.$this->Medium->buildWhereAcl($user, 0, $flag);
    $conditions = "Medium.id = $id".$this->Medium->buildWhereAcl($user, 0, $flag);
    $medium = $this->Medium->find($conditions);
    if (!$medium) {
      $this->Logger->debug("Deny access to image $id");
      $this->redirect(null, 403);
    }
    
    return $medium;
  }

  /** Fetches the source filename and checks it for read permission. If
   * filename is not readable it responses with 404
    @param image Medium data
    @return filename of image */
  function _getSourceFile($medium) {
    if ($this->Medium->isType($medium, MEDIUM_TYPE_VIDEO)) {
      $sourceFilename = $this->VideoFilter->getVideoPreviewFilename($medium);
    } else {
      $sourceFilename = $this->Medium->File->getFilename($medium['File'][0]);
    }
    if(!is_readable($sourceFilename)) {
      $this->Logger->debug("Medium file (id {$medium['Medium']['id']}) is not readable: $sourceFilename");
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
    
    $medium = $this->_getMedium($id, $outputType);
    $options = am(array('size' => 220, 'square' => false, 'quality' => OUTPUT_QUALITY), $this->_outputMap[$outputType]);
    $dst = $this->_getCacheDir($medium).$this->_getCacheFilename($id, $options['size']);

    if (!file_exists($dst)) {
      $src = $this->_getSourceFile($medium);
      $options['width'] = $medium['Medium']['width'];
      $options['height'] = $medium['Medium']['height'];

      switch ($medium['Medium']['orientation']) {
        case 1: break;
        case 3: $options['rotation'] = 180; break;
        case 6: $options['rotation'] = 90; break;
        case 8: $options['rotation'] = 270; break;
        default: 
          $this->Logger->warn("Unsupported rotation flag: ".$medium['Medium']['orientation']);
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

  function _scaleSize($medium, $size) {
    $width = $medium['Medium']['width'];
    $height = $medium['Medium']['height'];
    if ($width > $size && $width > $height) {
      $height = intval($size * $height / $width);
      $width = $size;
    } elseif ($height > $size) {
      $width = intval($size * $width / $height);
      $height = $size;
    }
    return array($width, $height);
  }

  function _createFlashVideo($id, $outputType) {
    $id = intval($id);
    if (!isset($this->_outputMap[$outputType])) {
      $this->Logger->err("Unknown ouput type $outputType");
      die("Internal error");
    }

    $medium = $this->_getMedium($id, $outputType);
    if (!$this->Medium->isVideo($medium)) {
      $this->err("Requested resource is no video");
      $this->redirect(null, 404);
    }

    $src = $this->Medium->getFilename($medium);
    $cacheDir = $this->_getCacheDir($medium);
    if (!$cacheDir) {
      $this->fatal("Precondition of cache directory failed: $cacheDir");
      die("Precondition of cache directory failed");
    }

    $options = $this->_outputMap[$outputType];
    $flashFilename = $cacheDir.$this->_getCacheFilename($id, $options['size'], 'flv');

    if (!file_exists($flashFilename)) {
      $bin = $this->getOption('bin.ffmpeg', 'ffmpeg');
      list($width, $height) = $this->_scaleSize($medium, $options['size']);
      $command = "$bin -i ".escapeshellarg($src)." -s {$width}x{$height} -r 15 -b {$options['bitrate']} -ar 22050 -ab 48 -y ".escapeshellarg($flashFilename);
      $output = array();
      $result = -1;
      $t1 = getMicrotime();
      exec($command, &$output, &$result);
      $t2 = getMicrotime();
      $this->Logger->debug("Command '$command' returnd $result and required ".round($t2-$t1, 4)."ms");
      if ($result != 0) {
        $this->Logger->err("Command '$command' returned unexcpected $result");
        $this->redirect(null, 500);
      } else {
        $this->Logger->info("Created flash video '$flashFilename' of '$src'");
      }
      
      $bin = $this->getOption('bin.flvtool2', 'flvtool2');
      $command = "$bin -U ".escapeshellarg($flashFilename);
      $output = array();
      $result = -1;
      $t1 = getMicrotime();
      exec($command, &$output, &$result);
      $t2 = getMicrotime();
      $this->Logger->debug("Command '$command' returnd $result and required ".round($t2-$t1, 4)."ms");
      if ($result != 0) {
        $this->Logger->err("Command '$command' returned unexcpected $result");
        $this->redirect(null, 500);
      } else {
        $this->Logger->info("Updated flash video '$flashFilename' with meta tags");
      }
    }
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
    $medium = $this->Medium->findById($id);
    $user = $this->getUser();
    if (!$this->Medium->checkAccess(&$medium, $user, ACL_READ_ORIGINAL, ACL_READ_MASK)) {
      $this->Logger->warn("User {$user['User']['id']} has no previleges to access image ".$medium['Medium']['id']);
      $this->redirect(null, 404);
    }
    $this->Logger->info("Request of image $id: original");
    $filename = $this->Medium->getFilename($medium);  

    $mediaOptions = $this->_getMediaOptions($filename);
    $mediaOptions['download'] = true;
    $this->view = 'Media';
    $this->set($mediaOptions);
  }
}
?>
