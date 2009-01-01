<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2009 Sebastian Felis, sebastian@phtagr.org
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the 
 * License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

class NmeaComponent extends Object {

  var $controller;

  var $components = array('Logger');

  var $minInterval = 600; // 10 minutes

  var $points = array();

  var $times = array();

  function startup(&$controller) {
    $this->controller =& $controller;
  }

  /** Checks the NMEA lind by calculating the checksum 
    @param line NMEA line 
    @return True if the NMEA is correct. Otherwise false */
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
      $this->Logger->warn("Wrong checksum! $sum!=$check (line: $line");
      return false;
    }

    return true;
  }

  /** Convert value to degree
    @param value Position of langitude or longitude
    @return Degree value of the given value */
  function _toDegree($value) {
    $value /= 100;
    $int = intval($value);
    return $int+(5*($value-$int)/3);
  }

  /** Add point to the point array
    @param gps Array with GPS data
    @param timeOffset Time offset to adjust time zone */
  function _addPoint($gps, $timeOffset = 0) {
    if (!isset($gps['time'])) {
      return false;
    }
    
    $time = $gps['time'];
    $sec = substr($time, 0, 2)*3600+substr($time, 2, 2)*60+substr($time, 4, 2);
    
    if (isset($gps['date'])) {
      $date = $gps['date'];
      $datesec = mktime(0, 0, 0, substr($date, 2, 2), substr($date, 0, 2), 2000+substr($date, 4, 2));
      if (isset($this->points[$sec])) {
        $old = $this->points[$sec];
        unset($this->points[$sec]);
        $gps = array_merge($old, $gps);
      }

      $time = $datesec+$sec+$timeOffset;
      $gps['sec'] = $time;
      $this->points[$time] = $gps;
    } else {
      $this->points[$sec] = $gps;
    }
  }

  /** Read GPS cordinates of NMEA file. It considers GGA and RMC records.
    @param filename Filename of the NMEA file
    @param timeOffset Time offset to adjust time zones. Default time zone of
    NMEA is UTC
    @param append If false existing data is purged. Otherwise new points are
    append 
    @return False on error */
  function readFile($filename, $timeOffset = 0, $append = false) {
    if (!is_readable($filename)) {
      $this->Logger->warn("File '$file' is not readable");
      return false;
    }
    $h = fopen($filename, 'r');
    if (!$h) {
      $this->Logger->warn("Could not open file '$file'");
      return false;
    }

    if (!$append) {
      $this->points = array();
    }

    while (!feof($h)) {
      $line = fgets($h);
      if (!$line) {
        continue;
      }

      if (preg_match('/^\$..GGA,/', $line)) {
        if (!$this->_checkNmeaLine($line)) {
          continue;
        }

        $parts = split(',', $line);
        if (count($parts) != 15) {
          continue;
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
        $gps = array(
                  'latitude' => $latitude, 
                  'longitude' => $longitude, 
                  'altitude' => $altitude, 
                  'satelites' => $satelites,
                  'time' => $parts[1]
                );
        $this->_addPoint($gps, $timeOffset);
      } elseif (preg_match('/^\$..RMC,/', $line)) {
        if (!$this->_checkNmeaLine($line)) {
          continue;
        }

        $parts = split(',', $line);
        if (count($parts) != 13) {
          continue;
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

        $gps = array(
                  'latitude' => $latitude, 
                  'longitude' => $longitude, 
                  'altitude' => $altitude, 
                  'time' => $parts[1], 
                  'date' => $parts[9]
                );
        $this->_addPoint($gps, $timeOffset);
      }
    }
    $this->times = array_keys($this->points);
    sort($this->times);
    $num = count($this->times);
    if ($num > 0) {
      $first = $this->times[0];
      $last = $this->times[$num-1];
      $startTime = date("Y-m-d H:i:s", $first);
      $seconds = $last - $first;
      $this->Logger->debug("Read '$filename' with ".count($this->times)." entries. Start $startTime for $seconds seconds.");
    } else {
      $this->Logger->verbose("File '$filename' does not contain any points");
    }
    return true;
  }

  /** Checks if the given time is within the interval 
    @param time Time in seconds
    @param timeOffset Offset for the time
    @return True if the time is in the current time interval */
  function _containsDate($time) {
    if (count($this->times) > 0 && 
      $time >= $this->times[0] - $this->minInterval && 
      $time <= $this->times[count($this->times)-1] + $this->minInterval) {
      return true;
    }
    return false;
  }

  /** Get lower index of datum which is before the given time. The next index
   * is after or equal the given time.
    @param time Time in seconds
    @param low Lower bound
    @param high Higher bound 
    @return Index of time which is before the given time.*/
  function _getIndex($time, $low, $high) {
    if ($high-$low < 2) {
      return $low;
    }

    $mid = intval($low + ($high-$low)/2);
    if ($time <= $this->times[$mid]) {
      return $this->_getIndex($time, $low, $mid);
    } else {
      return $this->_getIndex($time, $mid, $high);
    }
  }

  /** Estimate the position at a certain time
    @param time Time in seconds
    @param x First GPS point
    @param y Second GPS point
    @return Estimated position at the given time */
  function _estimatePosition($time, $x, $y) {
    // check pre conditions: x < time < y
    if ($x['sec'] > $y['sec']) {
      $z = $x;
      $x = $y;
      $y = $z;
    }
    $xSec = $x['sec'];
    $ySec = $y['sec'];
    $min = $this->minInterval;

    // time is within the interval
    if ($time < $xSec-$min || $time > $ySec+$min) {
      return false;
    }

    if (abs($xSec-$time) > $min && abs($ySec-$time) > $min) {
      // no point is near to x or y
      return false;
    } elseif ($time > $ySec || $time-$xSec > $min) {
      // point is near to y (and far away from x)
      return $y;
    } elseif ($time < $xSec || $ySec-$time > $min) {
      // point is near to x (and far away from y)
      return $x;
    } elseif ($xSec == $ySec) {
      return $x;
    }

    // calculate intermediate point p with linear scale
    $scale = ($time-$xSec)/($ySec-$xSec);
    $p['latitude']  = $x['latitude'] +$scale*($y['latitude'] -$x['latitude']);
    $p['longitude'] = $x['longitude']+$scale*($y['longitude']-$x['longitude']);
    $p['altitude']  = $x['altitude'] +$scale*($y['altitude'] -$x['altitude']);
    $p['sec'] = $time;

    return $p;
  }

  /** Returns count of available points */
  function getPointCount() {
    return count($this->times);
  }

  /** Return the position of the time
    @param time Time in seconds
    @param timeOffset Time Offset 
    @return Array of position. False on failure */
  function getPosition($time) {
    if (!$this->_containsDate($time)) {
      //echo "GPS track does not contain $time\n";
      return false;
    }
    $last = count($this->times)-1;
    $index = $this->_getIndex($time, 0, $last);
    if ($index === false || $index < 0 || $index > $last) {
      return false;
    } elseif ($index == 0 || $index == $last) {
      return $this->_estimatePosition(
        $time, $this->points[$this->times[$index]], $this->points[$this->times[$index]]);
    } else {
      return $this->_estimatePosition(
        $time, $this->points[$this->times[$index]], $this->points[$this->times[$index+1]]);
    }
  }

  function getNorthWest() {
    $maxLatitude = -400;
    $minLongitude = 400;
    foreach($this->points as $point) {
      $maxLatitude = max($maxLatitude, $point['latitude']);
      $minLongitude = min($minLongitude, $point['longitude']);
    }
    return array('latitude' => $maxLatitude, 'longitude' => $minLongitude);
  } 

  function getSouthEast() {
    $minLatitude = 400;
    $maxLongitude = -400;
    foreach($this->points as $point) {
      $minLatitude = min($minLatitude, $point['latitude']);
      $maxLongitude = max($maxLongitude, $point['longitude']);
    }
    return array('latitude' => $minLatitude, 'longitude' => $maxLongitude);
  } 

  /** returns the time interval of GPS coordinates
    @return Array of start and end time in seconds */
  function getTimeInterval() {
    return array(
      $this->times[0]-$this->minInterval, 
      $this->times[count($this->times)-1]+$this->minInterval);
  }
}
?>
