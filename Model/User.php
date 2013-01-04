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

class User extends AppModel
{
  var $name = 'User';

  var $actsAs = array('Cipher' => array());

  var $hasMany = array(
                  'Group' => array('dependent' => true),
                  'Option' => array('dependent' => true, 'dependent' => true),
                  'Guest' => array('foreignKey' => 'creator_id', 'dependent' => true)
                  );

  var $hasAndBelongsToMany = array(
                  'Member' => array(
                      'className' => 'Group',
                      'dependent' => true
                    )
                  );

  var $validate = array(
    'username' => array(
      'rule' => array('between', 3, 32),
      'message' => 'Username must be between 3 and 32 characters long.'),
    'password' => array(
      'rule' => array('between', 6, 32),
      'message' => 'Password must be between 6 and 32 characters long.'),
    'role' => array(
      'rule' => array('between', ROLE_GUEST, ROLE_ADMIN),
      'message' => 'Invalid role'),
    'email' => array(
      'rule' => array('email'),
      'message' => 'Email address is not valid'),
    'notify_interval' => array(
      'rule' => array('inList', array('0', '1800', '3600', '86400', '604800', '2592000')),
      'message' => 'Invalid notification interval')
    );

  public function afterFind($result, $primary = false) {
    if ($primary && isset($result[0]['Option'])) {
      $result[0]['Option'] = $this->Option->addDefaults($result[0]['Option']);
    }
    return $result;
  }

  public function __fromReadableSize($readable) {
    if (is_float($readable) || is_numeric($readable)) {
      return $readable;
    } elseif (preg_match_all('/^\s*(0|[1-9][0-9]*)(\.[0-9]+)?\s*([KkMmGg][Bb]?)?\s*$/', $readable, $matches, PREG_SET_ORDER)) {
      $matches = $matches[0];
      $size = (float)$matches[1];
      if (is_numeric($matches[2])) {
        $size += $matches[2];
      }
      if (is_string($matches[3])) {
        switch ($matches[3][0]) {
          case 'k':
          case 'K':
            $size = $size * 1024;
            break;
          case 'm':
          case 'M':
            $size = $size * 1024 * 1024;
            break;
          case 'g':
          case 'G':
            $size = $size * 1024 * 1024 * 1024;
            break;
          default:
            Logger::err("Unknown unit {$matches[3]}");
        }
      }
      if ($size < 0) {
        Logger::err("Size is negtive: $size");
        return 0;
      }
      return $size;
    } else {
      return 0;
    }
  }

  public function beforeValidate($options = array()) {
    if (isset($this->data['User']['password']) &&
      isset($this->data['User']['confirm'])) {
      if (empty($this->data['User']['password']) &&
        empty($this->data['User']['confirm'])) {
        // both are empty - clear it
        unset($this->data['User']['confirm']);
        unset($this->data['User']['password']);
      } elseif (empty($this->data['User']['password'])) {
        $this->invalidate('password', __('Password not given'));
      } elseif (empty($this->data['User']['confirm'])) {
        $this->invalidate('confirm', __('Password confirmation is missing'));
      } elseif ($this->data['User']['password'] != $this->data['User']['confirm']) {
        $this->invalidate('password', __('Password confirmation mismatch'));
        $this->invalidate('confirm', __('Password confirmation mismatch'));
      }
    }
    $id = false;
    if ($this->id) {
      $id = $this->id;
    } elseif (isset($this->data['User']['id'])) {
      $id = $this->data['User']['id'];
    }
    if (isset($this->data['User']['username']) && $id) {
      $other = $this->find('first', array('conditions' => array('User.username' => $this->data['User']['username']), 'recursive' => -1));
      if ($other && $other['User']['id'] != $id) {
        $this->invalidate('username', __('Username already taken'));
      }
    }
    return true;
  }

  public function beforeSave($options = array()) {
    if (isset($this->data['User']['quota'])) {
      $this->data['User']['quota'] = $this->__fromReadableSize($this->data['User']['quota']);
    }

    if (empty($this->data['User']['expires'])) {
      $this->data['User']['expires'] = null;
    }

    return true;
  }

  public function beforeDelete($cascade = true) {
    $id = $this->id;
    $this->bindModel(array('hasMany' => array('Media')));
    Logger::info("Delete all image database entries of user $id");
    $this->Media->deleteFromUser($id);

    $this->bindModel(array('hasMany' => array('MyFile')));
    $this->MyFile->deleteAll("File.user_id = $id");

    $dir = USER_DIR.$id;
    Logger::info("Delete user directory of user $id: $dir");
    $folder = new Folder();
    $folder->delete($dir);

    return true;
  }

  public function writeSession(&$user, &$session) {
    if (!$session || !isset($user['User']['id']) || !isset($user['User']['role']) || !isset($user['User']['username'])) {
      return;
    }
    $session->write('User.id', $user['User']['id']);
    $session->write('User.role', $user['User']['role']);
    $session->write('User.username', $user['User']['username']);
  }

  public function hasAnyWithRole($role = ROLE_ADMIN) {
    $role = min(max(intval($role), ROLE_NOBODY), ROLE_ADMIN);
    return $this->hasAny("role >= $role");
  }

  public function getNobody() {
    $nobody = array(
        'User' => array(
            'id' => -1,
            'username' => '',
            'role' => ROLE_NOBODY),
        'Member' => array(),
        'Group' => array(),
        'Option' => $this->Option->addDefaults(array()));
    return $nobody;
  }

  public function isExpired($data) {
    if (!isset($data['User']['expires']))
      return false;
    $now = time();
    $expires = strtotime($data['User']['expires']);
    if ($expires < $now)
      return true;
    return false;
  }

  /**
   * Generate a random key
   *
   * @param data User model data as reference
   * @param length Key length. Default is 10. Must be beween 3 and 128.
   * @param alphabet Key alphabet as string. Default is [a-zA-Z0-9]. Must be at least 10 characters long.
   * @return User model data
   */
  public function generateKey(&$data, $length = 10, $alphabet = false) {
    srand(microtime(true)*1000);

    if (!$alphabet || strlen(strval($alphabet)) < 10) {
      $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $alphabet .= 'abcdefghijklmnopqrstuvwxyz';
      $alphabet .= '0123456789';
    }
    $length = min(128, max(3, intval($length)));
    $count = strlen($alphabet);

    $key = '';
    for ($i = 0; $i < $length; $i++) {
      $key .= substr($alphabet, rand(0, $count - 1), 1);
    }
    $data['User']['key'] = $key;
    return $data;
  }

  public function getRootDir($data, $create = true) {
    if (!isset($data['User']['id'])) {
      Logger::err("Data does not contain user's id");
      return false;
    }

    $rootDir = USER_DIR.$data['User']['id'].DS.'files'.DS;
    if ($create) {
      $folder = new Folder();
      if (!$folder->create($rootDir)) {
        Logger::err("Could not create users root directory '$fileDir'");
        return false;
      }
    }
    return $rootDir;
  }

  public function allowWebdav($user) {
    if (isset($user['User']['quota']) && $user['User']['quota'] > 0) {
      return true;
    }
    return false;
  }

  public function canUpload($user, $size) {
    $this->bindModel(array('hasMany' => array('MyFile')));
    $userId = intval($user['User']['id']);
    if ($userId < 1) {
      return false;
    }

    $current = $this->MyFile->countBytes($userId, false);
    $quota = $user['User']['quota'];
    if ($current + $size <= $quota) {
      return true;
    }

    return false;
  }

  /**
   * Selects visible users for users profile
   *
   * @param user Current user
   * @param username Username to select.
   * @param boolean like If true search for similar
   * @return Array of users model data. If username is set only one user model data
   */
  public function findVisibleUsers($user, $username = false, $like = false) {
    $conditions = array();
    $findType = 'all';
    $resusive = -1;
    if ($username) {
      if ($like) {
        $conditions['User.username like'] = Sanitize::escape($username) . '%';
      } else {
        $conditions['User.username'] = $username;
        $findType = 'first';
      }
      $recursive = 2;
    }
    $joins = array();
    if ($user['User']['role'] < ROLE_ADMIN) {
      if ($user['User']['role'] < ROLE_GUEST) {
        $conditions['User.visible_level'] = PROFILE_LEVEL_PUBLIC;
      } else {
        $groupIds = Set::extract('/Member/id', $user);
        $groupIds = am($groupIds, Set::extract('/Group/id', $user));
        $conditions['OR'] = array('User.visible_level >=' => PROFILE_LEVEL_USER);
        if (count($groupIds)) {
          $conditions['OR']['AND'] = array(
            'User.visible_level' => PROFILE_LEVEL_GROUP,
            'MemberUser.group_id' => $groupIds
          );
        }
        $prefix = $this->tablePrefix;
        $joins[] = "LEFT JOIN `{$prefix}groups_users` AS `MemberUser` ON `MemberUser`.`user_id` = `User`.`id`";
      }
    }
    return $this->find($findType, array('conditions' => $conditions, 'joins' => $joins, 'recusive' => $resusive, 'group' => 'User.id'));
  }
}
?>
