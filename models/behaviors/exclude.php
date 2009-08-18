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

class ExcludeBehavior extends ModelBehavior {

  function setup(&$Model, $settings = array()) {
    if (!isset($this->settings[$Model->alias])) {
      $this->settings[$Model->alias] = array();
    }
    if (!is_array($settings)) {
      $settings = array();
    }
    $this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], $settings);
  }

  /**
    @param Model current model object
    @param name Name of binding
    @result Type of the binding to the current model */
  function _findBindingType(&$Model, $name) {
    if (isset($Model->hasAndBelongsToMany[$name])) {
      return 'hasAndBelongsToMany';
    } elseif (isset($Model->hasMany[$name])) {
      return 'hasMany';
    }
    return false;
  }

  /** Build SQL joins for hasAndBelongsToMany relations
    @param Model current model object
    @param query query array
    @param joinConditions Join conditions for HABTM bindings
    @param joinType Type of SQL join (INNER, LEFT, RIGHT) */
  function _buildHasAndBelongsToManyJoins(&$Model, &$query, $joinConditions, $options = array()) {
    $options = am(array('type' => false, 'count' => true), $options);

    $options['type'] = strtoupper($options['type']);
    if (!in_array($options['type'], array('', 'RIGHT', 'LEFT'))) {
      Logger::warn("Invalid join type: ".$options['type']);
      $options['type'] = '';
    }
    foreach ($joinConditions as $name => $queryConditions) {
      $config = $Model->hasAndBelongsToMany[$name];
      //Logger::trace($config);
      extract($config);

      $alias = $Model->{$name}->alias;
      $table = $Model->{$name}->tablePrefix.$Model->{$name}->table;

      $join = "{$options['type']} JOIN ( SELECT $with.$foreignKey";
      if ($options['count']) {
        $count = Inflector::camelize($name).'Count';
        $join .= ", COUNT($with.$foreignKey) AS $count";
        if (!isset($query['_counts'])) {
          $query['_counts'] = array();
        }
        $query['_counts'][] = $count;
      }
      $join .= " FROM {$Model->tablePrefix}$joinTable AS $with, $table AS $alias";
      $join .= " WHERE $with.$associationForeignKey = $alias.id";
      $join .=   " AND ( ".implode(" OR ", $queryConditions)." )";
      $join .= " GROUP BY $with.$foreignKey ";
      $join .= ") AS $with ON {$Model->alias}.id = $with.$foreignKey";
      $query['joins'][] = $join;
    }
    //Logger::debug($query);
  }

  /** Build SQL joins for hasMany relations
    @param Model current model object
    @param query current query array
    @param joinConditions Conditions
    @param options Options */
  function _buildHasManyJoins(&$Model, &$query, &$joinConditions, $options = array()) {
    $options['type'] = strtoupper($options['type']);
    if (!in_array($options['type'], array(false, 'RIGHT', 'LEFT'))) {
      Logger::warn("Invalid join type: ".$options['type']);
      $options['type'] = '';
    }
    foreach ($joinConditions as $name => $queryConditions) {
      $config = $Model->hasMany[$name];
      //Logger::trace($config);

      $alias = $Model->{$name}->alias;
      $table = $Model->{$name}->tablePrefix.$Model->{$name}->table;
      $foreignKey = $config['foreignKey'];

      $join = "{$options['type']} JOIN ( SELECT $alias.$foreignKey";
      if ($options['count']) {
        $count = Inflector::camelize($name).'Count';
        $join .= ", COUNT($alias.id) AS $count";
        if (!isset($query['_counts'])) {
          $query['_counts'] = array();
        }
        $query['_counts'][] = $count;
      }
      $join .= " FROM $table AS $alias";
      $join .= " WHERE ".implode(" OR ", $queryConditions);
      $join .= " GROUP BY $alias.$foreignKey ";
      $join .= ") AS $alias ON {$Model->alias}.id = $alias.$foreignKey";
      $query['joins'][] = $join;
    }
    //Logger::debug($query);
  }

  /**
    Extracts conditions for hasAndBelongsToMany and hasMany relations and build
    joins for these relations.
    @param Model current model object
    @param query query array
    @param options Join options*/
  function _buildJoins(&$Model, &$query, $options = array()) {
    $joinConditions = array();
    if (empty($query['conditions'])) {
      return;
    }
    if (!is_array($query['conditions'])) {
      $conditions = array($query['conditions']);  
    } else {
      $conditions =& $query['conditions'];
    }
    //Logger::debug($conditions);
    foreach ($conditions as $key => $condition) {
      // we expect only full conditions
      if (!is_string($condition)) {
        return false;
      }
      // Match 'Model.field'
      if (!preg_match('/^(.*)\./', $condition, $matches)) {
        continue;
      }
      $name = $matches[1];
      $type = $this->_findBindingType($Model, $name);
      if (!$type) {
        continue;
      }
      if ($type == 'hasAndBelongsToMany' || $type == 'hasMany') {
        if (!is_array($query['conditions'])) {
          unset($query['conditions']);
        } else  {
          unset($conditions[$key]);
        }
      }
      $joinConditions[$type][$name][] = $condition;
    }
    //Logger::debug($joinConditions);
    if (isset($joinConditions['hasAndBelongsToMany'])) {
      $this->_buildHasAndBelongsToManyJoins($Model, &$query, $joinConditions['hasAndBelongsToMany'], $options);
    }
    if (isset($joinConditions['hasMany'])) {
      $this->_buildHasManyJoins($Model, &$query, $joinConditions['hasMany'], $options);
    }
    return $query;
  }

  /** Build the exclusion statement of a query array
    @param Model current model object
    @param query query array
    @return SQL exclusion condition */
  function _buildExclusion(&$Model, $query) {
    $query = am(array('joins' => array()), $query);
    //Logger::debug($query);
    $this->_buildJoins($Model, &$query, array('count' => true, 'type' => 'LEFT'));
    //Logger::debug($query);
    $exclusion = " {$Model->alias}.id NOT IN (";
    $exclusion .= " SELECT {$Model->alias}.id";
    $exclusion .= " FROM {$Model->tablePrefix}{$Model->table} AS {$Model->alias} ";
    $exclusion .= implode(' ', $query['joins']); 

    // build condition for outer join
    if (count($query['_counts'])) {
      $counts = array();
      foreach ($query['_counts'] as $count) {
        $counts[] = $count." > 0";
      }
      $condition = '( '.join(' OR ', $counts).' )';
      $query['conditions'][] = $condition;
    }
    $exclusion .= " WHERE ".implode(' AND ', $query['conditions']);

    $exclusion .= ")";
    return $exclusion;
  }

  function beforeFind(&$Model, $query) {
    $exclude = false;
    if (isset($query['conditions']['exclude']) &&
      is_array($query['conditions']['exclude'])) {
      $exclude = $query['conditions']['exclude'];
      unset($query['conditions']['exclude']);
    } elseif (isset($query['exclude'])) {
      $exclude = $query['exclude'];
    }
    //Logger::debug($query);
    $this->_buildJoins($Model, &$query, array('type' => 'LEFT'));
    if ($exclude) {
      $query['conditions'][] = $this->_buildExclusion($Model, $exclude);
    }
    //Logger::debug($query);
    return $query;
  }

}
?>
