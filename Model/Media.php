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

class Media extends AppModel {
  var $name = 'Media';

  var $belongsTo = array(
    'User' => array());

  var $hasMany = array(
    'Comment' => array('dependent' => true),
    'File' => array('className' => 'MyFile'));

  var $hasAndBelongsToMany = array(
    'Group' => array(),
    'Field' => array()
    );

  var $_aclMap = array(
    ACL_LEVEL_GROUP => 'gacl',
    ACL_LEVEL_USER => 'uacl',
    ACL_LEVEL_OTHER => 'oacl');

  var $actsAs = array('Type', 'Flag', 'Cache');

  public function beforeSave($options = array()) {
    parent::beforeSave();
    $this->Field->createFields($this->data);
    return true;
  }

  public function beforeDelete($cascade = true) {
    // Delete media cache files
    $this->unbindAll();
    $this->set($this->findById($this->id));
    $this->deleteCache();
    return true;
  }

  public function afterDelete() {
    $this->File->unlinkMedia($this->id);
  }

  /**
   * Unlink a file form a media. This function takes care of deleting the
   * media. The media will be deleted if the file is dependent or no other
   * dependent file is left for the media.
   *
   * @param data Media model data or media Id. If it is false, it will use
   * $this->id.
   * @param fileId File id of the file to be unlinked
   */
  public function unlinkFile(&$data, $fileId) {
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

  public function addDefaultAcl(&$data, $user) {
    if (!$data) {
      $data = $this->data;
    }
    if (!isset($user) || !isset($user['User']['id'])) {
      Logger::err("User data is not correct! Media ACL will be wrong!");
      Logger::trace($user);
    }

    // Access control values
    $acl = $this->User->Option->getDefaultAcl($user);
    $data['Media']['user_id'] = $user['User']['id'];
    $data['Media']['gacl'] = $acl['gacl'];
    $data['Media']['uacl'] = $acl['uacl'];
    $data['Media']['oacl'] = $acl['oacl'];
    $data['Group']['Group'] = array($acl['groupId']);
    return $data;
  }

  /**
   * Returns the file model by its type
   *
   * @param data Media model data
   * @param fileType Required file type. Default is FILE_TYPE_IMAGE
   * @param fullModel If true returns the full associated file model. If false
   * returns only the file model of the media without associations
   * @return Fals on error, null if file was not found
   */
  public function getFile($data, $fileType = FILE_TYPE_IMAGE, $fullModel = true) {
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

  /**
   * Returns the main filename of the media. The main file is the file
   * which has FILE_FLAG_DEPENDENT flag. If no file has a FILE_FLAG_DEPENDENT
   * returns the first file
   *
   * @param array $media Media model data
   * @return filename
   */
  public function getMainFilename(&$media) {
    $filename = false;
    foreach ($media['File'] as $file) {
      if ($this->File->hasFlag($file, FILE_FLAG_DEPENDENT)) {
        $filename = $this->File->getFilename($file);
        break;
      }
    }
    if (!$filename && count($media['File'])) {
        $filename = $this->File->getFilename($media['File'][0]);
    }
    return $filename;
  }

  public function canRead(&$media, &$user) {
    return $this->checkAccess($media, $user, ACL_READ_PREVIEW, ACL_READ_MASK, null);
  }

  public function canReadOriginal(&$media, &$user) {
    return $this->checkAccess($media, $user, ACL_READ_ORIGINAL, ACL_READ_MASK, null);
  }

  public function canWrite(&$media, &$user) {
    return $this->checkAccess($media, $user, ACL_WRITE_TAG, ACL_WRITE_MASK, null);
  }

  public function canWriteAcl(&$media, &$user) {
    return ($media['Media']['user_id'] == $user['User']['id'] ||
            $user['User']['role'] >= ROLE_ADMIN);
  }

  /**
   * Returns true if current user is allowed of the current flag
   *
   * @param data Current Media array
   * @param user Current User array
   * @param flag Flag bit which should be checkt
   * @param mask Bitmask for the flag which should be checkt
   * @param userGroupIds Array of user's group ids. If groups is null, it will be created
   * by the user's data.
   * @return True is user is allowed, False otherwise
   */
  public function checkAccess(&$data, &$user, $flag, $mask, $userGroupIds = array()) {
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

    if ($userGroupIds === null) {
      $userGroupIds = Set::extract('/Group/id', $user);
      $userGroupIds = am($userGroupIds, Set::extract('/Member/id', $user));
    }
    $mediaGroupIds = Set::extract('/Group/id', $data);
    // user groups and media groups must match to gain access via common group
    $match = array_intersect($mediaGroupIds, $userGroupIds);
    if ($user['User']['role'] >= ROLE_GUEST &&
      ($data['Media']['gacl'] & $mask) >= $flag &&
      count($match) > 0) {
      return true;
    }

    // Media owner and admin check
    if ($user['User']['id'] == $data['Media']['user_id'] ||
      $user['User']['role'] == ROLE_ADMIN) {
      return true;
    }

    return false;
  }

  /**
   * Set the access flags of write and read options according to the current user
   *
   * @param data Reference of the Media array
   * @param user User array
   * @return $data of Media data with the access flags
   */
  public function setAccessFlags(&$data, $user) {
    if (!isset($data)) {
      return $data;
    }

    // at least dummy user
    $user = am(array('User' => array('id' => -1, 'role' => ROLE_NOBODY), 'Member' => array()), $user);
    //Logger::debug($user);

    $oacl = $data['Media']['oacl'];
    $uacl = $data['Media']['uacl'];
    $gacl = $data['Media']['gacl'];

    $userGroupIds = Set::extract('/Group/id', $user);
    $userGroupIds = am($userGroupIds, Set::extract('/Member/id', $user));

    $data['Media']['canWriteTag'] = $this->checkAccess($data, $user, ACL_WRITE_TAG, ACL_WRITE_MASK, $userGroupIds);
    $data['Media']['canWriteMeta'] = $this->checkAccess($data, $user, ACL_WRITE_META, ACL_WRITE_MASK, $userGroupIds);
    $data['Media']['canWriteCaption'] = $this->checkAccess($data, $user, ACL_WRITE_CAPTION, ACL_WRITE_MASK, $userGroupIds);

    $data['Media']['canReadPreview'] = $this->checkAccess($data, $user, ACL_READ_PREVIEW, ACL_READ_MASK, $userGroupIds);
    $data['Media']['canReadHigh'] = $this->checkAccess($data, $user, ACL_READ_HIGH, ACL_READ_MASK, $userGroupIds);
    $data['Media']['canReadOriginal'] = $this->checkAccess($data, $user, ACL_READ_ORIGINAL, ACL_READ_MASK, $userGroupIds);
    if (($data['Media']['oacl'] & ACL_READ_PREVIEW) > 0) {
      $data['Media']['visibility'] = ACL_LEVEL_OTHER;
    } elseif (($data['Media']['uacl'] & ACL_READ_PREVIEW) > 0) {
      $data['Media']['visibility'] = ACL_LEVEL_USER;
    } elseif (($data['Media']['gacl'] & ACL_READ_PREVIEW) > 0) {
      $data['Media']['visibility'] = ACL_LEVEL_GROUP;
    } else {
      $data['Media']['visibility'] = ACL_LEVEL_PRIVATE;
    }

    $data['Media']['isOwner'] = ($data['Media']['user_id'] == $user['User']['id']) ? true : false;
    $data['Media']['canWriteAcl'] = $this->checkAccess($data, $user, 1, 0, $userGroupIds);
    $data['Media']['isDirty'] = (($data['Media']['flag'] & MEDIA_FLAG_DIRTY) > 0) ? true : false;

    return $data;
  }

  /**
   * Increase the ACL level. It checks the current flag and increases the ACL
   * level of lower ACL levels (first level is ACL_LEVEL_GROUP, second level is
   * ACL_LEVEL_USER and the third level is ACL_LEVEL_OTHER).
   *
   * @param data Array of image data
   * @param flag Threshold flag which indicates the upper inclusive bound
   * @param mask Bit mask of flag
   * @param level Highes ACL level which should be increased
   */
  public function _increaseAcl(&$data, $flag, $mask, $level) {
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

  /**
   * Decrease the ACL level. Decreases the ACL level of higher ACL levels
   * according to the current flag (first level is ACL_LEVEL_GROUP, second level
   * is ACL_LEVEL_USER and the third level is ACL_LEVEL_OTHER). The decreased ACL
   * value is the ACL value of the higher levels which is less than the current
   * threshold or it is zero if no lower ACL value is available.
   *
   * @param data Array of image data
   * @param flag Threshold flag which indicates the upper exlusive bound
   * @param mask Bit mask of flag
   * @param level Lower ACL level which should be downgraded
   */
  public function _decreaseAcl(&$data, $flag, $mask, $level) {
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

  public function setAcl(&$data, $flag, $mask, $level) {
    if ($level<ACL_LEVEL_KEEP || $level>ACL_LEVEL_OTHER)
      return false;

    if ($level==ACL_LEVEL_KEEP)
      return $data;

    if ($level>=ACL_LEVEL_GROUP)
      $this->_increaseAcl($data, $flag, $mask, $level);

    if ($level<ACL_LEVEL_OTHER)
      $this->_decreaseAcl($data, $flag, $mask, $level+1);

    return $data;
  }

  /**
   * Generates a has and belongs to many relation query for the image
   *
   * @param id Id of the image
   * @param model Model name
   * @return Array of the relation model
   */
  public function _optimizedHabtm($id, $model) {
    if (!isset($this->hasAndBelongsToMany[$model]['cacheQuery'])) {
      $db =& ConnectionManager::getDataSource($this->useDbConfig);

      $table = $db->fullTableName($this->{$model}->table, false, false);
      $alias = $this->{$model}->alias;
      $key = $this->{$model}->primaryKey;

      $joinTable = $this->hasAndBelongsToMany[$model]['joinTable'];
      $joinTable = $db->fullTableName($joinTable, false, false);
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
        $list[] = $item[$model];
      }
    }
    return $list;
  }

  /**
   * Generates a has one relation query for the image
   *
   * @param modelId Id of the related model
   * @param model Model name
   * @return Array of the relation model
   */
  public function _optimizedBelongsTo($modelId, $model) {
    if (!$modelId)
      return array();

    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    if (!isset($this->belongsTo[$model]['cacheQuery'])) {
      $table = $db->fullTableName($this->{$model}->table, false, false);
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

  /**
   * Generates a has one relation query for the image
   *
   * @param modelId Id of the related model
   * @param model Model name
   * @return Array of the relation model
   */
  public function _optimizedHasMany($modelId, $model) {
    if (!$modelId) {
      return array();
    }

    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    if (!isset($this->hasMany[$model]['cacheQuery'])) {
      $config = $this->hasMany[$model];
      $table = $db->fullTableName($this->{$model}->table, false, false);
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

  /**
   * The function Model::find slows down the hole search. This function builds
   * the query manually for speed optimazation
   *
   * @param id Media id
   * @return Return the image Array as find
   */
  public function optimizedRead($id) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);
    $myTable = $db->fullTableName($this->table, false, false);
    $sql = "SELECT Media.* FROM `$myTable` AS Media WHERE Media.id = $id";
    $result = $this->query($sql);
    if (!$result)
      return array();

    $image = $result[0];

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
   * Build join for ACL condition
   */
  public function buildAclJoin($alias) {
    $this->bindModel(array('hasMany' => array($alias => array('className' => 'GroupsMedia'))));
    $config = $this->hasMany[$alias];
    $foreignKey = $config['foreignKey'];
    $join = array(
        'table' => $this->GroupsMedia,
        'alias' => $alias,
        'type' => 'LEFT',
        'conditions' => array("`{$this->alias}`.`{$this->primaryKey}` = `$alias`.`{$foreignKey}`")
        );
    return $join;
  }

  /**
   * Build ACL query for media.
   *
   * @param user Current user
   * @param userId User id of own user or foreign user. If user id is equal with
   * the id of the current user, the user is treated as 'My Medias'. Otherwise
   * the default acl will apply
   * @param level Level of ACL which image must be have. Default is ACL_READ_PREVIEW.
   * @return returns ACL query
   */
  public function buildAclQuery($user, $userId = 0, $level = ACL_READ_PREVIEW) {
    $level = intval($level);
    $conditions = array();
    $joins = array();
    if ($userId > 0 && $user['User']['id'] == $userId) {
      // My Medias
      if ($user['User']['role'] >= ROLE_USER) {
        $conditions['Media.user_id'] = $userId;
      } elseif ($user['User']['role'] == ROLE_GUEST) {
        $groupIds = Set::extract('/Member/id', $user);
        if (count($groupIds)) {
          $conditions['AclGroups.group_id'] = $groupIds;
          $conditions['Media.gacl >='] = $level;
          $joins[] = $this->buildAclJoin('AclGroups');
        } else {
          // no images
          $conditions[] = "1 = 0";
        }
      }
    } else {
      // Another user, if set
      if ($userId > 0) {
        $conditions['Media.user_id'] = $userId;
      }

      // General ACL
      if ($user['User']['role'] < ROLE_ADMIN) {
        $acl = array();
        // All images of group on Guests and Users
        if ($user['User']['role'] >= ROLE_GUEST) {
          $groupIds = Set::extract('/Group/id', $user);
          $groupIds = am($groupIds, Set::extract('/Member/id', $user));
          if (count($groupIds)) {
            $acl['AND'] = array(
              'AclGroups.group_id' => $groupIds,
              'Media.gacl >=' => $level);
            $joins[] = $this->buildAclJoin('AclGroups');
          }
        }
        if ($user['User']['role'] >= ROLE_USER) {
          // Own image
          if ($userId == 0) {
            $acl['Media.user_id'] = $user['User']['id'];
          }
          // Other users
          $acl['Media.uacl >='] = $level;
        }
        // Public
        $acl['Media.oacl >='] = $level;
        if (count($acl) == 1) {
          $conditions = am($conditions, $acl);
        } else {
          $conditions['OR'] = $acl;
        }
      }
    }
    return array('joins' => $joins, 'conditions' => $conditions);
  }

  public function updateRanking($data) {
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

  /**
   * Find all media matching param $options
   *
   * @param array $user
   * @param array $options
   * @return array
   */
  public function findAllByOptions($user = false, $options = array()) {

    $options = am(array('model' => 'File', 'field' => 'path', 'conditions' => array()), $options);

    $table = $this->tablePrefix.$this->table;//'media'
    $alias = $this->alias;//'Media'
    $key = $this->primaryKey;//'id'

    $assoc = $options['model'];
    $joinedTable = $this->{$assoc}->tablePrefix .$this->{$assoc}->table;
    $joinedAlias = $this->{$assoc}->alias;
    $joinedKey = $this->{$assoc}->primaryKey;

    if (isset($this->hasMany[$assoc])) {
        $conditions = $options['conditions'];
        $config = $this->hasMany[$assoc];
        $joinedForeignKey = $config['foreignKey'];
        $joinedConditions = array("`{$joinedAlias}`.`{$joinedForeignKey}` = `{$alias}`.`{$key}`");
        $joins = array(array(
                'table' => $joinedTable,
                'alias' => $joinedAlias,
                'conditions' => $joinedConditions));
        if ($user) {
          $conditions[] = "`$alias`.`user_id` = " . $user['User']['id'];
        }

    } elseif (isset($this->hasAndBelongsToMany[$assoc])) {
        $aclQuery = $this->buildAclQuery($user);//?
        $conditions = am($options['conditions'], $aclQuery['conditions']);

        $config = $this->hasAndBelongsToMany[$assoc];

        $joinTable = $this->tablePrefix.$config['joinTable'];
        $joinAlias = $config['with'];
        $foreignKey = $config['foreignKey'];
        $associationForeignKey = $config['associationForeignKey'];

        $joins = am(array(
            "JOIN `$joinTable` AS `$joinAlias` ON `$alias`.`$key` = `$joinAlias`.`$foreignKey`",
            "JOIN `$joinedTable` AS `$joinedAlias` ON `$joinAlias`.`$associationForeignKey` = `$joinedAlias`.`$key`"
            ), $aclQuery['joins']);

    } else {
      Logger::error("Model {$this->alias} has no relation to $assoc. Return empty result");
      return array();
    }

    $query = array(
        //'fields' => $fields,
        'joins' => $joins,
        'conditions' => $conditions,
        'group' => "`$alias`.`$key`"//avoid returning 2 media for 1 media with 2 files in path,
        //'order' => "hits DESC",
        //'limit' => $options['count'],
        //'page' => 0
        );
    $result = $this->find('all', $query);

    return $result;
  }

  /**
   * Create tag cloud of HABTM model assoziation
   *
   * @param array $user Current User
   * @param array $options
   * - model - HABTM Model name. Default is 'Field'
   * - field - Model field for tag cloud data. Default is 'data'
   * - count - Count of top cloud items
   * - conditions - Array of conditions
   * @return array Map from name to hits
   */
  public function cloud($user, $options = array()) {
    $options = am(array('model' => 'Field', 'field' => 'data', 'count' => 50, 'conditions' => array()), $options);
    $assoc = $options['model'];
    if (!isset($this->hasAndBelongsToMany[$assoc])) {
      Logger::error("Model {$this->alias} has no HABTM relation to $assoc. Return emtyp result");
      return array();
    }
    $myTable = $this->tablePrefix.$this->table;

    $table = $this->{$assoc}->tablePrefix.$this->{$assoc}->table;
    $alias = $this->{$assoc}->alias;
    $field = $options['field'];
    $key = $this->{$assoc}->primaryKey;

    $config = $this->hasAndBelongsToMany[$assoc];

    $joinTable = $this->tablePrefix.$config['joinTable'];
    $joinAlias = $config['with'];
    $foreignKey = $config['foreignKey'];
    $associationForeignKey = $config['associationForeignKey'];

    $aclQuery = $this->buildAclQuery($user);
    $fields = array("`$alias`.`$field`", "COUNT(`$alias`.`$field`) AS hits");
    $joins = am(array(
        "JOIN `$joinTable` AS `$joinAlias` ON `$alias`.`$key` = `$joinAlias`.`$associationForeignKey`",
        "JOIN `$myTable` AS `{$this->alias}` ON `$joinAlias`.`$foreignKey` = `{$this->alias}`.`{$this->primaryKey}`"
        ), $aclQuery['joins']);
    $conditions = am($options['conditions'], $aclQuery['conditions']);
    $query = array(
        'fields' => $fields,
        'joins' => $joins,
        'conditions' => $conditions,
        'group' => "`$alias`.`$field`",
        'order' => "hits DESC",
        'limit' => $options['count'],
        'page' => 0
    );
    $data = $this->{$assoc}->find('all', $query);
    if (count($data)) {
      $data = Set::combine($data, "{n}.$assoc.$field", "{n}.0.hits");
    }
    return $data;
  }

  /**
   * Deletes all HABTM association from images of a given user like Tag, Categories
   *
   * @param userId User ID
   */
  public function _deleteHasAndBelongsToManyFromUser($userId) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    $table = $db->fullTableName($this->table, false, false);
    $alias = $this->alias;
    $key = $this->primaryKey;

    Logger::info("Delete HasAndBelongsToMany Media association of user '$userId'");
    foreach ($this->hasAndBelongsToMany as $model => $data) {
      $joinTable = $db->fullTableName($data['joinTable'], false, false);
      $joinAlias = $data['with'];
      $foreignKey = $data['foreignKey'];
      $sql = "DELETE FROM `$joinAlias`".
             " USING `$joinTable` AS `$joinAlias`, `$table` AS `$alias`".
             " WHERE `$alias`.`user_id` = $userId AND `$alias`.`$key` = `$joinAlias`.`$foreignKey`";
      Logger::debug("Delete $model HABTM associations");
      $this->query($sql);
    }
  }

  public function _deleteHasManyFromUser($userId) {
    $db =& ConnectionManager::getDataSource($this->useDbConfig);

    $table = $db->fullTableName($this->table, false, false);
    $alias = $this->alias;
    $key = $this->primaryKey;

    Logger::info("Delete HasMany Media assosciation of user '$userId'");
    foreach ($this->hasMany as $model => $data) {
      if (!isset($data['dependent']) || !$data['dependent']) {
        continue;
      }
      $manyTable = $db->fullTableName($this->{$model}->table, false, false);
      $foreignKey = $data['foreignKey'];
      $sql = "DELETE FROM `$model`".
             " USING `$manyTable` AS `$model`, `$table` AS `$alias`".
             " WHERE `$alias`.`user_id` = $userId AND `$alias`.`$key` = `$model`.`$foreignKey`";
      Logger::debug("Delete $model HasMany associations");
      $this->query($sql);
    }
  }

  public function deleteFromUser($userId) {
    $this->bindModel(array(
      'hasMany' => array(
        'Comment' => array('dependent' => true)
      )));
    $this->_deleteHasAndBelongsToManyFromUser($userId);
    $this->_deleteHasManyFromUser($userId);
    $this->deleteAll("Media.user_id = $userId");
  }

  /**
   * Count all media given by the group IDs
   * @param groupIds Single group ID value or array of group IDs
   * @return Count of media which are assigned to the given groups
   */
  public function countByGroupId($groupIds) {
    $config = $this->hasAndBelongsToMany['Group'];
    $table = $config['joinTable'];
    $alias = $config['with'];
    $foreignKey = $config['foreignKey'];
    $associationForeignKey = $config['associationForeignKey'];

    $result = $this->find('count', array(
        'conditions' => array("{$alias}.{$associationForeignKey}" => $groupIds),
        'joins' => array(array(
          'table' => $table,
          'alias' => $alias,
          'conditions' => array("`{$alias}`.`{$foreignKey}` = `{$this->alias}`.`{$this->primaryKey}`")
        ))));
    return $result;
  }

  /**
   * Returns the rotation of the media
   *
   * @return One of 0, 90, 180, 270 degree
   */
  public function getRotationInDegree($media) {
    $degree = 0;
    $data = $this->stripAlias($media);
    switch ($data['orientation']) {
      case 1: break;
      case 3: $degree = 180; break;
      case 6: $degree = 90; break;
      case 8: $degree = 270; break;
      default:
        Logger::warn("Unsupported rotation flag: {$data['orientation']} for {$this->toStringModel($data)}");
        break;
    }
    return $degree;
  }

  /**
   * Split the geo information to latitude and longitude
   *
   * @param data Model Data
   * @param geo Geo data string
   * @return Model data
   */
  public function splitGeo(&$data, $geo) {
    $numbers = preg_split('/\s*,\s*/', trim($geo));
    if (count($numbers) != 2) {
      Logger::debug("Invalid geo input: $geo");
      return;
    } elseif ($numbers[0] == "-") {
      $data['Media']['latitude'] = '-';
      $data['Media']['longitude'] = '-';
      return;
    }
    // validate numbers
    foreach ($numbers as $number) {
      if (!preg_match('/^[+-]?\d+(\.\d+)?$/', $number)) {
        Logger::debug("Invalid geo input number: $number");
        return;
      }
    }
    $data['Media']['latitude'] = $numbers[0];
    $data['Media']['longitude'] = $numbers[1];
  }

  public function rotate(&$data, $orientation, $rotation) {
    $rotateClockwise = array(
      1 => 6, 6 => 3, 3 => 8, 8 => 1,
      2 => 5, 5 => 4, 4 => 7, 7 => 2
      );
    $rotated = $orientation;
    switch ($rotation) {
      case 'reset': $rotated = 1; break;
      case '270': $rotated = $rotateClockwise[$rotated];
      case '180': $rotated = $rotateClockwise[$rotated];
      case '90': $rotated = $rotateClockwise[$rotated];
      default: break;
    }
    if ($rotated != $orientation) {
      $data['Media']['orientation'] = $rotated;
    }
  }

  /**
   * Check acl group of the user and set it as media group id
   *
   * @param data Data input
   * @param user Current user
   */
  public function prepareGroupData(&$data, &$user) {
    if (!isset($data['Group']['id'])) {
      return;
    }
    $groupId = $data['Group']['id'];
    $groupIds = Set::extract('/Group/id', $this->Group->getGroupsForMedia($user));
    $groupIds[] = -1; // no group
    if (in_array($groupId, $groupIds)) {
      $data['Media']['group_id'] = $groupId;
    } else {
      $data['Media']['group_id'] = 0;
    }
    return $data;
  }

  /**
   * Update ACL of media
   *
   * @param target Target model data
   * @param media Media model data
   * @param data Update data
   */
  public function updateAcl(&$target, &$media, &$data) {
    $fields = array('gacl', 'uacl', 'oacl');
    // copy acl fields to target
    foreach ($fields as $field) {
      $target['Media'][$field] = $media['Media'][$field];
    }
    // Higher grants first
    if (!empty($data['Media']['writeMeta'])) {
      $this->setAcl($target, ACL_WRITE_META, ACL_WRITE_MASK, $data['Media']['writeMeta']);
    }
    if (!empty($data['Media']['writeTag'])) {
      $this->setAcl($target, ACL_WRITE_TAG, ACL_WRITE_MASK, $data['Media']['writeTag']);
    }

    if (!empty($data['Media']['readOriginal'])) {
      $this->setAcl($target, ACL_READ_ORIGINAL, ACL_READ_MASK, $data['Media']['readOriginal']);
    }
    if (!empty($data['Media']['readPreview'])) {
      $this->setAcl($target, ACL_READ_PREVIEW, ACL_READ_MASK, $data['Media']['readPreview']);
    }

    // Remove unchanged values
    foreach ($fields as $field) {
      if ($target['Media'][$field] == $media['Media'][$field]) {
        unset($target['Media'][$field]);
      }
    }
  }

  /**
   * Prepare the input data for edit
   *
   * @param type $data User input data
   * @param type $user Current user
   * @return array Array of add and removals
   */
  public function prepareMultiEditData(&$data, &$user) {
    $tmp = array();
    if (!empty($data['Media']['geo'])) {
      $this->splitGeo($data, $data['Media']['geo']);
    }
    $mediaFields = array('name', 'description', 'date', 'latitude', 'longitude', 'rotation', 'readPreview', 'readOriginal', 'writeTag', 'writeMeta');
    foreach ($mediaFields as $name) {
      if (!empty($data['Media'][$name])) {
        $tmp['Media'][$name] = $data['Media'][$name];
      }
    }

    $group = $this->Group->prepareMultiEditData($data, $user);
    if ($group) {
      $tmp['Group'] = $group['Group'];
    }
    $fields = $this->Field->prepareMultiEditData($data);
    if ($fields) {
      $tmp['Field'] = $fields['Field'];
    }
    if (!count($tmp)) {
      return false;
    }
    return $tmp;
  }

  public function editMulti(&$media, &$data, &$user) {
    $tmp = array('Media' => array('id' => $media['Media']['id'], 'user_id' => $media['Media']['user_id']));
    if (!isset($media['Media']['canWriteMeta'])) {
      $this->setAccessFlags($media, $user);
    }

    $fields = false;
    if ($media['Media']['canWriteCaption']) {
      $fields = $this->Field->editMulti($media, $data);
    } else if ($media['Media']['canWriteMeta']) {
      $fields = $this->Field->editMulti($media, $data, array('keyword', 'category', 'sublocation', 'city', 'state', 'country'));
    } else if ($media['Media']['canWriteTag']) {
      $fields = $this->Field->editMulti($media, $data, array('keyword'));
    }
    if ($fields) {
      $tmp['Field'] = $fields['Field'];
    }
    $mediaFields = array();
    if ($media['Media']['canWriteMeta']) {
      $mediaFields = am($mediaFields, array('latitude', 'longitude'));
    }
    if ($media['Media']['canWriteCaption']) {
      $mediaFields = am($mediaFields, array('name', 'caption', 'date'));
      if (isset($data['Media']['rotation'])) {
        $this->rotate($tmp, $media['Media']['orientation'], $data['Media']['rotation']);
      }
    }
    foreach ($mediaFields as $name) {
      if (empty($data['Media'][$name])) {
        continue;
      } else if ($data['Media'][$name] == '-') {
        $tmp['Media'][$name] = null;
      } else {
        $tmp['Media'][$name] = $data['Media'][$name];
      }
    }
    if ($media['Media']['canWriteAcl']) {
      $groups = $this->Group->editMetaMulti($media, $data);
      if ($groups) {
        $tmp['Group'] = $groups['Group'];
      }
    }
    // only meta data above mark media a dirty for meta data synchronization
    if (count($tmp) != 1 || count($tmp['Media']) != 2) {
      $tmp['Media']['flag'] = ($media['Media']['flag'] | MEDIA_FLAG_DIRTY);
    }

    if ($media['Media']['canWriteAcl']) {
      $this->updateAcl($tmp, $media, $data);
    }
    if (count($tmp) == 1 && count($tmp['Media']) == 2) {
      return false;
    }
    return $tmp;
  }

  /**
   * Creates an new media data with updated values of given data
   *
   * @param array $media Media model data array
   * @param array $data Input data array
   * @param array $user Current user
   * @return array
   */
  public function editSingle(&$media, &$data, &$user) {
    $tmp = array('Media' => array('id' => $media['Media']['id'], 'user_id' => $media['Media']['user_id']));
    if (!isset($media['Media']['canWriteMeta'])) {
      $this->setAccessFlags($media, $user);
    }
    // handle fields
    if ($media['Media']['canWriteCaption']) {
      $tmp = am($this->Field->editSingle($media, $data), $tmp);
    } else if ($media['Media']['canWriteMeta']) {
      $tmp = am($this->Field->editSingle($media, $data, array('keyword', 'category', 'sublocation', 'city', 'state', 'country')), $tmp);
    } else if ($media['Media']['canWriteTag']) {
      $tmp = am($this->Field->editSingle($media, $data, array('keyword')), $tmp);
    }
    if ($media['Media']['canWriteMeta']) {
      if (!empty($data['Media']['geo'])) {
        $this->splitGeo($data, $data['Media']['geo']);
      }
      $fields = array('latitude', 'longitude', 'altitude');
      foreach ($fields as $field) {
        if (isset($data['Media'][$field]) && $media['Media'][$field] !== $data['Media'][$field]) {
          $tmp['Media'][$field] = $data['Media'][$field];
        }
      }
    }
    if ($media['Media']['canWriteCaption']) {
      $fields = array('name', 'caption', 'date', 'latitude', 'longitude');
      foreach ($fields as $field) {
        if (isset($data['Media'][$field]) && $media['Media'][$field] !== $data['Media'][$field]) {
          $tmp['Media'][$field] = $data['Media'][$field];
        }
      }
      if (isset($data['Media']['rotation'])) {
        $this->rotate($tmp, $media['Media']['orientation'], $data['Media']['rotation']);
      }
    }
    if ($media['Media']['canWriteAcl']) {
      $groups = $this->Group->editMetaSingle($media, $data, $user);
      if (isset($groups['Group'])) {
        $tmp['Group'] = $groups['Group'];
      }
    }
    // only meta data above mark media a dirty for meta data synchronization
    if (count($tmp) != 1 || count($tmp['Media']) != 2) {
      $tmp['Media']['flag'] = ($media['Media']['flag'] | MEDIA_FLAG_DIRTY);
    }
    if ($media['Media']['canWriteAcl']) {
      $this->updateAcl($tmp, $media, $data);
    }
    // Unchanged data
    if (count($tmp) == 1 && count($tmp['Media']) == 2) {
      return false;
    }
    return $tmp;
  }

  /**
   * Mark all media dirty which are assigned to given group
   *
   * @param array $group Group model data
   * @return mixed True on success
   */
  public function markDirtyByGroup(&$group) {
    $emptyUser = array();
    return $this->markDirtyByGroupAndUser($group, $emptyUser);
  }

  /**
   * Mark all media dirty of media which are assigned to given group and
   * belong to given user
   *
   * @param array $group Group model data
   * @param array $user User model data
   * @return mixed True on success
   */
  public function markDirtyByGroupAndUser(&$group, &$user) {
    $config = $this->hasAndBelongsToMany['Group'];

    $alias = $this->alias;
    $table = $this->tablePrefix . $this->table;
    $key = $this->primaryKey;

    $joinTable = $this->tablePrefix . $config['joinTable'];
    $joinAlias = 'MediaGroup';
    $joinForeignKey = $config['foreignKey'];
    $joinAssociationForeignKey = $config['associationForeignKey'];

    $conditions = array(
        "`$alias`.`$key` = `$joinAlias`.`$joinForeignKey`",
        "`$joinAlias`.`$joinAssociationForeignKey` = " . $group['Group']['id']
        );
    if ($user) {
      $conditions[] = "`$alias`.`user_id` = " . $user['User']['id'];
    }
    $sql = "UPDATE $table `$alias`, $joinTable `$joinAlias`"
         . " SET `Media`.`flag` = `Media`.`flag` | " . MEDIA_FLAG_DIRTY
         . " WHERE " . join(" AND ", $conditions);
    $result = $this->query($sql);
    return is_array($result) && !count($result);
  }

  /**
   * Delete a group from all media
   *
   * @param array $group
   * @return mixed True on success
   */
  public function deleteGroup(&$group) {
    $config = $this->hasAndBelongsToMany['Group'];

    $joinTable = $this->tablePrefix . $config['joinTable'];
    $joinAlias = 'MediaGroup';
    $joinAssociationForeignKey = $config['associationForeignKey'];

    $sql = "DELETE FROM $joinTable WHERE `$joinAssociationForeignKey` = " . $group['Group']['id'];
    $result = $this->query($sql);
    return is_array($result) && !count($result);
  }

  /**
   * Delete a group from all media of given user
   *
   * @param array $group Group model data
   * @param array $user User model data
   * @return mixed True on success
   */
  public function deleteGroupByUser(&$group, &$user) {
    $config = $this->hasAndBelongsToMany['Group'];

    $alias = $this->alias;
    $table = $this->tablePrefix . $this->table;
    $key = $this->primaryKey;

    $joinTable = $this->tablePrefix . $config['joinTable'];
    $joinAlias = 'MediaGroup';
    $joinForeignKey = $config['foreignKey'];
    $joinAssociationForeignKey = $config['associationForeignKey'];

    $subQuery = "SELECT `$alias`.`$key` FROM $table `$alias` WHERE `$alias`.`user_id` = " . $user['User']['id'];
    $conditions = array(
        "`$joinAssociationForeignKey` = " . $group['Group']['id'],
        "`$joinForeignKey` IN ($subQuery)"
        );
    $sql = "DELETE FROM $joinTable WHERE " . join(" AND ", $conditions);
    $result = $this->query($sql);
    return is_array($result) && !count($result);
  }
}