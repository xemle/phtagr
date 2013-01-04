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

  /**
   * Returns the filter name
   *
   * @return string Filername
   */
  public function getName() {
    return false;
  }

  /**
   * Returns the file extensions which could be read from the filter
   *
   * @return array List of file extensions
   */
  public function getExtensions() {
    return false;
  }

  /**
   * Reads meat data from given file to media
   *
   * @param array $file File model data
   * @param array $media Media model data
   * @param array $options Options for the filter
   * @param mixed Media model data on success. False on error
   */
  public function read(&$file, &$media = null, $options = array()) {
    return false;
  }

  /**
   * Writes meta data to given file and media
   *
   * @param array $file File model data
   * @param array $media Media model data
   * @param array $options Options for the filter
   * @param boolean True on success
   */
  public function write(&$file, &$media, $options = array()) {
    return false;
  }
}

?>
