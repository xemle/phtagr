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
