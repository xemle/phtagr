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

class ExiftoolComponent extends BaseFilterComponent {
  var $controller = null;
  var $components = array('Command');

  public function getName() {
    return "Exiftool";
  }

  public function getExtensions() {
    return array('abcdef');
  }

  /**
   * Start exiftool process and open pipes
   */
  private function _startExiftool() {
    
    $usePipes = true; //to be changed in case of old exiftool versions or problems with non UTF8 filenames...
    if (!$usePipes || !$this->controller->getOption('bin.exiftool')) {
      return false;
    }
    $descriptors = array(
      0 => array('pipe', 'r'),             // stdin
      1 => array('pipe', 'w'),             // stdout
      2 => array('pipe', 'w'),             // stderr
    );
    $bin = $this->controller->getOption('bin.exiftool', 'exiftool');
    $cmd = $bin.' -config '.$this->_getExifToolConf().' -stay_open 1 -@ -';
    //http://www.php.net/manual/en/function.proc-open.php
    $process = proc_open($cmd, &$descriptors, &$pipes);
    if (is_resource($process)) {
      //$this->FilterManager->ExiftoolData = array('process' => $process, 'pipes' => $pipes);
      $this->ExiftoolData = array('process' => $process, 'pipes' => $pipes);
      return true;
    } else {
      return false;
    }
  }

  /**
   * Check if exiftool is still open
   */
  private function _IsOpenExiftool() {
    $ExiftoolData = $this->ExiftoolData;
    $process = $ExiftoolData['process'];
    if (is_resource($process)) {
      return true;
    } else {
      return $this->_startExiftool();
    }
  }

  /**
   * Close exiftool pipes and exit process
   */
  public function exitExiftool() {  
    //TODO: auto call exitExiftool before exit FilterManager and before shutdown (in case it  hangs up)
    $ExiftoolData = $this->ExiftoolData;
    $pipes = $ExiftoolData['pipes'];
    $process = $ExiftoolData['process'];
    if (is_resource($process)) {
      //exiftool is opened with -stay_open option
      //exiftool can be closed
      fwrite($pipes[0], "-stay_open\nFalse\n");
      fwrite($pipes[0], "-execute");
      fwrite($pipes[0], "\n");
      //close pipes
      fclose($pipes[0]);
      fclose($pipes[1]);
      fclose($pipes[2]);
      // ends process(handle)
      proc_close($process);
      $this->ExiftoolData = null;
    }
  }
  
  /**
   * Read exiftool Stdout pipe 
   */
  private function _readStdout($pipes)  {
    $starttime = microtime(true);
    $current_line = '';
    $stdout = array();
    stream_set_blocking($pipes[1], 0);//just as a precaution
    while ($current_line !== "{ready}\n") {
      //TODO exit loop after 2 sec if not processed
      $current_line  = fgets($pipes[1], 8192);  //1024? for speed?
      if ($current_line !== "{ready}\n" and $current_line !== false) {
        //$stdout .= fread($pipes[1], 16384); //for up to lenght 8192 
        $stdout[] = $current_line;
      }
      $processingtime = round((microtime(true)-$starttime),4); //seconds
      if ($processingtime>1){
        //increase for big video files?
        //probabily blocked
        $this->exitExiftool();
        return $stdout;
      }
    }
    return $stdout;
  }
  
  /**
   * Read exiftool Stderr pipe 
   */
  private function _readStderr($pipes)  {
    $starttime = microtime(true);
    $current_line = '';
    $stderr = array();
    stream_set_blocking($pipes[2], 0);
    while ($current_line !== false) {
      $current_line  = fgets($pipes[2], 8192);  //1024? for speed?
      $stderr[] = $current_line;
      $processingtime = round((microtime(true)-$starttime),4); //seconds
      if ($processingtime>1){
        //increase for big video files?
        //probabily blocked
        $this->exitExiftool();
        return $stderr;
      }
    }
    return $stderr;
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
      $usePipes = true; //to be changed in case of old exiftool versions or problems with non UTF8 filenames...
      if ($usePipes and ($this->_IsOpenExiftool())) {
        return $this->_readMetaDataPipes($filename);
      } else {
        return $this->_readMetaDataDirect($filename);
      }
    }

  /**
   * Read the meta data via exiftool, through pipes, using -stay_open option
   * avoid perl start-up time needed each exiftool call
   *
   * @param filename Filename to read
   * @result Array of metadata or false on error
   */
  private function _readMetaDataPipes($filename)  {
    $ExiftoolData = $this->ExiftoolData;
    $pipes = $ExiftoolData['pipes'];

    //TODO: use exiftool arg -json and json_decode ( string $json) OR exiftool arg -php and eval($array_string)

    //fwrite($pipes[0], "-json\n");

    //next line is not really necessary; good for reading over slow networks
    fwrite($pipes[0], "-fast2\n");

    // comment next lines in order to read all metadata, not only these fields
    fwrite($pipes[0], "-Error\n");
    fwrite($pipes[0], "-Warning\n");
    fwrite($pipes[0], "-FileName\n");
    fwrite($pipes[0], "-ObjectName\n");
    fwrite($pipes[0], "-DateTimeCreated\n");
    fwrite($pipes[0], "-SubSecDateTimeOriginal\n");
    fwrite($pipes[0], "-SubSecCreateDate\n");
    //read only IPTC:DateCreated for avoiding confusion with XMP-photoshop:DateCreated = Date + Time + zone
    fwrite($pipes[0], "-IPTC:DateCreated\n");
    fwrite($pipes[0], "-TimeCreated\n");
    fwrite($pipes[0], "-DateTimeOriginal\n");
    fwrite($pipes[0], "-FileModifyDate\n");
    fwrite($pipes[0], "-ImageWidth\n");
    fwrite($pipes[0], "-ImageHeight\n");
    //for numerical Orientation a # can be used as field suffix: -Orientation#
    fwrite($pipes[0], "-Orientation#\n");
    fwrite($pipes[0], "-Aperture\n");
    fwrite($pipes[0], "-ShutterSpeed\n");
    fwrite($pipes[0], "-Model\n");
    fwrite($pipes[0], "-ISO\n");
    fwrite($pipes[0], "-Comment\n");
    fwrite($pipes[0], "-UserComment\n");
    fwrite($pipes[0], "-GPSLatitude#\n");
    fwrite($pipes[0], "-GPSLatitudeRef\n");
    fwrite($pipes[0], "-GPSLongitude#\n");
    fwrite($pipes[0], "-GPSLongitudeRef\n");
    fwrite($pipes[0], "-Keywords\n");
    fwrite($pipes[0], "-Subject\n");
    fwrite($pipes[0], "-PhtagrGroups\n");
    fwrite($pipes[0], "-SupplementalCategories\n");
    //IPTC - location
    fwrite($pipes[0], "-City\n");
    fwrite($pipes[0], "-Sub-location\n");
    fwrite($pipes[0], "-Province-State\n");
    fwrite($pipes[0], "-Country-PrimaryLocationName\n");
    //XMP - location
    //fwrite($pipes[0], "-City\n");
    fwrite($pipes[0], "-Location\n");
    fwrite($pipes[0], "-State\n");
    fwrite($pipes[0], "-Country\n");
    //video
    fwrite($pipes[0], "-Width\n");
    fwrite($pipes[0], "-Height\n");
    fwrite($pipes[0], "-Duration\n");
    //???
    fwrite($pipes[0], "-caption\n");
      //$result['height'] = intval($data['ImageHeight']);
      //$result['width'] = intval($data['ImageWidth']);
      //$result['duration'] = ceil(intval($data['Duration']));


    fwrite($pipes[0], "-S\n");

    //-b          (-binary)            Output data in binary format
    //-n          (--printConv)        Read/write numerical tag values, not passed through print Conversation; (not human readable)
    // without -n values are readed in 'human readable' format
    // tag names in the input JSON file may be suffixed with a # to disable print conversion
    // exiftool -Orientation# -Orientation -S a.jpg
    // Orientation: 6
    // Orientation: Rotate 90 CW

    //numerical values format, not human readable; exemple: for 'orientation' field
    //fwrite($pipes[0], "-n\n");

    fwrite($pipes[0], $filename);
    fwrite($pipes[0], "\n");

    fwrite($pipes[0], "-execute\n");
    //"-executeNUMBER\n" can be utilized to obtain {readyNUMBER} on eof stdout

    $stdout = $this->_readStdout($pipes);
    $stderr = $this->_readStderr($pipes);

    if ((count($stderr) > 1) || ($stderr[0] != false)) {// and count($stdout) !==1//i.e.="    1 image files created "
      //TODO: test if warnings and original file internal errors are reported on stderr or stdout
      $errors = implode(",", $stderr);
      Logger::err(am("exiftool stderr returned errors: ",$errors));
    }
    
    //parse results
    $data = array();
    foreach ($stdout as $line) {
      list($name, $value) = preg_split('/:(\s+|$)/', $line);
      $data[$name] = str_replace("\n", "", $value);// to avoid new line at end of $media['Media']['model']
      //TODO - log fields Error and Warning
    }

    $data = $this->_videoMetaDataProcess($data, $filename);
    return $data;
    
  }


  /**
   * Read the meta data via exiftool from a file
   *
   * @param filename Filename to read
   * @result Array of metadata or false on error
   */
  private function _readMetaDataDirect($filename) {
    if (!$this->controller->getOption('bin.exiftool')) {
      return false;
    }
    // read meta data
    $bin = $this->controller->getOption('bin.exiftool', 'exiftool');
    $args = array('-config', $this->_getExifToolConf(), '-S', '-n', $filename);
    $result = $this->Command->run($bin, $args);
    $output = $this->Command->output;
    if ($result == 127) {
      Logger::err("$bin could not be found!");
      return false;
    } elseif ($result != 0) {
      Logger::err("$bin returned with error: $result (command: '{$this->Command->lastCommand}')");
      return false;
    }

    $data = array();
    foreach ($output as $line) {
      list($name, $value) = preg_split('/:(\s+|$)/', $line);
      $data[$name] = $value;
    }
    $data = $this->_videoMetaDataProcess($data, $filename);
    return $data;
  }

   private function _videoMetaDataProcess($data, $filename) {
    $filetype = $this->controller->MyFile->_getTypeFromFilename($filename);
    if ($filetype == 5) {
      // FILE_TYPE_VIDEOTHUMB
      $result = array();
      if (!isset($data['ImageWidth']) || !isset($data['ImageWidth']) || !isset($data['Duration']) ) {
        Logger::warn("Could not extract width, height, or durration from '$filename'");
        Logger::warn($result);
        return false;
      }
      $result['height'] = intval($data['ImageHeight']);
      $result['width'] = intval($data['ImageWidth']);
      $result['duration'] = ceil(intval($data['Duration']));

      if (isset($data['DateTimeOriginal'])) {
        $result['date'] = $data['DateTimeOriginal'];
      } else if (isset($data['FileModifyDate'])) {
        $result['date'] = $data['FileModifyDate'];
      }
      if (isset($data['Orientation'])) {
        $result['orientation'] = $data['Orientation'];
      }
      if (isset($data['Model'])) {
        $result['model'] = $data['Model'];
      }
      if (isset($data['GPSLatitude']) && isset($data['GPSLongitude'])) {
        $result['latitude'] = $data['GPSLatitude'];
        $result['longitude'] = $data['GPSLongitude'];
      }
      Logger::trace("Extracted " . count($result) . " fields via exiftool");
      Logger::trace($result);
      return $result;
    } else {
      return $data;
    }
  }
  
  
  /**
   * Write the meta data to an image file
   *
   * @param filename Filename 
   * @param args Arguments for exiftool
   * @return 
   */
  public function writeMetaData($filename, $tmp, $args) {

    if ($this->_IsOpenExiftool()) {
      $result = $this->_writeMetaDataPipes($filename, $tmp, $args);
    } else {
      $result = $this->_writeMetaDataDirect($filename, $tmp, $args);
    }
    $result = $this->_checktmp($result, $filename, $tmp);
    return $result;
  }

  
  /**
   * Write the meta data to an image file, through pipes
   *
   * @param filename Filename 
   * @param args Arguments for exiftool
   * @return 
   */
  private function _writeMetaDataPipes($filename, $tmp, $args) {
   
    $ExiftoolData = $this->ExiftoolData;
    $pipes = $ExiftoolData['pipes'];
    
    foreach ($args as $arg) {
      fwrite($pipes[0], $arg."\n");
    }
    fwrite($pipes[0], "-execute\n");

    $stdout = $this->_readStdout($pipes);
    $stderr = $this->_readStderr($pipes);
    
    if ((count($stderr) > 1) || ($stderr[0] != false)) {// and count($stdout) !==1//i.e.="    1 image files created "
      //TODO: test if warnings and original file internal errors are reported on stderr or stdout
      $errors = implode(",", $stderr);
      Logger::err(am("exiftool stderr returned errors: ",$errors));
      return $errors;
    }
    
    return 0;
  }

  
  /**
   * Write the meta data to an image file, directly calling exiftool
   *
   * @param filename Filename 
   * @param args Arguments for exiftool
   * @return 
   */
  private function _writeMetaDataDirect($filename, $tmp, $args) {
    $bin = $this->controller->getOption('bin.exiftool', 'exiftool');
    $args = am( $this->_getExifToolConf(), $args);
    $args = am('-config', $args);

    $result = $this->Command->run($bin, $args);
    $output = $this->Command->output;
    if ($result == 127) {
      Logger::err("$bin could not be found!");
      return false;
    } elseif ($result != 0) {
      $errors = implode(",", $output);
      Logger::err("$bin returned with error: $result (command: '{$this->Command->lastCommand}'). Errors: $errors");
      return false;
    }
  }
  
  
  /**
   * Check if newfile tmp was created by exiftool and replace original file
   *
   * @param filename Filename
   * @param filename temporrary Filename
   * @return False on error
   */
  private function _checktmp($result, $filename, $tmp) {

    clearstatcache(true, $tmp);

    if ($result != 0 || !file_exists($tmp)) {
      Logger::err("$bin returns with error: $result");//works for array or only for string?
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
    return true;
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
