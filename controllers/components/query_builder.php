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
  var $components = array();

  var $controller = null;

  /**
    'name' => 'Model.field' // map condition to 'Model.field = value'
    'name' => array('rule' => 'special rule name') 
    'name' => array('field' => 'Model.field', 'operand' => 'condition operand')
  */
  var $rules = array(
    'to' => array('field' => 'Media.date', 'operand' => '<='),
    'from' => array('field' => 'Media.date', 'operand' => '>='),
    'categories' => array('custom' => 'buildHabtm'),
    'locations' => array('custom' => 'buildHabtm'),
    'media' => 'Media.id',
    'user' => 'User.username',
    'tags' => array('custom' => 'buildHabtm'),
    );

  function initialize(&$controller) {
    $this->controller = &$controller;
    $this->Sanitize =& new Sanitize();
  }

  /** Get array value if exist or default value
    @param data Data array
    @param name Key of data
    @param default Default value
    @return data value or default */
  function _getParam(&$data, $name, $default = null) {
    if (!isset($data[$name])) {
      return $default;
    } else {
      return $data[$name];
    }
  }

  /** Sanitize the data
    @param Data as single value or array
    @return Sanitized data */
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

  /** Build a single condition by field, value, and the operand and sanitze the
    value(s)
    @param field Field name
    @param value Value as single value or array
    @param operand Optional operand. Default is '='
    @return Sanitized condition */
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

  /** Extract exclusion parameters. A exclustion is a value which starts with a
    minus sign ('-')
    @param data Parameter data
    @param skip Parameter list which are not evaluated and skiped
    @return exclusions parameter */
  function _extractExclusions(&$data, $skip = array('sort')) {
    $exclusions = array();
    if (!count($data)) {
      return $exclusions;
    }

    foreach ($data as $name => $values) {
      if (in_array($name, $skip)) {
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
    //Logger::info($exclusions);
    return $exclusions;
  }

  function buildConditions($data) {
    $query = array();
    if (!count($data)) {
      return $query;
    }
    foreach ($this->rules as $name => $rule) {
      if (!isset($data[$name])) {
        continue;
      } else {
        $value = $data[$name];
      }
      $method = 'build'.Inflector::camelize($name);
      if (method_exists($this, $method)) {
        call_user_method($method, &$this, &$data, &$query, $value);
        continue;
      }
      
      if (!is_array($rule)) {
        $query['conditions'][] = $this->_buildCondition($rule, $value);
      } else {
        $rule = am(array('custom' => false, 'operand' => '='), $rule);
        if ($rule['custom']) {
          if (!method_exists($this, $rule['custom'])) {
            Logger::err("Custom method {$rule['custom']} does not exists or is missing");
            continue;
          }
          Logger::debug("Call custom rule {$rule['custom']}");
          call_user_method($rule['custom'], &$this, &$data, &$query, $name, $value);
        } elseif (!isset($rule['field'])) {
           Logger::err("Field in rule is missing");
           Logger::debug($rule);
           continue;
        } else {
           $query['conditions'][] = $this->_buildCondition($rule['field'], $value, $rule['operand']);
        }
      }
    }

    // paging, offsets and limit
    if (isset($data['pos'])) { 
      $query['offset'] = $data['pos'];
    } elseif (isset($data['offset']) && isset($data['limit'])) {
      $query['offset'] = $data['offset'] * $data['limit'];
    }
    if (isset($data['limit'])) {
      $query['limit'] = $data['limit'];
    }
    if (isset($data['sort'])) {
      $this->_buildOrder(&$data, &$query);
    }
    return $query;
  }
  
  function build($data) {
    $exclude = $this->_extractExclusions(&$data);
    $query = $this->buildConditions(&$data);
    if (count($exclude)) {
      $query['conditions']['exclude'] = $this->buildConditions($exclude);
    }
    Logger::debug($query);
    return $query;
  }

  function _buildOrder(&$data, &$query) {
    if ($data['sort'] == 'default') {
      if (isset($query['_counts']) && count($query['_counts']) > 0) {
        Logger::debug($data);
        if ($this->_getParam(&$data, 'operand') == 'OR') {
          Logger::debug("------------ harhar");
          // global OR operand
          $counts = array();
          $conditions = array();
          foreach ($query['_counts'] as $count) {
            $counts[] = "( $count + 1 )";
            $conditions[] = "IFNULL($count,0)";
          }
          $query['conditions'][] = '( '.implode(' + ', $conditions).' ) > 0';
          $query['order'][] = implode(" * ", $counts).' DESC';
        } else {
          // OR operand on habtm
          foreach (array('tag', 'category', 'location') as $habtm) {
            $fieldCount = Inflector::camelize($habtm)."Count";
            if (in_array($fieldCount, $query['_counts']) &&
              $this->_getParam(&$data, $habtm."Op") == 'OR') {
              $query['order'][] = $fieldCount.' DESC';
            }
          }
        }
      }
      $query['order'][] = 'Media.date';
    } else {
      switch ($data['sort']) {
        case 'date':
          $query['order'][] = 'Media.date DESC'; 
          break;
        case '-date':
          $query['order'][] = 'Media.date ASC'; 
          break;
        default:
          Logger::err("Unknown sort value: {$data['sort']}");
      }
    }
    $query['order'][] = 'Media.id';
  }

  function buildHabtm(&$data, &$query, $name, $value) {
    if (count($data[$name]) == 0) {
      return;
    }

    $habtm = Inflector::singularize($name);

    $field = Inflector::camelize($habtm).'.name';
    $query['conditions'][] = $this->_buildCondition($field, $value);

    $fieldCount = Inflector::camelize($habtm).'Count';
    $query['_counts'][] = $fieldCount;

    $operand = $this->_getParam(&$data, 'operand');
    if ($operand == 'OR') {
      // handled by sort
      return;
    } elseif ($operand == null) {
      // habtm operand
      $operand = $this->_getParam(&$data, $habtm."Op", 'AND');
    }

    switch ($operand) {
      case 'AND':
        $query['conditions'][] = $fieldCount.' = '.count($data[$name]);
        break;
      case 'OR':
        $query['conditions'][] = $fieldCount.' > 0';
        break;
      default:
        Logger::err("Unknown $field operand '$operand'");
    }
  }

}
?>
