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

if (!App::import('Component', 'BaseFilter')) {
  Logger::error("Could not load BaseFilter");
}

class FilterManagerComponent extends Component {

  var $controller = null;
  var $components = array('FileManager');

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

  public function initialize(Controller $controller) {
    $this->controller = $controller;
    if (!isset($controller->MyFile) || !isset($controller->Media)) {
      Logger::err("Model MyFile and Media is not found");
      return false;
    }
    $this->loadFilter(array('ImageFilter', 'ReadOnlyImageFilter', 'VideoFilter', 'GpsFilter', 'Exiftool', 'SidecarFilter'));
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
      $config = am(array('priority' => 8), $config);
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
    $ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
    if (isset($this->extensions[$ext])) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Returns a filter by filename
   *
   * @param filename Filename
   * @result Filter which handles the file
   */
  public function getFilterByExtension($filename) {
    $ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
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
   * @param recursive Read files recursivly
   * @return array of files to read
   * @todo Add recursive read
   */
  public function _readPath($path, $options = array('recursive'=>0, 'extToRead'=>array('any'))) {
    if (!is_dir($path) || !is_readable($path)) {
      return array();
    }
    $files = array();
    $recursive = (bool) $options['recursive'];

    $folder = new Folder($path);
    $extensions = $this->getExtensions();
    if (!in_array('any', $options['extToRead'])) {
      $extensions = array_intersect($extensions, $options['extToRead']);
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
   * @param recursive True if read directory recursivly
   * @return Array of readed files. filename => Media model data (result of
   * FilterManager->read())
   */
  public function readFiles($files, $options) {

    $forceReadMeta = (bool) $options['forceReadMeta'];
    $stack = array();
    if (!is_array($files)) {
      $files = array($files);
    }

    foreach ($files as $file) {
      if (is_dir($file)) {
        $stack = am($stack, $this->_readPath($file, $options));
      } else {
        if (is_readable($file)) {
          $ext = strtolower(substr($file, strrpos($file, '.') + 1));
          if (in_array('any', $options['extToRead']) || in_array($ext, $options['extToRead'])) {
            $stack[] = $file;
          }
        }
      }
    }
    Logger::verbose("Found ".count($stack)." files to import");
    $extStack = $this->_sortFilesByExtension($stack);
    $order = $this->_getExtensionsByPriority();
    //Logger::debug($order);

    $result = array();
    foreach ($order as $ext) {
      if (!isset($extStack[$ext])) {
        continue;
      }
      // sort files by name
      $files = $extStack[$ext];
      sort($files);
      foreach ($files as $file) {
        $result[$file] = $this->read($file, $forceReadMeta);
      }
    }
    $this->Exiftool->exitExiftool();//TODO move this line in  before exit event and before shutdown
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
   * Import a file to the database
   *
   * @param filename Filename of the single file
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
    if (!$this->controller->MyFile->fileExists($filename) && !$this->FileManager->add($filename)) {
      Logger::err("Could not add file $filename");
      $this->addError($filename, 'FileAddError');
      return false;
    }

    $file = $this->controller->MyFile->findByFilename($filename);
    if (!$file) {
      Logger::err("Could not find file with filename: " . $filename);
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
      $media = $this->controller->Media->findById($file['File']['media_id']);
      $readed = strtotime($file['File']['readed']);
      if ($forceReadMeta) {
        $forceRead = true;
      }
      if ($readed && !$forceRead) {
        Logger::verbose("File '$filename' already readed. Skip reading!");
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

    //changes only for ImageFilter
    if ($filter->getName() == "Image" || $filter->getName() == "Sidecar") {
      if ($result) {$result = $filename;}
    }
    return $result;//overload memory with 20kb for each file
    //return $filename;//around 0.2kb

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
    $success = true;
    $filterMissing = false;
    foreach ($media['File'] as $file) {
      $file = $this->controller->MyFile->findById($file['id']);
      $filename = $this->controller->MyFile->getFilename($file);
      if (!file_exists($filename)) {
        Logger::err("File of media {$media['Media']['id']} does not exist: $filename");
        $this->addError($filename, 'FileNotFound');
        $success = false;
        continue;
      }
      $filter = $this->getFilterByExtension($filename);
      if (!$filter) {
        Logger::verbose("Could not find a filter for $filename");
        $this->addError($filename, 'FilterNotFound');
        $filterMissing = true;
        continue;
      }
      if (!is_writable(dirname($filename))) {
        Logger::err("Directory of file of media {$media['Media']['id']} is not writeable: $filename");
        $this->addError($filename, 'DirectoryNotWritable');
        $success = false;
        continue;
      }
      $filterMissing = false;
      Logger::trace("Call filter ".$filter->getName()." for $filename");
      $success = ($success && $filter->write($file, $media));
    }
    return $success && !$filterMissing;
  }

}

?>