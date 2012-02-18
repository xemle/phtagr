<?php
/*
 * phtagr.
 * 
 * social photo gallery for your community.
 * 
 * Copyright (C) 2006-2010 Sebastian Felis, sebastian@phtagr.org
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

App::uses('BaseFilter', 'Component');

class GpsFilterComponent extends BaseFilterComponent {

  var $controller = null;
  var $components = array('Nmea');

  function initialize(&$controller) {
    $this->controller =& $controller;
  }

  function getName() {
    return "Gps";
  }

  function getExtensions() {
    return array('log' => array('priority' => 2));
  }

  /** Read the meta data from the file 
   * @param file File data model
   * @param media Media data model
   * @param options 
   *  - offset Time offset in seconds
   *  - overwrite Overwrite GPS 
   *  - minInterval Threshold in seconds which media get a GPS point
   * @return The image data array or False on error */
  function read($file, &$media, $options = array()) {
    $options = am(array(
          'offset' => 120*60, 
          'overwrite' => false,
          'minInterval' => 600),
          $options);
    //Logger::trace($options);

    $filename = $this->controller->MyFile->getFilename($file);
    if (!$this->Nmea->readFile($filename)) {
      Logger::warn('Could not read file $filename');
      return false;
    }
    if ($this->Nmea->getPointCount() == 0) {
      Logger::warn("NMEA file has no points");
      return false;
    }
    $this->Enma->minInterval = $options['minInterval'];

    // fetch [first, last] positions
    $userId = $this->controller->getUserId();
    list($start, $end) = $this->Nmea->getTimeInterval();
    //Logger::trace("start: ".date("'Y-m-d H:i:s'", $start)." end: ".date("'Y-m-d H:i:s'", $end));

    $conditions = array(
      'Media.user_id' => $userId,
      'Media.date >= '.date("'Y-m-d H:i:s'", $start+$options['offset']).' AND '.
      'Media.date <= '.date("'Y-m-d H:i:s'", $end+$options['offset']));
    if (!$options['overwrite']) {
      $conditions['Media.latitude'] = null;
      $conditions['Media.longitude'] = null;
    }
    Logger::trace($conditions);
    $this->controller->Media->unbindAll();
    $mediaSet = $this->controller->Media->find('all', array('conditions' => $conditions));
    if (!count($mediaSet)) {
      Logger::info("No images found for GPS interval");
      return false;
    }
    // fetch images of same user, no gps, range
    foreach ($mediaSet as $media) {
      // Adjust time according offset and fetch position
      $date = strtotime($media['Media']['date'])-$options['offset'];
      $position = $this->Nmea->getPosition($date);
      // write position
      if (!$position) {
        Logger::debug("No GPS position found for image {$media['Media']['id']}");
        continue;
      }

      $media['Media']['latitude'] = $position['latitude'];
      $media['Media']['longitude'] = $position['longitude'];
      $media['Media']['flag'] |= MEDIA_FLAG_DIRTY;
      if ($this->controller->Media->save($media['Media'], true, array('latitude', 'longitude', 'flag'))) {
        Logger::debug("Update GPS position of image {$media['Media']['id']} to {$position['latitude']}/{$position['longitude']}");
      } else {
        Logger::warn("Could not update GPS position of image {$media['Media']['id']}");
      }
    }
    return 1;
  }

  function write($file, $media = null, $options = array()) {
    return 0;
  }
}

?>
