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

class Group extends AppModel
{
  var $name = 'Group';

  var $belongsTo = array('User' => array());

  var $hasAndBelongsToMany = array('Member' => array('className' => 'User'));

  function isNameUnique($group) {
    $conditions = array('name' => $group['Group']['name']);
    if (isset($group['Group']['id'])) {
      $conditions['id !='] = $group['Group']['id'];
    }
     
    return !$this->hasAny($conditions);
  }
  
  /** Return all groups which could be assigned to the media 
    @param user Current user model data
    @return Array of group model data */
  function getGroupsForMedia($user) {
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

  /** Subscribe a user to a group
    @param groupId Group ID
    @param userId User ID
    @return Return code */
  function subscribe($group, $userId) {
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

  /** Unsubscribe a user of a group
    @param groupName Group name
    @param userId User ID
    @return Return code */
  function unsubscribe($group, $userId) {
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

  /** Evaluates if the group is writeable */
  function isAdmin(&$group, &$user) {
    if ($user['User']['role'] >= ROLE_ADMIN || $user['User']['id'] == $group['Group']['user_id']) {
      return true;
    } else {
      return false;
    }
  }

  /** Set the writeable flag of the group
    @param group Group model data (as reference)
    @param user Current user
    @return Group model data */
  function setAdmin(&$group, &$user) {
    $group['Group']['is_admin'] = $this->isAdmin(&$group, &$user);
    return $group;
  }
}
?>
