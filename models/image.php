<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2008 Sebastian Felis, sebastian@phtagr.org
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

class Image extends AppModel
{
  var $name = 'Image';
  var $validate = array();

  var $belongsTo = array(
                    'User' => array(),
                    'Group' => array(),
                    );
  var $hasAndBelongsToMany = array(
                              'Tag' => array(),
                              'Location' => array('order' => 'Location.type'),
                              'Category' => array()
                              );
  
  var $_aclMap = array(ACL_LEVEL_GROUP => 'gacl',
                           ACL_LEVEL_USER => 'uacl',
                           ACL_LEVEL_OTHER => 'oacl');

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
  function filenameExists($filename) {
    $image = $this->findByFilename($filename);
    if ($image) {
      return $image['Image']['id'];
    } else {
      return false;
    }
  }

  function getFilename($data) {
    if (!isset($data['Image']['path']) || 
      !isset($data['Image']['file']))
      return false;
    
    return $data['Image']['path'].$data['Image']['file'];
  }

  /** Evaluates, if a given filesystem path is part of the users dir. Otherwise
   * the file is treated as an external file 
    @param data Image model data
    @param user Optional user model data
    @return True, if file is not within the users directory */
  function isExternal($data, $user = null) {
    if (!isset($data['Image']['path'])) {
      $this->Logger->err("Image path not set");
      return false;
    }
    if (!$user) {
      if (!isset($data['User']['id'])) {
        $this->Logger->err("User is not set");
        return false;
      }
      $user = $this->User->findById($data['Image']['user_id']);
    }
    $userDir = $this->User->getRootDir($user);
    if (strpos($data['Image']['path'], $userDir) === 0)
      return false;
    return true;
  }

  /** Evaluates, if a filename is a video file 
    @param data Image model data
    @return True, if filename is a video. False otherwise */
  function isVideo($data) {
    if (!isset($data['Image']['file']))
      return false;
    $file = $data['Image']['file'];

    $videoExtensions = array('avi', 'mpeg', 'mpg', 'mov');
    $ext = strtolower(substr($file, strrpos($file, '.')+1));
    if (!$ext)
      return false;
    return in_array($ext, $videoExtensions);
  }

  function addDefaultAcl(&$data, $user) {
    // Access control values
    $acl = $this->User->Preference->getDefaultAcl($user);
    $data['Image']['user_id'] = $user['User']['id'];
    $data['Image']['group_id'] = $acl['groupId'];
    $data['Image']['gacl'] = $acl['gacl'];
    $data['Image']['uacl'] = $acl['uacl'];
    $data['Image']['oacl'] = $acl['oacl'];
    return $data;
  }

  /** Insert file to the database
    @param filename Filename to be inserted
    @param user User model data of the current user
    @return Image id on success. False on error */
  function insertFile($filename, $user) {
    if (!file_exists($filename) || !is_readable($filename)) {
      $this->Logger->err("Cannot insert file '$filename'. File is does not exists or is not readable");
      return false;
    }
      
    $id = $this->filenameExists($filename);
    if ($id) {
      $this->Logger->debug("File '$filename' already in the database");
      return $id;
    }

    $data = array('Image' => array());
    $v = &$data['Image'];

    // Access control values
    $this->addDefaultAcl(&$data, $user);

    // File information
    $v['file'] = basename($filename);
    $v['path'] = Folder::slashTerm(dirname($filename));
    $v['filetime'] = date('Y-m-d H:i:s', filemtime($filename));
    $v['bytes'] = filesize($filename);

    // generic information
    $v['flag'] = 0;
    if ($this->isExternal($data, $user)) {
      $v['flag'] |= IMAGE_FLAG_EXTERNAL;
    }
    
    if (!$this->create($data) || !$this->save()) {
      $this->Logger->err("Could not create and save data");
      return false;
    }
    return $this->getLastInsertID();
  }

  function move($src, $dst) {
    $id = $this->filenameExists($src);
    if (!$id) {
      $this->Logger->err("Source '$src' was not found in the database!");
      return false;
    }
    if (!file_exists($dst)) {
      $this->Logger->err("Destination '$dst' does not exists!");
      return false;
    }
    $image = $this->findById($id);
    if (is_dir($dst)) {
      $image['Image']['path'] = Folder::slashTerm(dirname($dst));
    } else {
      $image['Image']['path'] = Folder::slashTerm(dirname($dst));
      $image['Image']['file'] = basename($dst);
    }
    if (!$this->save($image, true, array('path', 'file'))) {
      $this->Logger->err("Could not updated new filename '$dst' (id=$id)");
      return false;
    }
    return true;
  }
  
  function moveAll($src, $dst) {
    if (!is_dir($dst)) {
      $this->Logger->err("Destination '$dst' is not a directory");
      return false;
    }
    $src = Folder::slashTerm($src);
    $dst = Folder::slashTerm($dst);

    $db =& ConnectionManager::getDataSource($this->useDbConfig);
    $sql = "UPDATE ".$this->tablePrefix.$this->table." AS Image ".
           "SET path=REPLACE(path,".$db->value($src).",".$db->value($dst).") ".
           "WHERE path LIKE ".$db->value($src."%");
    $this->Logger->debug($sql);
    $this->query($sql);
    return true;
  }

  /** Inactivates an image and removes all habtm associations 
    @param data Image data
    @return Return updated image data */
  function deactivate(&$data) {
    if (!isset($data['Image']['flag'])) {
      $this->Logger->err("Invalid data");
      return false;
    }
    $data['Image']['flag'] = ($data['Image']['flag'] & ~IMAGE_FLAG_ACTIVE) & 0xff;
    foreach ($this->hasAndBelongsToMany as $assoc => $v) {
      $data[$assoc][$assoc] = array();
    }
    return $data;
  }

  /** Updates file with file size and file time 
    @param data Image data
    @return Return updated image data */
  function updateFile(&$data) {
    $filename = $data['Image']['path'].$data['Image']['file'];
    if (!file_exists($filename)) {
      $this->Logger->err("File '$filename' does not exists");
      return false;
    }
    $data['Image']['bytes'] = filesize($filename);
    $data['Image']['filetime'] = date('Y-m-d H:i:s', filemtime($filename));
    return $data;
  }

  function beforeDelete($cascade = true) {
    // prepare associations for deletion
    $this->bindModel(array('hasMany' => array('Property' => array('dependent' => true))));
    return true;
  }

  /** Returns true if current user is allowed of the current flag
    @param data Current Image array
    @param user Current User array
    @param flag Flag bit which should be checkt
    @param mask Bitmask for the flag which should be checkt
    @param groups Array of user's group. If groups is null, it will be created
    by the user's data.
    @return True is user is allowed, False otherwise */
  function checkAccess(&$data, &$user, $flag, $mask, &$groups=null) {
    if (!$data || !$user || !isset($data['Image']) || !isset($user['User'])) {
      $this->Logger->err("precondition failed");
      return false;
    }

    // check for public access
    if (($data['Image']['oacl'] & $mask) >= $flag)
      return true;

    // check for members
    if ($user['User']['role'] >= ROLE_USER && 
      ($data['Image']['uacl'] & $mask) >= $flag)
      return true;

    // check for group members
    if ($groups === null)
      $groups = Set::extract($user, 'Member.{n}.id');
    if ($user['User']['role'] >= ROLE_GUEST &&
      ($data['Image']['gacl'] & $mask) >= $flag &&
      in_array($data['Image']['group_id'], $groups))
      return true;

    // Image owner and admin check
    if ($user['User']['id'] == $data['Image']['user_id'] ||
      $user['User']['role'] == ROLE_ADMIN)
      return true;

    return false;
  }

  /** Set the access flags of write and read options according to the current user
    @param data Reference of the Image array 
    @param user User array
    @return $data of Image data with the access flags */
  function setAccessFlags(&$data, $user) {
    if (!isset($data)) 
      return $data;

    // at least dummy user
    $user = am(array('User' => array('id' => -1, 'role' => ROLE_NOBODY), 'Member' => array()), $user);
    //$this->Logger->debug($user);

    $oacl = $data['Image']['oacl'];
    $uacl = $data['Image']['uacl'];
    $gacl = $data['Image']['gacl'];
    
    $groups = Set::extract($user, 'Member.{n}.id');

    $data['Image']['canWriteTag'] = $this->checkAccess(&$data, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK, &$groups);    
    $data['Image']['canWriteMeta'] = $this->checkAccess(&$data, &$user, ACL_WRITE_META, ACL_WRITE_MASK, &$groups);    
    $data['Image']['canWriteCaption'] = $this->checkAccess(&$data, &$user, ACL_WRITE_CAPTION, ACL_WRITE_MASK, &$groups);    

    $data['Image']['canReadPreview'] = $this->checkAccess(&$data, &$user, ACL_READ_PREVIEW, ACL_READ_MASK, &$groups);    
    $data['Image']['canReadHigh'] = $this->checkAccess(&$data, &$user, ACL_READ_HIGH, ACL_READ_MASK, &$groups);    
    $data['Image']['canReadOriginal'] = $this->checkAccess(&$data, &$user, ACL_READ_ORIGINAL, ACL_READ_MASK, &$groups);    

    $data['Image']['isOwner'] = ife($data['Image']['user_id'] == $user['User']['id'], true, false);
    $data['Image']['canWriteAcl'] = $this->checkAccess(&$data, &$user, 1, 0, &$groups);    
    $data['Image']['isDirty'] = ife(($data['Image']['flag'] & IMAGE_FLAG_DIRTY) > 0, true, false);

    return $data;
  }

  /** Increase the ACL level. It checks the current flag and increases the ACL
   * level of lower ACL levels (first level is ACL_LEVEL_GROUP, second level is
   * ACL_LEVEL_USER and the third level is ACL_LEVEL_OTHER).
    @param data Array of image data
    @param flag Threshold flag which indicates the upper inclusive bound
    @param mask Bit mask of flag 
    @param level Highes ACL level which should be increased */
  function _increaseAcl(&$data, $flag, $mask, $level) {
    //$this->Logger->debug("Increase: {$data['Image']['gacl']},{$data['Image']['uacl']},{$data['Image']['oacl']}: $flag/$mask ($level)");
    if ($level>ACL_LEVEL_OTHER)
      return;

    for ($l=ACL_LEVEL_GROUP; $l<=$level; $l++) {
      $name = $this->_aclMap[$l];
      if (($data['Image'][$name]&($mask))<$flag)
        $data['Image'][$name]=($data['Image'][$name]&(~$mask))|$flag;
    }
    //$this->Logger->debug("Increase (result): {$data['Image']['gacl']},{$data['Image']['uacl']},{$data['Image']['oacl']}: $flag/$mask ($level)");
  }

  /** Decrease the ACL level. Decreases the ACL level of higher ACL levels
   * according to the current flag (first level is ACL_LEVEL_GROUP, second level
   * is ACL_LEVEL_USER and the third level is ACL_LEVEL_OTHER). The decreased ACL
   * value is the ACL value of the higher levels which is less than the current
   * threshold or it is zero if no lower ACL value is available. 
    @param data Array of image data
    @param flag Threshold flag which indicates the upper exlusive bound
    @param mask Bit mask of flag
    @param level Lower ACL level which should be downgraded */
  function _decreaseAcl(&$data, $flag, $mask, $level) {
    //$this->Logger->debug("Decrease: {$data['Image']['gacl']},{$data['Image']['uacl']},{$data['Image']['oacl']}: $flag/$mask ($level)");
    if ($level<ACL_LEVEL_GROUP)
      return;

    for ($l=ACL_LEVEL_OTHER; $l>=$level; $l--) {
      $name = $this->_aclMap[$l];
      // Evaluate the available ACL value which is lower than the threshold
      if ($l==ACL_LEVEL_OTHER) 
        $lower = 0;
      else {
        $next = $this->_aclMap[$l+1];
        $lower = $data['Image'][$next]&($mask);
      }
      $lower=($lower<$flag)?$lower:0;
      if (($data['Image'][$name]&($mask))>=$flag)
        $data['Image'][$name]=($data['Image'][$name]&(~$mask))|$lower;
    }
    //$this->Logger->debug("Decrease (result): {$data['Image']['gacl']},{$data['Image']['uacl']},{$data['Image']['oacl']}: $flag/$mask ($level)");
  }

  function setAcl(&$data, $flag, $mask, $level) {
    if ($level<ACL_LEVEL_KEEP || $level>ACL_LEVEL_OTHER)
      return false;

    if ($level==ACL_LEVEL_KEEP)
      return $data;

    if ($level>=ACL_LEVEL_GROUP)
      $this->_increaseAcl(&$data, $flag, $mask, $level);

    if ($level<ACL_LEVEL_OTHER)
      $this->_decreaseAcl(&$data, $flag, $mask, $level+1);

    return $data;
  }

  /** Generates a has and belongs to many relation query for the image
    @param id Id of the image
    @param model Model name
    @return Array of the relation model */
  function _optimizedHabtm($id, $model) {
    if (!isset($this->hasAndBelongsToMany[$model]['cacheQuery'])) {
      $db =& ConnectionManager::getDataSource($this->useDbConfig);

      $table = $db->fullTableName($this->{$model}->table, false);
      $alias = $this->{$model}->alias;
      $key = $this->{$model}->primaryKey;

      $joinTable = $this->hasAndBelongsToMany[$model]['joinTable'];
      $joinTable = $db->fullTableName($joinTable, false);
      $joinAlias = $this->hasAndBelongsToMany[$model]['with'];
      $foreignKey = $this->hasAndBelongsToMany[$model]['foreignKey'];
      $associationForeignKey = $this->hasAndBelongsToMany[$model]['associationForeignKey'];

      $sql = "SELECT `$alias`.* FROM `$table` AS `$alias`, `$joinTable` AS `$joinAlias` WHERE `$alias`.`$key` = `$joinAlias`.`$associationForeignKey` AND `$joinAlias`.`$foreignKey`=%d";
      if (!empty($this->hasAndBelongsToMany[$model]['order'])) {
        $sql .= " ORDER BY ".$this->hasAndBelongsToMany[$model]['order'];
      }
      $this->hasAndBelongsToMany[$model]['cacheQuery'] = $sql;
    }

    $sql = sprintf($this->hasAndBelongsToMany[$model]['cacheQuery'], $id);
    $result = $this->query($sql);

    $list = array();
    if ($result) {
      foreach ($result as $item) {
        $list[] = &$item[$model];
      }
    }
    return $list;
  }

  /** Generates a has one relation query for the image
    @param modelId Id of the related model
    @param model Model name
    @return Array of the relation model */
  function _optimizedBelongsTo($modelId, $model) {
    if (!$modelId)
      return array();

    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    if (!isset($this->belongsTo[$model]['cacheQuery'])) {
      $table = $db->fullTableName($this->{$model}->table, false);
      $alias = $this->{$model}->alias;
      $key = $this->{$model}->primaryKey;
      $tp = $this->tablePrefix;

      $sql = "SELECT `$alias`.* FROM `$table` AS `$alias` WHERE `$alias`.`$key`=%d";
      $this->belongsTo[$model]['cacheQuery'] = $sql;
    }

    $sql = sprintf($this->belongsTo[$model]['cacheQuery'], $modelId);
    $result = $this->query($sql);
    if (count($result))
      return $result[0][$model];
    else
      return array();
  }

  /** The function Model::find slows down the hole search. This function builds
   * the query manually for speed optimazation 
    @param id Image id
    @return Return the image Array as find */
  function optimizedRead($id) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);
    $myTable = $db->fullTableName($this->table, false);
    $sql = "SELECT Image.* FROM `$myTable` AS Image WHERE Image.id = $id";
    $result = $this->query($sql);
    if (!$result)
      return array();

    $image = &$result[0];

    foreach ($this->belongsTo as $model => $config) {
      $name = Inflector::underscore($model);
      $image[$model] = $this->_optimizedBelongsTo($image['Image'][$name.'_id'], $model);
    }

    foreach ($this->hasAndBelongsToMany as $model => $config) {
      $image[$model] = $this->_optimizedHabtm($id, $model);
    }
    return $image;
  }
 
  /** 
    @param user Current user
    @param userId User id of own user or foreign user. If user id is equal with
    the id of the current user, the user is treated as 'My Images'. Otherwise
    the default acl will apply 
    @param level Level of ACL which image must be have. Default is ACL_READ_PREVIEW.
    @return returns sql statement for the where clause which checks the access
    to the image */
  function buildWhereAcl($user, $userId = 0, $level = ACL_READ_PREVIEW) {
    $level = intval($level);
    $acl = '';
    if ($userId > 0 && $user['User']['id'] == $userId) {
      // My Images
      if ($user['User']['role'] >= ROLE_USER)
        $acl .= " AND Image.user_id = $userId";
      elseif ($user['User']['role'] == ROLE_GUEST) {
        if (count($user['Member'])) {
          $groupIds = Set::extract($user, 'Member.{n}.id');
          if (count($groupIds) > 1) {
            $acl .= " AND Image.group_id in ( ".implode(", ", $groupIds)." )";
            $acl .= " AND Image.gacl >= $level";
          } elseif (count($groupIds) == 1) {
            $acl .= " AND Image.group_id = {$groupIds[0]}";
            $acl .= " AND Image.gacl >= $level";
          }
        } else {
          // no images
          $acl .= " AND 1 = 0";
        }
      }
    } else {
      // Another user, if set
      if ($userId > 0)
        $acl .= " AND Image.user_id = $userId";

      // General ACL
      if ($user['User']['role'] < ROLE_ADMIN) {
        $acl .= " AND (";
        // All images of group on Guests and Users
        if ($user['User']['role'] >= ROLE_GUEST && count($user['Member'])) {
          $groupIds = Set::extract($user, 'Member.{n}.id');
          if (count($groupIds) > 1) {
            $acl .= " ( Image.group_id in ( ".implode(", ", $groupIds)." )";
            $acl .= " AND Image.gacl >= $level ) OR";
          } elseif (count($groupIds) == 1) {
            $acl .= " ( Image.group_id = {$groupIds[0]}";
            $acl .= " AND Image.gacl >= $level ) OR";
          }
        }
        if ($user['User']['role'] >= ROLE_USER) {
          // Own image
          if ($userId == 0) {
            $acl .= " Image.user_id = {$user['User']['id']} OR";
          }
          // Other users
          $acl .= " Image.uacl >= $level OR";
        }
        // Public 
        $acl .= " Image.oacl >= $level )";
      }
    }
    return $acl;
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

    $db =& ConnectionManager::getDataSource($this->useDbConfig);
    $conditions = '';
    if (is_dir($filename)) {
      $path = $db->value(Folder::slashTerm($filename).'%');
      $conditions .= "Image.path LIKE $path";
    } else {
      $path = $db->value(Folder::slashTerm(dirname($filename)));
      $file = $db->value(basename($filename));
      $conditions .= "Image.path=$path AND Image.file=$file";
    }
    $conditions .= $this->buildWhereAcl($user, 0, $flag);

    return $this->hasAny($conditions);
  }

  function queryCloud($user, $model='Tag', $num=50) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    $myTable = $db->fullTableName($this->table, false);

    $table = $db->fullTableName($this->{$model}->table, false);
    $alias = $this->{$model}->alias;
    $key = $this->{$model}->primaryKey;

    $joinTable = $this->hasAndBelongsToMany[$model]['joinTable'];
    $joinTable = $db->fullTableName($joinTable, false);
    $joinAlias = $this->hasAndBelongsToMany[$model]['with'];
    $foreignKey = $this->hasAndBelongsToMany[$model]['foreignKey'];
    $associationForeignKey = $this->hasAndBelongsToMany[$model]['associationForeignKey'];

    $sql="SELECT `$alias`.`name`,COUNT(`$alias`.`name`) AS hits".
         " FROM `$table` AS `$alias`,".
         "  `$joinTable` AS `$joinAlias`,".
         "  `$myTable` AS `{$this->alias}`".
         " WHERE `$alias`.`$key` = `$joinAlias`.`$associationForeignKey`".
         "   AND `$joinAlias`.`$foreignKey` = `{$this->alias}`.`{$this->primaryKey}`".
         "   AND Image.flag & ".IMAGE_FLAG_ACTIVE.
         $this->buildWhereAcl($user).
         " GROUP BY `$alias`.`name` ".
         " ORDER BY hits DESC LIMIT 0,".intval($num);

    return $this->query($sql);
  }

  function countBytes($userId, $includeExternal = false) {
    $userId = intval($userId);
    $sql = "SELECT SUM(bytes) AS total FROM ".$this->tablePrefix.$this->table." WHERE user_id=$userId";
    if (!$includeExternal) {
      $sql .= " AND flag & ".IMAGE_FLAG_EXTERNAL." = 0";
    }
    $result = $this->query($sql);
    return $result[0][0]['total'];
  }

  /** Deletes all HABTM association from images of a given user like Tag, Categories 
    @param userId User ID */
  function _deleteHasAndBelongsToManyFromUser($userId) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    $table = $db->fullTableName($this->table, false);
    $alias = $this->alias;
    $key = $this->primaryKey;

    $this->Logger->info("Delete HasAndBelongsToMany Image association of user '$userId'");
    foreach ($this->hasAndBelongsToMany as $model => $data) {
      $joinTable = $db->fullTableName($data['joinTable'], false);
      $joinAlias = $data['with'];
      $foreignKey = $data['foreignKey'];
      $sql = "DELETE FROM `$joinAlias`".
             " USING `$joinTable` AS `$joinAlias`, `$table` AS `$alias`".
             " WHERE `$alias`.`user_id` = $userId AND `$alias`.`$key` = `$joinAlias`.`$foreignKey`";
      $this->Logger->debug("Delete $model associations");
      $this->Logger->trace($sql);
      $this->query($sql);
    }
  }

  function _deleteHasManyFromUser($userId) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    $table = $db->fullTableName($this->table, false);
    $alias = $this->alias;
    $key = $this->primaryKey;

    $this->Logger->info("Delete HasMany Image assosciation of user '$userId'");
    foreach ($this->hasMany as $model => $data) {
      $manyTable = $db->fullTableName($this->{$model}->table, false);
      $foreignKey = $data['foreignKey'];
      $sql = "DELETE FROM `$model`".
             " USING `$manyTable` AS `$model`, `$table` AS `$alias`".
             " WHERE `$alias`.`user_id` = $userId AND `$alias`.`$key` = `$model`.`$foreignKey`";
      $this->Logger->debug("Delete $model associations");
      $this->Logger->trace($sql);
      $this->query($sql);
    }
  }

  function deleteFromUser($userId) {
    $this->_deleteHasAndBelongsToManyFromUser($userId);
    $this->_deleteHasManyFromUser($userId);
    $this->deleteAll("Image.user_id = $userId");
  }
}
?>
