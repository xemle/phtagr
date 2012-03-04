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

class FastFileResponderComponent extends Component {
  var $controller = null;
  var $components = array('Session', 'FileCache');
  var $sessionKey = 'fastFile.items';
  var $expireOffset = 10; // seconds

  function initialize(&$controller) {
    $this->controller = $controller;
  }

  function add($media, $name, $ext = 'jpg') {
    $file = $this->FileCache->getFilePath($media, $name);
    if (!is_readable($file)) {
      return false;
    }
    $key = $name . '-' . $media['Media']['id'];
    $files = (array) $this->Session->read($this->sessionKey);
    $files[$key] = array('expires' => time() + $this->expireOffset, 'file' => $file);
    $this->Session->write($this->sessionKey, $files);
    return true;
  }

  function addAll($data, $name, $ext = 'jpg') {
    foreach ($data as $media) {
      $this->add($media, $name, $ext);
    }
  }
}
?>
