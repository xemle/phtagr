<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2009 Sebastian Felis, sebastian@phtagr.org
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

class FilterManagerComponent extends Object {

  var $controller = null;
  var $components = array('FileManager');

  /** List of extensions
    'extensions' => filter
  */
  var $extensions = null;
  /** List of Filters 
    'name' => 'filter'
    */
  var $filters = array();
  /** config of different extensions 
    'ext' => array() */
  var $config = array();

  var $errors = 0;

  function startup(&$controller) {
    $this->controller =& $controller;
    if (!App::import('Component', 'BaseFilter')) {
      Logger::err("Could not find filter with name 'BaseFilter'");
      return false;
    }
    $this->MyFile =& $controller->MyFile;
    $this->Media =& $controller->Media;
    $this->loadFilter(array('ImageFilter', 'VideoFilter', 'GpsFilter'));
  }

  /** Reads a component and checks required functions 
    */
  function loadFilter($name) {
    if (is_array($name)) {
      foreach($name as $n) {
        $this->loadFilter($n);
      }
      return true;
    }

    if (!App::import('Component', $name)) {
      Logger::err("Could not find filter with name '$name'");
      return false;
    }
    $componentName = $name.'Component';
    if (!class_exists($componentName)) {
      Logger::err("Could nod find class '$componentName'");
      return false;
    }
    $filter = new $componentName;
    if ($this->_validateFilter($filter, $name)) {
      $filterName = $filter->getName();
      if (isset($this->filters[$filterName])) {
        Logger::verbose("Filter $filterName already loaded");
        return true;
      }
      $filter->MyFile =& $this->MyFile;
      $filter->Media =& $this->Media;
      $this->controller->Component->_loadComponents(&$filter);
      // init components to setup the controller
      foreach ($filter->components as $name) {
        $component =& $filter->$name;
        if (method_exists($component, 'startup')) {
          $component->startup(&$this->controller);
        }
      }
      $filter->init(&$this);

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
          $this->extensions[$ext] =& $filter;
          $this->config[$ext] = $config;
          $new[] = $ext;
        } else {
          Logger::warn("Filter for extension '$ext' already exists");
        }
      }
      if (count($new)) {
        Logger::trace("Loaded filter $name with extension(s): ".implode(', ', $new));
      }
      $this->filters[$filterName] =& $filter;
    }
  }

  function getFilter($name) {
    $filter = null;
    if (isset($this->filters[$name])) {
      $filter =& $this->filters[$name];
    } else {
      Logger::warn("Could not find filter '$name'");
      Logger::debug(array_keys($this->filters));
    }
    return $filter;
  }

  /** checks the filter for required functions
    getExtensions(), setManager(), read(), write() */
  function _validateFilter($filter, $name) {
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

  function isSupported($filename) {
    $ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
    if (isset($this->extensions[$ext])) {
      return true;
    } else {
      return false;
    }
  }

  function getFilterByExtension($filename) {
    $ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
    if (isset($this->extensions[$ext])) {
      return $this->extensions[$ext];
    } else {
      Logger::debug("No filter found for extension '$ext'");
    }
    return null;
  }

  function getExtensions() {
    return array_keys($this->extensions);
  }

  /** Sort files by their extensions and map them to an array where the
   * extension is the array key */
  function _sortFilesByExtension($files) {
    $mapping = array();
    foreach ($files as $file) {
      $base = basename($file);
      $ext = strtolower(substr($base, strrpos($base, '.')+1));
      $mapping[$ext][] = $file;
    }
    return $mapping;
  }

  /** Return all supported extensions sorted by their priority */
  function _getExtensionsByPriority() {
    $exts = $this->getExtensions();
    
    $order = array();
    foreach ($exts as $ext) {
      $order[$ext] = $this->config[$ext]['priority'];
    }
    arsort($order);
    return array_keys($order);
  }

  /** Read all supported files of a directory
    @param path Path of the directory to read
    @result array of files to read 
    @todo Add recursive read */
  function _readPath($path) {
    if (!is_dir($path) || !is_readable($path)) {
      return array();
    }
    $files = array();

    $folder =& new Folder($path);
    $extensions = $this->getExtensions();
    $pattern = ".*\.(".implode('|', $this->getExtensions()).")";
    $found = $folder->find($pattern);

    foreach ($found as $file) {
      $file = Folder::addPathElement($path, $file);
      if (is_readable($file)) {
        $files[] = $file;
      }
    }
    return $files;
  }

  /** Read a file or files or directories 
    @param single file or array of files and/or directories */
  function readFiles($files) {
    $stack = array();
    if (!is_array($files)) {
      $files = array($files);
    }

    foreach ($files as $file) {
      if (is_dir($file)) {
        $stack = am($stack, $this->_readPath($file));        
      } else {
        if (is_readable($file)) {
          $stack[] = $file;
        }
      }
    }
    Logger::verbose("Found ".count($stack)." files to import");
    $extStack = $this->_sortFilesByExtension($stack);
    $order = $this->_getExtensionsByPriority();
    //Logger::debug($order);
    $readed = 0;
    foreach ($order as $ext) {
      if (!isset($extStack[$ext])) {
        continue;
      }
      foreach ($extStack[$ext] as $file) {
        $result = $this->read($file);
        if ($result > 0) {
          $readed++;
        }
      }
    }
    return $readed;
  }

  /** Import a file to the database
    @param filename Filename of the single file */
  function read($filename) {
    if (!is_readable($filename)) {
      Logger::err("Could not read file $filename");
      $this->errors++;
      return false;
    }
    if (!$this->isSupported($filename)) {
      Logger::verbose("File $filename is not supported");
      return false;
    }
    if (!$this->MyFile->fileExists($filename) && !$this->FileManager->add($filename)) {
      Logger::err("Could not add file $filename");
      $this->errors++;
      return false;
    }

    $file = $this->MyFile->findByFilename($filename);

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

    if ($this->MyFile->hasMedia($file)) {
      $media = $this->Media->findById($file['File']['media_id']);
      $readed = strtotime($file['File']['readed']);
      if ($readed && !$forceRead) {
        Logger::verbose("File '$filename' already readed. Skip reading!");
        return 0;
      }
    } else {
      $media = false;
    }

    $filter = $this->getFilterByExtension($filename);
    Logger::debug("Read file $filename with filter ".$filter->getName());
    $result = $filter->read(&$file, &$media);
    if ($result < 0) {
      $this->errors++;
    }
    return $result;
  }

  /** Export database to file */
  function write(&$media) {
    foreach ($media['File'] as $file) {
      $file = $this->MyFile->findById($file['id']);
      $filename = $this->MyFile->getFilename($file);
      $filter = $this->getFilterByExtension($filename);
      if (!$filter) {
        Logger::verbose("Could not find a filter for $filename");
        continue;
      }
      Logger::trace("Call filter ".$filter->getName()." for $filename");
      $filter->write($file, &$media);
    }      
    return true;
  }

}

?>
