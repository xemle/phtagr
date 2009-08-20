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

App::import('File', 'Search', array('file' => APP.'search.php'));

class SearchHelper extends Search {

  var $helpers = array('Html'); 

  var $config = array();

  var $isMyMedia = false;

  /** Singular exception where the inflector does not match */
  var $singulars = array('pos');

  /** Initialize query parameters from the global parameter array, which is
   * set by the query component */
  function initialize($config = array()) {
    if (isset($this->params['search'])) {
      $params = $this->params['search'];
      $this->_data = $params['data'];
      $this->config['baseUri'] = $params['baseUri'];
      $this->config['defaults'] = $params['defaults'];
      if (isset($params['myMedia'])) {
        $this->isMyMedia = true;
      }
    }
    $this->config = am($config, $this->config);
  }

  /** Add parameter to the data array
    @param data Reference of data array
    @param add Add values */
  function _addParams(&$data, $add = false) {
    if (!$add) {
      return $data;
    }
    $add = (array)$add;

    foreach ($add as $name => $values) {
      if (!in_array($name, $this->singulars) && Inflector::pluralize($name) == $name || is_array($values)) {
        $name = Inflector::pluralize($name);
        if (!isset($data[$name])) {
          $data[$name] = array();
        }
        if (isset($data[$name]) && !is_array($data[$name])) {
          // fix array
          $data[$name] = array($data[$name]);
        }
        foreach ((array)$values as $value) {
          if (!in_array($value, $data[$name])) {
            $data[$name][] = $value;
          }
        }
      } else {
        $data[$name] = $values;
      }
    }

    return $data;
  }

  /** Removes parameter from the data array
    @param Reference of data array
    @param del Array of paramers which have to be removed */
  function _delParams(&$data, $del = false) {
    if (!$del) {
      return $data;
    }

    $del = (array)$del;

    foreach ($del as $name => $values) {
      if (is_numeric($name) && is_string($values)) {
        $name = $values;
      }
      if (!in_array($name, $this->singulars) && Inflector::pluralize($name) == $name || is_array($values)) {
        $name = Inflector::pluralize($name);
        if (!isset($data[$name])) {
          continue;
        }
        foreach ((array)$values as $value) {
          if (is_array($data[$name]) && in_array($value, $data[$name])) {
            $key = array_search($value, $data[$name]);
            unset($data[$name][$key]);
          }
        }
        if (count($data[$name]) == 0) {
          unset($data[$name]);
        }
      } else {
        unset($data[$name]);
      }
    }
    return $data;
  }

  /** Serialize the search
    @param data Search data. If false use current search. Default is false.
    @param add Array of parameters to add
    @param del Array of parameters to delete 
    @param options
      - defaults: Array of default values
    @result Serialized search as part of the URL */
  function serialize($data = false, $add = false, $del = false, $options = false) {
    $params = array();
    $config = $this->config;
    if (isset($options['defaults'])) {
      $config['defaults'] = am($config['defaults'], $options['defaults']);
    }

    if ($data === false || $data === null) {
      $data = $this->_data;
    }

    $this->_addParams(&$data, $add);
    $this->_delParams(&$data, $del);
    ksort($data);

    foreach ($data as $name => $values) {
      // get default value - if any
      if (isset($config['defaults'][$name])) {
        $default = $config['defaults'][$name];
      } else {
        $default = null;
      }
      if (empty($values) || $default === false) {
        continue;
      }

      if (is_array($values) && count($values) > 1) {
        // array handling
        if ($default && in_array($default, $values)) {
          unset($values[array_search($default, $values)]);
        }
        sort($values);
        $params[] = $name.':'.implode(',', $values);
      } else {
        // single value
        if (is_array($values)) {
          $values = array_shift($values);
        }
        if ($default === true || $default != $values) {
          // no default or disabled value
          $params[] = $name.':'.$values;
        }
      } 
    }
    return implode('/', $params);
  }

  /** 
    @param data Search data. If false use current search. Default is false.
    @param add Array of parameters to add
    @param del Array of parameters to delete 
    @return uri of current query */
  function getUri($data = false, $add = false, $del = false, $options = array()) {
    $serial = $this->serialize($data, $add, $del, $options);
    $config = am($this->config, $options);
    if ($serial) {
      return $config['baseUri'].'/'.$serial;
    } else {
      return $config['baseUri'];
    }
  }

  function link($data = false, $add = false, $del = false, $options = array()) {
    $out = $this->Html->link($this->getUri($data, $add, $del, $options));
    return $this->out($out);
  }

}
?>
