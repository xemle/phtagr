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

class Group extends AppModel {

  var $name = 'Group';

  var $belongsTo = array('User' => array());

  var $hasAndBelongsToMany = array('Member' => array('className' => 'User'));

  var $actsAs = array('WordList');

  public function isNameUnique($group) {
    $conditions = array('name' => $group['Group']['name']);
    if (isset($group['Group']['id'])) {
      $conditions['id !='] = $group['Group']['id'];
    }

    return !$this->hasAny($conditions);
  }

  /**
   * Prepare multi edit data for groups
   *
   * @param type $data User input data
   * @param type $user Current user
   * @return type
   */
  public function prepareMultiEditData(&$data, &$user) {
    if (empty($data['Group']['names'])) {
      return false;
    }
    $names = $data['Group']['names'];
    $words = $this->splitWords($names);
    if (!count($words)) {
      return false;
    }
    $addWords = $this->removeNegatedWords($words);
    $deleteWords = $this->getNegatedWords($words);

    $addGroups = $this->findAllByField($addWords, false);
    $deleteGroups = $this->findAllByField($deleteWords, false);

    // Remove invalid group additions for non admins
    if ($user['User']['role'] < ROLE_ADMIN) {
      $validGroupIds = Set::extract('/Group/id', $this->getGroupsForMedia($user));
      foreach ($addGroups as $i => $group) {
        if (!in_array($group['Group']['id'], $validGroupIds)) {
          unset($addGroups[$i]);
        }
      }
    }

    if (count($addGroups) || count($deleteGroups)) {
      return array('Group' => array('addGroup' => Set::extract("/Group/id", $addGroups), 'deleteGroup' => Set::extract("/Group/id", $deleteGroups)));
    } else {
      return false;
    }
  }

  /**
   * Add and delete groups according to the given data
   *
   * @param type $media
   * @param type $data
   * @return type
   */
  public function editMetaMulti(&$media, &$data) {
    if (empty($data['Group'])) {
      return false;
    }
    $oldIds = Set::extract('/Group/id', $media);
    $ids = am($oldIds, $data['Group']['addGroup']);
    $ids = array_unique(array_diff($ids, $data['Group']['deleteGroup']));
    if (array_diff($ids, $oldIds) || array_diff($oldIds, $ids)) {
      return array('Group' => array('Group' => $ids));
    } else {
      return false;
    }
  }

  public function editMetaSingle(&$media, &$data, &$user) {
    if (!isset($data['Group']['names'])) {
      return false;
    }
    $words = $this->splitWords($data['Group']['names'], false);
    $words = $this->removeNegatedWords($words);
    $groups = $this->findAllByField($words, false);
    $ids = Set::extract('/Group/id', $groups);
    if ($user['User']['role'] < ROLE_ADMIN) {
      $validGroupIds = Set::extract('/Group/id', $this->getGroupsForMedia($user));
      $ids = array_intersect($ids, $validGroupIds);
    }
    $oldIds = Set::extract("/Group/id", $media);
    if (count(array_diff($oldIds, $ids)) || count(array_diff($ids, $oldIds))) {
      return array('Group' => array('Group' => $ids));
    } else {
      return false;
    }
  }

  /**
   * Return all groups which could be assigned to the media
   *
   * @param user Current user model data
   * @return Array of group model data
   */
  public function getGroupsForMedia($user) {
    if ($user['User']['role'] >= ROLE_ADMIN) {
      return $this->find('all', array('recursive' => -1));
    }
    $groups = array();
    foreach($user['Group'] as $group) {
      $groups[] = array('Group' => $group);
    }
    $groupIds = Set::extract('/Group/id', $groups);
    foreach($user['Member'] as $group) {
      if (!in_array($group['id'], $groupIds) && $group['is_shared']) {
        $groups[] = array('Group' => $group);
      }
    }
    return $groups;
  }

  public function canSubscribe(&$group, &$user) {
    return $user['User']['role'] >= ROLE_ADMIN || $group['Group']['is_shared'] && !$group['Group']['is_moderated'];
  }

  /**
   * Subscribe a user to a group
   *
   * @param groupId Group ID
   * @param userId User ID
   * @return Return code
   */
  public function subscribe($group, $userId) {
    if (!$group) {
      return $this->returnCode(404, sprintf(__("%s not found", true), __("Group", true)));
    } elseif (!$userId) {
      return $this->returnCode(404, sprintf(__("%s not found", true), __("User", true)));
    }
    $memberIds = Set::extract("/Member/id", $group);
    if (!in_array($userId, $memberIds)) {
      $memberIds[] = $userId;
      if (!$this->saveHabtm($group['Group']['id'], 'Member', $memberIds)) {
        return $this->returnCode(505, sprintf(__("Could not save %s", true), __('Group', true)));
      } else {
        return $this->returnCode(201, sprintf(__("You are now subscribed to group %s", true), $group['Group']['name']));
      }
    } else {
      return $this->returnCode(200, sprintf(__("You are allready subscribed to group %s", true), $group['Group']['name']));
    }
  }

  /**
   * Unsubscribe a user of a group
   *
   * @param groupName Group name
   * @param userId User ID
   * @return Return code
   */
  public function unsubscribe($group, $userId) {
    if (!$group) {
      return $this->returnCode(404, sprintf(__("%s not found", true), __("Group", true)));
    }
    $memberIds = Set::extract("/Member/id", $group);
    if (in_array($userId, $memberIds)) {
      unset($memberIds[array_search($userId, $memberIds)]);
      if (!$this->saveHabtm($group['Group']['id'], 'Member', $memberIds)) {
        return $this->returnCode(505, sprintf(__("Could not save %s", true), __('Group', true)));
      } else {
        return $this->returnCode(201, sprintf(__("You are now unsubscribed of group %s", true), $group['Group']['name']));
      }
    } else {
      return $this->returnCode(200, sprintf(__("You are not subscribed to group %s", true), $group['Group']['name']));
    }
  }

  /**
   * Evaluates if the group is writeable
   */
  public function isAdmin(&$group, &$user) {
    if ($user['User']['role'] >= ROLE_ADMIN || $user['User']['id'] == $group['Group']['user_id']) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Set the writeable flag of the group
   *
   * @param group Group model data (as reference)
   * @param user Current user
   * @return Group model data
   */
  public function setAdmin(&$group, &$user) {
    $group['Group']['is_admin'] = $this->isAdmin($group, $user);
    return $group;
  }
}
?>
