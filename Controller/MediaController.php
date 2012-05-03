<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
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

  function beforeRender() {
    parent::beforeRender();
    $this->viewClass = 'Media';
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

  /** Fetch media and checks access
    @param id Media id
    @param type Preview type
    @return Media model data. If no media is found or access is denied it
    responses 404 */
  function _getMedia($id, $type = 'preview') {
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

  function _sendPreview($id, $type) {
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

  function _createFlashVideo($id) {
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

  function mini($id) {
    $this->_sendPreview($id, 'mini');
  }

  function thumb($id)	{
    $this->_sendPreview($id, 'thumb');
  }

  function preview($id) {
    $this->_sendPreview($id, 'preview');
  }

  function high($id) {
    $this->_sendPreview($id, 'high');
  }

  function hd($id) {
    $this->_sendPreview($id, 'hd');
  }

  function video($id) {
    $filename = $this->_createFlashVideo($id);
    $mediaOptions = $this->MyFile->getMediaViewOptions($filename);
    $mediaOptions['download'] = true;
    $this->viewClass = 'Media';
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
    if (!$this->Media->canReadOriginal(&$file, &$user)) {
      Logger::warn("User {$user['User']['id']} has no previleges to access image ".$file['Media']['id']);
      $this->redirect(null, 404);
    }
    if ($this->Media->hasFlag($file, MEDIA_FLAG_DIRTY)) {
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
}
?>
