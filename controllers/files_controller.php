<?php 
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2008 Sebastian Felis, sebastian@phtagr.org
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

class FilesController extends AppController
{
  var $name = 'Files';
  var $uses = array('Image');
  var $layout = null;
  var $_outputMap = array(
                      OUTPUT_TYPE_MINI => array('size' => OUTPUT_SIZE_MINI, 'square' => true),
                      OUTPUT_TYPE_THUMB => array('size' => OUTPUT_SIZE_THUMB),
                      OUTPUT_TYPE_PREVIEW => array('size' => OUTPUT_SIZE_PREVIEW),
                      OUTPUT_TYPE_HIGH => array('size' => OUTPUT_SIZE_HIGH, 'quality' => 90),
                      OUTPUT_TYPE_VIDEO => array('size' => OUTPUT_SIZE_VIDEO, 'bitrate' => OUTPUT_BITRATE_VIDEO)
                    );
  var $components = array('ImageFilter', 'VideoFilter', 'FileCache');

  function beforeFilter() {
    // Reduce security level for this controller if required. Security level
    // 'high' allows only 10 concurrent requests
    if (Configure::read('Security.level') === 'high') {
      Configure::write('Security.level', 'medium');
    }
    parent::beforeFilter();
  }
  
  function _getCacheDir($data) {
    if (!isset($data['Image']['id'])) {
      $this->Logger->debug("Data does not contain id of the image");
      return false;
    }

    $cacheDir = $this->FileCache->getPath($data['Image']['user_id'], $data['Image']['id']);
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

  /** Fetch image from database and checks access 
    @param id Image id
    @param outputType 
    @return Image data array. If no image is found or access is denied it responses 404 */
  function _getImage($id, $outputType) {
    if (!$this->Image->hasAny("Image.id = $id")) {
      $this->Logger->debug("No Image with id $id exists");
      $this->redirect(null, 404);
    }

    $user = $this->getUser();
    switch ($outputType) {
      case OUTPUT_TYPE_VIDEO:
      case OUTPUT_TYPE_HIGH:
        $flag = ACL_READ_HIGH; break;
      default:
        $flag = ACL_READ_PREVIEW; break;
    }
    $condition = "Image.id = $id AND Image.flag & ".IMAGE_FLAG_ACTIVE.$this->Image->buildWhereAcl($user, 0, $flag);
    $image = $this->Image->find($condition);
    if (!$image) {
      $this->Logger->debug("Deny access to image $id");
      $this->redirect(null, 403);
    }
    
    return $image;
  }

  /** Fetches the source filename and checks it for read permission. If
   * filename is not readable it responses with 404
    @param image Image data
    @return filename of image */
  function _getSourceFile($image) {
    if ($this->Image->isVideo($image)) {
      $sourceFilename = $this->VideoFilter->getVideoPreviewFilename($image);
    } else {
      $sourceFilename = $this->Image->getFilename($image);
    }
    if(!is_readable($sourceFilename)) {
      $this->Logger->debug("Image file (id {$image['Image']['id']}) is not readable: $sourceFilename");
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
    
    $image = $this->_getImage($id, $outputType);
    $sourceFilename = $this->_getSourceFile($image);

    $options = am(array('size' => 220, 'square' => false, 'quality' => OUTPUT_QUALITY), $this->_outputMap[$outputType]);

    $phpThumb = new phpThumb();

    $phpThumb->src = $sourceFilename;
    $phpThumb->w = $options['size'];
    $phpThumb->h = $options['size'];
    $phpThumb->q = $options['quality'];

    switch ($image['Image']['orientation']) {
      case 1:
        break;
      case 3:
        $phpThumb->ra=180; 
        break;
      case 6: 
        $phpThumb->ra=90; 
        break;
      case 8: 
        $phpThumb->ra=270; 
        break;
      default: 
        $this->Logger->warn("Unsupported ratation flag: ".$image['Image']['orientation']);
        break;
    }

    if ($options['square'] && $image['Image']['height']>0) {
      $width=$image['Image']['width'];
      $height=$image['Image']['height'];
      if ($width<$height) {
        $ratio = ($width/$height);
        $size = $options['size']/$ratio;
        $phpThumb->sx=0;
        $phpThumb->sy=intval(($size-$options['size'])/2);
      } else {
        $ratio = ($height/$width);
        $size = $options['size']/$ratio;
        $phpThumb->sx=intval(($size-$options['size'])/2);
        $phpThumb->sy=0;
      }

      if ($phpThumb->ra == 90 || $phpThumb->ra == 270) {
        $tmp = $phpThumb->sx;
        $phpThumb->sx = $phpThumb->sy;
        $phpThumb->sy = $tmp;
      }

      $phpThumb->sw=$options['size'];
      $phpThumb->sh=$options['size'];

      $phpThumb->w = $size;
      $phpThumb->h = $size;

      //$this->Logger->debug(sprintf("square: %dx%d %dx%d", 
      //  $phpThumb->sx, $phpThumb->sy, 
      //  $phpThumb->sw, $phpThumb->sh), LOG_DEBUG);
    }
    $phpThumb->config_imagemagick_path = $this->getOption('bin.convert', 'convert');
    $phpThumb->config_prefer_imagemagick = true;
    $phpThumb->config_imagemagick_use_thumbnail = false;
    $phpThumb->config_output_format = 'jpg';
    $phpThumb->config_error_die_on_error = true;
    $phpThumb->config_document_root = '';
    $phpThumb->config_temp_directory = APP . 'tmp';
    $phpThumb->config_allow_src_above_docroot = true;

    $cacheDir = $this->_getCacheDir($image);
    if (!$cacheDir) {
      $this->Logger->fatal("Cache directory for image failed");
      die("Precondition of cache directory failed");
    }
    $phpThumb->config_cache_directory = $cacheDir;
    $phpThumb->config_cache_disable_warning = false;

    $cacheFilename = $this->_getCacheFilename($id, $options['size']);
    $phpThumb->cache_filename = $phpThumb->config_cache_directory.$cacheFilename;
    
    //Thanks to Kim Biesbjerg for his fix about cached thumbnails being regenerated
    if(!is_file($phpThumb->cache_filename)) { 
      // Check if image is already cached.
      $t1 = getMicrotime();
      $result = $phpThumb->GenerateThumbnail();
      $t2 = getMicrotime();
      if ($result) {
        $this->Logger->debug("Render {$options['size']}x{$options['size']} image (id {$image['Image']['id']}) in ".round($t2-$t1, 4)."ms to '{$phpThumb->cache_filename}'");
        $phpThumb->RenderToFile($phpThumb->cache_filename);
        $this->ImageFilter->clearMetaData($phpThumb->cache_filename);
      } else {
        $this->Logger->err("Could not generate thumbnail: ".$phpThumb->error);
        $this->Logger->err($phpThumb->debugmessages);
        die('Failed: '.$phpThumb->error);
      }
    } 

    //$this->Logger->debug($phpThumb->debugmessages);
    
    if (is_file($phpThumb->cache_filename)) { 
      $this->_handleClientCache($phpThumb->cache_filename);

      // If thumb was already generated we want to use cached version
      $cachedImage = getimagesize($phpThumb->cache_filename);
      header('Content-Type: '.$cachedImage['mime']);
      readfile($phpThumb->cache_filename);
      exit;
    } 
    $this->Logger->err("Unexpected end of script! is_file($phpThumb->cache_filename)==false!");
    $this->Logger->err($phpThumb->debugmessages);
  }

  function _scaleSize($image, $size) {
    $width = $image['Image']['width'];
    $height = $image['Image']['height'];
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

    $image = $this->_getImage($id, $outputType);
    if (!$this->Image->isVideo($image)) {
      $this->err("Requested resource is no video");
      $this->redirect(null, 404);
    }

    $sourceFilename = $this->Image->getFilename($image);
    $cacheDir = $this->_getCacheDir($image);
    if (!$cacheDir) {
      $this->fatal("Precondition of cache directory failed: $cacheDir");
      die("Precondition of cache directory failed");
    }

    $options = $this->_outputMap[$outputType];
    $flashFilename = $cacheDir.$this->_getCacheFilename($id, $options['size'], 'flv');

    if (!file_exists($flashFilename)) {
      $bin = $this->getOption('bin.ffmpeg', 'ffmpeg');
      list($width, $height) = $this->_scaleSize($image, $options['size']);
      $command = "$bin -i ".escapeshellarg($sourceFilename)." -s {$width}x{$height} -r 15 -b {$options['bitrate']} -ar 22050 -ab 48 -y ".escapeshellarg($flashFilename);
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
        $this->Logger->info("Created flash video '$flashFilename' of '$sourceFilename'");
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
 
    $this->_handleClientCache($flashFilename);
    header('Content-Type: video/x-flv');
    readfile($flashFilename);
    exit; 
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
    $this->_createFlashVideo($id, OUTPUT_TYPE_VIDEO);
  }

  function original($id) {
    $id = intval($id);
    $image = $this->Image->findById($id);
    $user = $this->getUser();
    if (!$this->Image->checkAccess(&$image, $user, ACL_READ_ORIGINAL, ACL_READ_MASK)) {
      $this->Logger->warn("User {$user['User']['id']} has no previleges to access image ".$image['Image']['id']);
      $this->redirect(null, 404);
    }
    $this->Logger->info("Request of image $id: original");
    $filename = $this->Image->getFilename($image);  

    $size = getimagesize($filename);
    header('Content-Type: '.$size['mime']);

    readfile($filename);
    exit; 
  }
}
?>
