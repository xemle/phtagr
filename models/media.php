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

class Media extends AppModel
{
  var $name = 'Media';

  var $belongsTo = array(
    'User' => array(),
    'Group' => array());
  
  var $hasMany = array(
    'Comment' => array('dependent' => true),
    'File' => array('className' => 'MyFile'));

  var $hasAndBelongsToMany = array(
    'Tag' => array(),
    'Category' => array(),
    'Location' => array('order' => 'Location.type'));
  
  var $_aclMap = array(
    ACL_LEVEL_GROUP => 'gacl',
    ACL_LEVEL_USER => 'uacl',
    ACL_LEVEL_OTHER => 'oacl');

  var $actsAs = array('Type', 'Flag', 'Cache', 'Exclude');

  function beforeDelete($cascade = true) {
    // Delete media cache files
    $this->unbindAll();
    $this->set($this->findById($this->id));
    $this->deleteCache();
    return true;
  }

  function afterDelete() {
    $this->File->unlinkMedia($this->id);
  }

  /** Unlink a file form a media. This function takes care of deleting the
    * media. The media will be deleted if the file is dependent or no other
    * dependent file is left for the media.
    @param data Media model data or media Id. If it is false, it will use
    $this->id.
    @param fileId File id of the file to be unlinked */
  function unlinkFile(&$data, $fileId) {
    if (is_numeric($data)) {
      $mediaId = $data;
    } elseif (isset($data['Media']['id'])) {
      $mediaId = $data['Media']['id'];
    } elseif (isset($data['id'])) {
      $mediaId = $data['id'];
    } else {
      $mediaId = $this->id;
    }

    if (!$mediaId || $fileId <= 0) {
      Logger::err("Invalid input");
      return false;
    }

    $media = $this->findById($mediaId);
    if (!$media) {
      Logger::warn("Could not found media with id $mediaId");
      return false;
    }
    $fileIds = Set::extract("/File/id", $media);
    if (!in_array($fileId, $fileIds)) {
      Logger::warn("Media $mediaId does not have file $fileId");
      return false;
    }

    // Delete if: 1. only one file. 2. file with fileId is dependent. Or 3. if
    // no other file is dependend
    if (count($fileIds) == 1) {
      $delete = true;
    } else {
      $delete = true;
      foreach ($media['File'] as $file) {
        if ($file['id'] == $fileId) {
          if ($this->File->hasFlag($file, FILE_FLAG_DEPENDENT)) {
            $delete = true;
            break;
          }
          continue;
        } 
        if ($this->File->hasFlag($file, FILE_FLAG_DEPENDENT)) {
          $delete = false;
        }
      }
    }
    if ($delete) {
      Logger::info("Delete media $mediaId");
      $this->delete($mediaId);
    } else {
      $this->File->unlinkMedia(false, $fileId);
    }
    return true; 
  }

  function addDefaultAcl(&$data, $user) {
    if (!$data) {
      $data =& $this->data;
    }
    if (!isset($user) || !isset($user['User']['id'])) {
      Logger::err("User data is not correct! Media ACL will be wrong!");
      Logger::trace($user);
    }
    
    // Access control values
    $acl = $this->User->Option->getDefaultAcl($user);
    $data['Media']['user_id'] = $user['User']['id'];
    $data['Media']['group_id'] = $acl['groupId'];
    $data['Media']['gacl'] = $acl['gacl'];
    $data['Media']['uacl'] = $acl['uacl'];
    $data['Media']['oacl'] = $acl['oacl'];
    return $data;
  }

  /** Returns the file model by its type
    @param data Media model data
    @param fileType Required file type. Default is FILE_TYPE_IMAGE
    @param fullModel If true returns the full associated file model. If false
    returns only the file model of the media without associations 
    @return Fals on error, null if file was not found */
  function getFile($data, $fileType = FILE_TYPE_IMAGE, $fullModel = true) {
    if (!$data) {
      $data = $this->data;
    }

    if (!isset($data['File'])) {
      Logger::err("Precondition failed");
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
    @param data Current Media array
    @param user Current User array
    @param flag Flag bit which should be checkt
    @param mask Bitmask for the flag which should be checkt
    @param groups Array of user's group. If groups is null, it will be created
    by the user's data.
    @return True is user is allowed, False otherwise */
  function checkAccess(&$data, &$user, $flag, $mask, &$groups=null) {
    if (!$data || !$user || !isset($data['Media']) || !isset($user['User'])) {
      Logger::err("precondition failed");
      return false;
    }

    // check for public access
    if (($data['Media']['oacl'] & $mask) >= $flag)
      return true;

    // check for members
    if ($user['User']['role'] >= ROLE_USER && 
      ($data['Media']['uacl'] & $mask) >= $flag)
      return true;

    // check for group members
    if ($groups === null)
      $groups = Set::extract($user, 'Member.{n}.id');
    if ($user['User']['role'] >= ROLE_GUEST &&
      ($data['Media']['gacl'] & $mask) >= $flag &&
      in_array($data['Media']['group_id'], $groups))
      return true;

    // Media owner and admin check
    if ($user['User']['id'] == $data['Media']['user_id'] ||
      $user['User']['role'] == ROLE_ADMIN)
      return true;

    return false;
  }

  /** Set the access flags of write and read options according to the current user
    @param data Reference of the Media array 
    @param user User array
    @return $data of Media data with the access flags */
  function setAccessFlags(&$data, $user) {
    if (!isset($data)) 
      return $data;

    // at least dummy user
    $user = am(array('User' => array('id' => -1, 'role' => ROLE_NOBODY), 'Member' => array()), $user);
    //Logger::debug($user);

    $oacl = $data['Media']['oacl'];
    $uacl = $data['Media']['uacl'];
    $gacl = $data['Media']['gacl'];
    
    $groups = Set::extract($user, 'Member.{n}.id');

    $data['Media']['canWriteTag'] = $this->checkAccess(&$data, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK, &$groups);    
    $data['Media']['canWriteMeta'] = $this->checkAccess(&$data, &$user, ACL_WRITE_META, ACL_WRITE_MASK, &$groups);    
    $data['Media']['canWriteCaption'] = $this->checkAccess(&$data, &$user, ACL_WRITE_CAPTION, ACL_WRITE_MASK, &$groups);    

    $data['Media']['canReadPreview'] = $this->checkAccess(&$data, &$user, ACL_READ_PREVIEW, ACL_READ_MASK, &$groups);    
    $data['Media']['canReadHigh'] = $this->checkAccess(&$data, &$user, ACL_READ_HIGH, ACL_READ_MASK, &$groups);    
    $data['Media']['canReadOriginal'] = $this->checkAccess(&$data, &$user, ACL_READ_ORIGINAL, ACL_READ_MASK, &$groups);    
    if (($data['Media']['oacl'] & ACL_READ_PREVIEW) > 0) {
      $data['Media']['visibility'] = ACL_LEVEL_OTHER;
    } elseif (($data['Media']['uacl'] & ACL_READ_PREVIEW) > 0) {
      $data['Media']['visibility'] = ACL_LEVEL_USER;
    } elseif (($data['Media']['gacl'] & ACL_READ_PREVIEW) > 0) {
      $data['Media']['visibility'] = ACL_LEVEL_GROUP;
    } else {
      $data['Media']['visibility'] = ACL_LEVEL_PRIVATE;
    }

    $data['Media']['isOwner'] = ife($data['Media']['user_id'] == $user['User']['id'], true, false);
    $data['Media']['canWriteAcl'] = $this->checkAccess(&$data, &$user, 1, 0, &$groups);    
    $data['Media']['isDirty'] = ife(($data['Media']['flag'] & MEDIA_FLAG_DIRTY) > 0, true, false);

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
    //Logger::debug("Increase: {$data['Media']['gacl']},{$data['Media']['uacl']},{$data['Media']['oacl']}: $flag/$mask ($level)");
    if ($level>ACL_LEVEL_OTHER)
      return;

    for ($l=ACL_LEVEL_GROUP; $l<=$level; $l++) {
      $name = $this->_aclMap[$l];
      if (($data['Media'][$name]&($mask))<$flag)
        $data['Media'][$name]=($data['Media'][$name]&(~$mask))|$flag;
    }
    //Logger::debug("Increase (result): {$data['Media']['gacl']},{$data['Media']['uacl']},{$data['Media']['oacl']}: $flag/$mask ($level)");
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
    //Logger::debug("Decrease: {$data['Media']['gacl']},{$data['Media']['uacl']},{$data['Media']['oacl']}: $flag/$mask ($level)");
    if ($level<ACL_LEVEL_GROUP)
      return;

    for ($l=ACL_LEVEL_OTHER; $l>=$level; $l--) {
      $name = $this->_aclMap[$l];
      // Evaluate the available ACL value which is lower than the threshold
      if ($l==ACL_LEVEL_OTHER) 
        $lower = 0;
      else {
        $next = $this->_aclMap[$l+1];
        $lower = $data['Media'][$next]&($mask);
      }
      $lower=($lower<$flag)?$lower:0;
      if (($data['Media'][$name]&($mask))>=$flag)
        $data['Media'][$name]=($data['Media'][$name]&(~$mask))|$lower;
    }
    //Logger::debug("Decrease (result): {$data['Media']['gacl']},{$data['Media']['uacl']},{$data['Media']['oacl']}: $flag/$mask ($level)");
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

  /** Generates a has one relation query for the image
    @param modelId Id of the related model
    @param model Model name
    @return Array of the relation model */
  function _optimizedHasMany($modelId, $model) {
    if (!$modelId) {
      return array();
    }

    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    if (!isset($this->hasMany[$model]['cacheQuery'])) {
      $config = $this->hasMany[$model];
      $table = $db->fullTableName($this->{$model}->table, false);
      $alias = $this->{$model}->alias;
      $key = $config['foreignKey'];
      $tp = $this->tablePrefix;

      $sql = "SELECT `$alias`.* FROM `$table` AS `$alias` WHERE `$alias`.`$key`=%d";
      $this->hasMany[$model]['cacheQuery'] = $sql;
    }

    $sql = sprintf($this->hasMany[$model]['cacheQuery'], $modelId);
    //Logger::debug($sql);
    $result = $this->query($sql);
    if (count($result)) {
      $tmp = array();
      foreach ($result as $data) {
        $tmp[] = $data[$model];
      }
      return $tmp;
    } else {
      return array();
    }
  }

  /** The function Model::find slows down the hole search. This function builds
   * the query manually for speed optimazation 
    @param id Media id
    @return Return the image Array as find */
  function optimizedRead($id) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);
    $myTable = $db->fullTableName($this->table, false);
    $sql = "SELECT Media.* FROM `$myTable` AS Media WHERE Media.id = $id";
    $result = $this->query($sql);
    if (!$result)
      return array();

    $image = &$result[0];

    foreach ($this->belongsTo as $model => $config) {
      $name = Inflector::underscore(Inflector::singularize($model));
      $image[$model] = $this->_optimizedBelongsTo($image['Media'][$name.'_id'], $model);
    }

    foreach ($this->hasMany as $model => $config) {
      $name = Inflector::underscore(Inflector::singularize($model));
      $image[$model] = $this->_optimizedHasMany($image['Media']['id'], $model);
    }

    foreach ($this->hasAndBelongsToMany as $model => $config) {
      $image[$model] = $this->_optimizedHabtm($id, $model);
    }
    return $image;
  }
 
  /** 
    @param user Current user
    @param userId User id of own user or foreign user. If user id is equal with
    the id of the current user, the user is treated as 'My Medias'. Otherwise
    the default acl will apply 
    @param level Level of ACL which image must be have. Default is ACL_READ_PREVIEW.
    @return returns asscess conditions which considers the access to the media */
  function buildAclConditions($user, $userId = 0, $level = ACL_READ_PREVIEW) {
    $level = intval($level);
    $conditions = array();
    if ($userId > 0 && $user['User']['id'] == $userId) {
      // My Medias
      if ($user['User']['role'] >= ROLE_USER) {
        $conditions[] = "Media.user_id = $userId";
      } elseif ($user['User']['role'] == ROLE_GUEST) {
        if (count($user['Member'])) {
          $groupIds = Set::extract($user, 'Member.{n}.id');
          if (count($groupIds) > 1) {
            $conditions[] = "Media.group_id in ( ".implode(", ", $groupIds)." )";
            $conditions[] = "Media.gacl >= $level";
          } elseif (count($groupIds) == 1) {
            $conditions[] = "Media.group_id = {$groupIds[0]}";
            $conditions[] = "Media.gacl >= $level";
          }
        } else {
          // no images
          $conditions[] = "1 = 0";
        }
      }
    } else {
      // Another user, if set
      if ($userId > 0) {
        $conditions[] = "Media.user_id = $userId";
      }

      // General ACL
      if ($user['User']['role'] < ROLE_ADMIN) {
        $acl = "(";
        // All images of group on Guests and Users
        if ($user['User']['role'] >= ROLE_GUEST && count($user['Member'])) {
          $groupIds = Set::extract($user, 'Member.{n}.id');
          if (count($groupIds) > 1) {
            $acl .= " ( Media.group_id in ( ".implode(", ", $groupIds)." )";
            $acl .= " AND Media.gacl >= $level ) OR";
          } elseif (count($groupIds) == 1) {
            $acl .= " ( Media.group_id = {$groupIds[0]}";
            $acl .= " AND Media.gacl >= $level ) OR";
          }
        }
        if ($user['User']['role'] >= ROLE_USER) {
          // Own image
          if ($userId == 0) {
            $acl .= " Media.user_id = {$user['User']['id']} OR";
          }
          // Other users
          $acl .= " Media.uacl >= $level OR";
        }
        // Public 
        $acl .= " Media.oacl >= $level )";
        $conditions[] = $acl;
      }
    }
    return $conditions;
  }

  /** Checks if a user can read the original file 
    @param user Array of User model
    @param filename Filename of the file to be checked 
    @param flag Reading image flag which must match the condition 
    @return True if user can read the filename */
  function canRead($filename, $user, $flag = ACL_READ_ORIGINAL) {
    if (!file_exists($filename)) {
      Logger::debug("Filename does not exists: $filename");
      return false;
    }

    $db =& ConnectionManager::getDataSource($this->useDbConfig);
    $conditions = array();
    if (is_dir($filename)) {
      $path = $db->value(Folder::slashTerm($filename).'%');
      $conditions[] = "Media.path LIKE $path";
    } else {
      $path = $db->value(Folder::slashTerm(dirname($filename)));
      $file = $db->value(basename($filename));
      $conditions[] = "Media.path=$path AND Media.file=$file";
    }
    $acl = $this->buildAclConditions($user, 0, $flag);
    $conditions = am($conditions, $acl);

    return $this->hasAny($conditions);
  }

  function updateRanking($data) {
    if (!isset($data['Media']['id'])) {
      Logger::warn("Precondition failed");
      return false;
    }

    $timediff = time() - strtotime($data['Media']['lastview']);
    $ranking = (0.9 * $data['Media']['ranking']) + (0.1 / ($timediff + 1));

    $data['Media']['ranking'] = $ranking;
    $data['Media']['lastview'] = date("Y-m-d H:i:s", time());
    $data['Media']['clicks']++;
    if (!$this->save($data['Media'], true, array('clicks', 'ranking', 'lastview'))) {
      Logger::err("Could not save new ranking data");
      return false;
    } else {
      Logger::trace("Update ranking of media {$data['Media']['id']} to $ranking with {$data['Media']['clicks']} click(s)");
      return true;
    }
  }

  function cloud($user, $assoc = 'Tag', $num = 50) {
    if (!isset($this->hasAndBelongsToMany[$assoc])) {
      return array();
    }
    $myTable = $this->tablePrefix.$this->table;

    $table = $this->{$assoc}->tablePrefix.$this->{$assoc}->table;
    $alias = $this->{$assoc}->alias;
    $key = $this->{$assoc}->primaryKey;

    $config = $this->hasAndBelongsToMany[$assoc];

    $joinTable = $this->tablePrefix.$config['joinTable'];
    $joinAlias = $config['with'];
    $foreignKey = $config['foreignKey'];
    $associationForeignKey = $config['associationForeignKey'];

    $acl = $this->buildAclConditions($user);
    if ($acl) {
      $aclWhere = ' AND '.implode(' AND ', $acl);
    } else {
      $aclWhere = '';
    }
    $sql="SELECT `$alias`.`name`,COUNT(`$alias`.`name`) AS hits".
         " FROM `$table` AS `$alias`,".
         "  `$joinTable` AS `$joinAlias`,".
         "  `$myTable` AS `{$this->alias}`".
         " WHERE `$alias`.`$key` = `$joinAlias`.`$associationForeignKey`".
         "   AND `$joinAlias`.`$foreignKey` = `{$this->alias}`.`{$this->primaryKey}`".
         $aclWhere.
         " GROUP BY `$alias`.`name` ".
         " ORDER BY hits DESC LIMIT 0,".intval($num);

    $data = $this->query($sql);
    if (count($data)) {
      $data = Set::combine($data, "{n}.$assoc.name", "{n}.0.hits");
    }
    return $data;
  }

  /** Deletes all HABTM association from images of a given user like Tag, Categories 
    @param userId User ID */
  function _deleteHasAndBelongsToManyFromUser($userId) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    $table = $db->fullTableName($this->table, false);
    $alias = $this->alias;
    $key = $this->primaryKey;

    Logger::info("Delete HasAndBelongsToMany Media association of user '$userId'");
    foreach ($this->hasAndBelongsToMany as $model => $data) {
      $joinTable = $db->fullTableName($data['joinTable'], false);
      $joinAlias = $data['with'];
      $foreignKey = $data['foreignKey'];
      $sql = "DELETE FROM `$joinAlias`".
             " USING `$joinTable` AS `$joinAlias`, `$table` AS `$alias`".
             " WHERE `$alias`.`user_id` = $userId AND `$alias`.`$key` = `$joinAlias`.`$foreignKey`";
      Logger::debug("Delete $model HABTM associations");
      $this->query($sql);
    }
  }

  function _deleteHasManyFromUser($userId) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    $table = $db->fullTableName($this->table, false);
    $alias = $this->alias;
    $key = $this->primaryKey;

    Logger::info("Delete HasMany Media assosciation of user '$userId'");
    foreach ($this->hasMany as $model => $data) {
      if (!isset($data['dependent']) || !$data['dependent']) {
        continue;
      }
      $manyTable = $db->fullTableName($this->{$model}->table, false);
      $foreignKey = $data['foreignKey'];
      $sql = "DELETE FROM `$model`".
             " USING `$manyTable` AS `$model`, `$table` AS `$alias`".
             " WHERE `$alias`.`user_id` = $userId AND `$alias`.`$key` = `$model`.`$foreignKey`";
      Logger::debug("Delete $model HasMany associations");
      $this->query($sql);
    }
  }

  function deleteFromUser($userId) {
    $this->bindModel(array(
      'hasMany' => array(
        'Comment' => array('dependent' => true)
      )));
    $this->_deleteHasAndBelongsToManyFromUser($userId);
    $this->_deleteHasManyFromUser($userId);
    $this->deleteAll("Media.user_id = $userId");
  }

  /** Count all media given by the group IDs
    @param groupIds Single group ID value or array of group IDs
    @return Count of media which are assigned to the given groups */
  function countByGroupId($groupIds) {
    $this->unbindModel(array('belongsTo' => array('Group')));
    return $this->find('count', array(
      'conditions' => array('Group.id' => $groupId),
      'joins' => array("JOIN `{$this->tablePrefix}groups` AS `Group` ON `Media`.`group_id` = `Group`.`id`")));
  }
}
?>
