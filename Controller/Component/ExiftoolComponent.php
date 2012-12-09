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

App::uses('Component', 'Controller');

class ExiftoolComponent extends Component {

  var $controller = null;
  var $components = array('Command');
  var $enableImportLogging = true;

  var $bin = false;             // exiftool binary
  var $hasOptionConfig = false;
  var $hasOptionStayOpen = false;

  // to be changed in case of old exiftool versions or problems with non UTF8 filenames...
  var $usePipes = true;
  var $debugging = false;//set to true to stop exiting exiftool after not reading pipes for 1 sec(during any debugging)

  var $process = null;
  var $stdin = null;
  var $stdout = null;
  var $stderr = null;

  var $fieldMap = array(
      'keyword' => 'Keywords',
      'keyword2' => 'Subject',
      'category' => 'SupplementalCategories',
      'sublocation' => 'Sub-location',
      'city' => 'City',
      'state' => 'Province-State',
      'country' => 'Country-PrimaryLocationName'
      );

  public function initialize(Controller $controller) {
    if ($this->controller) {
      // It is already initialized
      return;
    }
    $this->controller = $controller;
    $this->_readExiftoolVersion();
  }

  /**
   * Close open pipes on compoment shutdown.
   *
   * Shutdown will be called by the shutdownProcess of controller.
   *
   * For Tests: Please make sure that you will call Controller::shutdownProcess()
   * in tests when you use Exiftool component
   *
   * @param Controller $controller
   */
  public function shutdown(Controller $controller) {
    $this->exitExiftool();
    unset($this->bin);
    unset($this->controller);
  }

  /**
   * @return boolean True if exiftool is enabled
   */
  public function isEnabled() {
    return $this->bin != null;
  }

  /**
   * Reads the exiftool version and sets supported features
   */
  private function _readExiftoolVersion() {
    $this->bin = $this->controller->getOption('bin.exiftool');
    if (!$this->bin) {
      return false;
    }
    $result = $this->Command->run($this->bin, array('-ver'));
    $outputline = join("", $this->Command->output);
    // result is 0 for no error
    if ($result || !preg_match('/^(\d+)\.(\d+).*/', $outputline, $version)) {
      Logger::err("Unexpected result output of exiftool ({$this->bin}): Returnd $result with output: $output. Disable exiftool");
      $this->bin = false;
      return false;
    }

    // Exif version in $version[0]. major version in $version[1], minor version in $version[2]
    // -config since 7.98
    if ($version[1] > 7 || ($version[1] == 7 && $version[2] >= 98)) {
      $this->hasOptionConfig = true;
    }
    // -stay_open since 8.42
    if ($version[1] > 8 || ($version[1] == 8 && $version[2] >= 42)) {
      $this->hasOptionStayOpen = true;
    }
  }

  /**
   * Start exiftool process and open pipes
   *
   * @return bool True on success
   */
  private function _startExiftool() {

    if (!$this->usePipes || !$this->hasOptionStayOpen) {
      return false;
    }
    $descriptors = array(
      0 => array('pipe', 'r'),             // stdin
      1 => array('pipe', 'w'),             // stdout
      2 => array('pipe', 'w'),             // stderr
    );
    $cmd = $this->bin . ' -config '.escapeshellarg($this->_getExifToolConf()) . ' -stay_open 1 -@ -';
    //http://www.php.net/manual/en/function.proc-open.php
    $this->process = proc_open($cmd, $descriptors, $pipes);
    if (is_resource($this->process)) {
      //$this->FilterManager->ExiftoolData = array('process' => $process, 'pipes' => $pipes);
      $this->stdin = $pipes[0];
      $this->stdout = $pipes[1];
      $this->stderr = $pipes[2];
      stream_set_blocking($this->stdout, 0);
      stream_set_blocking($this->stderr, 0);
      return true;
    } else {
      Logger::warn("Could not open exiftool pipes");
      return false;
    }
  }

  /**
   * Check if exiftool is still open
   *
   * @return bool True if exiftool pipes are open
   */
  private function _isExiftoolOpen() {
    if (!$this->process) {
      return $this->_startExiftool();
    }
    return is_resource($this->process);
  }

  private function _writeCommands(&$pipe, $commands) {
    if (!is_resource($pipe)) {
      Logger::err("Invalid pipe. Command could not be written: " . join(', ', $commands));
      return;
    }
    try {
      foreach ($commands as $command) {
        fwrite($pipe, $command);
        fwrite($pipe, "\n");
      }
    } catch (Exception $e) {
      Logger::err("Writing error on pipe: " . $e->getMessage() . " Command could not be written: " . join(', ', $commands));
    }
  }

  /**
   * Close exiftool pipes and exit process
   */
  public function exitExiftool() {
    if (!$this->process) {
      return;
    }
    if (is_resource($this->process)) {
      //exiftool is opened with -stay_open option
      //exiftool can be closed
      $this->_writeCommands($this->stdin, array('-stay_open', 'False', '-execute', ''));
      //close pipes
      fclose($this->stdin);
      fclose($this->stdout);
      fclose($this->stderr);
      // ends process(handle)
      proc_close($this->process);
    }
    unset($this->process);
    unset($this->stdin);
    unset($this->stdout);
    unset($this->stderr);
  }

  /**
   * Read lines from given pipe
   *
   * @param resource $pipe Pipe to read
   * @param mixed $stopToken Top token to read. Default is false
   * @return array Lines
   */
  private function _readFromPipe(&$pipe, $stopToken = false) {
    if (!is_resource($pipe)) {
      return false;
    }
    $starttime = $processingTime = microtime(true);
    $timeoutInSec = 0.2;
    if ($this->debugging) {
      $timeoutInSec = 20;
    }

    $lines = array();
    while ($processingTime - $starttime < $timeoutInSec) {
      $line = fgets($pipe, 8192);  //1024? for speed?
      if ($line !== $stopToken && $line !== false) {
        // lines have \n appended. Trim white space at the end
        $lines[] = rtrim($line);
      } else if ($line === $stopToken) {
        break;
      }
      $processingTime = microtime(true);
    }
    return $lines;
  }

  /**
   * Read path of exiftool configuration file
   */
  private function _getExifToolConf() {
    return APP . 'Config' . DS . 'ExifTool-phtagr.conf';
  }

  /**
   * Read the meta data via exiftool from a file
   *
   * @param filename Filename to read
   * @result Array of metadata or false on error
   */
  public function readMetaData($filename) {
    if (!$this->bin) {
      return false;
    } else if ($this->_isExiftoolOpen()) {
      return $this->_readMetaDataPipes($filename);
    } else {
      return $this->_readMetaDataDirect($filename);
    }
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

  private function _readFileGroups($fileGroups, &$media) {
    if (!$fileGroups) {
      return $media;
    }
    $fileGroupNames = array_unique(preg_split('/\s*,\s*/', trim($fileGroups)));
    $user = $this->controller->getUser();
    $dbGroups = $this->controller->Media->Group->find('all', array('conditions' => array('Group.name' => $fileGroupNames)));
    $dbGroupNames = Set::extract('/Group/name', $dbGroups);

    $mediaGroupIds = array();
    foreach ($fileGroupNames as $fileGroupName) {
      if (!in_array($fileGroupName, $dbGroupNames)) {
        // create missing group with restriced rights
        $group = $this->controller->Media->Group->save($this->controller->Media->Group->create(array('user_id' => $user['User']['id'], 'name' => $fileGroupName, 'description' => 'AUTO added group', 'is_hidden' => true, 'is_moderated' => true, 'is_shared' => false)));
        $mediaGroupIds[] = $group['Group']['id'];
      } else {
        $dbGroup = Set::extract("/Group[name=$fileGroupName]", $dbGroups);
        if (!$dbGroup) {
          Logger::err("Could not find group with name $fileGroupName in groups " . join(', ', Set::extract("/Group/name", $dbGroups)));
          continue;
        }
        $dbGroup = array_pop($dbGroup); // Set::extract returns always arrays
        if ($this->controller->Media->Group->isAdmin($dbGroup, $user)) {
          $mediaGroupIds[] = $dbGroup['Group']['id'];
        } else if ($this->controller->Media->Group->canSubscribe($dbGroup, $user)) {
          $this->controller->Media->Group->subscribe($dbGroup, $user['User']['id']);
          $mediaGroupIds[] = $dbGroup['Group']['id'];
        }
      }
    }

    // Default acl group is assigned by media creation
    if (!isset($media['Group']['Group'])){
      $media['Group']['Group'] = array();
    }
    $media['Group']['Group'] = am($media['Group']['Group'], $mediaGroupIds);
    return $media;
  }

  /**
   * Extract the image data from the exif tool array and save it as Media
   *
   * @param data Data array from exif tool array
   * @return Array of the the image data array as image model data
   */
  public function extractImageData(&$media, &$data) {
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

    //merge Keywords and Subject
    if (isset($data['Subject'])) {
      if (isset($data['Keywords'])) {
        $data['Keywords']=$data['Subject'].",".$data['Keywords'];
      } else {
        $data['Keywords']=$data['Subject'];
      }
    } elseif (isset($data['Keywords'])) {
      $data['Subject'] = $data['Keywords'];
    }

    // Associations to meta data: Tags, Categories, Locations
    foreach ($this->fieldMap as $field => $name) {
      //hack to allow two names with the same key (field)
      if ($field === 'keyword2') {
        $field = 'keyword';
        $isList = true;
      }
      $isList = $this->controller->Media->Field->isListField($field);
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

  /**
   * Read the meta data via exiftool, through pipes, using -stay_open option
   * avoid perl start-up time needed each exiftool call
   *
   * @param string $filename Filename to read
   * @result Array of metadata or false on error
   */
  private function _readMetaDataPipes($filename)  {
    $starttime = microtime(true);
    //TODO: use exiftool arg -json and json_decode ( string $json) OR exiftool arg -php and eval($array_string)

    //fwrite($this->stdin, "-json\n");

    //next line is not really necessary; good for reading over slow networks
    $this->_writeCommands($this->stdin, array('-fast2'));

    // comment next lines in order to read all metadata, not only these fields
    $base = array('-Error', '-Warning', '-FileName', '-ImageWidth', '-ImageHeight', '-ObjectName', '-DateTimeCreated', '-SubSecDateTimeOriginal', '-SubSecCreateDate');
    //for numerical Orientation a # can be used as field suffix: -Orientation#
    $exif = array('-Orientation#', '-Aperture', '-ShutterSpeed', '-Model', '-ISO', '-Comment', '-UserComment');
    $gps = array('-GPSLatitude#', '-GPSLatitudeRef', '-GPSLongitude#', '-GPSLongitudeRef');
    //read only IPTC:DateCreated for avoiding confusion with XMP-photoshop:DateCreated = Date + Time + zone
    //IPTC - location
    $iptc = array('-IPTC:DateCreated', '-TimeCreated', '-DateTimeOriginal', '-FileModifyDate', '-SupplementalCategories', '-Keywords', '-Subject', '-City', '-Sub-location', '-Province-State', '-Country-PrimaryLocationName', '-Caption');
    //XMP - location
    $xmp = array('-Location', '-State', '-Country', '-PhtagrGroups');
    $video = array('-Width', '-Height', '-Duration');

    $this->_writeCommands($this->stdin, $base);
    $this->_writeCommands($this->stdin, $exif);
    $this->_writeCommands($this->stdin, $gps);
    $this->_writeCommands($this->stdin, $iptc);
    $this->_writeCommands($this->stdin, $xmp);
    $this->_writeCommands($this->stdin, $video);

    //-b          (-binary)            Output data in binary format
    //-n          (--printConv)        Read/write numerical tag values, not passed through print Conversation; (not human readable)
    // without -n values are readed in 'human readable' format
    // tag names in the input JSON file may be suffixed with a # to disable print conversion
    // exiftool -Orientation# -Orientation -S a.jpg
    // Orientation: 6
    // Orientation: Rotate 90 CW

    //numerical values format, not human readable; exemple: for 'orientation' field
    //fwrite($pipes[0], "-n\n");

    $this->_writeCommands($this->stdin, array('-S', $filename, '', '-execute'));

    //"-executeNUMBER\n" can be utilized to obtain {readyNUMBER} on eof stdout

    $stdout = $this->_readFromPipe($this->stdout, "{ready}\n");
    $stderr = $this->_readFromPipe($this->stderr);

    if (count($stderr) > 1 || (count($stderr) && !($stderr[0] === false))) {// and count($stdout) !==1//i.e.="    1 image files created "
      //TODO: test if warnings and original file internal errors are reported on stderr or stdout
      $errors = implode(",", $stderr);
      Logger::err(am("exiftool stderr returned errors: ",$errors));
    }

    if ($this->enableImportLogging) {
      $processingtime = round((microtime(true)-$starttime),4); //seconds
      $this->log("----- read with PIPES. Just exiftool read time: ".$processingtime, 'import_memory_speed');
    }
    //parse results
    $data = array();
    foreach ($stdout as $line) {
      list($name, $value) = preg_split('/:(\s+|$)/', $line);
      $data[$name] = $value;
      //TODO - log fields Error and Warning
    }

    return $data;
  }


  /**
   * Read the meta data via exiftool from a file
   *
   * @param string $filename Filename to read
   * @result array Array of metadata or false on error
   */
  private function _readMetaDataDirect($filename) {
    $starttime = microtime(true);
    // read meta data
    $args = array();
    if ($this->hasOptionConfig) {
      $args = am($args, array('-config', $this->_getExifToolConf()));
    }
    $args = am($args, array('-S', '-n', $filename));
    $result = $this->Command->run($this->bin, $args);
    $output = $this->Command->output;
    if ($result == 127) {
      Logger::err("$bin could not be found!");
      return false;
    } elseif ($result != 0) {
      Logger::err("$bin returned with error: $result (command: '{$this->Command->lastCommand}')");
      return false;
    }
    if ($this->enableImportLogging) {
      $processingtime = round((microtime(true)-$starttime),4); //seconds
      $this->log("----- read directly with exiftool. Just exiftool read time: ".$processingtime, 'import_memory_speed');
    }
    $data = array();
    foreach ($output as $line) {
      list($name, $value) = preg_split('/:(\s+|$)/', $line);
      $data[$name] = $value;
    }
    return $data;
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
  public function createExportArguments(&$data, &$media, $filename) {
    $args = array();

    $args = am($args, $this->_createExportDate($data, $media));
    $args = am($args, $this->_createExportGps($data, $media));

    $args = am($args, $this->_createExportArgument($data, 'ObjectName', $media['Media']['name'], true));
    $args = am($args, $this->_createExportArgument($data, 'Orientation', $media['Media']['orientation']));
    $args = am($args, $this->_createExportArgument($data, 'Comment', $media['Media']['caption']));

    $args = am($args, $this->_createExportArgumentsForFields($data, $media));
    $args = am($args, $this->_createExportArgumentsForGroups($data, $media));

    if (!count($args)) {
      return $args;
    }

    //ignore minor errors -the file could had minor errors before importing to phtagr,
    //consequently the write process will fail due to previous minor errors
    $args[] = '-m';

    //write in binary format, not human readable; exemple: for 'orientation' field
    $args[] = '-n';

    //generates new IPTCDigest code in order to 'help' adobe products to see that the file was modified
    $args[] = '-IPTCDigest=new';

    $args[] = '-overwrite_original';
    $args[] = $filename;

    return $args;
  }

  private function _createExportArgumentsForFields(&$data, $media) {
    $args = array();
    // Associations to meta data: Tags, Categories, Locations
    foreach ($this->fieldMap as $field => $name) {
      $isList = $this->controller->Media->Field->isListField($field);
      //hack to allow two names with the same key (field)
      if ($field === 'keyword2') {
        $field = 'keyword';
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
   * Write the meta data to given file
   *
   * @param string $filename Filename
   * @param array $args Arguments for exiftool including filename
   * @return mixed True on no error
   */
  public function writeMetaData($filename, $args) {
    if (!$this->bin) {
      return false;
    } else if ($this->_isExiftoolOpen()) {
      $result = $this->_writeMetaDataPipes($args);
    } else {
      $result = $this->_writeMetaDataDirect($args);
    }
    clearstatcache(true, $filename);
    return $result;
  }


  /**
   * Write the meta data to an image file, through pipes
   *
   * @param filename Filename
   * @param args Arguments for exiftool
   * @return
   */
  private function _writeMetaDataPipes(&$args) {

    $this->_writeCommands($this->stdin, $args);
    $this->_writeCommands($this->stdin, array('-execute'));

    $this->_readFromPipe($this->stdout, "{ready}\n");
    $stderr = $this->_readFromPipe($this->stderr);

    if (count($stderr) > 1 || (count($stderr) && !($stderr[0] === false))) {// and count($stdout) !==1//i.e.="    1 image files created "
      //TODO: test if warnings and original file internal errors are reported on stderr or stdout
      $errors = implode(",", $stderr);
      Logger::err(am("exiftool stderr returned errors: ",$errors));
      return $errors;
    }

    return true;
  }


  /**
   * Write the meta data to an image file, directly calling exiftool
   *
   * @param array $args Arguments for exiftool
   * @return
   */
  private function _writeMetaDataDirect($args) {
    if ($this->hasOptionConfig) {
      $args = am(array('-config', $this->_getExifToolConf()), $args);
    }

    $result = $this->Command->run($this->bin, $args);
    $output = $this->Command->output;
    if ($result == 127) {
      Logger::err("{$this->bin} could not be found!");
      return false;
    } elseif ($result != 0) {
      $errors = implode(",", $output);
      Logger::err("{$this->bin} returned with error: $result (command: '{$this->Command->lastCommand}'). Errors: $errors");
      return false;
    }
    return true;
  }

  /**
   * Clear image metadata from a file
   *
   * @param filename Filename to file to clean
   */
  public function clearMetaData($filename) {
    if (!$this->bin) {
      return;
    } else if (!file_exists($filename)) {
      Logger::err("Filename '$filename' does not exists");
      return;
    } else if (!is_writeable($filename)) {
      Logger::err("Filename '$filename' is not writeable");
      return;
    }
    $args = array('-all=', '-overwrite_original', $filename);
    if ($this->_isExiftoolOpen()) {
      $result = $this->_writeMetaDataPipes($args);
    } else {
      $result = $this->Command->run($this->bin, $args);
    }
    if ($result != 0) {
      Logger::err("Cleaning of meta data of file '$filename' failed");
      return false;
    }
    Logger::debug("Cleaned meta data of '$filename'");
    return true;
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
