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
      $model->Logger->err("Precondition failed");
      return false;
    }

    if ($data['flag'] & $flag) {
      return true;
    }
    $data['flag'] |= $flag;
    if (!$model->save($data, true, array('flag'))) {
      $model->Logger->err("Could not update flag");
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
      $model->Logger->err("Precondition failed");
      return false;
    }

    if ($data['flag'] & $flag == 0) {
      return true;
    }
    $data['flag'] ^= $flag;
    if (!$model->save($data, true, array('flag'))) {
      $model->Logger->err("Could not update flag");
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
      $model->Logger->err("Precondition failed");
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
