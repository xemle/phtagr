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

App::uses('Sanitize', 'Utility');
App::uses('Folder', 'Utility');

class MyFile extends AppModel
{
  var $alias = 'File';
  var $useTable = 'files';

  var $belongsTo = array('Media', 'User');

  var $types = array(
    FILE_TYPE_IMAGE => array('bmp', 'gif', 'jpg', 'jpeg', 'png', 'psd', 'tif', 'tiff'),
    FILE_TYPE_SOUND => array('wav', 'mp3'),
    FILE_TYPE_VIDEO => array('avi', 'mpg', 'mpeg', 'mov', 'mp4', 'mts', 'flv', 'ogg'),
    FILE_TYPE_VIDEOTHUMB => array('thm'),
    FILE_TYPE_TEXT => array('txt'),
    FILE_TYPE_GPS => array('log'),
    FILE_TYPE_SIDECAR => array('xmp')
    );

  var $actsAs = array('Type', 'Flag');

  /**
   * Creates a model data for a file
   *
   * @param filename Filename
   * @param userId user Id (required)
   * @param optional file flag
   * @return model data
   */
  public function createFromFile($filename, $userId, $flag = 0) {
    if (is_dir($filename)) {
      $flag |= FILE_FLAG_DIRECTORY;
      $path = Folder::slashTerm($filename);
      $file = null;
      $size = 0;
      $type = FILE_TYPE_DIRECTORY;
    } else {
      $path = Folder::slashTerm(dirname($filename));
      $file = basename($filename);
      if (is_readable($filename)) {
        $size = filesize($filename);
      } else {
        $size = 0;
        Logger::info("Could not read file: " . $filename);
      }
      $type = $this->_getTypeFromFilename($filename);
    }

    $new = array(
      'File' => array(
        'path' => $path,
        'file' => $file,
        'size' => $size,
        'time' => date("Y-m-d H:i:s"),
        'flag' => $flag,
        'type' => $type,
        'user_id' => $userId,
        'media_id' => null
        )
      );
    if (is_readable($filename)) {
      $new['File']['time'] = date("Y-m-d H:i:s", filemtime($filename));
    }
    return $this->create($new, true);
  }

  /**
   * Returns the file type of a filename
   *
   * @param filename Filename of the file
   * @return Type of the file. If the type is not known it returns
   * FILE_TYPE_UNKNOWN
   */
  public function _getTypeFromFilename($filename) {
    $ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
    foreach ($this->types as $type => $extensions) {
      if (in_array($ext, $extensions)) {
        return $type;
      }
    }
    return FILE_TYPE_UNKNOWN;
  }

  /**
   * Deletes the linked file (if the file is not external) and also deletes
   * the media if the file is required by the media
   */
  public function beforeDelete($cascade = true) {
    $this->set($this->findById($this->id));
    if (!$this->hasFlag($this->data, FILE_FLAG_EXTERNAL)) {
      $filename = $this->getFilename();
      if (!@unlink($filename)) {
        Logger::err("Could not delete internal file $filename");
      } else {
        Logger::verbose("Delete internal file $filename");
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

  /**
   * If the media depends on the file the function deletes the media
   */
  public function afterDelete() {
    if ($this->hasFlag($this->data, FILE_FLAG_DEPENDENT) &&
      $this->hasMedia()) {
      Logger::verbose("Delete media {$this->data['Media']['id']} from dependent file {$this->data['File']['id']}");
      $this->Media->delete($this->data['File']['media_id']);
    }
  }

  /**
   * Search for an image by filename
   *
   * @param filename Filename of the current image
   */
  public function findByFilename($filename) {
    $file = basename($filename);
    $path = Folder::slashTerm(dirname($filename));

    return $this->find('first', array('conditions' => array("path" => $path, "file" => $file)));
  }

  /**
   * Find all files within the path
   *
   * @param string $path
   * @return array
   */
  public function findAllByPath($path) {
    return $this->find('all', array('conditions' => array("path" => Folder::slashTerm($path))));
  }

  /**
   * Checks if a file exists already in the database.
   *
   * @param filename Filename of image
   * @return Returns the ID if filename is already in the database, otherwise it
   * returns false.
   */
  public function fileExists($filename) {
    $file = $this->findByFilename($filename);
    if ($file) {
      return $file['File']['id'];
    } else {
      return false;
    }
  }

  /**
   * Returns the filename of the model
   *
   * @param data Optional model data. If data is null, the current model data is
   * used
   * @result Filename of the model
   */
  public function getFilename($data = null) {
    if (!$data) {
      $data = $this->data;
    }

    if (isset($data['File'])) {
      $data = $data['File'];
    }
    if (!isset($data['path']) ||
      !isset($data['file'])) {
      return false;
    }

    return $data['path'].$data['file'];
  }

  public function getExtension($data) {
    if (!$data) {
      $data = $this->data;
    }
    if (isset($data['File'])) {
      $data = $data['File'];
    }
    if (!isset($data['file'])) {
      Logger::err("Precondition failed");
      return false;
    }
    return strtolower(substr($data['file'], strrpos($data['file'], '.') + 1));
  }

  public function hasMedia($data = null) {
    if (!$data) {
      $data = $this->data;
    }

    if (isset($data['File'])) {
      $data = $data['File'];
    }
    if (isset($data['media_id']) && $data['media_id'] > 0) {
      return true;
    }

    return false;
  }

  public function setMedia($data, $mediaId) {
    if (!$data) {
      $data = $this->data;
    }

    if (isset($data['File'])) {
      $data = $data['File'];
    }

    if (!isset($data['id']) || !intval($mediaId)) {
      Logger::err("Precondition failed");
      return false;
    }
    $data['media_id'] = $mediaId;
    if (!$this->save($data, true, array('media_id'))) {
      Logger::err("Could not bind media $mediaId to file {$data['id']}");
      return false;
    }
    return true;
  }

  /**
   * Unlink the media from the file and delete external file if required
   *
   * @param data Media ID or file model data. If the media ID is given, all
   * files are unlinked from the media. If the file model data is given, only
   * the media of this single file is unlinked
   * @param fileId Optional file id
   */
  public function unlinkMedia($data, $fileId = false) {
    if (is_numeric($data)) {
      $conditions = array('File.media_id' => $data);
      if ($fileId) {
        $conditions['File.id'] = intval($fileId);
      }
    } elseif (is_array($data) && isset($data['File']['id'])) {
      $conditions = array('File.id' => $data['File']['id']);
    } elseif (!$data && $fileId) {
      $conditions = array('File.id' => intval($fileId));
    } else {
      Logger::warn("Invalid input");
      Logger::debug($data);
      return false;
    }

    $files = $this->find('all', array('conditions' => $conditions, 'recursive' => -1));
    if (!$files) {
      Logger::debug("No files found to unlink media");
      return true;
    }

    $ids = Set::extract('/File/id', $files);
    Logger::debug("Unlink media of files ".implode(', ', $ids));
    $this->updateAll(array('media_id' => null, 'readed' => null), array('id' => $ids));

    foreach ($files as $file) {
      if ($this->hasFlag($file, FILE_FLAG_EXTERNAL)) {
        Logger::debug("Delete external file {$file['File']['id']} from database");
        $this->delete($file['File']['id']);
      }
    }
  }

  /**
   * Checks if a user can read the original file
   *
   * @param user Array of User model
   * @param filename Filename of the file to be checked
   * @param flag Reading image flag which must match the condition
   * @return True if user can read the filename
   */
  public function canRead($filename, $user, $flag = ACL_READ_ORIGINAL) {
    if (!file_exists($filename)) {
      Logger::debug("Filename does not exists: $filename");
      return false;
    }

    $fields = array('File.id');
    $conditions = array();
    if (is_dir($filename)) {
      $sqlPath = Sanitize::escape(Folder::slashTerm($filename));
      $conditions['File.path LIKE'] = "$sqlPath%";
    } else {
      $sqlPath = Sanitize::escape(Folder::slashTerm(dirname($filename)));
      $sqlFile = Sanitize::escape(basename($filename));
      $conditions['File.path'] = $sqlPath;
      $conditions['File.file'] = $sqlFile;
    }

    $config = $this->belongsTo['Media'];
    $join = array(
        'table' => $this->Media,
        'alias' => $this->Media->alias,
        'type' => '',
        'conditions' => "`{$this->alias}.`{$config['foreignKey']}` = `{$this->Media->alias}`.`{$this->Media->primaryKey}`"
      );
    $joins[] = $join;
    $aclQuery = $this->Media->buildAclQuery($user, 0, $flag);
    if ($aclQuery['joins']) {
      $joins = am($joins, $aclQuery['joins']);
    }
    $conditions = am($conditions, $aclQuery['conditions']);
    $query = array('fields' => $fields, 'joins' => $joins, 'conditions' => $conditions);
    // fix for correct join order
    $this->unbindModel(array('belongsTo' => array('Media')));
    $result = $this->find('first', $query);
    if (isset($result['File']['id'])) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Count used bytes of a user
   *
   * @param userId User id
   * @param includeExternal Set true to include also external files. Default is
   * false
   */
  public function countBytes($userId, $includeExternal = false) {
    $userId = intval($userId);
    $conditions = array("User.id" => $userId);
    if (!$includeExternal) {
      $conditions[] = "File.flag & ".FILE_FLAG_EXTERNAL." = 0";
    }
    $result = $this->find('all', array('conditions' => $conditions, 'fields' => array('SUM(File.size) AS bytes')));
    return intval($result[0][0]['bytes']);
  }

  /**
   * Updates the file size and time to the model data
   *
   * @param data Optional model data
   */
  public function update($data = null) {
    if (!$data) {
      $data = $this->data;
    }

    if (isset($data['File']['id'])) {
      $this->set($this->findById($data['File']['id']));
      $filename = $this->getFilename();
      $this->data['File']['size'] = filesize($filename);
      $this->data['File']['time'] = date("Y-m-d H:i:s", filemtime($filename));
      if (!$this->save(null, true, array('size', 'time'))) {
        Logger::warn("Could not update file data of $filename");
      } else {
        Logger::debug("Update file type and size of $filename");
      }
    }
  }

  public function updateReaded($data = null) {
    if (!$data) {
      $data = $this->data;
    }
    if (isset($data['File'])) {
      $data = $data['File'];
    }
    if (!isset($data['id'])) {
      Logger::err("Precondition failed");
      return false;
    }
    $data['readed'] = date("Y-m-d H:i:s", time());
    if (!$this->save($data, true, array('readed'))) {
      Logger::err("could not save data");
      return false;
    }
    return true;
  }

  public function move($src, $dst) {
    if (is_dir($src)) {
      return $this->moveDir($src, $dst);
    }
    if (file_exists($dst)) {
      Logger::err("Destination '$dst' exists and cannot overwritten!");
      return false;
    }
    $data = $this->findByFilename($src);
    if (!$data) {
      Logger::err("Source '$src' was not found in the database!");
      return false;
    }

    if (!@rename($src, $dst)) {
      Logger::err("Could not move '$src'to '$dst'");
      return false;
    }
    if (is_dir($dst)) {
      $data['File']['path'] = Folder::slashTerm(dirname($dst));
    } else {
      $data['File']['path'] = Folder::slashTerm(dirname($dst));
      $data['File']['file'] = basename($dst);
      $data['File']['type'] = $this->_getTypeFromFilename($dst);
    }
    if (!$this->save($data, true, array('path', 'file', 'type'))) {
      Logger::err("Could not updated new filename '$dst' (id=$id)");
      return false;
    }
    return true;
  }

  /**
   * Move or rename a directory to another destination
   *
   * @param src Source directory
   * @param dst Destination directory or empty filename
   */
  public function moveDir($src, $dst) {
    if (!is_dir($src)) {
      Logger::err("Source '$src' is not a directory");
      return false;
    }
    // Allow dir and writeable parent dir
    if ((file_exists($dst) && !is_dir($dst)) ||
      (!file_exists($dst) && !is_writeable(dirname($dst)))) {
      Logger::err("Invalid destination '$dst'");
      return false;
    }

    if (!@rename($src, $dst)) {
      Logger::err("Could not rename directory");
      return false;
    }

    $src = Folder::slashTerm($src);
    $dst = Folder::slashTerm($dst);

    $sqlSrc = Sanitize::escape($src);
    $sqlDst = Sanitize::escape($dst);

    $sql = "UPDATE ".$this->tablePrefix.$this->table." AS File ".
           "SET path=REPLACE(path,'$sqlSrc','$sqlDst') ".
           "WHERE path LIKE '$sqlSrc%'";
    Logger::debug($sql);
    $this->query($sql);
    return true;
  }

  public function deletePath($path, $deleteFolder = false) {
    if (!file_exists($path)) {
      Logger::err("Path $path does not exists");
      return false;
    }
    if (!is_dir($path)) {
      return $this->delete($this->findByFilename($path));
    }
    $sqlPath = Sanitize::escape(Folder::slashTerm($path));
    $files = $this->deleteAll("File.path LIKE '$sqlPath%'", true, true);
    if ($deleteFolder) {
      $folder = new Folder();
      Logger::info("Delete folder $path");
      $folder->delete($path);
    }
    return true;
  }

  /**
   * Get options for Media View of a file
   *
   * @param filename Filename of the media
   * @return Array of Media Option for the view.
   */
  public function getMediaViewOptions($filename) {
    $path = substr($filename, 0, strrpos($filename, DS) + 1);
    $file = substr($filename, strrpos($filename, DS) + 1);
    $ext = strtolower(substr($file, strrpos($file, '.') + 1));
    $name = substr($file, 0, strrpos($file, '.'));
    $modified = date("Y-m-d H:i:s", filemtime($filename));

    $options = array(
      'id' => $file,
      'name' => $name,
      'extension' => $ext,
      'path' => $path,
      'modified' => $modified,
      'mimeType' => array('thm' => 'image/jpeg'));

    return $options;
  }

}
?>
