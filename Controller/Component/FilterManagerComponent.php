<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

if (!App::import('Component', 'BaseFilter')) {
  Logger::error("Could not load BaseFilter");
}

class FilterManagerComponent extends Component {

  var $controller = null;
  var $components = array('FileManager');
  var $enableImportLogging = true;

  // Option values
  var $writeEmbeddedEnabledOption = 'filter.write.metadata.embedded';
  var $writeSidecarEnabledOption = 'filter.write.metadata.sidecar';
  var $createSidecarOption = 'filter.create.metadata.sidecar';
  var $createSidecarForNonEmbeddableFileOption = 'filter.create.nonEmbeddableFile.metadata.sidecar';

  /**
   * True if embedded write support is enabled
   */
  var $writeEmbeddedEnabled = false;
  /**
   * True if sidecar write support is enabled
   */
  var $writeSidecarEnabled = false;
  /**
   * True if missing sidecare will be created
   */
  var $createSidecar = false;
  /**
   * True if XMP sidecar should be used for non embeddedable meta data like video files
   */
  var $createSidecarForNonEmbeddableFile = false;
  /**
   * True if write support is enabled
   */
  var $_writeEnabled = false;

  /**
   * List of extensions
   * 'extensions' => filter
   */
  var $extensions = null;
  /**
   * List of Filters
   * 'name' => 'filter'
   */
  var $filters = array();
  /** config of different extensions
    'ext' => array() */
  var $config = array();

  var $errors = array();
  var $skipped = array();

  var $fileCache = array();
  var $mediaCache = array();

  public function initialize(Controller $controller) {
    $this->controller = $controller;
    if (!isset($controller->MyFile) || !isset($controller->Media)) {
      Logger::err("Model MyFile and Media is not found");
      return;
    }
    $this->loadFilter(array('ImageFilter', 'ReadOnlyImageFilter', 'VideoFilter', 'GpsFilter', 'SidecarFilter'));

    $this->writeEmbeddedEnabled = $this->controller->getOption($this->writeEmbeddedEnabledOption);
    $this->writeSidecarEnabled = $this->controller->getOption($this->writeSidecarEnabledOption);
    $this->createSidecarForNonEmbeddableFile = $this->controller->getOption($this->createSidecarForNonEmbeddableFileOption);
    $this->createSidecar = $this->controller->getOption($this->createSidecarOption);

    $this->_writeEnabled = $this->writeEmbeddedEnabled || $this->writeSidecarEnabled || $this->createSidecar;
  }

  /**
   * Reads a component and checks required functions
   */
  public function loadFilter($name) {
    if (is_array($name)) {
      foreach($name as $n) {
        $this->loadFilter($n);
      }
      return true;
    }
    if (!$this->controller->loadComponent($name, $this)) {
      return false;
    }
    $filter = $this->{$name};
    if (!$this->_validateFilter($filter, $name)) {
      return false;
    }
    $filterName = $filter->getName();
    if (isset($this->filters[$filterName])) {
      return true;
    }
    $filter->FilterManager = $this;

    $filter->init($this);

    $extensions = $filter->getExtensions();
    if (!is_array($extensions)) {
      $extensions = array($extensions);
    }
    $new = array();
    foreach($extensions as $ext => $config) {
      if (is_numeric($ext)) {
        $ext = $config;
        $config = array();
      }
      $config = am(array('priority' => 8, 'hasMetaData' => false), $config);
      $ext = strtolower($ext);
      if (!isset($this->extensions[$ext])) {
        $this->extensions[$ext] = $filter;
        $this->config[$ext] = $config;
        $new[] = $ext;
      } else {
        Logger::warn("Filter for extension '$ext' already exists");
      }
    }
    if (count($new)) {
      //Logger::trace("Loaded filter $filterName ($name) with extension(s): ".implode(', ', $new));
    }
    $this->filters[$filterName] = $filter;
  }

  public function getFilter($name) {
    $filter = null;
    if (isset($this->filters[$name])) {
      $filter = $this->filters[$name];
    } else {
      Logger::warn("Could not find filter '$name'");
      Logger::debug(array_keys($this->filters));
    }
    return $filter;
  }

  /**
   * checks the filter for required functions
   * init(), getExtensions(), read(), write()
   */
  public function _validateFilter($filter, $name) {
    $methods = array('init', 'getExtensions', 'read', 'write');
    $missing = array();
    foreach ($methods as $method) {
      if (!method_exists($filter, $method)) {
        $missing[] = $method;
      }
    }
    if ($missing) {
      Logger::err("Could not import Filter '$name'. Missing function(s): ".implode(', ', $missing));
      return false;
    }
    return true;
  }

  /**
   * Evaluate if a filename is supported by a filter
   *
   * @param filename Filename
   * @return True if filename is supported. False otherwise
   */
  public function isSupported($filename) {
    $ext = $this->_getFileExtension($filename);
    if (isset($this->extensions[$ext])) {
      return true;
    } else {
      return false;
    }
  }

  private function _getFileExtension($filename) {
    return strtolower(substr($filename, strrpos($filename, '.') + 1));
  }
  /**
   * Returns a filter by filename
   *
   * @param filename Filename
   * @result Filter which handles the file
   */
  public function getFilterByExtension($filename) {
    $ext = $this->_getFileExtension($filename);
    if (isset($this->extensions[$ext])) {
      return $this->extensions[$ext];
    } else {
      Logger::debug("No filter found for extension '$ext'");
    }
    return null;
  }

  /**
   * Returns a list of supported file extensions as array
   *
   * @return Array of supported file extensions
   */
  public function getExtensions() {
    return array_keys($this->extensions);
  }

  /**
   * Sort files by their extensions and map them to an array where the
   * extension is the array key
   */
  public function _sortFilesByExtension($files) {
    $mapping = array();
    foreach ($files as $file) {
      $base = basename($file);
      $ext = strtolower(substr($base, strrpos($base, '.') + 1));
      $mapping[$ext][] = $file;
    }
    return $mapping;
  }

  /**
   * Return all supported extensions sorted by their priority
   */
  public function _getExtensionsByPriority() {
    $exts = $this->getExtensions();

    $order = array();
    foreach ($exts as $ext) {
      $order[$ext] = $this->config[$ext]['priority'];
    }
    arsort($order);
    return array_keys($order);
  }

  /**
   * Read all supported files of a directory
   *
   * @param path Path of the directory to read
   * @param array $options
   *  - recursive: True if read recursivly
   *  - extensions: List of extension to read
   * @return array of files to read
   */
  private function _readPath($path, $options = array()) {
    $options = am(array('recursive' => false, 'extensions' => array('any')), $options);
    if (!is_dir($path) || !is_readable($path)) {
      return array();
    }
    $files = array();
    $recursive = (bool) $options['recursive'];

    $folder = new Folder($path);
    $extensions = $this->getExtensions();
    if (!in_array('any', $options['extensions'])) {
      $extensions = array_intersect($extensions, $options['extensions']);
    }
    $pattern = ".*\.(".implode('|', $extensions).")";
    if ($recursive) {
      $found = $folder->findRecursive($pattern, true);
    } else {
      $found = $folder->find($pattern, true);
    }

    foreach ($found as $file) {
      if (!$recursive) {
        $file = Folder::addPathElement($path, $file);
      }
      if (is_readable($file)) {
        $files[] = $file;
      }
    }
    return $files;
  }

  /**
   * Read a file or files or directories
   *
   * @param single file or array of files and/or directories
   * @param array $options
   *  - resursive: True if read directory recursivly
   *  - forceReadMeta: Reread meta data
   *  - extenstions: Array of file extensions (lower case) to read
   * @return Array of readed files. filename => Media model data (result of
   * FilterManager->read())
   */
  public function readFiles($files, $options = array()) {
    $options = am(array('recursive' => false, 'forceReadMeta' => false, 'extensions' => array('any')), (array) $options);
    $stack = array();
    if (!is_array($files)) {
      $files = array($files);
    }

    foreach ($files as $file) {
      if (is_dir($file)) {
        $stack = am($stack, $this->_readPath($file, $options));
      } else if (is_readable($file)) {
        $ext = strtolower(substr($file, strrpos($file, '.') + 1));
        if (in_array('any', $options['extensions']) || in_array($ext, $options['extensions'])) {
          $stack[] = $file;
        }
      }
    }
    Logger::verbose("Found ".count($stack)." files to import");
    $extStack = $this->_sortFilesByExtension($stack);
    $order = $this->_getExtensionsByPriority();
    //Logger::debug($order);

    $result = array();
    $importLog = $this->_importlog($importLog, $file);
    if ($this->enableImportLogging) {
      $this->log(("Found ".count($stack)." files to import"), 'import_memory_speed');
    }
    foreach ($order as $ext) {
      if (!isset($extStack[$ext])) {
        continue;
      }
      // sort files by name
      $files = $extStack[$ext];
      sort($files);
      //new extension is processed, cache is cleared (ex:read xmp; media could be changed be previously by reading mainfile)
      $this->mediaCache = array();
      $this->fileCache = array();
      foreach ($files as $file) {
        $result[$file] = $this->read($file, $options['forceReadMeta']);
        $importLog = $this->_importlog($importLog, $file);
      }
    }
    return $result;
  }

  /**
   * Adds an error for a file
   *
   * @param filename Current filename
   * @param error Error code
   * @param msg Optional error message or longer description
   * @param data Optional error data
   */
  public function addError($filename, $error, $msg = '', $data = false) {
    $this->errors[$filename] = array('error' => $error, 'msg' => $msg, 'data' => $data);
  }

  /**
   * Clears the error array
   */
  public function clearErrors() {
    $this->errors = array();
  }

  /**
   * Returns array of all errors
   *
   * @return Array filename to error array
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Lookup file from database to match filename to Media.
   *
   * @param string $path Path of file
   * @param string $filename Filename
   * @return mixed File model data or false if file was not found
   */
  public function findFileInPath($path, $filename) {
    if (!isset($this->fileCache[$path])) {
      $this->fileCache[$path] = array();//cache only current folder to avoid memory issues
      $this->fileCache[$path] = $this->controller->MyFile->findAllByPath($path);
    }
    foreach ($this->fileCache[$path] as $file) {
      if ($file['File']['path'].$file['File']['file'] == $filename) {
        return $file;
      }
    }
    return false;
  }

  /**
   * Lookup media from database to match filename to Media.
   *
   * @param string $path Path of file
   * @param string $filename Filename
   * @return mixed Media model data or false if media was not found
   */
  public function _findMediaInPath($path, $filename) {
    if (!isset($this->mediaCache[$path])) {
      $this->mediaCache[$path] = array();//cache only current folder to avoid memory issues
      $this->mediaCache = array();
      $user = $this->controller->getUser();
      $this->mediaCache[$path] = $this->controller->Media->findAllByOptions($user, array('model' => 'File', 'conditions' => array('File.path' => $path)));
    }
    foreach ($this->mediaCache[$path] as $media) {
      foreach ($media['File'] as $file) {
        if ($file['path'].$file['file'] == $filename) {
          return $media;
        }
      }
    }
    return false;
  }

  /**
   * replace model in cache
   *
   * @param string $path Path of file
   * @param string $model Model
   * @param string $modelType 'Media' or 'File'
   * @return mixed Media model data or false if media was not found
   */
  public function _replaceInCache($path, $model, $modelType) {
    if ($modelType='Media') {
      $cache =& $this->mediaCache;
    } elseif ($modelType='File') {
      $cache =& $this->fileCache;
    } else {
      return false;
    }
    if (!isset($cache[$path])) {
      return false;
    }
    foreach ($cache[$path] as $key=>$cachedModel) {
      if ($model[$modelType]['id'] == $cachedModel[$modelType]['id']) {
        $cache[$path][$key] = $model;
        return true;
      }
    }
    return false;
  }

  /**
   * Import a file to the database
   *
   * @param string $filename Filename of the single file
   * @param bool $forceReadMeta Reread meta data from file
   * @return number Media id on success. Fals on error
   */
  public function read($filename, $forceReadMeta = false) {
    if (!is_readable($filename)) {
      Logger::err("Could not read file $filename");
      $this->addError($filename, 'FileNotReadable');
      return false;
    }
    if (!$this->isSupported($filename)) {
      Logger::verbose("File $filename is not supported");
      return false;
    }
    $path = Folder::slashTerm(dirname($filename));
    $file = $this->findFileInPath($path, $filename);
    if (!$file){
      if (!$this->FileManager->add($filename)) {
        Logger::err("Could not add file $filename");
        $this->addError($filename, 'FileAddError');
        return false;
      }

      $file = $this->controller->MyFile->findByFilename($filename);
      if (!$file) {
        Logger::err("Could not find file with filename: " . $filename);
        return false;
      }
      $this->fileCache[$path][] = $file;
    }

    // Check changes
    $fileTime = filemtime($filename);
    $dbTime = strtotime($file['File']['time']);
    //Logger::debug("db $dbTime file $fileTime");
    $forceRead = false;
    if ($fileTime > $dbTime) {
      Logger::warn("File '$filename' was changed without notice of phTagr! Read the file again.");
      // @todo Action if file is newer than phtagr. Eg. force rereada
      $forceRead = true;
    }

    if ($this->controller->MyFile->hasMedia($file)) {
      $media = $this->_findMediaInPath($path, $filename);
      $readed = strtotime($file['File']['readed']);
      if ($forceReadMeta) {
        $forceRead = true;
      }
      if ($readed && !$forceRead) {
        Logger::verbose("File '$filename' already readed. Skip reading!");
        $this->skipped[$filename] = 'skipped';
        //return $media;//overload memory with 20kb for each file
        return $filename;//around 0.2kb
      }
    } else {
      $media = false;
      if ($forceReadMeta) {
        //media missing, file will not be readed; force read meta only for EXISTING media;
        return false;
      }
    }

    $filter = $this->getFilterByExtension($filename);
    Logger::debug("Read file $filename with filter ".$filter->getName());
    $result = $filter->read($file, $media);

    if (isset($result['Media']['id'])) {
      return $result['Media']['id'];
    }

    return false;
  }

  private function _createAndWriteSidecar(&$media) {
    $filename = $this->controller->Media->getMainFilename($media);
    if (!$filename) {
      Logger::err("Main file for {$media['Media']['id']} not found");
      $this->addError($filename, 'FileNotFound');
      return false;
    } else if (!is_writable(dirname($filename))) {
      Logger::err("Can not create sidecar file for {$media['Media']['id']}");
      $this->addError($filename, 'DirectoryNotWritable');
      return false;
    }

    $sidecar = $this->SidecarFilter->create($filename);
    if (!$sidecar) {
      Logger::err("Creation of sidecar file for $filename failed");
      return false;
    }
    $file = $this->controller->MyFile->findByFilename($sidecar);
    if (!$file) {
      Logger::err("Can not find file in database for $sidecar");
      return false;
    }
    if (!$this->controller->MyFile->setMedia($file, $media['Media']['id'])) {
      Logger::err("Could not assign media {$media['Media']['id']} to file {$file['File']['id']}");
      return false;
    }
    return $this->SidecarFilter->write($file, $media);
  }

  /**
   * Collects all extensions with embeddable meta data
   *
   * @return array List of file extensions
   */
  private function _getExtensionsWithMetaData() {
    $result = array();
    foreach ($this->config as $ext => $config) {
      if ($config['hasMetaData']) {
        $result[] = $ext;
      }
    }
    return $result;
  }

  /**
   * Create additional files
   *
   * @param array $media Media model data
   */
  private function _createAdditionalFiles(&$media) {
    if ($this->controller->Media->isType($media, MEDIA_TYPE_VIDEO) && isset($this->VideoFilter)) {
      $this->VideoFilter->createThumb($media);
    }
  }

  /**
   * Export database to file
   */
  public function write(&$media) {
    if (!count($media['File'])) {
      Logger::warn("No files found for media {$media['Media']['id']}");
      $this->addError('Media-' . $media['Media']['id'], 'NoMediaFile');
      return false;
    }
    if (!$this->_writeEnabled) {
      return false;
    }
    $success = true;
    $filterMissing = false;
    $hasSidecar = false;
    $metaDataExtensions = $this->_getExtensionsWithMetaData();
    $hasFileWithMetaData = false;

    $this->_createAdditionalFiles($media);
    foreach ($media['File'] as $file) {
      $file = $this->controller->MyFile->findById($file['id']);
      $filename = $this->controller->MyFile->getFilename($file);
      if (!file_exists($filename)) {
        Logger::err("File of media {$media['Media']['id']} does not exist: $filename");
        $this->addError($filename, 'FileNotFound');
        $success = false;
        continue;
      } else if (!is_writable(dirname($filename))) {
        Logger::err("Directory of file of media {$media['Media']['id']} is not writeable: $filename");
        $this->addError($filename, 'DirectoryNotWritable');
        $success = false;
        continue;
      }
      $isSidecar = $this->controller->MyFile->isType($file, FILE_TYPE_SIDECAR);
      if ($isSidecar) {
        $hasSidecar = true;
      }
      $ext = $this->_getFileExtension($filename);
      if (in_array($ext, $metaDataExtensions)) {
        $hasFileWithMetaData = true;
      }
      if (!$isSidecar && !$this->writeEmbeddedEnabled) {
        continue;
      } else if ($isSidecar && !$this->writeSidecarEnabled) {
        continue;
      }
      $filter = $this->getFilterByExtension($filename);
      if (!$filter) {
        Logger::verbose("Could not find a filter for $filename");
        $this->addError($filename, 'FilterNotFound');
        $filterMissing = true;
        continue;
      }
      $filterMissing = false;
      Logger::trace("Call filter ".$filter->getName()." for $filename");
      $success = ($success && $filter->write($file, $media));
    }
    if (!$hasSidecar && ($this->createSidecar || (!$hasFileWithMetaData && $this->createSidecarForNonEmbeddableFile))) {
      $success = ($success && $this->_createAndWriteSidecar($media));
    }
    return $success && !$filterMissing;
  }

  /**
   * Log importing speed and memory utilized
   */
  Function _importlog(&$importLog = array(), $file) {
    if (!$this->enableImportLogging){
      return false;
    }
    $v =& $importLog;
    if (!isset($v['StartTime'])){
      $v['file_nr']=0;
      $v['StartTime'] = microtime(true);//seconds
      $v['StartMemory'] = round((memory_get_usage()/1000000),4);//MB
      $v['LastTime'] = $v['StartTime'];
      $v['LastMemory'] = $v['StartMemory'];
      $this->log(" \n \n \n \n new import ------------------------------------------------------------", 'import_memory_speed');//add 10 new lines  \n?
      return $importLog;
    }
    $CrtTime = microtime(true);
    $CrtMemory = round((memory_get_usage()/1000000),4);
    $v['file_nr']=$v['file_nr']+1;
    $averageFileMemory = round(($CrtMemory-$v['StartMemory'])*1000/$v['file_nr'],1);//kb  nr zecimale??
    $totalTime = round(($CrtTime-$v['StartTime']),4);
    $LastFileTime = round(($CrtTime-$v['LastTime']),4);
    $averageFileTime = round($totalTime/$v['file_nr'],4);
    $LastFileMemory = round(($CrtMemory-$v['LastMemory'])*1000,1);//kb  nr zecimale??
    $v['LastMemory'] = $CrtMemory;
    $v['LastTime'] = $CrtTime;

    $this->log($file, 'import_memory_speed');
    $this->log("------ FILE NO: ".$v['file_nr'].", total time ".$totalTime." seconds, LastFileTime ".$LastFileTime.", averageFileTime ".$averageFileTime." sec/file", 'import_memory_speed');
    $this->log("------ memory_get_usage = ".$CrtMemory." MB, used for last file = ".$LastFileMemory." kb, averageFileMemory ".$averageFileMemory." kb", 'import_memory_speed');

    return $importLog;
  }
}
