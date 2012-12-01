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

class SidecarFilterComponent extends BaseFilterComponent {
  var $controller = null;
  var $components = array('Command', 'FileManager', 'Command', 'Exiftool');

  var $fieldMapIPTC = array(
      'keyword' => 'Keywords',
      'category' => 'SupplementalCategories',
      'sublocation' => 'Sub-location',
      'city' => 'City',
      'state' => 'Province-State',
      'country' => 'Country-PrimaryLocationName'
      );
  var $fieldMapXMP = array(
      'keyword' => 'Subject',
      'category' => 'SupplementalCategories',
      'sublocation' => 'Location',
      'city' => 'City',
      'state' => 'State',
      'country' => 'Country'
      );

  /**
   * Map of directory to File models
   *
   * @var array
   */
  var $fileCache = array();

  public function getName() {
    return "Sidecar";
  }

  public function getExtensions() {
    return array('xmp' => array('priority' => 5));
  }

  public function hasSidecar($filename, $createSidecar = false) {

    if (!$this->controller->getOption('xmp.use.sidecar', 0)) {
      return false;
    }

    $media = $this->MyFile->findByFilename($filename);
    $xmpFilename = substr($filename, 0, strrpos($filename, '.') + 1) . 'xmp';

    if (!file_exists($xmpFilename)){
      if ($createSidecar){
        $this->_createSidecar($filename, $media);
      } else {
        return false;
      }
    }

    $sidecar = $this->MyFile->findByFilename($xmpFilename);
    if (!$sidecar){
      $this->FileManager->add($xmpFilename);
    }
    $sidecar = $this->MyFile->findByFilename($xmpFilename);

    $mediaId = $media['Media']['id'];
    if (!isset($sidecar['media_id'])) {
      //add media to sidecar file
      $sidecar = $this->MyFile->findByFilename($xmpFilename);
      if (!$this->controller->MyFile->setMedia($sidecar, $mediaId)) {
        Logger::err("File was not saved: " . $xmpFilename);
        $this->FilterManager->addError($xmpFilename, "FileSaveError");
        return false;
      }
    } elseif ($mediaId !== $sidecar['File']['media_id']) {
      //it exists but does not belong to this media
      return false;
    }

    //$this->controller->MyFile->updateReaded($sidecar);
    $this->controller->MyFile->setFlag($sidecar, FILE_FLAG_DEPENDENT);

    return true;

  }

  private function _createSidecar($filename) {

    $xmpFilename = substr($filename, 0, strrpos($filename, '.') + 1) . 'xmp';

    //create sidecar xmp if missing
    if (!file_exists($xmpFilename)) {
      Logger::debug("Write sidecar: $xmpFilename");
      if (!is_writable(dirname($filename))) {
        Logger::warn("Cannot create file sidecar. Directory of media is not writeable");
      }

      $bin = $this->controller->getOption('bin.exiftool', 'exiftool');
      $args[] = "-tagsfromfile";
      $args[] = $filename;
      $args[] = $xmpFilename ;
      $result = $this->Command->run($bin, $args);

      if ($result != 0 || !file_exists($xmpFilename)) {
        Logger::err("$bin returns with error(xmp sidecar creation): $result");
      }
    }

    return $this->FileManager->add($xmpFilename);
  }

  /**
   * Lookup file from database to match filename to Media.
   *
   * @param string $path Path of file
   * @param string $filename Filename
   * @return mixed File model data or false if file was not found
   */
  private function _findFileInPath($path, $filename) {
    if (!isset($this->fileCache[$path])) {
      $this->fileCache[$path] = $this->controller->MyFile->findAllByPath($path);
    }
    foreach ($this->fileCache[$path] as $file) {
      if ($file['File']['file'] == $filename) {
        return $file;
      }
    }
    return false;
  }

  /**
   * Finds the MainFile of a sidecar
   *
   * @param video File model data of the video
   * @return media of the MainFile file. False if no MainFile file was found
   */
  public function _findMainFile($sidecar) {
    $sidecarFilename = $this->controller->MyFile->getFilename($sidecar);
    $path = dirname($sidecarFilename);
    $folder = new Folder($path);
    $pattern = basename($sidecarFilename);
    $ExtensionsList = $this->FilterManager->getExtensions();

    $pattern = substr($pattern, 0, strrpos($pattern, '.')+1).'('.implode($ExtensionsList, '|').')';
    $found = $folder->find($pattern);
    asort($found);
    if (!count($found)) {
      return false;
    }
    foreach ($found as $file) {
      if (is_readable(Folder::addPathElement($path, $file))) {
        $MainFile = $this->_findFileInPath($path, $file);
        if ($MainFile) {
          return $MainFile;
        }
      }
    }
    return false;
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
  public function read(&$file, &$media = null, $options = array(), &$pipes = null) {
    $options = am(array('noSave' => false), $options);
    $filename = $this->MyFile->getFilename($file);

    if (!$this->controller->MyFile->isType($file, FILE_TYPE_SIDECAR) ||
        !$this->controller->getOption('bin.exiftool') ||
        !$this->controller->getOption('xmp.use.sidecar', 0)) {
      return false;
    }
    $sidecar = $this->MyFile->findByFilename($filename);
    if (!$media){
      if (isset($sidecar['File']['media_id'])){
        $media = $this->Media->findById($sidecar['File']['media_id']);
      } else {
        //search if media can be attached
        $media = $this->_findMainFile($sidecar);
        if (!isset($media['Media']['id'])) {
          return false;
        } else {
          $mediaId = $media['Media']['id'];
          // attach sidecar file to media
          if (!$this->controller->MyFile->setMedia($sidecar, $mediaId)) {
            Logger::err("File was not saved: " . $filename);
            $this->FilterManager->addError($filename, "FileSaveError");
            return false;
          }
        }
      }
    }

    $meta = $this->Exiftool->readMetaData($filename);

    if ($meta === false) {
      $this->FilterManager->addError($filename, 'NoMetaDataFound');
      return false;
    }

    $this->_extractImageData($media, $meta);

    if ($options['noSave']) {
      return $media;
    } elseif (!$this->Media->save($media)) {
      Logger::err("Could not save Media");
      Logger::trace($media);
      $this->FilterManager->addError($filename, 'MediaSaveError');
      return false;
    }

    Logger::verbose("Updated media (id ".$media['Media']['id'].")");

    $this->controller->MyFile->update($file);//fix for permanent changed outside
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


  private function _getExifToolConf() {
    return APP . 'Config' . DS . 'ExifTool-phtagr.conf';
  }



  /**
   * Extracts the date of the file. It extracts the date of IPTC and EXIF.
   * IPTC has the priority.
   *
   * @param data Meta data
   * @return string Date of the meta data or now if not data information was found
   */
  private function _extractMediaDate($data) {

    //sample error in old code,caused by
    //the fact that both XMP and IPTC contain
    //a field named [DateCreated], with different purpose
    //IPTC:DateCreated => YYYY:MM:DD
    //XMP: DateCreated => YYYY:MM:DD HH:MM:SS, without timezone
    //IPTC:TimeCreated => HH:MM:SS.020+03:00
    //if only shortname [DateCreated] is extracted, without Group
    //value should be corrected (by eliminating time and zone) with =>substr([DateCreated],0,10);

    //exemple:if no IPTC data and with XMP:DateCreated
    //old code will try to read DateCreated(from XMP) + TimeCreated(missing),ie: 2012:08:11 16:16:10 00:00:00

    // EXIF date
    //'DateTimeOriginal' is from EXIF group, all cameras should write this field
    $date = $this->_extract($data, 'DateTimeOriginal');

    if ($date) {
      return $date;
    }

    // IPTC date
    $dateIptc = $this->_extract($data, 'DateCreated', null);
    $dateIptc = substr($dateIptc,0,10);
    if ($dateIptc) {
      $time = $this->_extract($data, 'TimeCreated', null);
      if ($time) {
        $dateIptc .= ' '.$time;
      } else {
        $dateIptc .= ' 00:00:00';
      }
      return $dateIptc;
    }
    // No EXIF or IPTC date: Extract file modification time, or NOW

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
    //if (isset($data['ObjectName'])) {
    $v['name'] = $this->_extract($data, 'ObjectName', $v['name']);
    $v['date'] = $this->_extractMediaDate($data);
    //$v['width'] = $this->_extract($data, 'ImageWidth', 0);
    //$v['height'] = $this->_extract($data, 'ImageHeight', 0);
    //$v['duration'] = -1;
    $v['orientation'] = $this->_extract($data, 'Orientation', $v['orientation']);

    $v['aperture'] = $this->_extract($data, 'Aperture', $v['aperture']);
    $v['shutter'] = $this->_extract($data, 'ShutterSpeed', $v['shutter']);
    $v['model'] = $this->_extract($data, 'Model', $v['model']);
    $v['iso'] = $this->_extract($data, 'ISO', $v['iso']);
    $v['caption'] = $this->_extract($data, 'Comment', $v['caption']);

    // fetch GPS coordinates
    $latitude = $this->_extract($data, 'GPSLatitude', $v['latitude']);
    $latitudeRef = $this->_extract($data, 'GPSLatitudeRef', null);
    $longitude = $this->_extract($data, 'GPSLongitude', $v['longitude']);
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

    if (isset($data['Keywords'])) {
      if (isset($data['Subject'])) {
        $data['Subject']=$data['Keywords'].",".$data['Subject'];
      } else {
        $data['Subject']=$data['Keywords'];
      }
    }
    // Associations to meta data: Tags, Categories, Locations
    foreach ($this->fieldMapXMP as $field => $name) {
      $isList = $this->Media->Field->isListField($field);
      if ($isList) {
        if (isset($data[$name])) {
          $media['Field'][$field] = $this->_extractList($data, $name);
        }
      } else {
        if (isset($data[$name])) {
          $media['Field'][$field] = $this->_extract($data, $name);
        }
      }
    }

    // Associations to meta data: Groups
    if (isset($data['PhtagrGroups'])) {
      $fileGroups = $this->_extract($data, 'PhtagrGroups');
      $media = $this->_readFileGroups($fileGroups, $media);
    }
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
    if (isset($media['Group']['Group'])) {
      $media['Group']['Group'] = am($media['Group']['Group'], $mediaGroupIds);
    } else {
      $media['Group']['Group'] =  $mediaGroupIds;
    }
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

  function reverse_strrchr($haystack, $needle) {
    //?????
    $pos = strrpos($haystack, $needle);
    if($pos === false) {
        return $haystack;
    }
    return substr($haystack, 0, $pos + 1);
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

    $data = $this->Exiftool->readMetaData($filename);
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

    //write in numerical format, not human readable; exemple: for 'orientation' field
    //$args[] = '-n';
    // write orienttation corrected in _createExportArgument
    //-b          (-binary)            Output data in binary format
    //-n          (--printConv)        Read/write numerical tag values, not passed through print Conversation; (not human readable)
    //without -n values are written in human readable format
    //tag names in the input JSON file may be suffixed with a # to disable print conversion
    //less errors if -b,-n options are not used and non numeric values are incorrectly formated (ex:dates)

    //generates new IPTCDigest code in order to 'help' adobe products to see that the file was modified
    $args[] = '-IPTCDigest=new';

    $args['-o'] = $tmp;
    $args[] = $filename;
    $result = $this->Command->run($bin, $args);
    clearstatcache(true, $tmp);

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
      clearstatcache(true, $filename);
    }

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

    // Date priorities: EXIF, IPTC
    $dateExif = $this->_extract($data, 'DateTimeOriginal');
    if ($dateExif) {
      $timeFile = strtotime($dateExif);
    } else {
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
      }
    }

    //http://php.net/manual/en/function.date.php
    //I (capital i) 	Whether or not the date is in daylight saving time 	1 if Daylight Saving Time, 0 otherwise.
    //O Difference to Greenwich time (GMT) in hours 	Example: +0200
    //P Difference to Greenwich time (GMT) with colon between hours and minutes (added in PHP 5.1.3) 	Example: +02:00
    if ($timeDb && (!$timeFile || ($timeFile != $timeDb))) {
      if ($this->controller->getOption('xmp.use.sidecar', 0)) {
        //in sidecar change only XMP - EXIF field
        $args[] = '-DateTimeOriginal=' . date("Y:m:d H:i:sP", $timeDb);
      } else {
        //in file change both EXIF and IPTC
        //$args[] = '-DateTimeOriginal=' . date("Y:m:d H:i:sO", $timeDb);
        $args[] = '-DateTimeOriginal=' . date("Y:m:d H:i:sP", $timeDb);
        $args[] = '-IPTC:DateCreated=' . date("Y:m:d", $timeDb);
        $args[] = '-TimeCreated=' . date("H:i:sO", $timeDb);
        //Logger::trace("Set new date via IPTC: $arg");
      }
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
      if ($exifParam === 'Orientation') {$exifParam=$exifParam.'#';}
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

    $args['-config'] = $this->_getExifToolConf();

    $args = am($args, $this->_createExportDate($data, $media));
    $args = am($args, $this->_createExportGps($data, $media));

    $args = am($args, $this->_createExportArgument($data, 'ObjectName', $media['Media']['name'], true));
    $args = am($args, $this->_createExportArgument($data, 'Orientation', $media['Media']['orientation']));
    $args = am($args, $this->_createExportArgument($data, 'UserComment', $media['Media']['caption']));

    $args = am($args, $this->_createExportArgumentsForFields($data, $media));
    $args = am($args, $this->_createExportArgumentsForGroups($data, $media));

    return $args;
  }

  private function _createExportArgumentsForFields(&$data, $media) {
    $args = array();
    // Associations to meta data: Tags, Categories, Locations
    foreach ($this->fieldMapXMP as $field => $name) {
      $isList = $this->Media->Field->isListField($field);
      if ($name === 'Subject') {
        $isList = true;
      }
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

    // Associations to sidecar XMP meta data: Tags(keywords)
    if ($this->controller->getOption('xmp.use.sidecar', 0)) {
      $fileTags = $this->_extractList($data, 'Keywords');
      $dbTags = Set::extract("/Field[name=keyword]/data", $media);
      //XMP-pdf:Keywords(not IPTC:keywords) is string formated, not as a bag, can only be changed entirely
      if (count(array_diff($fileTags, $dbTags)) or count(array_diff($dbTags, $fileTags))) {
        $keywords = join(',',$dbTags);
        $args[] = '-Keywords=' . $keywords;
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

}