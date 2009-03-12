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

class Medium extends AppModel
{
  var $name = 'Medium';

  var $belongsTo = array(
    'User' => array(),
    'Group' => array());
  
  var $hasMany = array(
    'Comment' => array('dependent' => true, 'foreignKey' => 'medium_id'),
    'File' => array('className' => 'MyFile', 'foreignKey' => 'medium_id'));

  var $hasAndBelongsToMany = array(
    'Tag' => array('foreignKey' => 'medium_id'),
    'Category' => array('foreignKey' => 'medium_id'),
    'Location' => array('foreignKey' => 'medium_id', 'order' => 'Location.type'));
  
  var $_aclMap = array(
    ACL_LEVEL_GROUP => 'gacl',
    ACL_LEVEL_USER => 'uacl',
    ACL_LEVEL_OTHER => 'oacl');

  var $actsAs = array('Type', 'Flag', 'Cache');

  function beforeDelete($cascade = true) {
    // Delete media cache files
    $this->unbindAll();
    $this->set($this->findById($this->id));
    $this->deleteCache();
    return true;
  }

  function addDefaultAcl(&$data, $user) {
    // Access control values
    $acl = $this->User->Option->getDefaultAcl($user);
    $data['Medium']['user_id'] = $user['User']['id'];
    $data['Medium']['group_id'] = $acl['groupId'];
    $data['Medium']['gacl'] = $acl['gacl'];
    $data['Medium']['uacl'] = $acl['uacl'];
    $data['Medium']['oacl'] = $acl['oacl'];
    return $data;
  }

  /** Returns the file model by its type
    @param data Media model data
    @param fileType Required file type. Default is FILE_TYPE_IMAGE
    @param fullModel If true returns the full associated file model. If false
    returns only the file model of the medium without associations 
    @return Fals on error, null if file was not found */
  function getFile($data, $fileType = FILE_TYPE_IMAGE, $fullModel = true) {
    if (!$data) {
      $data = $this->data;
    }

    if (!isset($data['File'])) {
      $this->Logger->err("Precondition failed");
      return false;
    }

    foreach ($data['File'] as $file) {
      if ($file['type'] == $fileType) {
        if ($fullModel) {
          return $this->File->findById($file['id']);
        } else {
          return array('File' => $file);
        }
      }
    }

    return null;
  }

  /** Returns true if current user is allowed of the current flag
    @param data Current Medium array
    @param user Current User array
    @param flag Flag bit which should be checkt
    @param mask Bitmask for the flag which should be checkt
    @param groups Array of user's group. If groups is null, it will be created
    by the user's data.
    @return True is user is allowed, False otherwise */
  function checkAccess(&$data, &$user, $flag, $mask, &$groups=null) {
    if (!$data || !$user || !isset($data['Medium']) || !isset($user['User'])) {
      $this->Logger->err("precondition failed");
      return false;
    }

    // check for public access
    if (($data['Medium']['oacl'] & $mask) >= $flag)
      return true;

    // check for members
    if ($user['User']['role'] >= ROLE_USER && 
      ($data['Medium']['uacl'] & $mask) >= $flag)
      return true;

    // check for group members
    if ($groups === null)
      $groups = Set::extract($user, 'Member.{n}.id');
    if ($user['User']['role'] >= ROLE_GUEST &&
      ($data['Medium']['gacl'] & $mask) >= $flag &&
      in_array($data['Medium']['group_id'], $groups))
      return true;

    // Medium owner and admin check
    if ($user['User']['id'] == $data['Medium']['user_id'] ||
      $user['User']['role'] == ROLE_ADMIN)
      return true;

    return false;
  }

  /** Set the access flags of write and read options according to the current user
    @param data Reference of the Medium array 
    @param user User array
    @return $data of Medium data with the access flags */
  function setAccessFlags(&$data, $user) {
    if (!isset($data)) 
      return $data;

    // at least dummy user
    $user = am(array('User' => array('id' => -1, 'role' => ROLE_NOBODY), 'Member' => array()), $user);
    //$this->Logger->debug($user);

    $oacl = $data['Medium']['oacl'];
    $uacl = $data['Medium']['uacl'];
    $gacl = $data['Medium']['gacl'];
    
    $groups = Set::extract($user, 'Member.{n}.id');

    $data['Medium']['canWriteTag'] = $this->checkAccess(&$data, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK, &$groups);    
    $data['Medium']['canWriteMeta'] = $this->checkAccess(&$data, &$user, ACL_WRITE_META, ACL_WRITE_MASK, &$groups);    
    $data['Medium']['canWriteCaption'] = $this->checkAccess(&$data, &$user, ACL_WRITE_CAPTION, ACL_WRITE_MASK, &$groups);    

    $data['Medium']['canReadPreview'] = $this->checkAccess(&$data, &$user, ACL_READ_PREVIEW, ACL_READ_MASK, &$groups);    
    $data['Medium']['canReadHigh'] = $this->checkAccess(&$data, &$user, ACL_READ_HIGH, ACL_READ_MASK, &$groups);    
    $data['Medium']['canReadOriginal'] = $this->checkAccess(&$data, &$user, ACL_READ_ORIGINAL, ACL_READ_MASK, &$groups);    

    $data['Medium']['isOwner'] = ife($data['Medium']['user_id'] == $user['User']['id'], true, false);
    $data['Medium']['canWriteAcl'] = $this->checkAccess(&$data, &$user, 1, 0, &$groups);    
    $data['Medium']['isDirty'] = ife(($data['Medium']['flag'] & MEDIUM_FLAG_DIRTY) > 0, true, false);

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
    //$this->Logger->debug("Increase: {$data['Medium']['gacl']},{$data['Medium']['uacl']},{$data['Medium']['oacl']}: $flag/$mask ($level)");
    if ($level>ACL_LEVEL_OTHER)
      return;

    for ($l=ACL_LEVEL_GROUP; $l<=$level; $l++) {
      $name = $this->_aclMap[$l];
      if (($data['Medium'][$name]&($mask))<$flag)
        $data['Medium'][$name]=($data['Medium'][$name]&(~$mask))|$flag;
    }
    //$this->Logger->debug("Increase (result): {$data['Medium']['gacl']},{$data['Medium']['uacl']},{$data['Medium']['oacl']}: $flag/$mask ($level)");
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
    //$this->Logger->debug("Decrease: {$data['Medium']['gacl']},{$data['Medium']['uacl']},{$data['Medium']['oacl']}: $flag/$mask ($level)");
    if ($level<ACL_LEVEL_GROUP)
      return;

    for ($l=ACL_LEVEL_OTHER; $l>=$level; $l--) {
      $name = $this->_aclMap[$l];
      // Evaluate the available ACL value which is lower than the threshold
      if ($l==ACL_LEVEL_OTHER) 
        $lower = 0;
      else {
        $next = $this->_aclMap[$l+1];
        $lower = $data['Medium'][$next]&($mask);
      }
      $lower=($lower<$flag)?$lower:0;
      if (($data['Medium'][$name]&($mask))>=$flag)
        $data['Medium'][$name]=($data['Medium'][$name]&(~$mask))|$lower;
    }
    //$this->Logger->debug("Decrease (result): {$data['Medium']['gacl']},{$data['Medium']['uacl']},{$data['Medium']['oacl']}: $flag/$mask ($level)");
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
    @param id Medium id
    @return Return the image Array as find */
  function optimizedRead($id) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);
    $myTable = $db->fullTableName($this->table, false);
    $sql = "SELECT Medium.* FROM `$myTable` AS Medium WHERE Medium.id = $id";
    $result = $this->query($sql);
    if (!$result)
      return array();

    $image = &$result[0];

    foreach ($this->belongsTo as $model => $config) {
      $name = Inflector::underscore($model);
      $image[$model] = $this->_optimizedBelongsTo($image['Medium'][$name.'_id'], $model);
    }

    foreach ($this->hasAndBelongsToMany as $model => $config) {
      $image[$model] = $this->_optimizedHabtm($id, $model);
    }
    return $image;
  }
 
  /** 
    @param user Current user
    @param userId User id of own user or foreign user. If user id is equal with
    the id of the current user, the user is treated as 'My Mediums'. Otherwise
    the default acl will apply 
    @param level Level of ACL which image must be have. Default is ACL_READ_PREVIEW.
    @return returns sql statement for the where clause which checks the access
    to the image */
  function buildWhereAcl($user, $userId = 0, $level = ACL_READ_PREVIEW) {
    $level = intval($level);
    $acl = '';
    if ($userId > 0 && $user['User']['id'] == $userId) {
      // My Mediums
      if ($user['User']['role'] >= ROLE_USER)
        $acl .= " AND Medium.user_id = $userId";
      elseif ($user['User']['role'] == ROLE_GUEST) {
        if (count($user['Member'])) {
          $groupIds = Set::extract($user, 'Member.{n}.id');
          if (count($groupIds) > 1) {
            $acl .= " AND Medium.group_id in ( ".implode(", ", $groupIds)." )";
            $acl .= " AND Medium.gacl >= $level";
          } elseif (count($groupIds) == 1) {
            $acl .= " AND Medium.group_id = {$groupIds[0]}";
            $acl .= " AND Medium.gacl >= $level";
          }
        } else {
          // no images
          $acl .= " AND 1 = 0";
        }
      }
    } else {
      // Another user, if set
      if ($userId > 0)
        $acl .= " AND Medium.user_id = $userId";

      // General ACL
      if ($user['User']['role'] < ROLE_ADMIN) {
        $acl .= " AND (";
        // All images of group on Guests and Users
        if ($user['User']['role'] >= ROLE_GUEST && count($user['Member'])) {
          $groupIds = Set::extract($user, 'Member.{n}.id');
          if (count($groupIds) > 1) {
            $acl .= " ( Medium.group_id in ( ".implode(", ", $groupIds)." )";
            $acl .= " AND Medium.gacl >= $level ) OR";
          } elseif (count($groupIds) == 1) {
            $acl .= " ( Medium.group_id = {$groupIds[0]}";
            $acl .= " AND Medium.gacl >= $level ) OR";
          }
        }
        if ($user['User']['role'] >= ROLE_USER) {
          // Own image
          if ($userId == 0) {
            $acl .= " Medium.user_id = {$user['User']['id']} OR";
          }
          // Other users
          $acl .= " Medium.uacl >= $level OR";
        }
        // Public 
        $acl .= " Medium.oacl >= $level )";
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
      $conditions .= "Medium.path LIKE $path";
    } else {
      $path = $db->value(Folder::slashTerm(dirname($filename)));
      $file = $db->value(basename($filename));
      $conditions .= "Medium.path=$path AND Medium.file=$file";
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

    $this->Logger->debug($this->hasAndBelongsToMany[$model]);
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
    //     "   AND Medium.flag & ".MEDIUM_FLAG_ACTIVE.
         $this->buildWhereAcl($user).
         " GROUP BY `$alias`.`name` ".
         " ORDER BY hits DESC LIMIT 0,".intval($num);

    return $this->query($sql);
  }

  /** Deletes all HABTM association from images of a given user like Tag, Categories 
    @param userId User ID */
  function _deleteHasAndBelongsToManyFromUser($userId) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    $table = $db->fullTableName($this->table, false);
    $alias = $this->alias;
    $key = $this->primaryKey;

    $this->Logger->info("Delete HasAndBelongsToMany Medium association of user '$userId'");
    foreach ($this->hasAndBelongsToMany as $model => $data) {
      $joinTable = $db->fullTableName($data['joinTable'], false);
      $joinAlias = $data['with'];
      $foreignKey = $data['foreignKey'];
      $sql = "DELETE FROM `$joinAlias`".
             " USING `$joinTable` AS `$joinAlias`, `$table` AS `$alias`".
             " WHERE `$alias`.`user_id` = $userId AND `$alias`.`$key` = `$joinAlias`.`$foreignKey`";
      $this->Logger->debug("Delete $model HABTM associations");
      $this->query($sql);
    }
  }

  function _deleteHasManyFromUser($userId) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    $table = $db->fullTableName($this->table, false);
    $alias = $this->alias;
    $key = $this->primaryKey;

    $this->Logger->info("Delete HasMany Medium assosciation of user '$userId'");
    foreach ($this->hasMany as $model => $data) {
      if (!isset($data['dependent']) || !$data['dependent']) {
        continue;
      }
      $manyTable = $db->fullTableName($this->{$model}->table, false);
      $foreignKey = $data['foreignKey'];
      $sql = "DELETE FROM `$model`".
             " USING `$manyTable` AS `$model`, `$table` AS `$alias`".
             " WHERE `$alias`.`user_id` = $userId AND `$alias`.`$key` = `$model`.`$foreignKey`";
      $this->Logger->debug("Delete $model HasMany associations");
      $this->query($sql);
    }
  }

  function deleteFromUser($userId) {
    $this->bindModel(array(
      'hasMany' => array(
        'Property' => array('dependent' => true), 
        'Comment' => array('dependent' => true)
      )));
    $this->_deleteHasAndBelongsToManyFromUser($userId);
    $this->_deleteHasManyFromUser($userId);
    $this->deleteAll("Medium.user_id = $userId");
  }
}
?>
