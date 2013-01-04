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

class FileManagerComponent extends Component {

  var $controller = null;
  var $MyFile = null;
  var $User = null;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
    if (!empty($controller->MyFile)) {
      $this->MyFile = $controller->MyFile;
    } else {
      App::uses('Model', 'MyFile');
      $this->MyFile = new MyFile();
    }
    $this->User = $controller->User;
  }

  /**
   * Add a file to the database
   *
   * @param filename Filename to add
   * @param user Optional user. User model data or user Id.
   * @return array File id or false on error
   */
  public function add($filename, $user = false) {
    if (!is_readable($filename)) {
      Logger::error("Can not read file: $filename");
      return false;
    }
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

    $id = $this->controller->MyFile->fileExists($filename);
    if ($id) {
      $file = $this->controller->MyFile->findById($id);
      $this->controller->MyFile->update($file);
      Logger::verbose("Update file $filename (id $id)");
      return $id;
    }
    $flag = 0;
    if ($this->isExternal($filename)) {
      $flag |= FILE_FLAG_EXTERNAL;
    }
    $file = $this->controller->MyFile->createFromFile($filename, $userId, $flag);

    if ($this->controller->MyFile->save($file)) {
      $id = $this->controller->MyFile->getLastInsertID();
      Logger::verbose("Insert file $filename to database (id $id)");
      return $id;
    } else {
      Logger::err("Could not save file $filename to database");
      return false;
    }

  }

  /**
   * Delete a file
   *
   * @param file File ID, filename or file model data
   * @return True on success
   */
  public function delete($file) {
    $isExternal = $this->isExternal($file);
    if (is_string($file)) {
      if (is_dir($file)) {
        $deleteFolder = !$isExternal;
        return $this->controller->MyFile->deletePath($file, $deleteFolder);
      }
      $id = $this->controller->MyFile->fileExists($file);
      if (!$id) {
        if (!$isExternal && is_readable($file) && is_writable(dirname($file))) {
          Logger::warn("Delete unasigned internal file: $file");
          @unlink($file);
          return true;
        } else {
          Logger::warn("Could not find file $file");
          return false;
        }
      }
    } elseif (is_int($file)) {
      $id = $file;
    } elseif (is_array($file) && !empty($file['File']['id'])) {
      $id = $file['File']['id'];
    } else {
      Logger::warn("Could not determine file from $file");
      return false;
    }
    return $this->controller->MyFile->delete($id);
  }

  /**
   * Delete a file (Alias of delete())
   */
  public function del($file) {
    return $this->delete($file);
  }

  /**
   * Returns the internal directory of a user
   *
   * @param user Optional user
   * @return internal user directory
   */
  public function getUserDir($user = false) {
    if (!$user) {
      $user = $this->controller->getUser();
    }

    // create untailed directory, otherwise Folder->create() might act wrong
    $userDir = USER_DIR . $user['User']['id'] . DS . 'files';
    if (!is_dir($userDir)) {
      $Folder = new Folder();
      if (!$Folder->create($userDir)) {
        Logger::err(sprintf("Directory %s NOT created", $userDir));
        return false;
      }
    }
    return Folder::slashTerm($userDir);
  }

  /**
   * Evaluates if a filename is external or internal
   *
   * @param filename Filename
   * @param user Optional user
   * @return True if filename is external
   */
  public function isExternal($filename, $user = false) {
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

  /**
   * Checks if a user can read the file
   *
   * @param file Filename
   * @param user Optional user
   */
  public function canRead($file, $user = false) {
    if (!$user) {
      $user = $this->controller->getUser();
    }

  }

  /** Checks if the user can write to his user directory
    @param size Bytes to write
    @param user Optionial user */
  public function canWrite($size, $user = false) {
    if (!$user) {
      $user = $this->controller->getUser();
    }

    $current = $this->controller->MyFile->countBytes($user['User']['id'], false);
    $quota = $user['User']['quota'];
    if ($current + $size <= $quota) {
      return true;
    }

    return false;
  }

  public function copy($src, $dst) {
    if (is_dir($src)) {
      $folder = new Folder($src);
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
        $srcFile = $this->controller->MyFile->findByFilename($file);
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

  public function move($src, $dst) {
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
    return $this->controller->MyFile->move($src, $dst);
  }

  /**
   * Creates a unique filename within a path and a filename. The new filename
   * has the pattern of name.unique-number.extension
   *
   * @param path Path for the filename
   * @param filename Filename
   * @return unique filename
   */
  public function createUniqueFilename($path, $filename) {
    $path = Folder::slashTerm($path);
    if (!file_exists($path . $filename)) {
      return $filename;
    }
    $name = substr($filename, 0, strrpos($filename, '.'));
    $ext = substr($filename, strrpos($filename, '.') + 1);
    $found = false;
    $count = 0;
    while (!$found) {
      $new = $name . '.' . $count . '.' . $ext;
      if (!file_exists($path . $new)) {
        return $new;
      }
      $count++;
    }
  }
}

?>
