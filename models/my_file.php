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

class MyFile extends AppModel
{
  var $alias = 'File';
  var $useTable = 'files';

  var $belongsTo = array('Medium', 'User');

  /** Creates a model data for a file
    @param filename Filename
    @param userId user Id (required)
    @param optional file flag
    @return model data */
  function create($filename, $userId, $flag = 0) {
    if (is_dir($filename)) {
      $flag |= FILE_FLAG_DIRECTORY;
      $path = Folder::slashTerm($filename);
      $file = null;
      $size = 0;
    } else {
      $path = Folder::slashTerm(dirname($filename));
      $file = basename($filename);
      $size = filesize($filename);
    }

    $new = array();
    $new['File']['path'] = $path;
    $new['File']['file'] = $file;
    $new['File']['size'] = $size;
    $new['File']['time'] = date("Y-m-d H:i:s", filemtime($filename));
    $new['File']['flag'] = $flag;
    $new['File']['user_id'] = $userId;
    $new = parent::create($new, true);

    return $new;
  }

  /** Deletes the linked file (if the file is not external) and also deletes
   * the medium if the file is required by the medium */
  function beforeDelete($cascade = true) {
    $this->set($this->findById($this->id));
    if (!$this->hasFlag(null, FILE_FLAG_EXTERNAL)) {
      $filename = $this->getFilename();
      if (!@unlink($filename)) {
        $this->Logger->err("Could not delete file $filename");
      } else {
        $this->Logger->verbose("Delete file $filename");
      }
      if ($this->hasFlag(null, FILE_FLAG_DEPENDENT) && 
        isset($this->data['Medium']['id'])) {
        $this->Media->delete($this->data['Medium']['id']);
      }
    }
    // prepare associations for deletion
    $this->bindModel(array(
      'hasMany' => array(
        'Property' => array('foreignKey' => 'file_id', 'dependent' => true), 
        'Lock' => array('foreignKey' => 'file_id', 'dependent' => true)
      )));
    return true;
  }

  /** Search for an image by filename 
    @param filename Filename of the current image */
  function findByFilename($filename) {
    $file = basename($filename);
    $path = Folder::slashTerm(dirname($filename));

    return $this->find(array("path" => $path, "file" => $file));
  }

  /** Checks if a file exists already in the database.
    @param filename Filename of image
    @return Returns the ID if filename is already in the database, otherwise it
    returns false. */
  function fileExists($filename) {
    $file = $this->findByFilename($filename);
    if ($file) {
      return $file['File']['id'];
    } else {
      return false;
    }
  }

  /** Returns the filename of the model
    @param data Optional model data. If data is null, the current model data is
    used 
    @result Filename of the model */
  function getFilename($data = null) {
    if (!$data) {
      $data = $this->data;
    }

    if (!isset($data['File']['path']) || 
      !isset($data['File']['file'])) {
      return false;
    }
    
    return $data['File']['path'].$data['File']['file'];
  }

  /** Checks if a user can read the original file 
    @param user Array of User model
    @param filename Filename of the file to be checked 
    @param flag Reading image flag which must match the condition 
    @return True if user can read the filename */
  function canRead($filename, $user, $flag = ACL_READ_ORIGINAL) {
    if (!file_exists($filename)) {
      $this->Logger->debug("Filename does not exists: $filename");
      return false;
    }

    uses('sanitize');
    $sanitize = new Sanitize();

    $conditions = '';
    if (is_dir($filename)) {
      $sqlPath = $sanitize->escape($filename);
      $conditions .= "File.path LIKE '$sqlPath%'";
    } else {
      $sqlPath = $sanitize->escape(Folder::slashTerm(dirname($filename)));
      $sqlFile = $sanitize->escape(basename($filename));
      $conditions .= "File.path = '$sqlPath' AND File.file = '$sqlFile'";
    }
    // @TODO Fix ACL
    //$conditions .= $this->buildWhereAcl($user, 0, $flag);
    $this->Logger->debug($conditions);

    return $this->hasAny($conditions);
  }

  function checkAccess($data, $user, $flag, $mask) {
    if (!empty($data['Medium']['id'])) {
      return $this->Medium->checkAccess($data, $user, $flag, $mask);
    } elseif (isset($data['File']['user_id']) &&
      isset($user['User']['id']) &&
      $data['File']['user_id'] == $user['User']['id']) {
      return true;
    }
    return false;
  }
  /** Count used bytes of a user
    @param userId User id
    @param includeExternal Set true to include also external files. Default is
    false */
  function countBytes($userId, $includeExternal = false) {
    $userId = intval($userId);
    $conditions = array("User.id" => $userId);
    if (!$includeExternal) {
      $conditions[] = "File.flag & ".FILE_FLAG_EXTERNAL." = 0";
    }
    $result = $this->findAll($conditions, array('SUM(File.size) AS bytes'));
    return intval($result[0][0]['bytes']);
  }

  /** Evaluates a flag of the model data 
    @param data Model data. If null, use current data
    @param flag to evaluate
    @return True if the flag is set, false otherwise. On error it returns null
    */
  function hasFlag($data, $flag) {
    if (!$data) {
      $data = $this->data;
    }

    if (!isset($data['File']['flag'])) {
      $this->Logger->err("Invalid data");
      $this->Logger->debug($data);
      return null;
    }

    if ($data['File']['flag'] & $flag > 0) {
      return true;
    } else {
      return false;
    }
  }

  /** Updates the file size and time to the model data 
    @param data Optional model data */
  function update($data = null) {
    if (!$data) {
      $data = $this->data;
    }
    
    if (isset($data['File']['id'])) {
      $this->set($this->findById($data['File']['id']));
      $filename = $this->getFilename();
      $this->data['File']['size'] = filesize($filename);
      $this->data['File']['time'] = date("Y-m-d H:i:s", filemtime($filename));
      if (!$this->save(null, true, array('size', 'time'))) {
        $this->Logger->warn("Could not update file data of $filename");
      } else {
        $this->Logger->debug("Update file type and size of $filename");
      }
    }
  }

  function move($src, $dst) {
    if (is_dir($src)) {
      return $this->moveDir($src, $dst);
    }
    if (file_exists($dst)) {
      $this->Logger->err("Destination '$dst' exists and cannot overwritten!");
      return false;
    }
    $data = $this->findByFilename($src);
    if (!$data) {
      $this->Logger->err("Source '$src' was not found in the database!");
      return false;
    }

    if (!@rename($src, $dst)) {
      $this->Logger->err("Could not move '$src'to '$dst'");
      return false;
    }
    if (is_dir($dst)) {
      $data['File']['path'] = Folder::slashTerm(dirname($dst));
    } else {
      $data['File']['path'] = Folder::slashTerm(dirname($dst));
      $data['File']['file'] = basename($dst);
    }
    if (!$this->save($data, true, array('path', 'file'))) {
      $this->Logger->err("Could not updated new filename '$dst' (id=$id)");
      return false;
    }
    return true;
  }
  
  /** Move or rename a directory to another destination 
    @param src Source directory
    @param dst Destination directory or empty filename*/
  function moveDir($src, $dst) {
    if (!is_dir($src)) {
      $this->Logger->err("Source '$src' is not a directory");
      return false;
    }
    // Allow dir and writeable parent dir
    if ((file_exists($dst) && !is_dir($dst)) || 
      (!file_exists($dst) && !is_writeable(dirname($dst)))) {
      $this->Logger->err("Invalid destination '$dst'");
      return false;
    }

    if (!@rename($src, $dst)) {
      $this->Logger->err("Could not rename directory");
      return false;
    }

    $src = Folder::slashTerm($src);
    $dst = Folder::slashTerm($dst);

    uses('sanitize');
    $sanitize = new Sanitize();
    $sqlSrc = $sanitize->escape($src);
    $sqlDst = $sanitize->escape($dst);

    $sql = "UPDATE ".$this->tablePrefix.$this->table." AS File ".
           "SET path=REPLACE(path,'$sqlSrc','$sqlDst') ".
           "WHERE path LIKE '$sqlSrc%'";
    $this->Logger->debug($sql);
    $this->query($sql);
    return true;
  }

  function deletePath($path, $deleteFolder = false) {
    if (!file_exists($path)) {
      $this->Logger->err("Path $path does not exists");
      return false;
    }
    if (!is_dir($path)) {
      return $this->delete($this->findByFilename($path));
    }
    uses('sanitize');
    $sanitize = new Sanitize();
    $sqlPath = $sanitize->escape(Folder::slashTerm($path));
    $files = $this->deleteAll("File.path LIKE '$sqlPath%'", true, true);
    if ($deleteFolder) {
      $folder = new Folder();
      $this->Logger->info("Delete folder $path");
      $folder->delete($path);
    }
    return true;
  }

}
?>
