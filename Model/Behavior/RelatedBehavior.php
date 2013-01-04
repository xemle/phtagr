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

class RelatedBehavior extends ModelBehavior {

  var $config = array();

  /**
   * Config the behavior
   *
   * @param Model Current model reference.
   * @param config Configuration for the related results
   * - relatedHabtm: Related HasAndBelongsToMany model. Default is the first HABTM model
   * - fields: Array of fields of the model which should be fetched
   * - limit: Limit count of the related items. Default is 16
   */
  public function setup(Model $Model, $config = array()) {
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

  /**
   * Search for related items
   *
   * <code>
   * $keyword = $this->Media->Field->findByData($name);
   * $this->Media->Field->Behaviors->attach('Related', array('relatedHabtm' => 'Media', 'fields' => array('id', 'data')));
   * $this->Media->Field->bindModel(array('hasAndBelongsToMany' => array('Media')));
   * $this->data = $this->Media->Field->related($keyword['Field']['id']);
   * </code>
   *
   * @param Model Reference to the current model (Set automatic by the Behavior)
   * @param ids Id or Array of Ids to be related to
   * @param options Options
   * - relatedHabtm: (Optional) Related HasAndBelongsToMany model
   * - fields: (Optional) Array of fields of the model
   * - limit: (Optional) Limit count of the related items
   */
  public function related(&$Model, $ids, $options = array()) {
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
