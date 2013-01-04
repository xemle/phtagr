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

class TypeBehavior extends ModelBehavior
{
  var $config = array();

  public function setup(Model $model, $config = array()) {
    $this->config[$model->name] = $config;
  }

  public function isType(&$model, $data, $type) {
    if (!$data) {
      $data = $model->data;
    }

    $modelData = $data;
    if (isset($modelData[$model->alias])) {
      $modelData = $modelData[$model->alias];
    }
    if (!isset($modelData['type'])) {
      Logger::err("Precondition failed");
      Logger::debug($data);
      return null;
    }

    return $modelData['type'] == $type ? true : false;
  }

  public function setType(&$model, $data, $type) {
    if (!$data) {
      $data = $model->data;
    }
    $modelData = $data;
    if (isset($modelData[$model->alias])) {
      $modelData = $modelData[$model->alias];
    }

    if (!isset($modelData['id'])) {
      Logger::err("Precondition failed");
      return null;
    }

    $modelData['type'] = $type;
    if (!$model->save($modelData, true, array('type'))) {
      Logger::err("Could not update type of model {$model->alias} {$modelData['id']} to type {$modelData}");
    }
  }

  public function getType(&$model, $data) {
    if (!$data) {
      $data = $model->data;
    }
    $modelData = $data;
    if (isset($modelData[$model->alias])) {
      $modelData = $modelData[$model->alias];
    }

    if (!isset($modelData['type'])) {
      Logger::err("Precondition failed");
      return null;
    }

    return $modelData['type'];
  }
}
?>
