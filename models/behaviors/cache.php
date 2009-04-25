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

/** Cache File behavior for media
  @see CacheFileComponent */
class CacheBehavior extends ModelBehavior 
{
  var $config = array();

  function setup(&$model, $config = array()) {
    $this->config[$model->name] = $config;
  }

  /** Deletes all cache files of a given media
    @param model Reference of model
    @param data Model data 
    @return True on success */
  function deleteCache(&$model, &$data = null) {
    if (!$data) {
      $data =& $model->data;
    }
    if (isset($data[$model->alias])) {
      $data = $data[$model->alias];
    }
    if (!isset($data['user_id']) || !isset($data['id'])) {
      $model->Logger->err("Precondition failed");
      return false;
    }
    
    $cacheDir = USER_DIR.$data['user_id'].DS.'cache'.DS;
    $cacheDir .= sprintf("%04d", ($data['id'] / 1000)).DS;
    if (!is_dir($cacheDir)) {
      return true;
    }

    // catch all cache files and delete them
    $pattern = sprintf("%07d-.*", $data['id']);
    $folder =& new Folder($cacheDir);
    $files = $folder->find($pattern);
    if (!$files) {
      $model->Logger->trace("No cache files found for media {$data['id']}");
    } else {
      foreach ($files as $file) {
        if (!@unlink($folder->addPathElement($cacheDir, $file))) {
          $model->Logger->err("Could not delete cache file ".$folder->addPathElement($cacheDir, $file));
        } else {
          $model->Logger->trace("Deleted cache file '$file'");
        }
      }
      $model->Logger->debug("Deleted cache files of media {$data['id']}");
    }
    return true;
  }
}
?>
