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
class MediaController extends AppController
{
  var $name = 'Media';
  var $uses = array('User', 'Media', 'MyFile');
  var $layout = null;
  var $config = array(
    'video' => array('size' => OUTPUT_SIZE_VIDEO, 'bitrate' => OUTPUT_BITRATE_VIDEO)
                    );
  var $components = array('PreviewManager');

  public function beforeFilter() {
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

  public function beforeRender() {
    parent::beforeRender();
    $this->viewClass = 'Media';
  }

  /**
   * Fetch the request headers. Getting headers sent by the client. Convert
   * header to lower case since it is case insensitive.
   *
   * @return Array of request header
   */
  public function _getRequestHeaders() {
    $headers = array();
    if (function_exists('apache_request_headers')) {
      $headers = apache_request_headers();
      foreach($headers as $h => $v) {
        $headers[strtolower($h)] = $v;
      }
    } else {
      $headers = array();
      foreach($_SERVER as $h => $v) {
        if(preg_match('/HTTP_(.+)/', $h, $hp)) {
          $headers[strtolower($hp[1])] = $v;
        }
      }
    }
    return $headers;
  }

  /**
   * Checking if the client is validating his cache and the cache file is the
   * concurrent one. If clients file is OK, it will respond '304 Not Modified'
   *
   * @param filename filename of cache file
   */
  public function _handleClientCache($filename) {
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

  /**
   * Fetch media and checks access
   *
   * @param id Media id
   * @param type Preview type
   * @return Media model data. If no media is found or access is denied it
   * responses 404
   */
  public function _getMedia($id, $type = 'preview') {
    $user = $this->getUser();
    switch ($type) {
      case 'hd':
        $flag = ACL_READ_ORIGINAL; break;
      case 'high':
        $flag = ACL_READ_HIGH; break;
      default:
        $flag = ACL_READ_PREVIEW; break;
    }
    $query = $this->Media->buildAclQuery($user, 0, $flag);
    $query['conditions']['Media.id'] = $id;
    $this->Media->bindModel(array('hasMany' => array('GroupsMedia' => array())));
    $media = $this->Media->find('first', $query);
    if (!$media) {
      Logger::verbose("Media not found or access denied for media $id");
      $this->redirect(null, 403);
    }

    return $media;
  }

  public function _sendPreview($id, $type) {
    $media = $this->_getMedia($id, $type);
    $preview = $this->PreviewManager->getPreview($media, $type);
    if (!$preview) {
      Logger::err("Fetching preview type '{$type}' for {$this->Media->toString($media)} failed");
      $this->redirect(null, 403);
    }
    $this->_handleClientCache($preview);

    $this->set($this->MyFile->getMediaViewOptions($preview));
    $this->viewClass = 'Media';
  }

  public function _createFlashVideo($id) {
    $id = intval($id);
    $media = $this->_getMedia($id, 'preview');
    $this->loadComponent('FlashVideo');
    $config = array(
      'size' => OUTPUT_SIZE_VIDEO,
      'bitrate' => OUTPUT_BITRATE_VIDEO
      );
    $flashFilename = $this->FlashVideo->create($media, $config);

    if (!is_file($flashFilename)) {
      Logger::err("Could not create preview file {$flashFilename}");
      $this->redirect(null, 500);
    }

    return $flashFilename;
  }

  public function mini($id) {
    $this->_sendPreview($id, 'mini');
  }

  public function thumb($id)	{
    $this->_sendPreview($id, 'thumb');
  }

  public function preview($id) {
    $this->_sendPreview($id, 'preview');
  }

  public function high($id) {
    $this->_sendPreview($id, 'high');
  }

  public function hd($id) {
    $this->_sendPreview($id, 'hd');
  }

  public function video($id) {
    $filename = $this->_createFlashVideo($id);
    $mediaOptions = $this->MyFile->getMediaViewOptions($filename);
    $mediaOptions['download'] = true;
    $this->viewClass = 'Media';
    $this->set($mediaOptions);
  }

  public function file($id) {
    $id = intval($id);
    $file = $this->MyFile->findById($id);
    $user = $this->getUser();
    if (!$this->MyFile->hasMedia($file)) {
      Logger::warn("User {$user['User']['id']} requested file {$file['File']['id']} without media");
      $this->redirect(null, 404);
    }
    if (!$this->Media->canReadOriginal($file, $user)) {
      Logger::warn("User {$user['User']['id']} has no previleges to access image ".$file['Media']['id']);
      $this->redirect(null, 404);
    }
    if ($this->Media->hasFlag($file, MEDIA_FLAG_DIRTY) && $this->getOption('filter.write.onDemand')) {
      $media = $this->Media->findById($file['Media']['id']);
      $this->loadComponent('FilterManager');
      $this->FilterManager->write($media);
    }
    Logger::info("Request of media {$file['Media']['id']}: file $id '{$file['File']['file']}'");
    $filename = $this->MyFile->getFilename($file);

    $mediaOptions = $this->MyFile->getMediaViewOptions($filename);
    $mediaOptions['download'] = true;
    $this->viewClass = 'Media';
    $this->set($mediaOptions);
  }

  /**
   * Extract media files by requested format
   *
   * @param media Media model data
   * @param format File format
   * @return array of file information (name, filename, size)
   */
  public function _getMediaFiles($media, $format) {
    $files = array();
    if (!count($media['File'])) {
      return $files;
    }
    $writeMetaData = $this->getOption('filter.write.onDemand');
    if ($format == 'original') {
      // Write meta data if dirty
      if ($this->Media->hasFlag($media, MEDIA_FLAG_DIRTY) && $writeMetaData) {
        $this->loadComponent('FilterManager');
        if ($this->FilterManager->write($media)) {
          // reload written media
          $media = $this->Media->findById($media['Media']['id']);
        }
      }
      foreach ($media['File'] as $file) {
        $filename = $this->Media->File->getFilename($file);
        if (!is_readable($filename)) {
          continue;
        }
        $files[] = array('name' => $file['file'], 'filename' => $filename, 'size' => filesize($filename));
      }
    } else if (in_array($format, array('mini', 'thumb', 'preview', 'high', 'hd'))) {
      $filename = false;
      $mediaType = $this->Media->getType($media);
      if ($mediaType == MEDIA_TYPE_VIDEO) {
        $filename = $this->_createFlashVideo($media['Media']['id']);
        if (is_readable($filename)) {
          $video = $this->Media->getFile($media, FILE_TYPE_VIDEO);
          $name = substr($video['File']['file'], 0, strrpos($video['File']['file'], '.')) . '.flv';
          $files[] = array('name' => $format . '/' . $name, 'filename' => $filename, 'size' => filesize($filename));
        }
      } else if ($mediaType != MEDIA_TYPE_IMAGE) {
        Logger::warn("Media type $mediaType for {$media['Media']['id']} is not suppored yet");
      } else {
        $filename = $this->PreviewManager->getPreview($media, $format);
        if (is_readable($filename)) {
          $name = $media['File'][0]['file'];
          $files[] = array('name' => $format . '/' . $name, 'filename' => $filename, 'size' => filesize($filename));
        }
      }
    }
    return $files;
  }

  /**
   * Create a zip file and streams the zip file directly to the client
   *
   * @param name Name of the zip file
   * @param files Array of files
   */
  public function _createZipFile($name, $files) {
    App::import('Vendor', 'ZipStream/ZipStream');

    ini_set('memory_limit', '51002M');
    ini_set('max_execution_time', 120);

    $zip = new ZipStream($name);
    $zip->setComment("phTagr Download Zip File\nSee http://www.phtagr.org\nCreated on " . date('Y-m-d H:i:s'));

    foreach ($files as $file) {
      $zip->addLargeFile($file['filename'], $file['name']);
    }

    $zip->finalize();
    $this->redirect(null, 200, true);
  }

  public function zip($format) {
    // get explorer crumbs
    $params = split('/', $this->request->url);
    $crumbs = join('/', array_splice($params, 3));
    $redirectUrl = "/explorer/view/" . $crumbs;
    if (empty($this->request->data)) {
      $this->redirect(null, 404, true);
    }
    if (!isset($this->request->data['Media']['ids'])) {
      $this->redirect(null, 404, true);
    }
    if (!preg_match('/^\d+(,\d+)*$/', $this->request->data['Media']['ids'])) {
      Logger::warn("Invalid id input: " . $this->request->data['Media']['ids']);
      $this->Session->setFlash("Invalid media ids");
      $this->redirect($redirectUrl);
    } else if (!in_array($format, array('mini', 'thumb', 'preview', 'high', 'hd', 'original'))) {
      Logger::warn("Invalid format: $format");
      $this->Session->setFlash("Unsupported download format: " + $format);
      $this->redirect($redirectUrl);
    }
    $ids = preg_split('/\s*,\s*/', trim($this->request->data['Media']['ids']));
    $ids = array_unique($ids);
    if ($this->hasRole(ROLE_GUEST) && count($ids) > BULK_DOWNLOAD_FILE_COUNT_USER) {
      Logger::warn("Download of more than 240 media is not allowed");
      $this->Session->setFlash(__("Download of more than %d media is not allowed", BULK_DOWNLOAD_FILE_COUNT_USER));
      $this->redirect($redirectUrl);
    } else if (!$this->hasRole(ROLE_GUEST) && count($ids) > BULK_DOWNLOAD_FILE_COUNT_ANONYMOUS) {
      Logger::warn("Download of more than 12 media is not allowed for anonymous visitors");
      $this->Session->setFlash(__("Download of more than %d media is not allowed for anonymous visitors", BULK_DOWNLOAD_FILE_COUNT_ANONYMOUS));
      $this->redirect($redirectUrl);
    }

    $user = $this->getUser();
    $allMedia = $this->Media->find('all', array('conditions' => array('Media.id' => $ids)));
    $files = array();
    foreach ($allMedia as $media) {
      $this->Media->setAccessFlags($media, $user);
      if (!$media['Media']['canReadPreview']) {
        continue;
      }
      $fileFormat = $format;
      // Downgrade size if original is not allowed
      if ($fileFormat == 'original' && !$media['Media']['canReadOriginal']) {
        $fileFormat = 'high';
      }
      $files = am($files, $this->_getMediaFiles($media, $fileFormat));
    }
    if (!count($files)) {
      Logger::warn("No files for download");
      $this->Session->setFlash(__("No files for download found for given media set"));
      $this->redirect($redirectUrl);
    }
    $sizes = Set::extract('/size', $files);
    if (array_sum($sizes) > BULK_DOWNLOAD_TOTAL_MB_LIMIT * 1024 * 1024) {
      Logger::warn("Download of not more than " . BULK_DOWNLOAD_TOTAL_MB_LIMIT . " MB is not allowed");
      $this->Session->setFlash(__("Download of not more than %d MB is not allowed", BULK_DOWNLOAD_TOTAL_MB_LIMIT));
      $this->redirect($redirectUrl);
    }

    $zipName = 'phtagr-' . date('Y-m-d_H-i-s') . '.zip';
    $this->_createZipFile($zipName, $files);
  }
}
?>
