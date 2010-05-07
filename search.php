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

class Search extends Object
{

  var $_data;

  /** Clears all parameters and set them to the defaults*/
  function clear() {
    $this->_data = array();
  }

  /** Returns all parameters 
    @return Parameter array */
  function getParams() {
    return $this->_data;
  }

  /** Set all parameters 
    @param data Parameter array
    @note The parameters are not validated! */
  function setParams($data = array()) {
    $this->_data = $data;
  }

  /** Validate parameter value.
    @note Overwritten by inherited classes */
  function validate($name, $value) {
    return true;
  }

  /** Returns parameter 
    @param name Name of parameter 
    @param default Default value, if the parameter does not exists. Default
    value is null */
  function getParam($name, $default = null) {
    if (!empty($this->_data[$name])) {
      return $this->_data[$name];
    } else {
      return $default;
    }
  }

  /** Set a singular parameter 
    @param name Parameter name
    @param value Parameter value
    @return True on success */
  function setParam($name, $value) {
    if ($this->validate($name, $value)) {
      $this->_data[$name] = $value;    
      return true;
    } else {
      return false;
    }
  }

  function addParam($name, $value) {
    $name = Inflector::pluralize($name);
    if (is_array($value)) {
      foreach ($value as $v) {
        $this->addParam($name, $v);
      }
      return;
    }
    
    if ((!isset($this->_data[$name]) || !in_array($value, $this->_data[$name])) &&
      $this->validate($name, $value)) {
      $this->_data[$name][] = $value;
    }
  }

  function delParam($name, $value = false) {
    if (!isset($this->_data[$name])) {
      return;
    }
    
    if (!empty($value)) {
      if (is_array($value)) {
        foreach ($value as $v) {
          $this->delParam($name, $v);
        }
        return;
      }
      // handle array
      $key = array_search($value, $this->_data[$name]);
      if ($key !== false) {
        unset($this->_data[$name][$key]);
      }
      if (count($this->_data[$name]) == 0) {
        unset($this->_data[$name]);
      }
    } else {
      // handle single value
      unset($this->_data[$name]);
    }
  }

  function __call($name, $args) {
    if (!preg_match('/^(get|set|add|del|delete)(.*)$/', $name, $matches)) {
      $this->log("Undefined function $name");
      return;
    }
    $name = Inflector::underscore($matches[2]);
    switch ($matches[1]) {
      case 'get':
        if (count($args) > 0) {
          return $this->getParam($name, $args[0]);
        } else {
          return $this->getParam($name);
        }
        break;
      case 'set':
        if (count($args) == 1) {
          return $this->setParam($name, $args[0]);
        }
        break;
      case 'add':
        if (count($args) == 1) {
          return $this->addParam($name, $args[0]);
        }
        break;
      case 'del':
      case 'delete':
        if (count($args) == 1) {
          if (!isset($this->_data[$name])) {
            $plural = Inflector::pluralize($name);
            if (isset($this->_data[$plural])) {
              $name = $plural;
            }
          }
          $this->delParam($name, $args[0]);
        } else {
          $this->delParam($name);
        }
        break;
    }
  }
}
?>
