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

class Group extends AppModel
{
  var $name = 'Group';

  var $belongsTo = array('User' => array());

  var $hasAndBelongsToMany = array('Member' => array('className' => 'User'));

  /** Creates a special system group for the user for the media access management
   * @param user User model data */
  function createSystemGroup($user) {
    if ($user['User']['system_group_id']) {
      return true;
    }

    $group = $this->create(array(
      'type' => GROUP_TYPE_SYSTEM, 
      'user_id' => $user['User']['id'], 
      'name' => '_' . $user['User']['username'],
      'description' => 'INTERNAL USER SYSTEM GROUP',
      'access' => GROUP_ACCESS_MEMBER,
      'media_view' => GROUP_MEDIAVIEW_FULL,
      'tagging' => GROUP_TAGGING_FULL
      ));
    if (!$this->save($group)) {
      Logger::err("Could not create system user group of user {$user['User']['id']}");
      return false;
    }
    $groupId = $this->getLastInsertID();

    $this->User->id = $user['User']['id'];
    $this->User->saveField('system_group_id', $groupId);
    Logger::info("Created system user group for user {$user['User']['id']}: $groupId");

    return true;
  }

  function findAccessGroups($user) {
    if ($user['User']['role'] == ROLE_NOBODY) {
      $groups = $this->find('all', array('conditions' => array('Group.access >=' => GROUP_ACCESS_ANONYMOUS)));
    } elseif ($user['User']['role'] == ROLE_GUEST) {
      $groups = $this->find('all', array('conditions' => array('OR' => array('Group.access >=' => GROUP_ACCESS_ANONYMOUS, 'Member.user_id' => $user['User']['id']))));
    } elseif ($user['User']['role'] <= ROLE_SYSOP) {
      $groups = $this->find('all', array('conditions' => array('OR' => array('Group.access >=' => GROUP_ACCESS_REGISTERED, 'Member.user_id' => $user['User']['id']))));
    }
    return $groups;
  }
}
?>