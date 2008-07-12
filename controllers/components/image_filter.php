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

class ImageFilterComponent extends Object {

  var $controller = null;
  var $locationMap = array(
                        LOCATION_CITY => 'City', 
                        LOCATION_SUBLOCATION => 'Sub-location',
                        LOCATION_STATE => 'Province-State',
                        LOCATION_COUNTRY => 'Country-PrimaryLocationName');

  function startup(&$controller) {
    $this->controller =& $controller;
  }

  /** Read the meta data from the file 
   * @param image Image data model
   * @param filename Optional filename for import meta data
   * @return The image data array or False on error */
  function readFile($image, $filename = false) {
    if (!$filename)
      $filename = $this->controller->Image->getFilename($image);
    if (!file_exists($filename) || !is_readable($filename)) {
      $this->controller->Logger->warn("File: $filename does not exists nor is readable");
      $this->controller->Logger->trace($image);
      return false;
    }

    $meta = $this->_readMetaData($filename);
    if ($meta === false) {
      return false;
    }

    return $this->_extractImageData(&$image, $meta);
  }

  /** Read the meta data viea exiftool from a file
    * @param filename Filename to read 
    * @result Array of metadata or false on error */
  function _readMetaData($filename) {
    // read meta data
    $bin = $this->controller->getPreferenceValue('bin.exiftool', 'exiftool');
    $command = "$bin -S -n ".escapeshellarg($filename);
    $output = array();
    $result = -1;
    $t1 = getMicrotime();
    exec($command, &$output, &$result);
    $t2 = getMicrotime();
    $this->controller->Logger->trace("$bin call needed ".round($t2-$t1, 4)."ms");
    
    if ($result == 127) {
      $this->controller->Logger->err("$bin could not be found!");
      return false;
    } elseif ($result != 0) {
      $this->controller->Logger->err("$bin returned with error: $result (command: \"$command\")");
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
  function _extractImageDate($data) {
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

  /** Extract the image data from the exif tool array and save it as image 
   * @param data Data array from exif tool array 
   * @return Array of the the image data array as image model data 
   */
  function _extractImageData($image, $data) {
    $user = $this->controller->getUser();

    $v = &$image['Image'];

    // Image information
    $v['name'] = $this->_extract($data, 'FileName');
    // TODO Read IPTC date, than EXIF date
    $v['date'] = $this->_extractImageDate($data);
    if (!$this->controller->Image->isVideo($image)) {
      $v['width'] = $this->_extract($data, 'ImageWidth', 0);
      $v['height'] = $this->_extract($data, 'ImageHeight', 0);
      $v['duration'] = -1;
    }
    $v['orientation'] = $this->_extract($data, 'Orientation', 1);

    $v['aperture'] = $this->_extract($data, 'Aperture', NULL);
    $v['shutter'] = $this->_extract($data, 'ShutterSpeed', NULL);

    // fetch GPS coordinates
    $latitude = $this->_extract($data, 'GPSLatitude', null);
    $latitudeRef = $this->_extract($data, 'GPSLatitudeRef', null);
    $longitude = $this->_extract($data, 'GPSLongitude', null);
    $longitudeRef = $this->_extract($data, 'GPSLongitudeRef', null);
    if ($latitude && $latitudeRef && $longitude && $longitudeRef) {
      if ($latitudeRef == 'S')
        $latitude *= -1;
      if ($longitudeRef == 'W')
        $longitude *= -1;
      $v['latitude'] = $latitude; 
      $v['longitude'] = $longitude; 
    }
   
    // Associations to meta data: Tags, Categories, Locations
    $keywords = $this->_extract($data, 'Keywords');
    $ids = $this->controller->Tag->createIdListFromText($keywords, 'name', true);
    if (count($ids) > 0)
      $image['Tag']['Tag'] = am($ids, set::extract($image, 'Tag.{n}.id'));
    
    $categories = $this->_extract($data, 'SupplementalCategories');
    $ids = $this->controller->Category->createIdListFromText($categories, 'name', true);
    if (count($ids) > 0)
      $image['Category']['Category'] = am($ids, set::extract($image, 'Category.{n}.id'));
  
    // City, Sub-location, Province-State, Country-PrimaryLocationName
    $items = array();
    foreach ($this->locationMap as $type => $name) {
      $value = $this->_extract($data, $name);
      if ($value)
        $items[] = array('name' => $value, 'type' => $type);
    }
    $ids = $this->controller->Location->createIdList($items, true);
    if (count($ids) > 0)
      $image['Location']['Location'] = am($ids,  set::extract($image, 'Location.{n}.id'));

    return $image;
  }

  /** Write the meta data to an image file 
   * @param data Image data
   * @param filename Optional filename for export meta data
   * @return False on error */
  function writeFile($image, $filename = false) {
    if (!$filename)
      $filename = $this->controller->Image->getFilename($image);

    if (!file_exists($filename) || !is_writeable(dirname($filename)) || !is_writeable($filename)) {
      $this->controller->Logger->warn("File: $filename does not exists nor is readable");
      return false;
    }

    $data = $this->_readMetaData($filename);
    if ($data === false) {
      return false;
    }

    $args = $this->_createExportArguments($data, $image);
    if ($args == '') {
      $this->controller->Logger->debug("File '$filename' has no metadata changes");
      $update = array();
      $update['id'] = $image['Image']['id'];
      $update['flag'] = ($image['Image']['flag'] ^ IMAGE_FLAG_DIRTY) & 0xff;
      if (!$this->controller->Image->save(array("Image" => $update), true, array_keys($update)))
        $this->controller->warn("Could not update image data of image {$image['Image']['id']}");
      return true;
    }

    $tmp = $this->_getTempFilename($filename);
    $bin = $this->controller->getPreferenceValue('bin.exiftool', 'exiftool');
    $command = "$bin $args -o ".escapeshellarg($tmp).' '.escapeshellarg($filename);
    $this->controller->Logger->trace("Execute command: \"$command\"");
    $output = array();
    $result = -1;
    $t1 = getMicrotime();
    exec($command, &$output, &$result);
    $t2 = getMicrotime();
    $this->controller->Logger->trace("$bin call needed ".round($t2-$t1, 4)."ms");

    if ($result != 0 || !file_exists($tmp)) {
      $this->controller->Logger->err("$bin returns with error: $result (command: $command)");
      if (file_exists($tmp))
        unlink($tmp);
      return false;
    } else {
      $tmp2 = $this->_getTempFilename($filename);
      if (!rename($filename, $tmp2)) {
        $this->controller->Logger->err("Could not rename original file '$filename' to temporary file '$tmp2'");
        unlink($tmp);
        return false;
      }
      rename($tmp, $filename);
      unlink($tmp2);
    }
    
    // update new filesize and filetime
    @clearstatcache();
    $update = array();
    $update['id'] = $image['Image']['id'];
    if (!$this->controller->Image->isVideo($filename)) {
      $update['bytes'] = filesize($filename);
      $update['filetime'] = date("Y-m-d H:i:s", filemtime($filename));
    }
    $update['flag'] = ($image['Image']['flag'] ^ IMAGE_FLAG_DIRTY) & 0xff;
    if (!$this->controller->Image->save(array("Image" => $update), true, array_keys($update)))
      $this->controller->warn("Could not update image data of image {$image['Image']['id']}");
    return true;
  }

  /** Creates the export arguments for date for IPTC if date information of the
   * file differs from the database entry
    @param data Meta data of the file
    @param image Model data of the current image
    @return export arguments or an empty string */
  function _createExportDate($data, $image) {
    // Remove IPTC data and time if database date is not set
    if (!$image['Image']['date']) {
      $arg .= ' -DateCreated-= -TimeCreated-=';
      return '';
    }

    $timeDb = strtotime($image['Image']['date']);
    $timeFile = false;

    // Date priorities: IPTC, EXIF
    $dateIptc = $this->_extract($data, 'DateCreated');
    if ($dateIptc) {
      $time = $this->_extract($data, 'TimeCreated');
      if ($time) {
        $dateIptc .= ' '.$time;
      } else {
        $dateIptc .= ' 00:00:00';
      }
      $timeFile = strtotime($dateIptc);
    } else {
      $dateExif = $this->_extract($data, 'DateTimeOriginal');
      if ($dateExif) {
        $timeFile = strtotime($dateExif);
      }
    }

    $arg = '';
    if ($timeDb && (!$timeFile || ($timeFile != $timeDb))) {
      $arg .= ' -DateCreated='.escapeshellarg(date("Y:m:d", $timeDb));
      $arg .= ' -TimeCreated='.escapeshellarg(date("H:i:s", $timeDb));
      //$this->controller->Logger->trace("Set new date via IPTC: $arg");
    }
    return $arg;
  }

  /** Create arguments to export the metadata from the database to the file.
    * @param data metadata from the file (Exiftool information)
    * @param image Image data array */
  function _createExportArguments($data, $image) {
    $args = '';

    $args .= $this->_createExportDate($data, $image);

    // Associations to meta data: Tags, Categories, Locations
    $keywords = $this->_extract($data, 'Keywords');
    if ($keywords)
      $fileTags = preg_split('/\s*,\s*/', trim($keywords));
    else
      $fileTags = array();

    if (count($image['Tag']))
      $dbTags = Set::extract($image, "Tag.{n}.name");
    else
      $dbTags = array();

    foreach (array_diff($fileTags, $dbTags) as $del) 
      $args .= " -Keywords-=".escapeshellarg($del);
    foreach (array_diff($dbTags, $fileTags) as $add) 
      $args .= " -Keywords+=".escapeshellarg($add);

    $categories = $this->_extract($data, 'SupplementalCategories');
    if ($categories)
      $fileCategories = preg_split('/\s*,\s*/', trim($categories));
    else
      $fileCategories = array();

    if (count($image['Category']))
      $dbCategories = Set::extract($image, "Category.{n}.name");
    else
      $dbCategories = array();

    foreach (array_diff($fileCategories, $dbCategories) as $del) 
      $args .= " -SupplementalCategories-=".escapeshellarg($del);
    foreach (array_diff($dbCategories, $fileCategories) as $add) 
      $args .= " -SupplementalCategories+=".escapeshellarg($add);

    // Locations
    if (count($image['Location']))
      $dbLocations = Set::combine($image, "Location.{n}.type", "Location.{n}.name");
    else
      $dbLocations = array();

    foreach ($this->locationMap as $type => $name) {
      $fileValue = $this->_extract($data, $name);
      $dbValue = $this->_extract($dbLocations, $type);

      // DB overwrites file!
      if (!$fileValue && $dbValue) {
        $args .= " -$name=".escapeshellarg($dbValue);
      } elseif($fileValue && !$dbValue) {
        $args .= " -$name=";
      }
    }

    return $args;
  }

  /** Search for an given hash values by a key. If the key does not exists,
   * return the default value
   * @param data Hash array
   * @param key Key of the hash value
   * @param default Default Value which will be return, if the key does not
   *        exists. Default value is null.
   * @return The hash value or the default value, id hash key is not set */
  function _extract($data, $key, $default = null) {
    if (isset($data) && isset($data[$key]))
      return $data[$key];
    else
      return $default;
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
