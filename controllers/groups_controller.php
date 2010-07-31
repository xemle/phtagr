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
class GroupsController extends AppController {
  var $name = 'Groups';
  var $uses = array('Group', 'User');
  var $components = array('RequestHandler');
  var $helpers = array('Form', 'Ajax');
  var $menuItems = array();

  function beforeFilter() {
    parent::beforeFilter();
    $this->requireRole(ROLE_USER);
  }

  function beforeRender() {
    $this->_setMenu();
    parent::beforeRender();
  }

  function index() {
    $userId = $this->getUserId();
    $this->data = $this->Group->find('all', array('conditions' => (array('User.id' => $userId))));
  }

  function autocomplete() {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      $this->redirect(null, '404');
    }
    $userId = $this->getUserId();
    uses('sanitize');
    $sanitize = new Sanitize();
    $escUsername = $sanitize->escape($this->data['User']['username']);
    $guests = $this->User->find('all', array('conditions' => "User.creator_id=$userId AND User.username LIKE '%$escUsername%'"));
    $this->data = $guests;
    $this->layout = "bare";
  }

  function add() {
    if (!empty($this->data)) {
      $userId = $this->getUserId();
      $this->data['Group']['user_id'] = $userId;
      if ($this->Group->hasAny(array('name' => $this->data['Group']['name'], 'user_id' => $userId))) {
        $this->Session->setFlash(sprintf(__("Group '%s' already exists", true), $this->data['Group']['name']));
      } elseif ($this->Group->save($this->data)) {
        $groupId = $this->Group->getLastInsertID();
        $group = $this->Group->findById($groupId);
        $user = $this->getUser();
        Logger::info("User '{$user['User']['username']}' ({$user['User']['id']}) created a group '{$group['Group']['name']}' ({$group['Group']['id']})");
        $this->Session->setFlash(sprintf(__("Add successfully group '%s'", true), $this->data['Group']['name']));
        $this->redirect("edit/$groupId");
      } else {
        $this->Session->setFlash(sprintf(__("Could not add group '%s'!", true), $this->data['Group']['name']));
      }
    }
  }

  function edit($groupId) {
    $userId = $this->getUserId();
    $group = $this->Group->find(array('Group.id' => $groupId, 'Group.user_id' => $userId));
    if (!$group) {
      $this->Session->setFlash(__("Could not find group", true));
      $this->redirect("index");
    }
    $this->data = $group;

    $this->menuItems[] = array(
      'text' => sprintf(__('Group: %s', true), $this->data['Group']['name']), 
      'type' => 'text', 
      'submenu' => array(
        'items' => array(
          array(
            'text' => __('Edit', true), 
            'link' => 'edit/'.$groupId
            )
          )
        )
      );
  }

  /**
    @todo Reset all group information of image 
    @todo Check for permission! */
  function delete($groupId) {
    $userId = $this->getUserId();
    $group = $this->Group->find(array('Group.id' => $groupId, 'Group.user_id' => $userId));
    if ($group) {
      $this->Group->delete($groupId);
      $user = $this->getUser();
      Logger::info("User '{$user['User']['username']}' ({$user['User']['id']}) deleted group '{$group['Group']['name']}' ({$group['Group']['id']})");
      $this->Session->setFlash(sprintf(__("Successfully deleted group '%s'", true), $group['Group']['name']));
    } else {
      $this->Session->setFlash(__("Could not find group", true));
    }
    $this->redirect("index");
  }

  function addMember($groupId) {
    if (!empty($this->data)) {
      $userId = $this->getUserId();
      $group = $this->Group->find(array('Group.id' => $groupId, 'Group.user_id' => $userId));
      // TODO Allow only users and own guests? Currently allow all guests and users
      $user = $this->User->findByUsername($this->data['User']['username']);

      if (!$group) {
        $this->Session->setFlash(__("Could not find group"), true);
        $this->redirect("index");
      } elseif (!$user) {
        $this->Session->setFlash(sprintf(__("Could not find user with username '%s'", true), $this->data['User']['username']));
        $this->redirect("edit/$groupId");
      } else {
        $list = Set::extract($group, "Member.{n}.id");
        $list[] = $user['User']['id'];
        $group['Member']['Member'] = array_unique($list);
        if ($this->Group->save($group)) {
          Logger::info("Add user '{$user['User']['username']}' ({$user['User']['id']}) to group '{$group['Group']['name']}' ({$group['Group']['id']})");
          $this->Session->setFlash(sprintf(__("Add user '%s' to group '%s'", true), $user['User']['username'], $group['Group']['name']));
        } else {
          $this->Session->setFlash(sprintf(__("Could not add user '%s' to group '%s'!", true), $this->data['User']['username'], $this->data['Group']['name']));
        }
        $this->redirect("edit/$groupId");
      }
    }
  }

  function deleteMember($groupId, $memberId) {
    $userId = $this->getUserId();
    $group = $this->Group->find(array('Group.id' => $groupId, 'Group.user_id' => $userId));
    if (!$group) {
      $this->Session->setFlash(__("Could not find group", true));
      $this->redirect("index");
    } else {
      $list = Set::extract($group, "Member.{n}.id");
      $key = array_search($memberId, $list);
      if ($key === false) {
        $this->Session->setFlash(__("Could not find group", true));
      } else {
        unset($list[$key]);
        $group['Member']['Member'] = array_unique($list);
        if (!$this->Group->save($group)) {
          $this->Session->setFlash(__("Could not save group", true));
        } else {
          $user = $this->getUser();
          Logger::info("Delete user '{$user['User']['username']}' ({$user['User']['id']}) from group '{$group['Group']['name']}' ({$group['Group']['id']})");
          $this->Session->setFlash(sprintf(__("Member was successfully deleted from group '%s'", true), $group['Group']['name']));
        }
      }
      $this->redirect("edit/$groupId");
    }
  }

  function _getMenuItems() {
    $items = array();
    $items[] = array('text' => __('List groups', true), 'link' => 'index');
    $items[] = array('text' => __('Add group', true), 'link' => 'add');
    $items = am($items, $this->menuItems);
    return $items;
  }

  function _setMenu() {
    $items = $this->requestAction('/options/getMenuItems');
    $me = '/'.strtolower(Inflector::pluralize($this->name));
    foreach ($items as $index => $item) {
      if ($item['link'] == $me) {
        $item['submenu'] = array('items' => $this->_getMenuItems());
        $items[$index] = $item;
      }
    }
    $menu = array('items' => $items);
    $this->set('mainMenu', $menu);
  }
}
?>
