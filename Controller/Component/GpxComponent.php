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

App::uses('Xml', 'Utility');

class GpxComponent extends Component {

  var $controller;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  /**
   * Add point to the point array
   *
   * @param DomNode $point
   * @return Array Gps point
   */
  public function _readTrackPoint(&$point) {
    if (!$point) {
      return false;
    }
    $result = array();
    foreach ($point->attributes() as $name => $value) {
      if ($name == 'lat') {
        $result['latitude'] = (string) $value;
      } else if ($name == 'lon') {
        $result['longitude'] = (string) $value;
      }
    }
    if ($point->time) {
      $result['date'] = (string) $point->time;
    }
    if (count($result) < 3) {
      return false;
    }
    if ($point->ele) {
      $result['altitude'] = (string) $point->ele;
    }
    return $result;
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
    $t1 = microtime(true);
    try {
      $xml = Xml::build($filename);
    } catch (XmlException $xe) {
      Logger::err("Could not read gpx file $filename: " . $xe->getMessage());
      return false;
    }

    $points = array();
    foreach ($xml->trk as $trk) {
      foreach ($trk->trkseg as $trkseg) {
        foreach ($trkseg->trkpt as $trkpt) {
          $point = $this->_readTrackPoint($trkpt);
          if ($point) {
            $points[] = $point;
          }
        }
      }
    }
    unset($xml);
    $time = sprintf("%.3fs", microtime(true) - $t1);

    Logger::info("Read $filename with " . count($points) . " track points in $time");
    return $points;
  }
}
