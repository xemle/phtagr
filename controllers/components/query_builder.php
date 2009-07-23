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

App::import('Core', array('Sanitize'));

class QueryBuilderComponent extends Object
{
  var $components = array('Logger');

  var $controller = null;

  var $rules = array(
    'to' => array('field' => 'Media.date', 'operand' => '<='),
    'from' => array('field' => 'Media.date', 'operand' => '>='),
    'categories' => 'Category.name',
    'locations' => 'Location.name',
    'media' => 'Media.id',
    'page' => array('rule' => 'offset'),
    'show' => array('rule' => 'limit'),
    'tags' => 'Tag.name',
    'tagOp' => array('rule' => array('custom', 'buildHabtmOperand'))
    );

  function initialize(&$controller) {
    $this->controller = &$controller;
    $this->Sanitize =& new Sanitize();
  }

  function _sanitizeData($data) {
    if (!is_array($data)) {
      if (is_numeric($data)) {
        return $data;
      } else {
        return "'".$this->Sanitize->escape($data)."'";
      }
    } else {
      $escaped = array();
      foreach ($data as $value) {
        $escaped[] = $this->_sanitizeData($value);
      }
      return $escaped;
    }
  }

  function _buildCondition($field, $value, $operand = '=') {
    $condition = $field;
    if (!is_array($value)) {
      $condition .= " $operand ".$this->_sanitizeData($value);
    } elseif (count($value) == 1) {
      $condition .= " $operand ".$this->_sanitizeData(array_pop($value));
    } else {
      sort($value);
      $condition .= ' IN ('.implode(', ', $this->_sanitizeData($value)).')';
    } 
    return $condition; 
  }

  /** Extract exclusions 
    @param data Parameter data
    @param allow Parameter list which are not evaluated
    @return exclusions parameter */
  function _extractExclusions(&$data, $allow = array('sort')) {
    $exclusions = array();
    if (!count($data)) {
      return $exclusions;
    }

    foreach ($data as $name => $values) {
      if (in_array($name, $allow)) {
        continue;
      }

      if (!is_array($values)) {
        // single values
        if (preg_match('/^-(.*)$/', $values, $matches)) {
          $exclusions[$name] = $matches[1];
          unset($data[$name]);
        }
      } else {
        // array values
        foreach ($values as $key => $value) {
          if (preg_match('/^-(.*)$/', $value, $matches)) {
            $exclusions[$name] = $matches[1];
            unset($data[$name][$key]);
          }
        }
        // unset data if empty
        if (count($data[$name]) == 0) {
          unset($data[$name]);
        }
      }
    }
    Logger::info($exclusions);
    return $exclusions;
  }

  function buildConditions($data) {
    $query = array();
    if (!count($data)) {
      return $query;
    }
    foreach ($data as $name => $value) {
      $method = 'build'.Inflector::camelize($name);
      //Logger::info("Looking for method $method");
      if (method_exists($this, $method)) {
        call_user_method($method, &$this, &$data, &$query, $value);
        continue;
      }
      if (!isset($this->rules[$name])) {
        Logger::warn("No rule definition for '$name'!");
        continue;
      }
      
      $rule = $this->rules[$name];
      if (!is_array($rule)) {
        $query['conditions'][] = $this->_buildCondition($rule, $value);
      } else {
        $rule = am(array('rule' => 'default', 'operand' => '='), $rule);
        if (is_array($rule['rule'])) {
          $type = $rule['rule'][0];
        } else {
          $type = $rule['rule'];
        }
        switch ($type) {
          case 'limit':
            $query['limit'] = $this->_sanitizeData($value);
            break;
          case 'offset':
            $query['offset'] = $this->_sanitizeData($value);
            break;
          case 'custom':
            if (!isset($rule['rule'][1]) ||
              !method_exists($this, $rule['rule'][1])) {
              Logger::err("Method does not exists or is missing");
              continue;
            }
            call_user_method($rule['rule'][1], &$this, &$data, &$query, $name, $value);
            break;
          default:
            if (!isset($rule['field'])) {
              Logger::err("Field in rule is missing");
              Logger::debug($rule);
              break;
            }
            $query['conditions'][] = $this->_buildCondition($rule['field'], $value, $rule['operand']);
        }
      }
    }
    if (isset($query['offset']) && isset($query['limit'])) {
      $query['offset'] = $query['offset'] * $query['limit'];
    }
    return $query;
  }
  
  function build($data) {
    $exclude = $this->_extractExclusions(&$data);
    $query = $this->buildConditions(&$data);
    Logger::debug($query);
    if (count($exclude)) {
      $query['conditions']['exclude'] = $this->buildConditions($exclude);
    }
    return $query;
  }

  //function buildHabtmOperand(&$data, &$query, $value) {
  function buildHabtmOperand(&$data, &$query, $name, $value) {
    //Logger::info($query);
    //Logger::info($value);
    if ($name == 'tagOp') {
      $field = 'tags';
      $fieldCount = 'TagCount';
    } 
    if (!isset($data[$field]) || count($data[$field]) < 1) {
      return;
    }

    switch ($value) {
      case 'AND':
        $query['conditions'][] = $fieldCount.' = '.count($data[$field]);
        break;
      case 'OR':
        $query['conditions'][] = $fieldCount.' > 0';
        $query['order'][] = $fieldCount.' DESC';
        break;
      default:
        Logger::err("Unknown $field operand '$value'");
    } 
    Logger::debug($query);
  }
}
?>
