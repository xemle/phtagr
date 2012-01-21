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

class DeleteUnusedBehavior extends ModelBehavior 
{
  var $config = array();

  /** Config the behavior
    @param Model Current model reference.
    @param config Configuration for the related results
    - relatedHabtm: Related HasAndBelongsToMany model. Default is the first HABTM model
   */ 
  function setup(&$Model, $config = array()) {
    $default = array();
    if (isset($Model->hasAndBelongsToMany) && is_array($Model->hasAndBelongsToMany)) {
      foreach($Model->hasAndBelongsToMany as $key => $definitions) {
        if (is_string($key)) {
          $default['relatedHabtm'] = $key;
          break;
        } elseif (is_string($definitions)) {
          $default['relatedHabtm'] = $definitions;
          break;
        }
      }
    }
    $this->config[$Model->name] = am($default, $config);
  }

  function _getConditions(&$Model) {
    $config = $this->config[$Model->name];
    $relatedHabtm = $config['relatedHabtm'];
    if (!isset($Model->hasAndBelongsToMany[$relatedHabtm])) {
      return false;
    }
    $prefix = $Model->tablePrefix;
    $alias = $Model->alias;
    $key = $Model->primaryKey;

    $joinTable = $Model->hasAndBelongsToMany[$relatedHabtm]['joinTable'];
    $foreignKey = $Model->hasAndBelongsToMany[$relatedHabtm]['foreignKey'];

    return array("$alias.$key NOT IN (SELECT `$foreignKey` FROM `$prefix$joinTable`)");
  }

  function findAllUnused(&$Model) {
    $conditions = $this->_getConditions($Model);
    if (!$conditions) {
      return false;
    }
    return $Model->find('all', array('conditions' => $conditions));
  }

  function deleteAllUnused(&$Model) {
    $conditions = $this->_getConditions($Model);
    if (!$conditions) {
      return false;
    }
    Logger::debug("Delete all unused association for {$Model->alias} with conditions: " . join(' and ', $conditions));
    return $Model->deleteAll($conditions);
  }

}
?>
