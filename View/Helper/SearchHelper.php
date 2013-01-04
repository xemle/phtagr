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

if (!class_exists('Search')) {
  App::import('File', 'Search', array('file' => APP.'search.php'));
}

class SearchHelper extends Search {

  var $helpers = array('Html');

  var $config = array(
    'baseUri' => '/explorer/query',
    'afterUri' => false,
    'defaults' => array(
      'page' => 1,
      'pos' => false,
      'show' => 12
      )
    );

  var $isMyMedia = false;

  /**
   * Singular exception where the inflector does not match
   */
  var $singulars = array('pos');

  /**
   * Initialize query parameters from the global parameter array, which is
   * set by the query component. All search parameters are reset after calling
   * this function. All previous changes are overritten
   */
  function initialize($config = array()) {
    if (isset($this->request->params['search'])) {
      $params = $this->request->params['search'];
      $this->_data = $params['data'];
      $this->config['baseUri'] = $params['baseUri'];
      $this->config['afterUri'] = $params['afterUri'];
      $this->config['defaults'] = $params['defaults'];
      if (isset($params['myMedia'])) {
        $this->isMyMedia = true;
      }
    }
    if (isset($config['defaults'])) {
      $this->config['defaults'] = am($this->config['defaults'], $config['defaults']);
      unset($config['defaults']);
    }
    $this->config = am($this->config, $config);
  }

  function __call($name, $args) {
    if (preg_match('/^(get|set|add|del|delete)(.*)$/', $name)) {
      return parent::__call($name, $args);
    } else {
      return;
    }
  }

  /**
   * Add parameter to the data array
   *
   * @param data Reference of data array
   * @param add Add values
   */
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

  /**
   * Removes parameter from the data array
   *
   * @param Reference of data array
   * @param del Array of paramers which have to be removed
   */
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

  /**
   * Serialize the search
   *
   * @param data Search data. If false use current search. Default is false.
   * @param add Array of parameters to add
   * @param del Array of parameters to delete
   * @param options
   *   - defaults: Array of default values
   * @return Serialized search as part of the URL
   */
  function serialize($data = false, $add = false, $del = false, $options = false) {
    $params = array();
    $config = $this->config;
    if (isset($options['defaults'])) {
      $config['defaults'] = am($config['defaults'], $options['defaults']);
    }

    if ($data === false || $data === null) {
      $data = $this->_data;
    }

    $this->_addParams($data, $add);
    $this->_delParams($data, $del);
    if (!$data) {
      return '';
    }
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
          $params[] = $name.':'.$this->encode($values);
        }
      }
    }
    return implode('/', $params);
  }

  /**
   * @param data Search data. If false use current search. Default is false.
   * @param add Array of parameters to add
   * @param del Array of parameters to delete
   * @return uri of current query
   */
  function getUri($data = false, $add = false, $del = false, $options = array()) {
    $serial = $this->serialize($data, $add, $del, $options);
    $config = am($this->config, $options);
    $uri = $config['baseUri'];
    if ($serial) {
      $uri .= '/' . $serial;
    }
    if ($config['afterUri']) {
      $uri .= $config['afterUri'];
    }
    return $uri;
  }

  function link($data = false, $add = false, $del = false, $options = array()) {
    $out = $this->Html->link($this->getUri($data, $add, $del, $options));
    return $this->out($out);
  }

}
?>