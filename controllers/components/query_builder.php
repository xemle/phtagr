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
    'categories' => array('custom' => 'buildHabtm'),
    'east' => array('field' => 'Media.longitude', 'operand' => '<='),
    'from' => array('field' => 'Media.date', 'operand' => '>='),
    'groups' => 'Group.name',
    'locations' => array('custom' => 'buildHabtm'),
    'media' => 'Media.id',
    'name' => 'Media.name',
    'north' => array('field' => 'Media.latitude', 'operand' => '<='),
    'south' => array('field' => 'Media.latitude', 'operand' => '>='),
    'to' => array('field' => 'Media.date', 'operand' => '<='),
    'tags' => array('custom' => 'buildHabtm'),
    'type' => array('field' => 'Media.type', 'mapping' => array('image' => MEDIA_TYPE_IMAGE, 'video' => MEDIA_TYPE_VIDEO)),
    //'visibility' => true, // calls buildVisibility
    'user' => true,
    'west' => array('field' => 'Media.longitude', 'operand' => '>='),
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

  /** Sanitize the data by escaping the value
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
    @param Options Optional options
      - operand: Default is '='
      - mapping: Array of value mapping
    @return Sanitized condition */
  function _buildCondition($field, $value, $options = false) {
    if (is_string($options)) {
      $o['operand'] = $options;
      $options = $o;
    }
    $options = am(array('operand' => '=', 'mapping' => array()), $options);
    $operands = array('=', '>', '<', '>=', '<=', 'IN', 'NOT IN', 'LIKE');
    extract($options);
    if (!in_array(strtoupper($operand), $operands)) {
      Logger::err("Illigal operand '$operand'. Set it to '='");
      $operand = '=';
    }
    
    if (isset($mapping) && !is_array($value) && isset($mapping[$value])) {
      $value = $mapping[$value];
    }
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
  function _extractExclusions(&$data, $skip = array('sort', 'north', 'south', 'west', 'east')) {
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
            if (!isset($exclusions[$name])) {
              $exclusions[$name] = array();
            }
            $exclusions[$name][] = $matches[1];
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
    $query = array('conditions' => array());
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
        $rule = am(array('custom' => false, 'operand' => '=', 'mapping' => false), $rule);
        if ($rule['custom']) {
          if (!method_exists($this, $rule['custom'])) {
            Logger::err("Custom method {$rule['custom']} does not exists or is missing");
            continue;
          }
          //Logger::debug("Call custom rule {$rule['custom']}");
          call_user_method($rule['custom'], &$this, &$data, &$query, $name, $value);
        } elseif (!isset($rule['field'])) {
           Logger::err("Field in rule is missing");
           Logger::debug($rule);
           continue;
        } else {
           $query['conditions'][] = $this->_buildCondition($rule['field'], $value, $rule);
        }
      }
    }

    // paging, offsets and limit
    if (!empty($data['pos'])) { 
      $query['offset'] = $data['pos'];
    } elseif (isset($data['show']) && isset($data['page'])) {
      $query['page'] = $data['page'];
    }
    if (isset($data['show'])) {
      $query['limit'] = $data['show'];
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
      $exclude['operand'] = 'OR';
      $query['conditions']['exclude'] = $this->buildConditions($exclude);
    }
    return $query;
  }

  function _buildOrder(&$data, &$query) {
    if ($data['sort'] == 'default') {
      if (isset($query['_counts']) && count($query['_counts']) > 0) {
        if ($this->_getParam(&$data, 'operand') == 'OR') {
          // global OR operand
          $counts = array();
          $conditions = array();
          foreach ($query['_counts'] as $count) {
            $counts[] = "( COALESCE($count, 0) + 1 )";
            $conditions[] = "COALESCE($count, 0)";
          }
          $query['conditions'][] = '( '.implode(' + ', $conditions).' ) > 0';
          $query['order'][] = implode(" * ", $counts).' DESC';
        } else {
          // OR operand on habtm
          foreach (array('tag', 'category', 'location') as $habtm) {
            $fieldCount = Inflector::camelize($habtm)."Count";
            if (in_array($fieldCount, $query['_counts']) &&
              $this->_getParam(&$data, $habtm."_op") == 'OR') {
              $query['order'][] = "COALESCE($fieldCount, 0) DESC";
            }
          }
        }
      }
      $query['order'][] = 'Media.date DESC';
    } else {
      switch ($data['sort']) {
        case 'date':
          $query['order'][] = 'Media.date DESC'; 
          break;
        case '-date':
          $query['order'][] = 'Media.date ASC'; 
          break;
        case 'newest':
          $query['order'][] = 'Media.created DESC'; 
          break;
        case 'changes':
          $query['order'][] = 'Media.modified DESC'; 
          break;
        case 'viewed':
          $query['order'][] = 'Media.lastview DESC'; 
          break;
        case 'popularity':
          $query['order'][] = 'Media.ranking DESC'; 
          break;
        case 'random':
          $query['order'][] = 'RAND()'; 
          break;
        default:
          Logger::err("Unknown sort value: {$data['sort']}");
      }
    }
  }

  /**
    @param data Search parameters array
    @param SQL array
    @param name Parameter name
    @param value Parameter value */
  function buildHabtm(&$data, &$query, $name, $value) {
    if (count($data[$name]) == 0) {
      return;
    }

    $habtm = Inflector::singularize($name);

    $field = Inflector::camelize($habtm).'.name';

    $tags = array();
    foreach($value as $v) {
      if (preg_match('/[*\?]/', $v)) {
        $v = preg_replace('/\*/', '%', $v);
        $v = preg_replace('/\?/', '_', $v);
        $query['conditions'][] = $this->_buildCondition($field, $v, array('operand' => 'LIKE'));
      } else {
        $tags[] = $v;
      }
    }
    if (count($tags)) {
      $query['conditions'][] = $this->_buildCondition($field, $tags);
    }

    $fieldCount = Inflector::camelize($habtm).'Count';
    $query['_counts'][] = $fieldCount;

    // handle operand conditions (AND and OR)
    $operand = $this->_getParam(&$data, 'operand');
    if ($operand == 'OR') {
      // handled by _buildOrder()
      return;
    } elseif ($operand == null) {
      // habtm operand
      $operand = $this->_getParam(&$data, $habtm."_op", 'AND');
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

  function buildUser(&$data, &$query, $value) {
    $user = $this->controller->getUser();
    $userId = 0;

    // get users id for backwards compatibility
    $u = $this->controller->User->findByUsername($value);
    if ($u && ($u['User']['role'] >= ROLE_USER ||
        $u['User']['role'] == ROLE_GUEST && $u['User']['id'] == $user['User']['id'])) {
      $userId = $u['User']['id'];
    } else {
      // user not found or wrong invalid guest name
      Logger::warn("Invalid user. Disable search");
      $query['conditions'][] = '1 = 0';
    }

    if ($userId) {
      $query['conditions'][] = "User.id = $userId";
    }
  }

  function buildVisibility(&$data, &$query, $value) {
    // allow only admins to query others visibility, otherwise query only media
    // of the current user
    $me = $this->controller->getUser();
    if (isset($data['user']) && $data['user'] != $me['User']['username'] && $me['User']['role'] == ROLE_ADMIN) {
      $user = $this->controller->User->findByUsername($data['user']);
      if ($user && $user['User']['role'] >= ROLE_USER) {
        $userId = $user['User']['id'];
      } else {
        $userId = -1;
      }
    } else {
      $userId = $this->controller->getUserId();
    }
    $query['conditions'][] = $this->_buildCondition("User.id", $userId);

    // setup visibility level
    switch ($value) {
      case 'private':
        $query['conditions'][] = $this->_buildCondition("Media.gacl", ACL_READ_PREVIEW, '<');
        break;
      case 'group':
        $query['conditions'][] = $this->_buildCondition("Media.gacl", ACL_READ_PREVIEW, '>=');
        $query['conditions'][] = $this->_buildCondition("Media.uacl", ACL_READ_PREVIEW, '<');
        break;
      case 'user':
        $query['conditions'][] = $this->_buildCondition("Media.uacl", ACL_READ_PREVIEW, '>=');
        $query['conditions'][] = $this->_buildCondition("Media.oacl", ACL_READ_PREVIEW, '<');
        break;
      case 'public':
        $query['conditions'][] = $this->_buildCondition("Media.oacl", ACL_READ_PREVIEW, '>=');
        break;
      default:
        Logger::err("Unknown visibility value $value");
    }
  }
}
?>