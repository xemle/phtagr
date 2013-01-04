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

class FastFileResponderComponent extends Component {
  var $controller = null;
  var $components = array('Session', 'FileCache');
  var $sessionKey = 'fastFile.items';
  var $expireOffset = 10; // seconds
  var $excludeMediaIds = array();

  public function initialize(Controller $controller) {
    $this->controller = $controller;
    $this->removeExpiredItems();
  }

  public function removeExpiredItems() {
    $now = time();
    $validItems = array();
    $files = (array) $this->Session->read($this->sessionKey);
    foreach ($files as $key => $values) {
      if ($values['expires'] > $now) {
        $validItems[$key] = $values;
      }
    }
    $this->Session->write($this->sessionKey, $validItems);
  }

  public function add($media, $name, $ext = 'jpg') {
    $file = $this->FileCache->getFilePath($media, $name);
    if (!is_readable($file) || in_array($media['Media']['id'], $this->excludeMediaIds)) {
      return false;
    }
    $key = $name . '-' . $media['Media']['id'];
    $files = (array) $this->Session->read($this->sessionKey);
    $files[$key] = array('expires' => time() + $this->expireOffset, 'file' => $file);
    $this->Session->write($this->sessionKey, $files);
    return true;
  }

  public function addAll($data, $name, $ext = 'jpg') {
    foreach ($data as $media) {
      $this->add($media, $name, $ext);
    }
  }

  public function excludeMedia($media) {
    $this->excludeMediaIds[] = $media['Media']['id'];
  }
}
?>
