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

class DeleteUnusedBehavior extends ModelBehavior 
{
  var $config = array();

  /** Config the behavior
    @param Model Current model reference.
    @param config Configuration for the related results
    - relatedHabtm: Related HasAndBelongsToMany model. Default is the first HABTM model
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
    $this->config[$Model->name] = am($default, $config);
  }

  public function _getConditions(&$Model) {
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

  public function findAllUnused(&$Model) {
    $conditions = $this->_getConditions($Model);
    if (!$conditions) {
      return false;
    }
    return $Model->find('all', array('conditions' => $conditions));
  }

  public function deleteAllUnused(&$Model) {
    $conditions = $this->_getConditions($Model);
    if (!$conditions) {
      return false;
    }
    Logger::debug("Delete all unused association for {$Model->alias} with conditions: " . join(' and ', $conditions));
    return $Model->deleteAll($conditions);
  }

}
?>
