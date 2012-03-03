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

class TypeBehavior extends ModelBehavior 
{
  var $config = array();

  function setup(&$model, $config = array()) {
    $this->config[$model->name] = $config;
  }

  function isType(&$model, $data, $type) {
    if (!$data) {
      $data =& $model->data;
    }
    if (isset($data[$model->alias])) {
      $data = $data[$model->alias];
    }
    if (!isset($data['type'])) {
      Logger::err("Precondition failed");
      Logger::debug($data);
      return null;
    }

    return $data['type'] == $type ? true : false;
  }

  function setType(&$model, $data, $type) {
    if (!$data) {
      $data =& $model->data;
    }
    if (isset($data[$model->alias])) {
      $data =& $data[$model->alias];
    }

    if (!isset($data['id'])) {
      Logger::err("Precondition failed");
      return null;
    }

    $data['type'] = $type;
    if (!$model->save($data, true, array('type'))) {
      Logger::err("Could not update type of model {$model->alias} {$data['id']} to type {$type}");
    }
  }

  function getType(&$model, $data) {
    if (!$data) {
      $data =& $model->data;
    }
    if (isset($data[$model->alias])) {
      $data =& $data[$model->alias];
    }

    if (!isset($data['type'])) {
      Logger::err("Precondition failed");
      return null;
    }

    return $data['type'];
  }
}
?>
