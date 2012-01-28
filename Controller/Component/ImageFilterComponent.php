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

class ImageFilterComponent extends BaseFilterComponent {
  var $controller = null;
  var $components = array('Command');

  var $locationMap = array(
                        LOCATION_CITY => 'City', 
                        LOCATION_SUBLOCATION => 'Sub-location',
                        LOCATION_STATE => 'Province-State',
                        LOCATION_COUNTRY => 'Country-PrimaryLocationName');

  function getName() {
    return "Image";
  }

  function getExtensions() {
    return array('jpeg', 'jpg');
  }

  /** Read the meta data from the file 
   * @param file File model data
   * @param media Reference of Media model data
   * @param options Options
   *  - noSave if set dont save model data
   * @return The image data array or False on error */
  function read($file, &$media, $options = array()) {
    $options = am(array('noSave' => false), $options);
    $filename = $this->MyFile->getFilename($file);

    if ($this->controller->getOption('bin.exiftool')) {
      $meta = $this->_readMetaData($filename);
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
      $media = $this->controller->Media->addDefaultAcl(&$media, &$user);
      
      $isNew = true;
    };

    if ($this->controller->getOption('bin.exiftool')) {
      $this->_extractImageData(&$media, $meta);
    } else {
      $this->_extractImageDataGetId3(&$media, $meta);
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

  /** Clear image metadata from a file
    @param filename Filename to file to clean */
  function clearMetaData($filename) {
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

  /** Read the meta data viea exiftool from a file
    * @param filename Filename to read 
    * @result Array of metadata or false on error */
  function _readMetaData($filename) {
    if (!$this->controller->getOption('bin.exiftool')) {
      return false;
    }
    // read meta data
    $bin = $this->controller->getOption('bin.exiftool', 'exiftool');
    $result = $this->Command->run($bin, array('-S', '-n', $filename));
    $output = $this->Command->output;
    if ($result == 127) {
      Logger::err("$bin could not be found!");
      return false;
    } elseif ($result != 0) {
      Logger::err("$bin returned with error: $result (command: \"$command\")");
      return false;
    }

    $data = array();
    foreach ($output as $line) {
      list($name, $value) = preg_split('/:(\s+|$)/', $line);
      $data[$name] = $value;
    }
    return $data;
  }

  /** Extracts the date of the file. It extracts the date of IPTC and EXIF.
   * IPTC has the priority. 
    @param data Meta data 
    @return Date of the meta data or now if not data information was found */
  function _extractMediaDateGetId3($data) {
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

  /** Extracts the date of the file. It extracts the date of IPTC and EXIF.
   * IPTC has the priority. 
    @param data Meta data 
    @return Date of the meta data or now if not data information was found */
  function _extractMediaDate($data) {
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
    // No IPTC date: Extract Exif date or now
    return $this->_extract($data, 'DateTimeOriginal', date('Y-m-d H:i:s', time()));
  }

  /** Extract the image data from the exif tool array and save it as Media
   * @param data Data array from exif tool array 
   * @return Array of the the image data array as image model data 
   */
  function _extractImageData($media, $data) {
    $user = $this->controller->getUser();

    $v = &$media['Media'];

    // Media information
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
    $keywords = $this->_extract($data, 'Keywords');
    $ids = $this->controller->Tag->createIdListFromText($keywords, 'name', true);
    if (count($ids) > 0)
      $media['Tag']['Tag'] = am($ids, set::extract($media, 'Tag.{n}.id'));
    
    $categories = $this->_extract($data, 'SupplementalCategories');
    $ids = $this->controller->Category->createIdListFromText($categories, 'name', true);
    if (count($ids) > 0)
      $media['Category']['Category'] = am($ids, set::extract($media, 'Category.{n}.id'));
  
    // City, Sub-location, Province-State, Country-PrimaryLocationName
    $items = array();
    foreach ($this->locationMap as $type => $name) {
      $value = $this->_extract($data, $name);
      if ($value)
        $items[] = array('name' => $value, 'type' => $type);
    }
    $ids = $this->controller->Location->createIdList($items, true);
    if (count($ids) > 0)
      $media['Location']['Location'] = am($ids,  set::extract($media, 'Location.{n}.id'));

    return $media;
  }

  function _compute($value) {
    if ($value && preg_match('/(\d+)\/(\d+)/', $value, $m)) {
      return ($m[1] / $m[2]);
    } else {
      return $value;
    }
  }

  function _computeGps($values) {
    if (!is_array($values) || count($values) < 3) {
      return $values;
    }

    $d = $this->_compute($values[0]);
    $m = $this->_compute($values[1]);
    $s = $this->_compute($values[2]); 
      
    $v = floatVal($d + ($m / 60) + ($s / 3600));
    return $v;
  }

  function _computeSutter($value) {
    if (!$value) {
      return $value;
    }

    $v = 1 / pow(2, $this->_compute($value));
    return $v;
  }

  /** Extract the image data from the exif tool array and save it as Media
   * @param data Data array from exif tool array 
   * @return Array of the the image data array as image model data 
   */
  function _extractImageDataGetId3($media, $data) {
    $user = $this->controller->getUser();

    $v = &$media['Media'];

    // Media information
    $v['name'] = $this->_extract($data, 'filename');
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
    $v['caption'] = $this->_extract($data, 'jpg/exif/EXIF/ISOSpeedRatings', null);

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
    $keywords = $this->_extract($data, 'iptc/IPTCApplication/Keywords', array());
    $ids = $this->controller->Tag->createIdListFromText(implode(',', $keywords), 'name', true);
    if (count($ids) > 0)
      $media['Tag']['Tag'] = am($ids, set::extract($media, 'Tag.{n}.id'));
    
    $categories = $this->_extract($data, 'iptc/IPTCApplication/SupplementalCategories', array());
    $ids = $this->controller->Category->createIdListFromText(implode(',', $categories), 'name', true);
    if (count($ids) > 0)
      $media['Category']['Category'] = am($ids, set::extract($media, 'Category.{n}.id'));
  
    // City, Sub-location, Province-State, Country-PrimaryLocationName
    $items = array();
    foreach ($this->locationMap as $type => $name) {
      $value = $this->_extract($data, "iptc/IPTCApplication/$name/0");
      if ($value)
        $items[] = array('name' => $value, 'type' => $type);
    }
    $ids = $this->controller->Location->createIdList($items, true);
    if (count($ids) > 0)
      $media['Location']['Location'] = am($ids,  set::extract($media, 'Location.{n}.id'));

    return $media;
  }

  /** Write the meta data to an image file 
   * @param file File model data
   * @param media Media model data
   * @param options Array of options
   * @return False on error */
  function write($file, $media = null, $options = array()) {
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

    $data = $this->_readMetaData($filename);
    if ($data === false) {
      Logger::warn("File has no metadata!");
      return false;
    }

    $args = $this->_createExportArguments($data, $media);
    if (!count($args)) {
      Logger::debug("File '$filename' has no metadata changes");
      if (!$this->Media->deleteFlag($media, MEDIA_FLAG_DIRTY)) {
        $this->controller->warn("Could not update image data of media {$media['Media']['id']}");
      }
      return true;
    }

    $tmp = $this->_getTempFilename($filename);
    $bin = $this->controller->getOption('bin.exiftool', 'exiftool');
    $args['-o'] = $tmp;
    $args[] = $filename;
    $result = $this->Command->run($bin, $args);

    if ($result != 0 || !file_exists($tmp)) {
      Logger::err("$bin returns with error: $result");
      if (file_exists($tmp)) {
        @unlink($tmp);
      }
      return false;
    } else {
      $tmp2 = $this->_getTempFilename($filename);
      if (!rename($filename, $tmp2)) {
        Logger::err("Could not rename original file '$filename' to temporary file '$tmp2'");
        @unlink($tmp);
        return false;
      }
      rename($tmp, $filename);
      @unlink($tmp2);
    }
    
    $this->controller->MyFile->update($file);
    if (!$this->Media->deleteFlag($media, MEDIA_FLAG_DIRTY)) {
      $this->controller->warn("Could not update image data of media {$media['Media']['id']}");
    }
  }

  /** Creates the export arguments for date for IPTC if date information of the
   * file differs from the database entry
    @param data Meta data of the file
    @param image Model data of the current image
    @return export arguments or an empty string 
    @note IPTC dates are set in the default timezone */
  function _createExportDate($data, $media) {
    // Remove IPTC data and time if database date is not set
    $args = array();
    if (!$media['Media']['date']) {
      $args[] = '-DateCreated-=';
      $args[] = '-TimeCreated-=';
      return '';
    }

    $timeDb = strtotime($media['Media']['date']);
    $timeFile = false;

    // Date priorities: IPTC, EXIF
    $dateIptc = $this->_extract($data, 'DateCreated');
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
      $args[] = '-DateCreated=' . date("Y:m:d", $timeDb);
      $args[] = '-TimeCreated=' . date("H:i:sO", $timeDb);
      //Logger::trace("Set new date via IPTC: $arg");
    }
    return $args;
  }

  function _createExportGps(&$data, &$media) {
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
    * @return Array of export arguments
    */
  function _createExportArgument(&$data, $exifParam, $currentValue) {
    $args = array();
    $fileValue = $this->_extract($data, $exifParam);
    if ($fileValue != $currentValue) {
      $args[] = "-$exifParam=$currentValue";
    }
    return $args;
  }

  /** Create arguments to export the metadata from the database to the file.
    * @param data metadata from the file (Exiftool information)
    * @param image Media data array */
  function _createExportArguments($data, $media) {
    $args = array();

    $args = am($args, $this->_createExportDate($data, $media));
    $args = am($args, $this->_createExportGps($data, $media));
    
    $args = am($args, $this->_createExportArgument(&$data, 'Orientation', $media['Media']['orientation']));
    $args = am($args, $this->_createExportArgument(&$data, 'Comment', $media['Media']['caption']));

    // Associations to meta data: Tags, Categories, Locations
    $keywords = $this->_extract($data, 'Keywords');
    if ($keywords) {
      $fileTags = array_unique(preg_split('/\s*,\s*/', trim($keywords)));
    } else {
      $fileTags = array();
    }

    if (count($media['Tag'])) {
      $dbTags = Set::extract($media, "Tag.{n}.name");
    } else {
      $dbTags = array();
    }

    foreach (array_diff($fileTags, $dbTags) as $del) {
      $args[] = '-Keywords-=' . $del;
    }
    foreach (array_diff($dbTags, $fileTags) as $add) {
      $args[] = '-Keywords+=' . $add;
    }

    $categories = $this->_extract($data, 'SupplementalCategories');
    if ($categories) {
      $fileCategories = array_unique(preg_split('/\s*,\s*/', trim($categories)));
    } else {
      $fileCategories = array();
    }

    if (count($media['Category'])) {
      $dbCategories = Set::extract($media, "Category.{n}.name");
    } else {
      $dbCategories = array();
    }
    foreach (array_diff($fileCategories, $dbCategories) as $del) {
      $args[] = '-SupplementalCategories-=' . $del;
    }
    foreach (array_diff($dbCategories, $fileCategories) as $add) {
      $args[] = '-SupplementalCategories+=' . $add;
    }
    // Locations
    if (count($media['Location'])) {
      $dbLocations = Set::combine($media, "Location.{n}.type", "Location.{n}.name");
    } else {
      $dbLocations = array();
    }

    foreach ($this->locationMap as $type => $name) {
      $fileValue = $this->_extract($data, $name);
      $dbValue = $this->_extract($dbLocations, $type);

      // DB overwrites file!
      if (!$fileValue && $dbValue) {
        $args[] = "-$name=" . $dbValue;
      } elseif($fileValue && !$dbValue) {
        $args[] = "-$name=";
      }
    }

    return $args;
  }

  /** Search for an given hash values by a key. If the key does not exists,
   * return the default value
   * @param data Hash array
   * @param key Path or key of the hash value
   * @param default Default Value which will be return, if the key does not
   *        exists. Default value is null.
   * @return The hash value or the default value, id hash key is not set */
  function _extract($data, $key, $default = null) {
    $paths = explode('/', trim($key, '/'));
    $result =& $data;
    foreach ($paths as $p) {
      if (!isset($result[$p])) {
        return $default;
      }
      $result =& $result[$p];
    }
    return $result;
  }

  /** Generates a unique temporary filename
    * @param filename Current filename
    */
  function _getTempFilename($filename) {
    // create temporary file
    $tmp = "$filename.tmp";
    $count = 0;
    while (file_exists($tmp)) {
      $tmp = "$filename.$count.tmp";
      $count++;
    }
    return $tmp;
  }
  
  function _readMetaDataGetId3($filename) {
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

?>
