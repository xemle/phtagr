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
  var $components = array('Logger', 'FileManager');

  /** List of extensions
    'extensions' => filter
  */
  var $extensions = null;
  /** List of Filters 
    'name' => 'filter'
    */
  var $filters = array();

  function startup(&$controller) {
    $this->controller =& $controller;
    if (!App::import('Component', 'BaseFilter')) {
      $this->Logger->err("Could not find filter with name 'BaseFilter'");
      return false;
    }
    $this->MyFile =& $controller->MyFile;
    $this->Medium =& $controller->Medium;
    $this->loadFilter('ImageFilter');
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
      $this->Logger->err("Could not find filter with name '$name'");
      return false;
    }
    $componentName = $name.'Component';
    if (!class_exists($componentName)) {
      $this->Logger->err("Could nod find class '$componentName'");
      return false;
    }
    $filter = new $componentName;
    if ($this->_validateFilter($filter, $name)) {
      $filterName = $filter->getName();
      if (isset($this->filters[$filterName])) {
        return true;
      }
      $filter->MyFile =& $this->MyFile;
      $filter->Medium =& $this->Medium;
      $this->controller->Component->_loadComponents(&$filter);
      $filter->init(&$this);

      $extensions = $filter->getExtensions();
      if (!is_array($extensions)) {
        $extensions = array($extensions);
      }
      $new = array();
      foreach($extensions as $ext) {
        $ext = strtolower($ext);
        if (!isset($this->extensions[$ext])) {
          $this->extensions[$ext] =& $filter;
          $new[] = $ext;
        } else {
          $this->Logger->warn("Filter for extension '$ext' already exists");
        }
      }
      if (count($new)) {
        $this->Logger->trace("Loaded filter $name with extension(s): ".implode(', ', $new));
      }
    }
  }

  function getFilter($name) {
    if (isset($this->filters[$name])) {
      return $this->filters[$name];
    } else {
      $this->Logger->debug("Could not find filter '$name'");
    }
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
      $this->Logger->err("Could not import Filter '$name'. Missing function(s): ".implode(', ', $missing));
      return false;
    }
    return true;
  }

  function isSupported($filename) {
    $ext = substr($filename, strrpos($filename, '.') + 1);
    if (isset($this->extensions[$ext])) {
      return true;
    } else {
      return false;
    }
  }

  function getFilterByExtension($filename) {
    $ext = substr($filename, strrpos($filename, '.') + 1);
    if (isset($this->extensions[$ext])) {
      return $this->extensions[$ext];
    } else {
      $this->Logger->debug("No filter found for extension '$ext'");
    }
    return null;
  }

  function getExtensions() {
    return array_keys($this->extensions);
  }

  /** Read filename to database 
    */
  function read($filename) {
    if (!is_readable($filename)) {
      $this->Logger->err("Could not read file $filename");
      return -1;
    }
    if (!$this->isSupported($filename)) {
      $this->Logger->verbose("File $filename is not supported");
      return 0;
    }
    if (!$this->MyFile->fileExists($filename) && !$this->FileManager->add($filename)) {
      $this->Logger->err("Could not add file $filename");
      return -1;
    }
    $file = $this->MyFile->findByFilename($filename);
    $filter = $this->getFilterByExtension($filename);

    if ($this->MyFile->hasMedium($file)) {
      $medium = $this->Medium->findById($file['File']['medium_id']);
    } else {
      $medium = false;
    }
    $this->Logger->debug("Read file $filename with filter ".$filter->getName());
    $dataFile = $filter->read($file, &$medium);
  }

  /** Export database to file */
  function write(&$medium) {
    foreach ($medium['File'] as $file) {
      $file = $this->MyFile->findById($file['id']);
      $filename = $this->MyFile->getFilename($file);
      $filter = $this->getFilterByExtension($filename);
      if (!$filter) {
        $this->Logger->verbose("Could not find a filter for $filename");
        continue;
      }
      $this->Logger->trace("Call filter ".$filter->getName()." for $filename");
      $filter->write($file, &$medium);
    }      
    return true;
  }

}

?>
