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

class FlagBehavior extends ModelBehavior
{
  var $config = array();

  public function setup(Model $model, $config = array()) {
    $this->config[$model->name] = $config;
  }

  public function setFlag(&$model, $data, $flag) {
    if (!$data) {
      $data = $model->data;
    }

    $modelData = $data;
    if (isset($modelData[$model->alias])) {
      $modelData = $modelData[$model->alias];
    }
    if (!isset($modelData['id']) || !isset($modelData['flag'])) {
      Logger::err("Precondition failed");
      return false;
    }

    if ($modelData['flag'] & $flag) {
      return true;
    }

    $modelData['flag'] |= $flag;
    if (!$model->save($modelData, true, array('flag'))) {
      Logger::err("Could not update flag");
      return false;
    }
    return true;
  }

  public function deleteFlag(&$model, $data, $flag) {
    if (!$data) {
      $data = $model->data;
    }

    $modelData = $data;
    if (isset($modelData[$model->alias])) {
      $modelData = $modelData[$model->alias];
    }
    if (!isset($modelData['id']) || !isset($modelData['flag'])) {
      Logger::err("Precondition failed");
      return false;
    }

    if ($modelData['flag'] & $flag == 0) {
      return true;
    }
    $modelData['flag'] ^= $flag;
    if (!$model->save($modelData, true, array('flag'))) {
      Logger::err("Could not update flag");
      return false;
    }
    return true;
  }

  /** Alias for deleteFlag */
  public function delFlag(&$model, $data, $flag) {
    return $this->deleteFlag($model, $data, $flag);
  }

  public function hasFlag(&$model, $data, $flag) {
    if (!$data) {
      $data = $model->data;
    }

    $modelData = $data;
    if (isset($modelData[$model->alias])) {
      $modelData = $modelData[$model->alias];
    }
    if (!isset($modelData['flag'])) {
      Logger::err("Precondition failed! Model {$model->alias} has no flag field.");
      return false;
    }

    if (($modelData['flag'] & $flag) > 0) {
      return true;
    } else {
      return false;
    }
  }

}
?>
