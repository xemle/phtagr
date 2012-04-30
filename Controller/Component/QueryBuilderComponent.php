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

App::uses('Sanitize', 'Utility');

class QueryBuilderComponent extends Component
{
  var $controller = null;

  /**
   * 'name' => 'Model.field' // map condition to 'Model.field = value'
   * 'name' => array('rule' => 'special rule name') 
   * 'name' => array('field' => 'Model.field', 'operand' => 'condition operand')
   */
  var $rules = array(
    'categories' => array('custom' => 'buildHabtm'),
    'created_from' => array('field' => 'Media.created', 'operand' => '>='),
    'east' => array('field' => 'Media.longitude', 'operand' => '<='),
    'exclude_user' => array('field' => 'Media.user_id', 'operand' => '!='),
    'folder' => true, // calls buildFolder
    'from' => array('field' => 'Media.date', 'operand' => '>='),
    'groups' => array('custom' => 'buildHabtm'),
    'locations' => array('custom' => 'buildHabtm'),
    'media' => 'Media.id',
    'name' => 'Media.name',
    'north' => array('field' => 'Media.latitude', 'operand' => '<='),
    'south' => array('field' => 'Media.latitude', 'operand' => '>='),
    'to' => array('field' => 'Media.date', 'operand' => '<='),
    'tags' => array('custom' => 'buildHabtm'),
    'type' => array('field' => 'Media.type', 'mapping' => array('image' => MEDIA_TYPE_IMAGE, 'video' => MEDIA_TYPE_VIDEO)),
    'visibility' => true, // calls buildVisibility
    'west' => array('field' => 'Media.longitude', 'operand' => '>='),
    );

  function initialize(&$controller) {
    $this->controller = &$controller;
  }

  /** 
   * Get array value if exist or default value
   * 
   * @param data Data array
   * @param name Key of data
   * @param default Default value
   * @return data value or default 
   */
  function _getParam(&$data, $name, $default = null) {
    if (!isset($data[$name])) {
      return $default;
    } else {
      return $data[$name];
    }
  }

  /** 
   * Sanitize the data by escaping the value
   * 
   * @param Data as single value or array
   * @return Sanitized data 
   */
  function _sanitizeData($data) {
    if (!is_array($data)) {
      if (is_numeric($data)) {
        return $data;
      } else {
        return "'".Sanitize::escape($data)."'";
      }
    } else {
      $escaped = array();
      foreach ($data as $value) {
        $escaped[] = $this->_sanitizeData($value);
      }
      return $escaped;
    }
  }

  /** 
   * Build a single condition by field, value, and the operand and sanitze the
   * value(s)
   * 
   * @param field Field name
   * @param value Value as single value or array
   * @param Options Optional options
   *   - operand: Default is '='
   *   - mapping: Array of value mapping
   * @return Sanitized condition 
   */
  function _buildCondition($field, $value, $options = false) {
    if (is_string($options)) {
      $o['operand'] = $options;
      $options = $o;
    }
    $options = am(array('operand' => '=', 'mapping' => array()), $options);
    $operands = array('=', '!=', '>', '<', '>=', '<=', 'IN', 'NOT IN', 'LIKE');
    extract($options);
    if (!in_array(strtoupper($operand), $operands)) {
      Logger::err("Illigal operand '$operand'. Set it to '='");
      $operand = '=';
    }
    
    if (isset($mapping) && !is_array($value) && isset($mapping[$value])) {
      $value = $mapping[$value];
    }
    $condition = $field;
    $value = (array)$value;
    if (count($value) == 1 && $operand != 'IN' && $operand != 'NOT IN') {
      $condition .= " $operand " . $this->_sanitizeData(array_pop($value));
    } else {
      if ($operand == '=') {
        $operand = 'IN';
      }
      if ($operand != 'IN' && $operand != 'NOT IN') {
        Logger::err("Illigal operand '$operand' for field '$field' with array values: " . implode(', ', $value) . ". Use first value.");
        $condition .= " $operand ".$this->_sanitizeData(array_pop($value));
      } else {
        sort($value);
        $condition .= " $operand (" . implode(', ', $this->_sanitizeData($value)) . ')';
      }
    } 
    return $condition; 
  }

  /** 
   * Extract exclusion parameters. A exclustion is a value which starts with a
   * minus sign ('-')
   * 
   * @param data Parameter data
   * @param skip Parameter list which are not evaluated and skiped
   * @return exclusions parameter 
   */
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
    $this->_buildAccessConditions(&$data, &$query);

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
        case 'name':
          $query['order'][] = 'Media.name'; 
          break;
        case '-name':
          $query['order'][] = 'Media.name DESC'; 
          break;
        default:
          Logger::err("Unknown sort value: {$data['sort']}");
      }
    }
  }

  /**
   * @param data Search parameters array
   * @param SQL array
   * @param name Parameter name
   * @param value Parameter value 
   */
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
        $query['conditions'][] = $fieldCount.' >= '.count($data[$name]);
        break;
      case 'OR':
        $query['conditions'][] = $fieldCount.' > 0';
        break;
      default:
        Logger::err("Unknown $field operand '$operand'");
    }
  }

  function _buildAccessConditions(&$data, &$query) {
    if (isset($data['visibility'])) {
      return true;
    }
    $user = $this->controller->getUser();
    $userId = 0;
    if (isset($data['user'])) {
      // get users id for backwards compatibility
      $u = $this->controller->User->findByUsername($data['user']);
      if ($u && ($u['User']['role'] >= ROLE_USER ||
          $u['User']['role'] == ROLE_GUEST && $u['User']['id'] == $user['User']['id'])) {
        $userId = $u['User']['id'];
      } else {
        // user not found or wrong invalid guest name
        Logger::warn("Invalid user. Disable search");
        $query['conditions'][] = '1 = 0';
      }
    }
    $acl = $this->controller->Media->buildAclConditions($user, $userId);
    $query['conditions'] = am($query['conditions'], $acl);
  }

  function buildFolder(&$data, &$query, $value) {
    if (!isset($data['user']) || $value === false) {
      return;
    }
    $me = $this->controller->getUser();
    if ($data['user'] != $me['User']['username']) {
      $user = $this->controller->User->find('first', array('conditions' => array('User.username' => $data['user'], 'User.role >=' => ROLE_USER), 'fields' => 'User.id', 'recursive' => -1));
      if (!$user) {
        return;
      }
      $userId = $user['User']['id'];
    } else {
      $userId = $this->controller->getUserId();
    }
    $uploadPath = USER_DIR . $userId . DS . 'files' . DS . $value . '%'; 
    $query['conditions'][] = $this->_buildCondition("File.path", $uploadPath, array('operand' => 'LIKE'));
    $query['conditions'][] = "FileCount > 0";
    $query['_counts'][] = "FileCount";
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