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

class Search extends Object
{

  var $_data;

  /** List of chars to escape on setParam() */
  var $escapeChars = '=,/';

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
    @param validate Optional parameter to validate the parameter. Default is
    true
    @return True on success */
  function setParam($name, $value, $validate = true) {
    if ($validate === false || $this->validate($name, $value)) {
      $this->_data[$name] = $value;
      return true;
    } else {
      return false;
    }
  }

  /** Add a parameter to an array.
    @param name Parameter name.
    @param value Parameter value (which will be pluralized)
    @param validate Optional parameter to validate the parameter. Default is
    true
    @note The name will be pluralized. */
  function addParam($name, $value, $validate = true) {
    $name = Inflector::pluralize($name);
    if (is_array($value)) {
      foreach ($value as $v) {
        $this->addParam($name, $v, $validate);
      }
      return;
    }

    if ((!isset($this->_data[$name]) || !in_array($value, $this->_data[$name])) &&
      ($validate === false || $this->validate($name, $value))) {
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
      $this->log("Search: Undefined function $name");
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
        } elseif (count($args) == 2) {
          return $this->setParam($name, $args[0], $args[1]);
        }
        break;
      case 'add':
        if (count($args) == 1) {
          return $this->addParam($name, $args[0]);
        } elseif (count($args) == 2) {
          return $this->addParam($name, $args[0], $args[1]);
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

  function encode($input) {
    $out = '';
    $input = (string)$input;
    $len = strlen($input);
    for ($i = 0; $i < $len; $i++) {
      $c = substr($input, $i, 1);
      if (strpos($this->escapeChars, $c) !== false) {
        $c = '=' . dechex(ord($c));
      }
      $out = $out . $c;
    }
    return $out;
  }

  function _c2h($c) {
    $d = ord($c);
    if ($d >= 48 && $d <= 57) {
      return $d - 48;
    } elseif ($d >= 65 && $d <= 70) {
      return $d - 55;
    } elseif ($d >= 97 && $d <= 102) {
      return $d - 87;
    } else {
      return false;
    }
  }

  function _dechex($c1, $c2) {
    $d1 = $this->_c2h($c1);
    $d2 = $this->_c2h($c2);
    if ($d1 === false || $d2 === false) {
      return false;
    } else {
      return chr($d1 * 16 + $d2);
    }
  }

  function decode($input) {
    $out = '';
    $input = (string)$input;
    $len = strlen($input);
    for ($i = 0; $i < $len; $i++) {
      $c = substr($input, $i, 1);
      if ($c == '=') {
        if ($i + 2 >= $len) {
          break;
        }
        $c1 = substr($input, $i + 1, 1);
        $c2 = substr($input, $i + 2, 1);
        $c = $this->_dechex($c1, $c2);
        if ($c !== false) {
          $out .= $c;
        }
        $i += 2;
      } else {
        $out .= $c;
      }
    }
    return $out;
  }
}
?>
