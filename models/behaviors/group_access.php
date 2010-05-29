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

class GroupAccessBehavior extends ModelBehavior 
{
  var $config = array();

  function setup(&$Model, $config = array()) {
    $this->config[$Model->name] = (array)$config;
    $this->config[$Model->name]['user'] = $Model->User->getNobody();
  }

  /** Set the current user for access controll 
   * @param model Current model object
   * @param user Current user data */
  function setUser(&$Model, &$user) {
    $this->config[$Model->name]['user'] = $user;
  }

  function setMediaAccess(&$Model, &$media, $user = false) {
    if (!$media) {
      return false;
    }

    if (!$user) {
      $user = $this->config[$Model->name]['user'];
    }

    if ($user['User']['id'] == $media['User']['id']) {
      $media['Media']['isOwner'] = true;
    } else {
      $media['Media']['isOwner'] = false;
    }

    $userGroupIds = Set::extract('/Group/id', $user);
 
    $access = GROUP_ACCESS_PRIVATE;
    $tagging = GROUP_TAGGING_READONLY;
    $view = GROUP_MEDIAVIEW_VIEW;

    foreach ($media['Group'] as $group) {
      $access = max($access, $group['access']);
      if (!in_array($group['id'], $userGroupIds)) {
        $continue;
      }
      $tagging = max($tagging, $group['tagging']);
      $view = max($view, $group['media_view']);
    }
    
    $media['Media']['access'] = $access;
    $media['Media']['tagging'] = $tagging;
    $media['Media']['media_view'] = $view;

    if ($user['User']['id'] == $media['User']['id'] || $user['User']['role'] == ROLE_ADMIN) {
      $media['Media']['tagging'] = GROUP_TAGGING_FULL;
      $media['Media']['media_view'] = GROUP_MEDIAVIEW_FULL;
    }

    if ($media['Media']['flag'] & MEDIA_FLAG_DIRTY) {
      $media['Media']['isDirty'] = true;
    } else {
      $media['Media']['isDirty'] = false;
    }
    return $media;
  }

  /** Finds all valid groups for a media of the current user
   * @param model Current model object
   * @param user Optional user. If not set the default user is used
   * @return Array of groups for media */
  function findMediaGroup(&$Model, $user = false) {
    if (!$user) {
      $user = $this->config[$Model->name]['user'];
    }
    $groups = $Model->Group->find('all', array('conditions' => array('Group.user_id' => $user['User']['id'])));
    return $groups;
  }

  /** Finds all groups for access the current user
   * @param model Current model object
   * @param user Optional user. If not set the default user is used
   * @return Array of groups for media */
  function findAccessGroup(&$Model, $user = false) {
    if (!$user) {
      $user = $this->config[$Model->name]['user'];
    }
    switch ($user['User']['role']) {
      case ROLE_NOBODY: 
      case ROLE_GUEST: 
        $access = GROUP_ACCESS_ANONYMOUS; 
        break;
      case ROLE_USER: 
      case ROLE_SYSOP: 
      case ROLE_ADMIN: 
        $access = GROUP_ACCESS_REGISTERED; 
        break;
      default:
        Logger::err("Unkown user role: {$user['User']['role']}");
        $access = GROUP_ACCESS_ANONYMOUS; 
        break;
    }

    $conditions = array('Group.access >=' => $access);
    if ($user['User']['id'] > 0) {
      $conditions['Group.user_id'] = $user['User']['id'];
    }

    $memberIds = Set::extract('/Member/id', $user);
    if (count($memberIds)) {
      $conditions[] = 'Group.id IN ( ' . implode(', ', $memberIds) . ' )';
    }

    $groups = $Model->Group->find('all', array(
      'conditions' => array('OR' => $conditions),
      'recursive' => -1
      ));
    //Logger::debug($groups);
    return $groups;
  }

  /** Builds the sql access join 
   * @param Model Current model object
   * @param query Current query (as reference) */
  function _buildAccessJoin(&$Model, &$query) {
    $modelAlias = $Model->alias;
    $table = $Model->tablePrefix . 'groups_media';
    $alias = 'GroupsMedia';
    $foreignKey = 'media_id';

    $groups = $this->findAccessGroup(&$Model);
    $groupIds = Set::extract('/Group/id', $groups);
    if (!count($groupIds)) {
      $groupIds = array(-1);
    }

    $query['joins'][] = "JOIN $table AS $alias ON {$Model->alias}.id = $alias.$foreignKey";
    $query['conditions'] = (array)$query['conditions'];
    $query['conditions'][] = "$alias.group_id IN ( " . join(', ', $groupIds) . " )";
    $query['group'][] = "Media.id";
  }

  function beforeFind(&$Model, $query)  {
    $this->_buildAccessJoin(&$Model, &$query);
    return $query;
  }
}

?>