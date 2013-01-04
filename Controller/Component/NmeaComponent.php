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

/**
 * Reads NMEA GPS data log
 *
 * @see http://aprs.gids.nl/nmea for details
 */
class NmeaComponent extends Component {

  var $controller;

  var $gga = array();

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  /**
   * Checks the NMEA lind by calculating the checksum
   *
   * @param line NMEA line
   * @return True if the NMEA is correct. Otherwise false
   */
  public function _checkNmeaLine($line) {
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
  public function _toDegree($value) {
    $value /= 100;
    $int = intval($value);
    return $int+(5*($value-$int)/3);
  }

  /**
   * Create UTC date from gps data
   *
   * @param String $date in format mmDDYY
   * @param String $time Time in format HHMMSS
   * @return String Date in ISO8601 format YYYY-mm-DDTHH:MM:SSZ
   */
  public function _getIso8601Date($date, $time) {
    // Split 2-digit year to year 1970 - 2069
    $year = intval(substr($date, 4, 2));
    if ($year >= 70) {
      $year += 1900;
    } else {
      $year += 2000;
    }
    $result = $year . '-' . substr($date, 2, 2) . '-' . substr($date, 0, 2) . 'T';
    $result .= substr($time, 0, 2) . ':' . substr($time, 2, 2) . ':' . substr($time, 4) . 'Z';
    return $result;
  }

  /**
   * Read NMEA GPGGA data line
   *
   * From http://aprs.gids.nl/nmea
   *
   * eg3. $GPGGA,hhmmss.ss,llll.ll,a,yyyyy.yy,a,x,xx,x.x,x.x,M,x.x,M,x.x,xxxx*hh
   * 1    = UTC of Position
   * 2    = Latitude
   * 3    = N or S
   * 4    = Longitude
   * 5    = E or W
   * 6    = GPS quality indicator (0=invalid; 1=GPS fix; 2=Diff. GPS fix)
   * 7    = Number of satellites in use [not those in view]
   * 8    = Horizontal dilution of position
   * 9    = Antenna altitude above/below mean sea level (geoid)
   * 10   = Meters  (Antenna height unit)
   * 11   = Geoidal separation (Diff. between WGS-84 earth ellipsoid and
   *        mean sea level.  -=geoid is below WGS-84 ellipsoid)
   * 12   = Meters  (Units of geoidal separation)
   * 13   = Age in seconds since last update from diff. reference station
   * 14   = Diff. reference station ID#
   * 15   = Checksum
   *
   * @param String $line
   * @param Int $offset
   * @return Array Gps point
   */
  public function _readGga($line) {
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
              'altitude' => floatval($altitude),
              'satelites' => intval($satelites),
            );
    $this->gga[$parts[1]] = $point;
    return false;
  }

  /**
   * Read NMEA GPRMC data line
   *
   * From http://aprs.gids.nl/nmea
   *
   * eg4. $GPRMC,hhmmss.ss,A,llll.ll,a,yyyyy.yy,a,x.x,x.x,ddmmyy,x.x,a*hh
   * 1    = UTC of position fix
   * 2    = Data status (V=navigation receiver warning)
   * 3    = Latitude of fix
   * 4    = N or S
   * 5    = Longitude of fix
   * 6    = E or W
   * 7    = Speed over ground in knots
   * 8    = Track made good in degrees True
   * 9    = UT date
   * 10   = Magnetic variation degrees (Easterly var. subtracts from true course)
   * 11   = E or W
   * 12   = Checksum
   *
   * @param String $line
   * @return Array
   */
  public function _readRmc($line) {
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

    $date = $this->_getIso8601Date($parts[9], $parts[1]);
    $point = array(
              'latitude' => $latitude,
              'longitude' => $longitude,
              'date' => $date,
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
  public function readFile($filename) {
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