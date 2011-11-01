<?php
/*
 * phtagr.
 * 
 * social photo gallery for your community.
 * 
 * Copyright (C) 2006-2010 Sebastian Felis, sebastian@phtagr.org
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

class FileCacheComponent extends Object {

  var $controller = null;

  function initialize(&$controller) {
    $this->controller = $controller;
  }

  /** Returns the cache path of the user
    @param media Media model data
    @param create Creates the directory if true. If false and the directory
    does not exists, it returns false. Default is true.
    @return Path for the cache file. False on error */
  function getPath($media, $create = true) {
    $userId = intval($media['Media']['user_id']);
    $mediaId = intval($media['Media']['id']);

    $cacheDir = USER_DIR . $userId . DS . 'cache' . DS;
    $dir = intval($mediaId/1000);
    $cacheDir .= sprintf("%04d", $dir).DS;

    if (!is_dir($cacheDir)) {
      if (!$create) {
        return false;
      }

      $folder =& new Folder($cacheDir);
      if (!$folder->create($cacheDir)) {
        Logger::err("Could not create cache dir '$cacheDir'");
        return false;
      } else {
        Logger::debug("Cache dir '$cacheDir' created");
      }
    } 
    if (!is_writeable($cacheDir)) {
      Logger::err("Cache directory '$cacheDir' is not writeable");
      return false;
    }
    return $cacheDir;
  }

  /** Returns the filename prefix of the cache file
    @param id ID of the current image/file
    @return filename prefix */
  function getFilenamePrefix($id) {
    $prefix = sprintf("%07d-", intval($id));
    return $prefix;
  }

  function getFilename($media, $alias, $ext = 'jpg') {
    $prefix = $this->getFilenamePrefix($media['Media']['id']);
    return $prefix . $alias . "." . $ext;
  }

  /** Returns the full path of the cache file 
    @param media Media model data
    @param alias Alias for cache file
    @param ext (Optional) file extension. Default is 'jpg'
    @return Full path of the cache file. False on error */
  function getFilePath($media, $alias, $ext = 'jpg') {
    $path = $this->getPath($media);
    if (!$path) {
      return false;
    }
    return $path . $this->getFilename($media, $alias, $ext);
  }

  /** Deletes all cached files of a specific image/file.
    @param userId Id of the current user
    @param mediaId Id of the current image/file */
  function delete(&$media) {
    $mediaId = intval($media['Media']['id']);
    $cacheDir = $this->getPath(&$media, false);
    if (!$cacheDir) {
      Logger::trace("No cache dir found for media $mediaId");
      return true;
    }

    $folder =& new Folder($cacheDir);
    $pattern = $this->getFilenamePrefix($mediaId).'.*';
    $files = $folder->find($pattern);
    if ($files) {
      Logger::debug("Delete cached files of image $mediaId");
      foreach($files as $file) {
        Logger::trace("Delete cache file '$file'");
        unlink($folder->addPathElement($cacheDir, $file));
      }
    } else {
      Logger::trace("No cached files found for image $mediaId");
    }
  }

  /** Deletes all cached files of the given user
    @param userId Id of the user */
  function deleteAll($userId) {
    $userId = intval($userId);
    $cacheDir = USER_DIR.$userId.DS.'cache'.DS;
    if (is_dir($cacheDir)) {
      $folder = new Folder();
      $folder->delete($cacheDir);
      Logger::info("Deleted cache dir '$cacheDir'");
    } else {
      Logger::debug("User $userId has no cached files");
    }
  }
}

?>
