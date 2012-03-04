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

class FlagBehavior extends ModelBehavior 
{
  var $config = array();

  function setup(&$model, $config = array()) {
    $this->config[$model->name] = $config;
  }

  function setFlag(&$model, $data, $flag) {
    if (!$data) {
      $data = $model->data;
    }
    if (isset($data[$model->alias])) {
      $data =& $data[$model->alias];
    }

    if (!isset($data['id']) || !isset($data['flag'])) {
      Logger::err("Precondition failed");
      return false;
    }

    if ($data['flag'] & $flag) {
      return true;
    }
    $data['flag'] |= $flag;
    if (!$model->save($data, true, array('flag'))) {
      Logger::err("Could not update flag");
      return false;
    }
    return true;
  }

  function deleteFlag(&$model, &$data, $flag) {
    if (!$data) {
      $data = $model->data;
    }
    
    if (isset($data[$model->alias])) {
      $data =& $data[$model->alias];
    }
    if (!isset($data['id']) || !isset($data['flag'])) {
      Logger::err("Precondition failed");
      return false;
    }

    if ($data['flag'] & $flag == 0) {
      return true;
    }
    $data['flag'] ^= $flag;
    if (!$model->save($data, true, array('flag'))) {
      Logger::err("Could not update flag");
      return false;
    }
    return true;
  }

  /** Alias for deleteFlag */
  function delFlag(&$model, $data, $flag) {
    return $this->deleteFlag($model, $data, $flag);
  }

  function hasFlag(&$model, &$data, $flag) {
    if (!$data) {
      $data = $model->data;
    }
    
    if (isset($data[$model->alias])) {
      $data = $data[$model->alias];
    }
    if (!isset($data['flag'])) {
      Logger::err("Precondition failed");
      return false;
    }

    if (($data['flag'] & $flag) > 0) {
      return true;
    } else {
      return false;
    }
  }

}
?>
