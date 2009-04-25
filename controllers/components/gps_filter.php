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

class GpsFilterComponent extends BaseFilterComponent {

  var $controller = null;
  var $components = array('Logger', 'Nmea');

  function startup(&$controller) {
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
    //$this->Logger->trace($options);

    $filename = $this->MyFile->getFilename($file);
    if (!$this->Nmea->readFile($filename)) {
      $this->Logger->warn('Could not read file $filename');
      return false;
    }
    if ($this->Nmea->getPointCount() == 0) {
      $this->Logger->warn("NMEA file has no points");
      return false;
    }
    $this->Enma->minInterval = $options['minInterval'];

    // fetch [first, last] positions
    $userId = $this->controller->getUserId();
    list($start, $end) = $this->Nmea->getTimeInterval();
    //$this->Logger->trace("start: ".date("'Y-m-d H:i:s'", $start)." end: ".date("'Y-m-d H:i:s'", $end));

    $conditions = array(
      'Media.user_id' => $userId,
      'Media.date >= '.date("'Y-m-d H:i:s'", $start+$options['offset']).' AND '.
      'Media.date <= '.date("'Y-m-d H:i:s'", $end+$options['offset']));
    if (!$options['overwrite']) {
      $conditions['Media.latitude'] = null;
      $conditions['Media.longitude'] = null;
    }
    $this->Logger->trace($conditions);
    $this->Media->unbindAll();
    $mediaSet = $this->Media->findAll($conditions);
    if (!count($mediaSet)) {
      $this->Logger->info("No images found for GPS interval");
      return false;
    }
    // fetch images of same user, no gps, range
    foreach ($mediaSet as $media) {
      // Adjust time according offset and fetch position
      $date = strtotime($media['Media']['date'])-$options['offset'];
      $position = $this->Nmea->getPosition($date);
      // write position
      if (!$position) {
        $this->Logger->debug("No GPS position found for image {$media['Media']['id']}");
        continue;
      }

      $media['Media']['latitude'] = $position['latitude'];
      $media['Media']['longitude'] = $position['longitude'];
      $media['Media']['flag'] |= MEDIA_FLAG_DIRTY;
      if ($this->Media->save($media['Media'], true, array('latitude', 'longitude', 'flag'))) {
        $this->Logger->debug("Update GPS position of image {$media['Media']['id']} to {$position['latitude']}/{$position['longitude']}");
      } else {
        $this->Logger->warn("Could not update GPS position of image {$media['Media']['id']}");
      }
    }
    return 1;
  }

  function write($file, $media = null, $options = array()) {
    return 0;
  }
}

?>
