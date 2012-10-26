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

App::uses('Xml', 'Utility');

class GpxComponent extends Component {

  var $controller;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  public function _getChildByName($node, $childName) {
    if (!$node) {
      return null;
    }
    foreach ($node->childNodes as $child) {
      if ($child->nodeName == $childName) {
        return $child;
      }
    }
    return null;
  }

  /**
   * Add point to the point array
   *
   * @param DomNode $point
   * @return Array Gps point
   */
  public function _readTrackPoint($point) {
    $lat = $point->getAttribute('lat');
    $long = $point->getAttribute('lon');
    if (!$lat || !$long) {
      return false;
    }
    $timeNode = $this->_getChildByName($point, 'time');
    if (!$timeNode) {
      return false;
    }
    $altitude = $this->_getChildByName($point, 'ele');
    $point = array(
        'latitude' => $lat,
        'longitude' => $long,
        'date' => $timeNode->nodeValue);
    if ($altitude) {
      $point['altitude'] = $altitude->nodeValue;
    }
    return $point;
  }

  /**
   * Read GPS track points from GPX file.
   *
   * @param filename Filename of the NMEA file
   * @return Array of GPS points. False on error
   */
  public function readFile($filename) {
    if (!is_readable($filename)) {
      Logger::warn("File '$file' is not readable");
      return false;
    }
    try {
      $xml = Xml::build($filename, array('return' => 'domdocument'));
    } catch (XmlException $xe) {
      Logger::err("Could not read gpx file $filename: " . $xe->getMessage());
      return false;
    }

    $points = array();
    foreach ($xml->getElementsByTagName('trkpt') as $trkpt) {
      $point = $this->_readTrackPoint($trkpt);
      if ($point) {
        $points[] = $point;
      }
    }

    Logger::info("Read $filename with " . count($points) . " track points");
    return $points;
  }

}