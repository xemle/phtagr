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
    @param userId Id of the current user
    @param mediaId Id of the current image/file
    @param create Creates the directory if true. If false and the directory
    does not exists, it returns false. Default is true.
    @return Path for the cache file. False on error */
  function getPath($userId, $mediaId, $create = true) {
    $userId = intval($userId);
    $mediaId = intval($mediaId);

    $cacheDir = USER_DIR.$userId.DS.'cache'.DS;
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
        Logger::debug("Create cache dir '$cacheDir'");
      }
    }
    return $cacheDir;
  }

  /** Returns the filename prefix of the cache file
    @param mediaId Id of the current image/file
    @return filename prefix */
  function getFilenamePrefix($mediaId) {
    $mediaId = intval($mediaId);

    $prefix = sprintf("%07d-", $mediaId);
    return $prefix;
  }

  /** Returns the full cache filename prefix of a media
    @param media Media model data
    @param cache filename prefix */
  function getFilename($media) {
    if (!isset($media['Media']['id']) || !isset($media['Media']['user_id'])) {
      Logger::err("Precondition failed");
      Logger::debug($media);
      return false;
    }
    $userId = $media['Media']['user_id'];
    $mediaId = $media['Media']['id'];

    $path = $this->getPath($userId, $mediaId);
    if (!$path) {
      return false;
    }
    return $path.$this->getFilenamePrefix($mediaId);
  }

  /** Deletes all cached files of a specific image/file.
    @param userId Id of the current user
    @param mediaId Id of the current image/file */
  function delete($userId, $mediaId) {
    $mediaId = intval($mediaId);
    $cacheDir = $this->getPath($userId, $mediaId, false);
    if (!$cacheDir) {
      Logger::trace("No cache dir found for image $mediaId");
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
