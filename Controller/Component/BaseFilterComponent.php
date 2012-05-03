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

  function initialize(&$controller) {
    $this->controller =& $controller;
  }

  function init(&$manager) {
    if ($manager->controller) {
      $this->controller =& $manager->controller;
    }
    $this->Manager =& $manager;
    $this->Media =& $manager->controller->Media;
    $this->MyFile =& $manager->controller->MyFile;
    return true;
  }

  function getName() {
    return false;
  }

  function getExtensions() {
    return false;
  }

  function read($file, $media = false, $options = array()) {
    return false;
  }

  function write($file, $media = false, $options = array()) {
    return false;
  }
}

?>
