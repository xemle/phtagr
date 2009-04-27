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
    if ($data[$model->alias]) {
      $data = $data[$model->alias];
    }
    if (!isset($data['type'])) {
      $model->Logger->err("Precondition failed");
      $model->Logger->debug($data);
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
      $model->Logger->err("Precondition failed");
      return null;
    }

    $data['type'] = $type;
    if (!$model->save($data, true, array('type'))) {
      $model->Logger->err("Could not update type of model {$model->alias} {$data['id']} to type {$type}");
    }
  }
}
?>
