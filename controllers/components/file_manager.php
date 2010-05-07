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

class FileManagerComponent extends Object {

  var $controller = null;
  var $MyFile = null;
  var $User = null;

  function initialize(&$controller) {
    $this->controller = $controller;
    $this->MyFile = $controller->MyFile;
    $this->User = $controller->User;
  }

  /** Add a file to the database 
    @param filename Filename to add
    @param user Optional user. User model data or user Id.
    @return File id or false on error */
  function add($filename, $user = false) {
    if (!$user) {
      $userId = $this->controller->getUserId();
    } elseif (is_numeric($user)) {
      $userId = intval($user);
    } else {
      if (!isset($user['User']['id'])) {
        Logger::err('Unexcpected user data array. Use current user.');
        Logger::debug($user); 
        $userId = $this->controller->getUserId();
      } else {
        $userId = $user['User']['id'];
      } 
    }

    $id = $this->MyFile->fileExists($filename);
    if ($id) {
      Logger::verbose("File $filename already exists (id $id)");
      return $id;
    }
    $flag = 0;
    if ($this->isExternal($filename)) {
      $flag |= FILE_FLAG_EXTERNAL;
    }
    $file = $this->MyFile->create($filename, $userId, $flag);

    if ($this->MyFile->save($file)) {
      $id = $this->MyFile->getLastInsertID();
      Logger::verbose("Insert file $filename to database (id $id)");
      return $id;
    } else {
      Logger::err("Could not save file $filename to database");
      return false;
    }

  }

  /** Delete a file
    @param file File ID, filename or file model data 
    @return True on success */
  function delete($file) {
    if (is_string($file)) {
      if (is_dir($file)) {
        $deleteFolder = !$this->isExternal($file);
        return $this->MyFile->deletePath($file, $deleteFolder);
      }
      $id = $this->MyFile->fileExists($file);
      if (!$id) {
        Logger::warn("Could not find file $file");
        return false;
      }
    } elseif (is_int($file)) {
      $id = $file;
    } elseif (is_array($file) && !empty($file['File']['id'])) {
      $id = $file['File']['id'];
    } else {
      Logger::warn("Could not determine file from $file");
      return false;
    }
    return $this->MyFile->delete($id);
  }

  /** Delete a file (Alias of delete()) */
  function del($file) {
    return $this->delete($file); 
  }

  /** Returns the internal directory of a user
    @param user Optional user
    @return internal user directory */
  function getUserDir($user = false) {
    if (!$user) {
      $user = $this->controller->getUser();
    }
    $userDir = USER_DIR.$user['User']['id'].DS.'files'.DS;
    $folder = new Folder();
    if (!$folder->create($userDir)) {
      Logger::err("Could not create users root directory '$userDir'");
      return false;
    }
    return $userDir;
  }

  /** Evaluates if a filename is external or internal 
    @param filename Filename
    @param user Optional user
    @return True if filename is external */
  function isExternal($filename, $user = false) {
    if (!is_dir($filename)) {
      $filename = Folder::slashTerm(dirname($filename));
    }
    $userDir = $this->getUserDir($user);
    if (strpos($filename, $userDir) === 0) {
      return false;
    } else {
      return true;
    }
  }

  /** Checks if a user can read the file
    @param file Filename
    @param user Optional user */
  function canRead($file, $user = false) {
    if (!$user) {
      $user = $this->controller->getUser();
    }
    
  }

  /** Checks if the user can write to his user directory
    @param size Bytes to write
    @param user Optionial user */
  function canWrite($size, $user = false) {
    if (!$user) {
      $user = $this->controller->getUser();
    }

    $current = $this->MyFile->countBytes($user['User']['id'], false);
    $quota = $user['User']['quota'];
    if ($current + $size <= $quota) {
      return true;
    }

    return false;
  }

  function copy($src, $dst) {
    if (is_dir($src)) {
      $folder =& new Folder($src);
      list($dirs, $files) = $folder->tree($src);
      sort($dirs);
      sort($files);
      // TODO check users quota for all files to copy
      // Create required directories
      foreach ($dirs as $dir) {
        $dstDir = str_replace($src, $dst, $dir);
        Logger::info($dstDir);
        if (!file_exists($dstDir) && !@mkdir($dstDir)) {
          Logger::err("Could not create directory '$dstDir'");
          return false;
        }
        // COPY properties
      }
    } else {
      $dirs = array();
      // TODO check users quota
      $files = array($src);
    }

    $user = $this->controller->getUser();
    foreach ($files as $file) {
      $dstFile = str_replace($src, $dst, $file);
        
      if (!@copy($file, $dstFile)) {
        Logger::err("Could not copy file '$file' to '$dstFile'");
        //return "409 Conflict";
        return false;
      }
      $dstFileId = $this->add($dstFile, $user);
      if (!$dstFileId) {
        Logger::err("Could not insert copied file '$dstFile' to database (from '$file')");
        unlink($dstFile);
        // return "409 Conflict";
        return false;
      } else {
        // Copy all properties
        $srcFile = $this->MyFile->findByFilename($file);
        if (!$srcFile) {
          Logger::warn("Could not found source '$file' in database");
        } else {
          if (!empty($srcFile['Property'])) {
            $this->controller->Property->copy($srcFile, $dstFileId);
            Logger::debug("Copy properties from '$file' to '$dstFile'");
          }
        }
      }
      Logger::info("Insert copied file '$dstFile' to database (from '$file')");
    }
    return true;
  }

  function move($src, $dst) {
    if (!file_exists($src)) {
      Logger::err("Invalid source: $src. File does not exists");
      return false;
    }
    if ((!file_exists($dst) && !is_writeable(dirname($dst))) ||
      (is_dir($dst) && !is_writeable($dst))) {
      Logger::err("Invalid destination $dst. Destination is not writeable");
      return false;
    } elseif (file_exists($dst) && !is_dir($dst)) {
      Logger::err("Invalid destination: $dst. Destination is a file");
      return false;
    }
    return $this->MyFile->move($src, $dst);
  }
}

?>
