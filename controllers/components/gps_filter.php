<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2008 Sebastian Felis, sebastian@phtagr.org
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

class GpsFilterComponent extends Object {

  var $controller = null;
  var $components = array('Logger', 'Nmea');

  function startup(&$controller) {
    $this->controller =& $controller;
  }

  /** Read the meta data from the file 
   * @param image Image data model
   * @param filename Optional filename for import meta data
   * @return The image data array or False on error */
  function readFile($filename, $options) {
    $options = am(array(
          'offset' => 0, 
          'overwrite' => false,
          'minInterval' => 600),
          $options);
    //$this->Logger->trace($options);

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
      'Image.user_id' => $userId,
      'Image.date >= '.date("'Y-m-d H:i:s'", $start+$options['offset']).' AND '.
      'Image.date <= '.date("'Y-m-d H:i:s'", $end+$options['offset']));
    if (!$options['overwrite']) {
      $conditions['Image.latitude'] = null;
      $conditions['Image.longitude'] = null;
    }
    //$this->Logger->trace($conditions);
    $this->controller->Image->unbindAll();
    $images = $this->controller->Image->findAll($conditions);
    if (!count($images)) {
      $this->Logger->info("No images found for GPS interval");
      return false;
    }
    // fetch images of same user, no gps, range
    foreach ($images as $image) {
      // Adjust time according offset and fetch position
      $date = strtotime($image['Image']['date'])-$options['offset'];
      $position = $this->Nmea->getPosition($date);
      // write position
      if (!$position) {
        $this->Logger->debug("No GPS position found for image {$image['Image']['id']}");
        continue;
      }

      $image['Image']['latitude'] = $position['latitude'];
      $image['Image']['longitude'] = $position['longitude'];
      $image['Image']['flag'] |= IMAGE_FLAG_DIRTY;
      if ($this->controller->Image->save($image, true, array('latitude', 'longitude', 'flag'))) {
        $this->Logger->debug("Update GPS position of image {$image['Image']['id']} to {$position['latitude']}/{$position['longitude']}");
      } else {
        $this->Logger->warn("Could not update GPS position of image {$image['Image']['id']}");
      }
    }
  }

}

?>
