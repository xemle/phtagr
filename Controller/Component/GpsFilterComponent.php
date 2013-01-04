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

App::uses('BaseFilter', 'Component');

class GpsFilterComponent extends BaseFilterComponent {

  var $controller = null;
  var $components = array('Nmea', 'Gpx');
  var $points = array();
  var $times = array();
  var $range = 600;
  var $utcZone = null;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
    $this->utcZone = new DateTimeZone('UTC');
  }

  public function getName() {
    return "Gps";
  }

  public function getExtensions() {
    return array(
        'log' => array('priority' => 2),
        'gpx' => array('priority' => 3));
  }

  /**
   * Read the meta data from the file
   *
   * @param array $file File data model
   * @param array $media Media data model
   * @param array $options
   *  - offset Time offset in seconds
   *  - overwrite Overwrite GPS
   *  - range Threshold in seconds which media get a GPS point
   * @return The image data array or False on error
   */
  public function read(&$file, &$media = null, $options = array()) {
    $offset = $this->controller->getOption('filter.gps.offset', 0) * 60;
    $overwrite = $this->controller->getOption('filter.gps.overwrite', 0);
    $range = $this->controller->getOption('filter.gps.range', 10) * 60;

    $options = am(array(
          'offset' => $offset,
          'overwrite' => $overwrite,
          'range' => $range),
          $options);
    $this->range = $options['range'];
    //Logger::trace($options);

    $filename = $this->controller->MyFile->getFilename($file);
    $ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
    $this->clear();
    $points = array();
    if ($ext == 'log') {
      $points = $this->Nmea->readFile($filename);
    } else if ($ext == 'gpx') {
      $points = $this->Gpx->readFile($filename);
    }
    if (!is_array($points)) {
      Logger::debug("Reading GPS data from $filename has no points");
      return false;
    }
    $this->addPoints($points, $options['offset']);
    unset($points);

    // fetch [first, last] positions
    $userId = $this->controller->getUserId();
    list($start, $end) = $this->getTimeInterval();
    $startDate = gmdate("Y-m-d H:i:s", $start);
    $endDate = gmdate("Y-m-d H:i:s", $end);
    //Logger::trace("start: ".date("'Y-m-d H:i:sZ'", $start)." end: ".date("'Y-m-d H:i:sZ'", $end));


    // Calculate only with UTC time stamps
    $conditions = array(
      'Media.user_id' => $userId,
      "Media.date >= '$startDate' AND Media.date <= '$endDate'");
    if (!$options['overwrite']) {
      $conditions['Media.latitude'] = null;
      $conditions['Media.longitude'] = null;
    }
    //Logger::trace($conditions);
    $this->controller->Media->unbindAll();
    $mediaSet = $this->controller->Media->find('all', array('conditions' => $conditions));
    if (!count($mediaSet)) {
      Logger::info("No images found for GPS interval $startDate to $endDate");
      return false;
    }
    // fetch images of same user, no gps, range
    $updated = 0;
    $mediaIds = array();
    foreach ($mediaSet as $media) {
      $utc = new DateTime($media['Media']['date'], $this->utcZone);
      $time = $utc->format('U');

      // evaluate position
      $position = $this->getPosition($time);
      if (!$position) {
        Logger::debug("No GPS position found for image {$media['Media']['id']}");
        continue;
      }
      $media['Media']['latitude'] = $position['latitude'];
      $media['Media']['longitude'] = $position['longitude'];
      $media['Media']['flag'] |= MEDIA_FLAG_DIRTY;
      if ($this->controller->Media->save($media['Media'], true, array('latitude', 'longitude', 'flag'))) {
        $mediaIds[] = $media['Media']['id'];
        Logger::debug("Update GPS position of image {$media['Media']['id']} to {$position['latitude']}/{$position['longitude']}");
      } else {
        Logger::warn("Could not update GPS position of image {$media['Media']['id']}");
      }
      $updated++;
    }
    Logger::info("Updated $updated of " . count($mediaSet) . " media for interval $startDate to $endDate");
    if (count($mediaIds)) {
      $dummy = array('Media' => array('id' => array_pop($mediaIds)));
      return $dummy;
    }
    return false;
  }

  public function write(&$file, &$media, $options = array()) {
    return 0;
  }

  public function clear() {
    $this->points = array();
    $this->times = array();
  }

  /**
   * Add GPS points to current filter
   *
   * @param type $points Array of points. A point is an array with at least
   * following array keys: time, latitude, longitude.
   * @param offset Timeoffset for points
   */
  public function addPoints($points, $offset) {
    foreach ($points as $point) {
      if (!isset($point['date']) || !isset($point['latitude']) || !isset($point['longitude'])) {
        continue;
      }
      // Adjust offset to point
      $utc = new DateTime($point['date'], $this->utcZone);
      $time = $utc->format('U') + $offset;
      $point['time'] = $time;
      $this->points[$time] = $point;
    }
    $this->times = array_keys($this->points);
    sort($this->times);
  }

  /**
   * Checks if the given time is within the interval
   *
   * @param time Time in seconds
   * @return True if the time is in the current time interval
   */
  private function _containsDate($time) {
    if (count($this->times) > 0 &&
      $time >= $this->times[0] - $this->range &&
      $time <= $this->times[count($this->times)-1] + $this->range) {
      return true;
    }
    return false;
  }

  /**
   * Get lower index of datum which is before the given time. The next index
   * is after or equal the given time.
   *
   * @param time Time in seconds
   * @param low Lower bound
   * @param high Higher bound
   * @return Index of time which is before the given time.
   */
  private function _getIndex($time, $low, $high) {
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

  /**
   * Estimate the position at a certain time
   *
   * @param time Time in seconds
   * @param x First GPS point
   * @param y Second GPS point
   * @return Estimated position at the given time
   */
  private function _estimatePosition($time, $x, $y) {
    // check pre conditions: x < time < y
    if ($x['time'] > $y['time']) {
      $z = $x;
      $x = $y;
      $y = $z;
    }
    $xSec = $x['time'];
    $ySec = $y['time'];
    $min = $this->range;

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
    if (isset($x['altitude']) && isset($y['altitude'])) {
      $p['altitude']  = $x['altitude'] +$scale*($y['altitude'] -$x['altitude']);
    }
    $p['time'] = $time;

    return $p;
  }

  /**
   * Returns count of available points
   */
  public function getPointCount() {
    return count($this->times);
  }

  /**
   * Return the position of the time
   *
   * @param time Time in seconds
   * @return Array of position. False on failure
   */
  public function getPosition($time) {
    if (!$this->_containsDate($time)) {
      //echo "GPS track does not contain $time\n";
      return false;
    }
    $last = count($this->times)-1;
    $index = $this->_getIndex($time, 0, $last);
    if ($index === false || $index < 0 || $index > $last) {
      return false;
    } elseif ($index == 0 || $index == $last) {
      $indexTime = $this->times[$index];
      return $this->_estimatePosition(
        $time, $this->points[$indexTime], $this->points[$indexTime]);
    } else {
      $indexTime1 = $this->times[$index];
      $indexTime2 = $this->times[$index+1];
      return $this->_estimatePosition(
        $time, $this->points[$indexTime1], $this->points[$indexTime2]);
    }
  }

  public function getNorthWest() {
    $maxLatitude = -400;
    $minLongitude = 400;
    foreach($this->points as $point) {
      $maxLatitude = max($maxLatitude, $point['latitude']);
      $minLongitude = min($minLongitude, $point['longitude']);
    }
    return array('latitude' => $maxLatitude, 'longitude' => $minLongitude);
  }

  public function getSouthEast() {
    $minLatitude = 400;
    $maxLongitude = -400;
    foreach($this->points as $point) {
      $minLatitude = min($minLatitude, $point['latitude']);
      $maxLongitude = max($maxLongitude, $point['longitude']);
    }
    return array('latitude' => $minLatitude, 'longitude' => $maxLongitude);
  }

  /**
   * returns the time interval of GPS coordinates
   *
   * @return Array of start and end time in seconds
   */
  public function getTimeInterval() {
    if (!count($this->times)) {
      return array(0, 0);
    }
    return array(
      $this->times[0]-$this->range,
      $this->times[count($this->times)-1]+$this->range);
  }
}
