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

class ImageFilterComponent extends BaseFilterComponent {
  var $controller = null;
  var $components = array('Command');

  var $fieldMap = array(
      'keyword' => 'Keywords',
      'category' => 'SupplementalCategories',
      'sublocation' => 'Sub-location',
      'city' => 'City',
      'state' => 'Province-State',
      'country' => 'Country-PrimaryLocationName'
      );

  var $locationMap = array(
                        LOCATION_CITY => 'City',
                        LOCATION_SUBLOCATION => 'Sub-location',
                        LOCATION_STATE => 'Province-State',
                        LOCATION_COUNTRY => 'Country-PrimaryLocationName');

  public function getName() {
    return "Image";
  }

  public function getExtensions() {
    return array('jpeg', 'jpg');
  }

  /**
   * Read the meta data from the file
   *
   * @param file File model data
   * @param media Reference of Media model data
   * @param options Options
   *  - noSave if set dont save model data
   * @return mixed The image data array or False on error
   */
  public function read(&$file, &$media = null, $options = array()) {
    $options = am(array('noSave' => false), $options);
    $filename = $this->MyFile->getFilename($file);

    if ($this->controller->getOption('bin.exiftool')) {
      $meta = $this->FilterManager->Exiftool->readMetaData($filename);
    } else {
      $meta = $this->_readMetaDataGetId3($filename);
    }

    if ($meta === false) {
      $this->FilterManager->addError($filename, 'NoMetaDataFound');
      return false;
    }

    $isNew = false;
    if (!$media) {
      $media = $this->Media->create(array(
        'type' => MEDIA_TYPE_IMAGE,
        ), true);
      if ($this->controller->getUserId() != $file['File']['user_id']) {
        $user = $this->Media->User->findById($file['File']['user_id']);
      } else {
        $user = $this->controller->getUser();
      }
      $media = $this->controller->Media->addDefaultAcl($media, $user);

      $isNew = true;
    };

    if ($this->controller->getOption('bin.exiftool')) {
      $this->_extractImageData($media, $meta);
    } else {
      $this->_extractImageDataGetId3($media, $meta);
    }
    // fallback for image size
    if (!isset($media['Media']['width']) || $media['Media']['width'] == 0 ||
      !isset($media['Media']['height']) || $media['Media']['height'] == 0) {
      $size = getimagesize($filename);
      if ($size) {
        $media['Media']['width'] = $size[0];
        $media['Media']['height'] = $size[1];
      } else {
        Logger::warn("Could not determine image size of $filename");
      }
    }
    if ($options['noSave']) {
      return $media;
    } elseif (!$this->Media->save($media)) {
      Logger::err("Could not save Media");
      Logger::trace($media);
      $this->FilterManager->addError($filename, 'MediaSaveError');
      return false;
    }
    if ($isNew) {
      $mediaId = $this->Media->getLastInsertID();
      if (!$this->controller->MyFile->setMedia($file, $mediaId)) {
        $this->Media->delete($mediaId);
        $this->FilterManager->addError($filename, 'FileSaveError');
        return false;
      } else {
        Logger::info("Created new Media (id $mediaId)");
        $media = $this->Media->findById($mediaId);
      }
    } else {
      Logger::verbose("Updated media (id ".$media['Media']['id'].")");
    }
    $this->controller->MyFile->updateReaded($file);
    $this->controller->MyFile->setFlag($file, FILE_FLAG_DEPENDENT);
    return $media;
  }

  /**
   * Clear image metadata from a file
   *
   * @param filename Filename to file to clean
   */
  public function clearMetaData($filename) {
    if (!file_exists($filename)) {
      Logger::err("Filename '$filename' does not exists");
      return;
    }
    if (!is_writeable($filename)) {
      Logger::err("Filename '$filename' is not writeable");
      return;
    }

    $bin = $this->controller->getOption('bin.exiftool', 'exiftool');
    $this->Command->run($bin, array('-all=', $filename));

    Logger::debug("Cleaned meta data of '$filename'");
  }

  /**
   * Extracts the date of the file. It extracts the date of IPTC and EXIF.
   * IPTC has the priority.
   *
   * @param data Meta data
   * @return string Date of the meta data or now if not data information was found
   */
  private function _extractMediaDateGetId3($data) {
    // IPTC date
    $dateIptc = $this->_extract($data, 'iptc/IPTCApplication/DateCreated/0', null);
    if ($dateIptc) {
      $dateIptc = substr($dateIptc, 0, 4).'-'.substr($dateIptc, 4, 2).'-'.substr($dateIptc, 6);
      $time = $this->_extract($data, 'iptc/IPTCApplication/TimeCreated/0', null);
      if ($time) {
        $time = substr($time, 0, 2).':'.substr($time, 2, 2).':'.substr($time, 4);
        $dateIptc .= ' '.$time;
      } else {
        $dateIptc .= ' 00:00:00';
      }
      return $dateIptc;
    }
    // No IPTC date: Extract Exif date or now
    return $this->_extract($data, 'jpg/exif/EXIF/DateTimeOriginal', date('Y-m-d H:i:s', time()));
  }

  /**
   * Extracts the date of the file. It extracts the date of IPTC and EXIF.
   * IPTC has the priority.
   *
   * @param data Meta data
   * @return string Date of the meta data or now if not data information was found
   */
  private function _extractMediaDate($data) {
    // IPTC date
    $dateIptc = $this->_extract($data, 'DateCreated', null);
    if ($dateIptc) {
      $time = $this->_extract($data, 'TimeCreated', null);
      if ($time) {
        $dateIptc .= ' '.$time;
      } else {
        $dateIptc .= ' 00:00:00';
      }
      return $dateIptc;
    }
    // No IPTC date: Extract Exif date, file modification time, or NOW
    $date = $this->_extract($data, 'DateTimeOriginal');
    if (!$date) {
      $date = $this->_extract($data, 'FileModifyDate');
    }
    if (!$date) {
      $date = date('Y-m-d H:i:s', time());
    }
    return $date;
  }

  /**
   * Extract the image data from the exif tool array and save it as Media
   *
   * @param data Data array from exif tool array
   * @return Array of the the image data array as image model data
   */
  private function _extractImageData(&$media, &$data) {
    $user = $this->controller->getUser();

    $v =& $media['Media'];

    // Media information
    $v['name'] = $this->_extract($data, 'ObjectName', $this->_extract($data, 'FileName'));
    $v['name'] = $this->_extract($data, 'FileName');
    // TODO Read IPTC date, than EXIF date
    $v['date'] = $this->_extractMediaDate($data);
    $v['width'] = $this->_extract($data, 'ImageWidth', 0);
    $v['height'] = $this->_extract($data, 'ImageHeight', 0);
    $v['duration'] = -1;
    $v['orientation'] = $this->_extract($data, 'Orientation', 1);

    $v['aperture'] = $this->_extract($data, 'Aperture', NULL);
    $v['shutter'] = $this->_extract($data, 'ShutterSpeed', NULL);
    $v['model'] = $this->_extract($data, 'Model', null);
    $v['iso'] = $this->_extract($data, 'ISO', null);
    $v['caption'] = $this->_extract($data, 'Comment', null);

    // fetch GPS coordinates
    $latitude = $this->_extract($data, 'GPSLatitude', null);
    $latitudeRef = $this->_extract($data, 'GPSLatitudeRef', null);
    $longitude = $this->_extract($data, 'GPSLongitude', null);
    $longitudeRef = $this->_extract($data, 'GPSLongitudeRef', null);

    if ($latitude && $latitudeRef && $longitude && $longitudeRef) {
      if ($latitudeRef == 'S' && $latitude > 0) {
        $latitude *= -1;
      }
      if ($longitudeRef == 'W' && $longitude > 0) {
        $longitude *= -1;
      }
      $v['latitude'] = $latitude;
      $v['longitude'] = $longitude;
    }

    // Associations to meta data: Tags, Categories, Locations
    foreach ($this->fieldMap as $field => $name) {
      $isList = $this->Media->Field->isListField($field);
      if ($isList) {
        $media['Field'][$field] = $this->_extractList($data, $name);
      } else {
        $media['Field'][$field] = $this->_extract($data, $name);
      }
    }

    // Associations to meta data: Groups
    $fileGroups = $this->_extract($data, 'PhtagrGroups');
    $media = $this->_readFileGroups($fileGroups, $media);

    return $media;
  }

  private function _readFileGroups($fileGroups, &$media) {
    if (!$fileGroups) {
      return $media;
    }
    $fileGroupNames = array_unique(preg_split('/\s*,\s*/', trim($fileGroups)));
    $user = $this->controller->getUser();
    $dbGroups = $this->Media->Group->find('all', array('conditions' => array('Group.name' => $fileGroupNames)));
    $dbGroupNames = Set::extract('/Group/name', $dbGroups);
    
    $mediaGroupIds = array();
    foreach ($fileGroupNames as $fileGroupName) {
      if (!in_array($fileGroupName, $dbGroupNames)) {
        // create missing group with restriced rights
        $group = $this->Media->Group->save($this->Media->Group->create(array('user_id' => $user['User']['id'], 'name' => $fileGroupName, 'description' => 'AUTO added group', 'is_hidden' => true, 'is_moderated' => true, 'is_shared' => false)));
        $mediaGroupIds[] = $group['Group']['id'];
      } else {
        $dbGroup = Set::extract("/Group[name=$fileGroupName]", $dbGroups);
        if (!$dbGroup) {
          Logger::err("Could not find group with name $fileGroupName in groups " . join(', ', Set::extract("/Group/name", $dbGroups)));
          continue;
        }
        $dbGroup = array_pop($dbGroup); // Set::extract returns always arrays
        if ($this->Media->Group->isAdmin($dbGroup, $user)) {
          $mediaGroupIds[] = $dbGroup['Group']['id'];
        } else if ($this->Media->Group->canSubscribe($dbGroup, $user)) {
          $this->Media->Group->subscribe($dbGroup, $user['User']['id']);
          $mediaGroupIds[] = $dbGroup['Group']['id'];
        }
      }
    }

    // Default acl group is assigned by media creation
    $media['Group']['Group'] = am($media['Group']['Group'], $mediaGroupIds);
    return $media;
  }

  private function _compute($value) {
    if ($value && preg_match('/(\d+)\/(\d+)/', $value, $m)) {
      return ($m[1] / $m[2]);
    } else {
      return $value;
    }
  }

  private function _computeGps($values) {
    if (!is_array($values) || count($values) < 3) {
      return $values;
    }

    $d = $this->_compute($values[0]);
    $m = $this->_compute($values[1]);
    $s = $this->_compute($values[2]);

    $v = floatVal($d + ($m / 60) + ($s / 3600));
    return $v;
  }

  private function _computeSutter($value) {
    if (!$value) {
      return $value;
    }

    $v = 1 / pow(2, $this->_compute($value));
    return $v;
  }

  /**
   * Extract the image data from the exif tool array and save it as Media
   *
   * @param data Data array from exif tool array
   * @return Array of the the image data array as image model data
   */
  private function _extractImageDataGetId3(&$media, &$data) {
    $user = $this->controller->getUser();

    $v =& $media['Media'];

    // Media information
    $v['name'] = $this->_extract($data, 'iptc/IPTCApplication/ObjectName/0', $this->_extract($data, 'filename'));
    // TODO Read IPTC date, than EXIF date
    $v['date'] = $this->_extractMediaDateGetId3($data);
    $v['width'] = $this->_extract($data, 'jpg/exif/COMPUTED/Width', 0);
    $v['height'] = $this->_extract($data, 'jpg/exif/COMPUTED/Height', 0);
    $v['duration'] = -1;
    $v['orientation'] = $this->_extract($data, 'jpg/exif/IFD0/Orientation', 1);

    $v['aperture'] = $this->_compute($this->_extract($data, 'jpg/exif/EXIF/ApertureValue', null));
    $v['shutter'] = $this->_computeSutter($this->_extract($data, 'jpg/exif/EXIF/ShutterSpeedValue', null));
    $v['model'] = $this->_extract($data, 'jpg/exif/IFD0/Model', null);
    $v['iso'] = $this->_extract($data, 'jpg/exif/EXIF/ISOSpeedRatings', null);
    //Logger::debug($data);

    // fetch GPS coordinates
    $latitude = $this->_computeGps($this->_extract($data, 'jpg/exif/GPS/GPSLatitude', null));
    $latitudeRef = $this->_extract($data, 'jpg/exif/GPS/GPSLatitudeRef', null);
    $longitude = $this->_computeGps($this->_extract($data, 'jpg/exif/GPS/GPSLongitude', null));
    $longitudeRef = $this->_extract($data, 'jpg/exif/GPS/GPSLongitudeRef', null);
    if ($latitude && $latitudeRef && $longitude && $longitudeRef) {
      if ($latitudeRef == 'S' && $latitude > 0) {
        $latitude *= -1;
      }
      if ($longitudeRef == 'W' && $longitude > 0) {
        $longitude *= -1;
      }
      $v['latitude'] = $latitude;
      $v['longitude'] = $longitude;
    }

    // Associations to meta data: Tags, Categories, Locations
    foreach ($this->fieldMap as $field => $name) {
      $isList = $this->Media->Field->isListField($field);
      $value = $this->_extract($data, "iptc/IPTCApplication/$name", array());
      if (!$value) {
        continue;
      }
      if (!$isList && is_array($value)) {
        $media['Field'][$field] = $value[0];
      } else {
        $media['Field'][$field] = $value;
      }
    }
    return $media;
  }

  /**
   * Write the meta data to an image file
   *
   * @param file File model data
   * @param media Media model data
   * @param options Array of options
   * @return mixed False on error
   */
  public function write(&$file, &$media, $options = array()) {
    if (!$file || !$media) {
      Logger::err("File or media is empty");
      return false;
    }
    if (!$this->controller->getOption('bin.exiftool')) {
      Logger::err("Exiftool is not defined. Abored writing of meta data");
      return false;
    }
    $filename = $this->controller->MyFile->getFilename($file);
    if (!file_exists($filename) || !is_writeable(dirname($filename)) || !is_writeable($filename)) {
      $id = isset($media['Media']['id']) ? $media['Media']['id'] : 0;
      Logger::warn("File: $filename (#$id) does not exists nor is readable");
      return false;
    }

    $data = $this->FilterManager->Exiftool->readMetaData($filename);
    if ($data === false) {
      Logger::warn("File has no metadata!");
      return false;
    }

    $args = $this->_createExportArguments($data, $media);
    if (!count($args)) {
      Logger::debug("File '$filename' has no metadata changes");
      if (!$this->Media->deleteFlag($media, MEDIA_FLAG_DIRTY)) {
        Logger::warn("Could not update image data of media {$media['Media']['id']}");
      }
      return true;
    }

    $tmp = $this->_getTempFilename($filename);
    $bin = $this->controller->getOption('bin.exiftool', 'exiftool');

    //ignore minor errors -the file could had minor errors before importing to phtagr,
    //consequently the write process will fail due to previous minor errors
    $args[] = '-m';

    //write in binary format, not human readable; exemple: for 'orientation' field
    $args[] = '-n';

    //generates new IPTCDigest code in order to 'help' adobe products to see that the file was modified
    $args[] = '-IPTCDigest=new';

    $args[] = '-o';
    $args[] = $tmp;
    $args[] = $filename;

    $result = $this->FilterManager->Exiftool->writeMetaData($filename, $tmp, $args);
    if (!$result) {
      return false;
    }
    $this->FilterManager->Exiftool->exitExiftool();//TODO move this line in parent controller before exit  and before shutdown

    $this->controller->MyFile->update($file);
    if (!$this->Media->deleteFlag($media, MEDIA_FLAG_DIRTY)) {
      $this->controller->warn("Could not update image data of media {$media['Media']['id']}");
    }
    return true;
  }

  /**
   * Creates the export arguments for date for IPTC if date information of the
   * file differs from the database entry
   *
   * @param data Meta data of the file
   * @param image Model data of the current image
   * @return array export arguments or an empty string
   * @note IPTC dates are set in the default timezone
   */
  private function _createExportDate($data, $media) {
    // Remove IPTC data and time if database date is not set
    $args = array();
    if (!$media['Media']['date']) {
      $args[] = '-IPTC:DateCreated-=';
      $args[] = '-TimeCreated-=';
      return '';
    }

    $timeDb = strtotime($media['Media']['date']);
    $timeFile = false;

    // Date priorities: IPTC, EXIF
    $dateIptc = $this->_extract($data, 'DateCreated');
      //correct possible reading from XMP: DateCreated instead of IPTC:DateCreated
      $dateIptc=substr($dateIptc,0,10);
    if ($dateIptc) {
      $time = $this->_extract($data, 'TimeCreated');
      if ($time) {
        $dateIptc .= ' '.$time;
      } else {
        //Midnight with timezone
        $dateIptc .= ' 00:00:00'.date('O');
      }
      $timeFile = strtotime($dateIptc);
    } else {
      $dateExif = $this->_extract($data, 'DateTimeOriginal');
      if ($dateExif) {
        $timeFile = strtotime($dateExif);
      }
    }

    if ($timeDb && (!$timeFile || ($timeFile != $timeDb))) {
      $args[] = '-IPTC:DateCreated=' . date("Y:m:d", $timeDb);
      $args[] = '-TimeCreated=' . date("H:i:sO", $timeDb);
      //Logger::trace("Set new date via IPTC: $arg");
    }
    return $args;
  }

  private function _createExportGps(&$data, &$media) {
    $args = array();

    $latitude = $this->_extract($data, 'GPSLatitude', null);
    $latitudeRef = $this->_extract($data, 'GPSLatitudeRef', null);
    $longitude = $this->_extract($data, 'GPSLongitude', null);
    $longitudeRef = $this->_extract($data, 'GPSLongitudeRef', null);

    if ($latitude && $latitudeRef && $longitude && $longitudeRef) {
      if ($latitudeRef == 'S' && $latitude > 0) {
        $latitude *= -1;
      }
      if ($longitudeRef == 'W' && $longitude > 0) {
        $longitude *= -1;
      }
    }

    $latitudeDb = $media['Media']['latitude'];
    if ($latitude != $latitudeDb) {
      if (!$latitudeDb) {
        $latitudeRef = '';
        $latitudeDb = '';
      } elseif ($latitudeDb < 0) {
        $latitudeRef = 'S';
        $latitudeDb *= -1;
      } else  {
        $latitudeRef = 'N';
      }
      $args[] = '-GPSLatitude=' . $latitudeDb;
      $args[] = '-GPSLatitudeRef=' . $latitudeRef;
    }

    $longitudeDb = $media['Media']['longitude'];
    if ($longitude != $longitudeDb) {
      if (!$longitudeDb) {
        $longitudeRef = '';
        $longitudeDb = '';
      } elseif ($longitudeDb < 0) {
        $longitudeRef = 'W';
        $longitudeDb *= -1;
      } else  {
        $longitudeRef = 'E';
      }
      $args[] = '-GPSLongitude=' . $longitudeDb;
      $args[] = '-GPSLongitudeRef=' . $longitudeRef;
    }
    return $args;
  }

  /**
   * Create generic export argument
   *
   * @param data Exif data
   * @param exifParam Exif parameter
   * @param currentValue Current value
   * @param removeIfEqual If set to true and currentValue is equal to fileValue
   * the flag will be removed
   * @return array Array of export arguments
   */
  private function _createExportArgument(&$data, $exifParam, $currentValue, $removeIfEqual = false) {
    $args = array();
    $fileValue = $this->_extract($data, $exifParam);
    if ($fileValue != $currentValue) {
      $args[] = "-$exifParam=$currentValue";
    } else if ($fileValue && $removeIfEqual) {
      $args[] = "-$exifParam=";
    }
    return $args;
  }

  /**
   * Create arguments to export the metadata from the database to the file.
   *
   * @param data metadata from the file (Exiftool information)
   * @param image Media data array
   */
  private function _createExportArguments(&$data, $media) {
    $args = array();
 
    $args = am($args, $this->_createExportDate($data, $media));
    $args = am($args, $this->_createExportGps($data, $media));

    $args = am($args, $this->_createExportArgument($data, 'ObjectName', $media['Media']['name'], true));
    $args = am($args, $this->_createExportArgument($data, 'Orientation', $media['Media']['orientation']));
    $args = am($args, $this->_createExportArgument($data, 'Comment', $media['Media']['caption']));

    $args = am($args, $this->_createExportArgumentsForFields($data, $media));
    $args = am($args, $this->_createExportArgumentsForGroups($data, $media));

    return $args;
  }

  private function _createExportArgumentsForFields(&$data, $media) {
    $args = array();
    // Associations to meta data: Tags, Categories, Locations
    foreach ($this->fieldMap as $field => $name) {
      $isList = $this->Media->Field->isListField($field);
      if ($isList) {
        $fileValue = $this->_extractList($data, $name);
      } else {
        $fileValue = $this->_extract($data, $name);
      }
      $dbValue = Set::extract("/Field[name=$field]/data", $media);
      if (!$isList) {
        $dbValue = array_pop($dbValue);
        if ($dbValue && $fileValue != $dbValue) {
          // write value if database value differs from file value
          // (file value does not exist or database value was changed)
          $args[] = "-$name=" . $dbValue;
        } elseif($fileValue && !$dbValue) {
          // remove file value if no database value is empty
          $args[] = "-$name=";
        }
      } else {
        foreach (array_diff($fileValue, $dbValue) as $del) {
          $args[] = "-$name-=" . $del;
        }
        foreach (array_diff($dbValue, $fileValue) as $add) {
          $args[] = "-$name+=" . $add;
        }
      }
    }

    return $args;
  }

  private function _createExportArgumentsForGroups(&$data, $media) {
    //add Groups to metadata xmp:   XMP-Phtagr:PhtagrGroups
    $fileGroups = $this->_extractList($data, 'PhtagrGroups');

    if (count($media['Group'])) {
      $dbGroups = Set::extract('/Group/name', $media);
    } else {
      $dbGroups = array();
    }

    $user = $this->controller->getUser();
    $allowedGroupNames = Set::extract('/Group/name', $this->controller->Media->Group->getGroupsForMedia($user));

    $args = array();
    foreach (array_diff($fileGroups, $dbGroups) as $del) {
      // do not erase existing, not allowed (yet) groups = delete only allowed groups
      if (in_array($del, $allowedGroupNames)) {
        $args[] = '-PhtagrGroups-=' . $del;
      }
    }

    foreach (array_diff($dbGroups, $fileGroups) as $add) {
      $args[] = '-PhtagrGroups+=' . $add;
    }
    return $args;
  }

  /**
   * Search for an given hash values by a key. If the key does not exists,
   * return the default value
   *
   * @param data Hash array
   * @param key Path or key of the hash value
   * @param default Default Value which will be return, if the key does not
   *        exists. Default value is null.
   * @return mixed The hash value or the default value, id hash key is not set
   */
  private function _extract(&$data, $key, $default = null) {
    $paths = explode('/', trim($key, '/'));
    $result = $data;
    foreach ($paths as $p) {
      if (!isset($result[$p])) {
        return $default;
      }
      $result =& $result[$p];
    }
    return $result;
  }

  private function _extractList(&$data, $key, $default = array()) {
    $value = $this->_extract($data, $key);
    if (!$value) {
      return $default;
    }
    $values = array_unique(preg_split('/\s*,\s*/', trim($value)));
    return $values;
  }

  /**
   * Generates a unique temporary filename
   *
   * @param filename Current filename
   */
  private function _getTempFilename($filename) {
    // create temporary file
    $tmp = "$filename.tmp";
    $count = 0;
    while (file_exists($tmp)) {
      $tmp = "$filename.$count.tmp";
      $count++;
    }
    return $tmp;
  }

  private function _readMetaDataGetId3($filename) {
    App::import('vendor', 'getid3/getid3');
    $getId3 = new getId3();
    // disable not required modules
    $getId3->option_tag_id3v1 = false;
    $getId3->option_tag_id3v2 = false;
    $getId3->option_tag_lyrics3 = false;
    $getId3->option_tag_apetag = false;

    $data = $getId3->analyze($filename);
    if (isset($data['error'])) {
      $this->FilterManager->addError($filename, 'VendorError', '', $data['error']);
      Logger::err("GetId3 analyzing error: {$data['error'][0]}");
      Logger::debug($data);
      return false;
    }
    return $data;
  }

/*
exiftool -S -n IMG_0498.jpg|sed -e 's/^/  [/' -e 's/: /] => "/' -e 's/$/"/'

array
(
  [ExifToolVersion] => "6.45"
  [FileName] => "IMG_0498.jpg"
  [FileSize] => "1156509"
  [FileModifyDate] => "2007:12:18 08:46:00"
  [FileType] => "JPEG"
  [MIMEType] => "image/jpeg"
  [Make] => "Canon"
  [Model] => "Canon DIGITAL IXUS 40"
  [Orientation] => "1"
  [XResolution] => "180"
  [YResolution] => "180"
  [ResolutionUnit] => "2"
  [ModifyDate] => "2005:06:23 21:03:48"
  [YCbCrPositioning] => "1"
  [ExposureTime] => "0.01666667"
  [FNumber] => "2.8"
  [ExifVersion] => "0220"
  [DateTimeOriginal] => "2005:06:23 21:03:48"
  [CreateDate] => "2005:06:23 21:03:48"
  [ComponentsConfiguration] => "..."
  [CompressedBitsPerPixel] => "3"
  [ShutterSpeedValue] => "0.0166740687605754"
  [ApertureValue] => "2.79795934507662"
  [MaxApertureValue] => "2.79795934507662"
  [Flash] => "25"
  [FocalLength] => "5.8"
  [MacroMode] => "2"
  [Self-timer] => "0"
  [Quality] => "3"
  [CanonFlashMode] => "1"
  [ContinuousDrive] => "0"
  [FocusMode] => "4"
  [CanonImageSize] => "0"
  [EasyMode] => "0"
  [DigitalZoom] => "0"
  [Contrast] => "0"
  [Saturation] => "0"
  [Sharpness] => "0"
  [CameraISO] => "Auto"
  [MeteringMode] => "3"
  [FocusRange] => "1"
  [AFPoint] => "16385"
  [CanonExposureMode] => "0"
  [LensType] => "-1"
  [LongFocal] => "17400"
  [ShortFocal] => "5800"
  [FocalUnits] => "1000"
  [MaxAperture] => "2.79795934507662"
  [MinAperture] => "5.59591869015324"
  [FlashBits] => "8200"
  [FocusContinuous] => "0"
  [AESetting] => "0"
  [ZoomSourceWidth] => "2272"
  [ZoomTargetWidth] => "2272"
  [PhotoEffect] => "0"
  [FocalType] => "2"
  [ScaledFocalLength] => "5800"
  [AutoISO] => "282.842712474619"
  [BaseISO] => "50"
  [MeasuredEV] => "-3.65625"
  [TargetAperture] => "2.79795934507662"
  [TargetExposureTime] => "0.0166740687605754"
  [ExposureCompensation] => "0"
  [WhiteBalance] => "0"
  [SlowShutter] => "0"
  [SequenceNumber] => "0"
  [FlashGuideNumber] => "6.25"
  [FlashExposureComp] => "0"
  [AutoExposureBracketing] => "0"
  [AEBBracketValue] => "0"
  [FocusDistanceUpper] => "2.84"
  [FocusDistanceLower] => "0"
  [BulbDuration] => "0"
  [CameraType] => "250"
  [AutoRotate] => "0"
  [NDFilter] => "0"
  [Self-timer2] => "0"
  [NumAFPoints] => "9"
  [CanonImageWidth] => "2272"
  [CanonImageHeight] => "1704"
  [CanonImageWidthAsShot] => "2272"
  [CanonImageHeightAsShot] => "284"
  [AFPointsUsed] => "0"
  [CanonImageType] => "IMG:DIGITAL IXUS 40 JPEG"
  [CanonFirmwareVersion] => "Firmware Version 1.00"
  [FileNumber] => "1040498"
  [OwnerName] => ""
  [CanonModelID] => "22282240"
  [UserComment] => ""
  [FlashpixVersion] => "0100"
  [ColorSpace] => "1"
  [ExifImageWidth] => "2272"
  [ExifImageLength] => "1704"
  [InteropIndex] => "R98"
  [InteropVersion] => "0100"
  [RelatedImageWidth] => "2272"
  [RelatedImageLength] => "1704"
  [FocalPlaneXResolution] => "10142.86"
  [FocalPlaneYResolution] => "10142.86"
  [FocalPlaneResolutionUnit] => "25.4"
  [SensingMethod] => "2"
  [FileSource] => "3"
  [CustomRendered] => "0"
  [ExposureMode] => "0"
  [DigitalZoomRatio] => "1"
  [SceneCaptureType] => "0"
  [Compression] => "6"
  [ThumbnailOffset] => "5120"
  [ThumbnailLength] => "4289"
  [ImageWidth] => "2272"
  [ImageHeight] => "1704"
  [Keywords] => "night, sebastian"
  [City] => "heidelberg"
  [Sub-location] => "neckarwiese"
  [Country-PrimaryLocationName] => "germany"
  [SupplementalCategories] => "party, pc"
  [Province-State] => "bw"
  [Aperture] => "2.8"
  [ConditionalFEC] => "0"
  [DriveMode] => "2"
  [FlashOn] => "1"
  [FlashType] => "0"
  [ISO] => "141.421356237309"
  [ImageSize] => "2272x1704"
  [Lens] => "5.8"
  [RedEyeReduction] => "0"
  [ScaleFactor35efl] => "6.08360904012188"
  [ShootingMode] => "10"
  [ShutterCurtainHack] => "0"
  [ShutterSpeed] => "0.01666667"
  [ThumbnailImage] => "(Binary data 4289 bytes, use -b option to extract)"
  [CircleOfConfusion] => "0.00493888749765297"
  [FocalLength35efl] => "35.2849324327069"
  [HyperfocalDistance] => "2.43258946878119"
  [LV] => "8.38204879878595"
  [Lens35efl] => "35.2849324327069"
)
*/
}
