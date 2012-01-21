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

class RelatedBehavior extends ModelBehavior 
{
  var $config = array();

  /** Config the behavior
    @param Model Current model reference.
    @param config Configuration for the related results
    - relatedHabtm: Related HasAndBelongsToMany model. Default is the first HABTM model
    - fields: Array of fields of the model which should be fetched
    - limit: Limit count of the related items. Default is 16 */
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
    $default['fields'] = array($Model->primaryKey);
    foreach($Model->_schema as $name => $definitions) {
      if (in_array($definitions['type'], array('string', 'text'))) {
        $default['fields'][] = $name;
      }
    }
    $default['limit'] = 16;

    $this->config[$Model->name] = am($default, $config);
  }

  /** Search for related items
   *
   * <code>
   * $tag = $this->Media->Tag->findByName($name);
   * $this->Media->Tag->Behaviors->attach('Related', array('relatedHabtm' => 'Media', 'fields' => array('id', 'name')));
   * $this->Media->Tag->bindModel(array('hasAndBelongsToMany' => array('Media')));
   * $this->data = $this->Media->Tag->related($tag['Tag']['id']);
   * </code>
   * 
   * @param Model Reference to the current model (Set automatic by the Behavior)
   * @param ids Id or Array of Ids to be related to
   * @param options Options
   * - relatedHabtm: (Optional) Related HasAndBelongsToMany model
   * - fields: (Optional) Array of fields of the model
   * - limit: (Optional) Limit count of the related items */
  function related(&$Model, $ids, $options = array()) {
    $ids = (array)$ids;
    if (count($ids) == 0) {
      return false;
    }

    $config = am($this->config[$Model->name], $options);
    $relatedHabtm = $config['relatedHabtm'];
    if (!isset($Model->hasAndBelongsToMany[$relatedHabtm])) {
      return false;
    }
    $prefix = $Model->tablePrefix;
    $table = $Model->table;
    $alias = $Model->alias;
    $key = $Model->primaryKey;
    $count = 'Count';

    $joinTable = $Model->hasAndBelongsToMany[$relatedHabtm]['joinTable'];
    $joinAlias = $Model->hasAndBelongsToMany[$relatedHabtm]['with'];
    $associationForeignKey = $Model->hasAndBelongsToMany[$relatedHabtm]['associationForeignKey'];
    $foreignKey = $Model->hasAndBelongsToMany[$relatedHabtm]['foreignKey'];

    $sqlFields = array("COUNT(*) AS $count");
    foreach((array)$config['fields'] as $field) {
      $sqlFields[] = $alias . '.' . $field;
    }

    $sql =  "SELECT " . implode(', ', $sqlFields) . " ";
    $sql .= "FROM $prefix$joinTable AS $joinAlias ";
    $sql .= "JOIN $prefix$table AS $alias ON $joinAlias.$foreignKey = $alias.$key ";
    $sql .= "WHERE $joinAlias.$foreignKey NOT IN (" . implode(', ', $ids) . ") ";
    $sql .=  "AND $joinAlias.$associationForeignKey IN (";
    $sql .=   "SELECT $joinAlias.$associationForeignKey ";
    $sql .=   "FROM $prefix$joinTable AS $joinAlias ";
    $sql .=   "WHERE $joinAlias.$foreignKey IN (" . implode(', ', $ids) . ")";
    $sql .=  ") ";
    $sql .= "GROUP BY $joinAlias.$foreignKey ";
    $sql .= "ORDER BY $count DESC ";
    $sql .= "LIMIT {$config['limit']}";

    $result = $Model->query($sql);

    return $result;
  }
}
?>
