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

class BaseFilterComponent extends Component {

  var $components = array();
  var $controller = null;

  var $Manager = null;
  var $Media = null;
  var $MyFile = null;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  public function init(&$manager) {
    if ($manager->controller) {
      $this->controller = $manager->controller;
    }
    $this->Manager = $manager;
    $this->Media = $manager->controller->Media;
    $this->MyFile = $manager->controller->MyFile;
    return true;
  }

  public function getName() {
    return false;
  }

  public function getExtensions() {
    return false;
  }

  public function read(&$file, &$media = null, $options = array()) {
    return false;
  }

  public function write(&$file, &$media, $options = array()) {
    return false;
  }
}

?>
