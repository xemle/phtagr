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

class QueryBuilderComponent extends Component {
  var $controller = null;

  /**
   * 'name' => 'Model.field' // map condition to 'Model.field = value'
   * 'name' => array('rule' => 'special rule name')
   * 'name' => array('field' => 'Model.field', 'operand' => 'condition operand')
   */
  var $conditionRules = array(
    'category' => array('field' => 'Field.data', 'with' => array('Field.name' => 'category')),
    'city' => array('field' => 'Field.data', 'with' => array('Field.name' => 'city')),
    'country' => array('field' => 'Field.data', 'with' => array('Field.name' => 'country')),
    'created_from' => array('field' => 'Media.created', 'operand' => '>='),
    'east' => array('field' => 'Media.longitude', 'operand' => '<='),
    'folder' => 'File.path',
    'from' => array('field' => 'Media.date', 'operand' => '>='),
    'group' => 'Group.name',
    'location' => array('field' => 'Field.data', 'with' => array('Field.name' => array('sublocation', 'city', 'state', 'country'))),
    'media' => 'Media.id',
    'name' => 'Media.name',
    'north' => array('field' => 'Media.latitude', 'operand' => '<='),
    'south' => array('field' => 'Media.latitude', 'operand' => '>='),
    'state' => array('field' => 'Field.data', 'with' => array('Field.name' => 'state')),
    'sublocation' => array('field' => 'Field.data', 'with' => array('Field.name' => 'sublocation')),
    'to' => array('field' => 'Media.date', 'operand' => '<='),
    'tag' => array('field' => 'Field.data', 'with' => array('Field.name' => 'keyword')),
    'type' => 'Media.type',
    'west' => array('field' => 'Media.longitude', 'operand' => '>='),
    );
  var $counter = 0;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  /**
   * Get array value if exist or default value
   *
   * @param data Data array
   * @param name Key of data
   * @param default Default value
   * @return data value or default
   */
  private function _getParam(&$data, $name, $default = null) {
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
  private function _sanitizeData($data) {
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
   * @return Array of Sanitized condition and value count
   */
  private function _buildCondition($field, $value, $options = array()) {
    $options = am(array('operand' => '='), $options);
    $operands = array('=', '!=', '>', '<', '>=', '<=', 'IN', 'NOT IN', 'LIKE');
    extract($options);
    if (!in_array(strtoupper($operand), $operands)) {
      Logger::err("Illigal operand '$operand'. Set it to '='");
      $operand = '=';
    }

    $condition = $field;
    $value = (array)$value;
    $count = count($value);
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
    return array($condition, $count);
  }

  private function _getModelName($field) {
    if (preg_match('/^(\w+)\..*/', $field, $m)) {
      return $m[1];
    }
    Logger::error("Could not get model name from $field!");
    return false;
  }

  private function _addCondition(&$modelToConditions, $field, &$condition, $count = 1) {
    $model = $this->_getModelName($field);
    if (!$model) {
      return;
    } else if (!isset($modelToConditions[$model])) {
      $modelToConditions[$model] = array('conditions' => array(), 'count' => 0);
    }
    $modelToConditions[$model]['conditions'][] = $condition;
    $modelToConditions[$model]['count'] += $count;
  }

  /**
   * Build condition list from params
   *
   * @param array params Parameter array
   * @return array Array of conditions sorted by model
   */
  private function _buildConditions(&$params) {
    $conditions = array();
    if (!count($params)) {
      return $conditions;
    }
    foreach ($params as $name => $value) {
      if (!isset($this->conditionRules[$name])) {
        Logger::warn("No rule for $name. Skip it.");
        continue;
      }

      $rule = $this->conditionRules[$name];
      if (!is_array($rule)) {
        list($condition, $count) = $this->_buildCondition($rule, $value);
        $this->_addCondition($conditions, $rule, $condition, $count);
      } else {
        $rule = am(array('field' => false, 'operand' => '=', 'with' => false), $rule);
        if (!$rule['field']) {
           Logger::err("Field in rule is missing for parameter name '$name'");
           Logger::debug($rule);
           continue;
        } else if ($rule['with']) {
          list($condition, $count) = $this->_buildCondition($rule['field'], $value, $rule);
          if (is_array($rule['with'])) {
            $withConditions = array();
            foreach ($rule['with'] as $field => $value) {
              list($withCondition) = $this->_buildCondition($field, $value);
              $withConditions[] = $withCondition;
            }
            if (count($withConditions) > 1) {
              $flat = "( " . join(" AND ", $withConditions) . " )";
            } else {
              $flat = $withConditions[0];
            }
            $combined = array('AND' => am($condition, $flat));
          } else {
            $combined = array('AND' => am($condition, $rule['with']));
          }
          $this->_addCondition($conditions, $rule['field'], $combined, $count);
        } else {
          list($condition, $count) = $this->_buildCondition($rule['field'], $value, $rule);
          $this->_addCondition($conditions, $rule['field'], $condition, $count);
        }
      }
    }
    return $conditions;
  }

  /**
   * Extract inclusion and exclusion parameters. A exclustion is a value
   * starting with a minus sign ('-'). An inclusion starts with a plus sign.
   *
   * @param data Parameter data
   * @param skip Parameter list which are not evaluated and skiped
   * @return array Array of inclusions, optionals and exclusions
   */
  private function _splitRequirements(&$data, $skip = array('sort', 'north', 'south', 'west', 'east')) {
    $inclusions = array();
    $exclusions = array();
    if (!count($data)) {
      return array($inclusions, $exclusions);
    }

    foreach ($data as $name => $values) {
      if (in_array($name, $skip)) {
        continue;
      }

      if (!is_array($values)) {
        // single values
        if (preg_match('/^([-+])(.*)$/', $values, $matches)) {
          if ($matches[1] == '-') {
            $exclusions[$name] = $matches[2];
          } else {
            $inclusions[$name] = $matches[2];
          }
          unset($data[$name]);
        }
      } else {
        // array values
        foreach ($values as $key => $value) {
          if (preg_match('/^([-+])(.*)$/', $value, $matches)) {
            if ($matches[1] == '-') {
              $list =& $exclusions;
            } else {
              $list =& $inclusions;
            }
            if (!isset($list[$name])) {
              $list[$name] = array();
            }
            $list[$name][] = $matches[2];
            unset($data[$name][$key]);
          }
        }
        // unset data if empty
        if (count($data[$name]) == 0) {
          unset($data[$name]);
        }
      }
    }
    return array($inclusions, $exclusions);
  }

  private function _buildSqlConditions($conditions) {
    $result = array();
    foreach ($conditions as $key => $value) {
      $sqls = array();
      $operand = 'AND';
      if ($key === 'OR') {
        $sqls = $this->_buildSqlConditions($value);
        $operand = 'OR';
      } else if ($key === 'AND' || (is_numeric($key) && is_array($value))) {
        $sqls = $this->_buildSqlConditions($value);
      } else if (!is_numeric($key) && is_array($value)) {
        $sqls[] = $key . "(" . join(', ', $this->_sanitizeData($value)) . ")";
      } else if (!is_numeric($key)) {
        $value = $this->_sanitizeData($value);
        if (preg_match('/(.*)\s*(=|<|>|>=|<=|IN|LIKE)\s*/', $key, $m)) {
          if ($m[2] == 'IN') {
            $sqls[] = "{$m[1]} {$m[2]} (" . join(", ", (array) $value) . ")";
          } else {
            $sqls[] = "{$m[1]} {$m[2]} " . $value;
          }
        } else {
          $sqls[] = $key . ' = ' . $value;
        }
      } else {
        $sqls[] = $value;
      }
      if (count($sqls) > 1) {
        $result[] = "(" . join(" $operand ", $sqls) . ")";
      } else if (count($sqls) > 0) {
        $result[] = array_pop($sqls);
      }
    }
    return $result;
  }

  private function _buildHABTMStatement($Model, $assoc, $conditions, $count, $operand) {
    if (!isset($Model->{$assoc})) {
      Logger::err("Could not access $assoc");
    }
    $this->counter++;
    $table = $Model->{$assoc}->tablePrefix.$Model->{$assoc}->table;
    $alias = $Model->{$assoc}->alias;
    $key = $Model->{$assoc}->primaryKey;

    $config = $Model->hasAndBelongsToMany[$assoc];

    $joinTable = $Model->tablePrefix.$config['joinTable'];
    $joinAlias = "{$config['with']}{$this->counter}";
    $foreignKey = $config['foreignKey'];
    $associationForeignKey = $config['associationForeignKey'];

    $modelAlias = $Model->alias;
    $modelKey = $Model->primaryKey;
    $counterName = "{$assoc}Count{$this->counter}";

    $join = "LEFT JOIN ("
            ." SELECT `$joinAlias`.`$foreignKey`,COUNT(*) as $counterName"
            ." FROM `$joinTable` AS `$joinAlias`, `$table` AS `$alias`"
            ." WHERE `$joinAlias`.`$associationForeignKey` = `$alias`.`$key`"
            ." AND (" . join(' AND ', $this->_buildSqlConditions($conditions)) . ")"
            ." GROUP BY `$joinAlias`.`$foreignKey`) AS $joinAlias ON `$joinAlias`.`$foreignKey` = `$modelAlias`.`$modelKey`";

    if ($operand === 'AND') {
      $conditions = array("COALESCE($counterName, 0) >= " . $count);
    } else if ($operand === 'OR') {
      $conditions = array("COALESCE($counterName, 0) >= " . 1);
    } else if ($operand === 'NOT') {
      $conditions = array("COALESCE($counterName, 0) = " . 0);
    }
    return array('joins' => array($join), 'conditions' => $conditions, '_counters' => array($counterName));
  }

  private function _getAssociationType($Model, $assoc) {
    $data = $Model->hasAndBelongsToMany;
    if (isset($Model->hasAndBelongsToMany[$assoc])) {
      return 'HABTM';
    }
    return false;
  }

  private function _buildJoins($conditions, $operand = 'AND') {
    $Model =& $this->controller->Media;
    $query = array();
    foreach ($conditions as $assoc => $assocConditions) {
      $type = $this->_getAssociationType($Model, $assoc);
      if ($type == 'HABTM') {
        $query = array_merge_recursive($query, $this->_buildHABTMStatement($Model, $assoc, $assocConditions['conditions'], $assocConditions['count'], $operand));
      }
    }
    return $query;
  }

  public function build($data) {
    $this->counter = 0;
    list($include, $exclude) = $this->_splitRequirements($data);
    $operand = $this->_getParam($data, 'operand', 'AND');
    $query = array();

    $conditionsByModel = $this->_buildConditions($data);
    $subQuery = $this->_buildJoins($conditionsByModel, $operand);
    $query = array_merge_recursive($query, $subQuery);
    if (count($exclude)) {
      $conditionsByModel = $this->_buildConditions($exclude);
      $excludeQuery = $this->_buildJoins($conditionsByModel, 'NOT');
      unset($excludeQuery['_counters']);
      $query = array_merge_recursive($query, $excludeQuery);
    }
    if (count($include)) {
      $conditionsByModel = $this->_buildConditions($exclude);
      $includeQuery = $this->_buildJoins($conditionsByModel, 'AND');
      $query = array_merge_recursive($query, $includeQuery);
    }
    $this->_buildAccessConditions($data, $query);
    $this->_buildOrder($data, $query);
    $visibility = $this->_getParam($data, 'visibility');
    if ($visibility) {
      $this->_buildVisibility($data, $query, $visibility);
    }
    $query['group'] = 'Media.id';
    return $query;
  }

  private function _buildOrder(&$data, &$query) {
    if (!isset($data['sort']) || $data['sort'] == 'default') {
      if (isset($query['_counters']) && count($query['_counters']) > 0) {
        if (count($query['_counters']) > 1) {
          $counters = array();
          foreach ($query['_counters'] as $counter) {
            $counters[] = "( COALESCE($counter, 0) + 1 )";
          }
          $query['order'][] = implode(" * ", $counters).' DESC';
        } else {
          $counter = $query['_counters'][0];
          $query['order'][] = "COALESCE($counter, 0) DESC";
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
   * @param array $options Array of builder options
   */
  private function buildField(&$data, &$query, $name, $values, $options) {
    if (count($data[$name]) == 0) {
      return;
    }

    $model = 'Field';
    if (isset($options['model'])) {
      $model = $options['model'];
    }
    if (isset($options['names'])) {
      $name = $options['names'];
    }
    $count = 0;
    $fieldValues = array();
    foreach((array)$values as $value) {
      if (preg_match('/[*\?]/', $value)) {
        $value = preg_replace('/\*/', '%', $value);
        $value = preg_replace('/\?/', '_', $value);
        $valueCondition = $this->_buildCondition($model.'.data', $value, array('operand' => 'LIKE'));
        $query['conditions'][] = array('AND' => array(
            $this->_buildCondition($model.'.name', (array)$name),
            $valueCondition
        ));
      } else {
        $fieldValues[] = $value;
      }
      $count++;
    }
    if ($fieldValues) {
        $query['conditions'][] = array('AND' => array(
            $this->_buildCondition($model.'.name', (array)$name),
            $this->_buildCondition($model.'.data', (array)$fieldValues),
        ));
    }

    // handle operand conditions (AND and OR)
    $operand = $this->_getParam($data, 'operand', 'OR');

    $counter = $model.'Count';
    if (!isset($query['_counts']) || !in_array($counter, $query['_counts'])) {
      $query['_counts'][] = $counter;
    }
    $counter = $counter . ' >=';
    if ($operand === 'AND') {
      $count = !empty($query['conditions'][$counter]) ? $query['conditions'][$counter] : 0;
      $count += count($data[$name]);
      $query['conditions'][$counter] = $count;
    } else if ($operand === 'OR') {
      $query['conditions'][$counter] = 1;
    } else {
      Logger::err("Unknown operand '$operand'");
    }
  }

  private function _buildAccessConditions(&$data, &$query) {
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
    $aclQuery = $this->controller->Media->buildAclQuery($user, $userId);
    $query = array_merge_recursive($query, $aclQuery);
  }

  private function buildFolder(&$data, &$query, $value) {
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

  private function _buildVisibility(&$data, &$query, $value) {
    // allow only admins to query others visibility, otherwise query only media
    // of the current user
    $me = $this->controller->getUser();
    $userId = $this->controller->getUserId();
    if (isset($data['user'])) {
      if ($data['user'] != $me['User']['username'] && $me['User']['role'] == ROLE_ADMIN) {
        $user = $this->controller->User->findByUsername($data['user']);
        if ($user && $user['User']['role'] >= ROLE_USER) {
          $userId = $user['User']['id'];
        } else {
          $userId = -1;
        }
      } else if ($data['user'] != $me['User']['username']) {
        // Deny invalid user parameter
        $query['conditions'][] = "1 = 0";
        return;
      }
    }

    list($condition) = $this->_buildCondition("Media.user_id", $userId);
    $query['conditions'][] = $condition;

    // setup visibility level
    switch ($value) {
      case 'private':
        list($condition) = $this->_buildCondition("Media.gacl", ACL_READ_PREVIEW, array('operand' => '<'));
        $query['conditions'][] = $condition;
        break;
      case 'group':
        list($condition1) = $this->_buildCondition("Media.gacl", ACL_READ_PREVIEW, array('operand' => '>='));
        $query['conditions'][] = $condition1;
        list($condition2) = $this->_buildCondition("Media.uacl", ACL_READ_PREVIEW, array('operand' => '<'));
        $query['conditions'][] = $condition2;
        break;
      case 'user':
        list($condition1) = $this->_buildCondition("Media.uacl", ACL_READ_PREVIEW, array('operand' => '>='));
        $query['conditions'][] = $condition1;
        list($condition2) = $this->_buildCondition("Media.oacl", ACL_READ_PREVIEW, array('operand' => '<'));
        $query['conditions'][] = $condition2;
        break;
      case 'public':
        list($condition) = $this->_buildCondition("Media.oacl", ACL_READ_PREVIEW, '>=');
        $query['conditions'][] = $condition;
        break;
      default:
        Logger::err("Unknown visibility value $value");
        $query['conditions'][] = "1 = 0";
    }
  }
}
