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

  /** Merge flags from an array given in an array of $data[Model][flag]
    @param model Current model
    @param data model data
    @param mask Allowed flag mask */
  function mergeFlags(&$model, &$data, $mask) {
    if (!$data) {
      $data = $model->data;
    }
    
    if (is_array($data[$model->alias]['flag'])) {
      $flags = 0;
      foreach ($data[$model->alias]['flag'] as $flag) {
        $flags |= $flag;
      }
      $data[$model->alias]['flag'] = ($flags & $mask);
    }
  }

  function explodeFlags(&$model, &$data, $mapping = false) {
    if (!isset($data[$model->alias]['flag'])) {
      Logger::warn("Flag data is missing");
      Logger::trace($data);
      return;
    }
    $flags = $data[$model->alias]['flag'];

    if (!$mapping) {
      $mapping = $this->config[$model->name]['mapping'];
    }
    foreach ($mapping as $flag => $name) {
      if (($flags & $flag) > 0) {
        $data[$model->alias][$name] = true;
      } else {
        $data[$model->alias][$name] = false;
      }
    }
  }

  function implodeFlags(&$model, &$data, $mapping = false) {
    if (!$mapping) {
      $mapping = $this->config[$model->name]['mapping'];
    }

    $flags = 0;
    foreach ($mapping as $flag => $name) {
      if (isset($data[$model->alias][$name]) && $data[$model->alias][$name] > 0) {
        $flags |= $flag;
      }
    }
    $data[$model->alias]['flag'] = $flags;
  } 
}

?>
