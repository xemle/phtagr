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

class NmeaComponent extends Component {

  var $controller;

  var $gga = array();

  function initialize(&$controller) {
    $this->controller =& $controller;
  }

  /**
   * Checks the NMEA lind by calculating the checksum
   *
   * @param line NMEA line
   * @return True if the NMEA is correct. Otherwise false
   */
  function _checkNmeaLine($line) {
    $i = 1;
    $len = strlen($line);
    $sum = 0;
    while ($i < $len && $line[$i] != '*') {
      $sum ^= (ord($line[$i++]) & 0xff);
    }
    if ($i >= $len+3 || $line[$i] != '*') {
      return false;
    }

    $sum = strtolower(dechex($sum));
    $check = strtolower(substr($line, $i+1, 2));

    if ($sum != $check) {
      Logger::warn("Wrong checksum! $sum!=$check (line: $line");
      return false;
    }

    return true;
  }

  /**
   * Convert value to degree
   *
   * @param value Position of langitude or longitude
   * @return Degree value of the given value
   */
  function _toDegree($value) {
    $value /= 100;
    $int = intval($value);
    return $int+(5*($value-$int)/3);
  }

  /**
   * Create epoch time from gps data
   *
   * @param String $date in format mmDDYY
   * @param String $time Time in format HHMMSS
   * @return int Time in seconds
   */
  function _getTime($date, $time) {
    $result = substr($time, 0, 2)*3600+substr($time, 2, 2)*60+substr($time, 4, 2);
    $result = $time + mktime(0, 0, 0, substr($date, 2, 2), substr($date, 0, 2), 2000+substr($date, 4, 2));
    return $result;
  }

  /**
   * Read NMEA GPGGA data line
   *
   * @param String $line
   * @param Int $offset
   * @return Array Gps point
   */
  function _readGga($line) {
    if (!$this->_checkNmeaLine($line)) {
      return false;
    }

    $parts = split(',', $line);
    if (count($parts) != 15) {
      return false;
    }

    $latitude = $parts[2];
    if ($parts[3] == 'S') {
      $latitude *= -1;
    }
    $longitude = $parts[4];
    if ($parts[5] == 'W') {
      $longitude *= -1;
    }
    $satelites = $parts[7];
    $altitude = $parts[9];

    $longitude = $this->_toDegree($longitude);
    $latitude = $this->_toDegree($latitude);

    $point = array(
              'latitude' => $latitude,
              'longitude' => $longitude,
              'altitude' => $altitude,
              'satelites' => $satelites,
            );
    $this->gga[$parts[1]] = $point;
    return false;
  }

  /**
   * Read NMEA GPRMC data line
   *
   * @param String $line
   * @return Array
   */
  function _readRmc($line) {
    if (!$this->_checkNmeaLine($line)) {
      return false;
    }

    $parts = split(',', $line);
    if (count($parts) != 13) {
      return false;
    }

    $latitude = $parts[3];
    if ($parts[4] == 'S') {
      $latitude *= -1;
    }
    $longitude = $parts[5];
    if ($parts[6] == 'W') {
      $longitude *= -1;
    }

    $longitude = $this->_toDegree($longitude);
    $latitude = $this->_toDegree($latitude);

    $time = $this->_getTime($parts[9], $parts[1]);
    $point = array(
              'latitude' => $latitude,
              'longitude' => $longitude,
              'time' => $time,
            );

    // Merge GGA info with altitude via timestamp info
    if (isset($this->gga[$parts[1]])) {
      $point = am($this->gga[$parts[1]], $point);
    }
    return $point;
  }

  /**
   * Read GPS cordinates of NMEA file. It considers GGA and RMC records.
   *
   * @param filename Filename of the NMEA file
   * @return False on error
   */
  function readFile($filename) {
    if (!is_readable($filename)) {
      Logger::warn("File '$file' is not readable");
      return false;
    }
    $h = fopen($filename, 'r');
    if (!$h) {
      Logger::warn("Could not open file '$file'");
      return false;
    }

    $points = array();
    // Storage of GGA info with altitude
    $this->gga = array();
    while (!feof($h)) {
      $line = fgets($h);
      if (!$line) {
        continue;
      }

      if (preg_match('/^\$..GGA,/', $line)) {
        $this->_readGga($line);
      } elseif (preg_match('/^\$..RMC,/', $line)) {
        $point = $this->_readRmc($line);
        if ($point) {
          $points[] = $point;
        }
      }
    }
    return $points;
  }
}