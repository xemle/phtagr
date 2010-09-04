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
  var $uses = array('Group', 'User', 'Media');
  var $components = array('RequestHandler', 'Security', 'Email', 'Search');
  var $helpers = array('Form', 'Ajax', 'ImageData');
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
    if ($this->hasRole(ROLE_ADMIN)) {
      $this->data = $this->Group->find('all');
    } else {
      $this->data = $this->Group->find('all', array('conditions' => (array('OR' => array('User.id' => $userId, 'Group.is_hidden' => false)))));
    }
  }

  function view($name) {
    $this->data = $this->Group->findByName($name);
    if (!$this->data) {
      $this->Session->setFlash(sprintf(__("%s not found", true), __("Group", true)));
      $this->redirect('index');
    }

    $this->Search->addGroup($name);
    $this->Search->setShow(6);
    $this->set('media', $this->Search->paginate());   
  }

  function create() {
    if (!empty($this->data)) {
      $user = $this->getUser();
      $this->data['Group']['user_id'] = $user['User']['id'];
      if (!$this->Group->isNameUnique($this->data)) {
        $this->Session->setFlash(sprintf(__("%s already exists", true), __('Group', true)));
      } elseif ($this->Group->save($this->data)) {
        $groupId = $this->Group->getLastInsertID();
        $group = $this->Group->findById($groupId);
        $user = $this->getUser();
        Logger::info("User '{$user['User']['username']}' ({$user['User']['id']}) created group '{$group['Group']['name']}' ({$group['Group']['id']})");
        $this->Session->setFlash(sprintf(__("Add successfully group '%s'", true), $this->data['Group']['name']));
        $this->redirect("view/{$group['Group']['name']}");
      } else {
        $this->Session->setFlash(sprintf(__("Could not create group '%s'", true), $this->data['Group']['name']));
      }
    }
  }

  function _sendSubscribtionRequest($group) {
    $this->Email->to = sprintf("%s <%s>", $group['User']['username'], $group['User']['email']);
    $user = $this->getUser();

    $this->Email->subject = "Group {$group['Group']['name']}: Subscription request for user {$user['User']['username']}";

    $this->Email->template = 'group_subscribtion_request';
    $this->set('group', $group);
    $this->set('user', $user);

    if (!$this->Email->send()) {
      Logger::err(sprintf("Could not send group subscription request to {$group['User']['username']} <{$group['User']['email']}>"));
      if ($this->Email->smtpError) {
        Logger::err($this->Email->smtpError);
      }
      $this->Session->setFlash(__('Mail could not be sent', true));
      return false;
    }
    Logger::info("Sent group subscribe request of user {$user['User']['username']} for group {$group['Group']['name']} to {$group['User']['username']}");
    $this->Session->setFlash(__("Group subscription request was sent", true));
    return true;
  }

  function _sendConfirmation($group, $user) {
    $this->Email->to = sprintf("%s <%s>", $user['User']['username'], $user['User']['email']);
    $this->Email->subject = "Group {$group['Group']['name']}: Your subscription was accepted";
    $this->Email->template = 'group_confirmation';

    $this->set('group', $group);
    $this->set('user', $user);

    if (!$this->Email->send()) {
      Logger::err(sprintf("Could not send group confirmation to {$user['User']['username']} <{$user['User']['email']}>"));
      if ($this->Email->smtpError) {
        Logger::err($this->Email->smtpError);
      }
      return false;
    }
    Logger::info("Sent group confirmation to user {$user['User']['username']} for group {$group['Group']['name']}");
    return true;
  }

  function _sendSubscribtion($group) {
    $this->Email->to = sprintf("%s <%s>", $group['User']['username'], $group['User']['email']);
    $user = $this->getUser();

    $this->Email->subject = "Group {$group['Group']['name']}: Subscription request for user {$user['User']['username']}";

    $this->Email->template = 'group_subscribtion';
    $this->set('group', $group);
    $this->set('user', $user);

    if (!$this->Email->send()) {
      Logger::err(sprintf("Could not send new group subscription to {$group['User']['username']} <{$group['User']['email']}>"));
      return false;
    }
    Logger::info("Sent new group subscribtion of user {$user['User']['username']} for group {$group['Group']['name']} to {$group['User']['username']}");
    return true;
  }

  function _sendUnsubscribtion($group) {
    $this->Email->to = sprintf("%s <%s>", $group['User']['username'], $group['User']['email']);
    $user = $this->getUser();

    $this->Email->subject = "Group {$group['Group']['name']}: Subscription request for user {$user['User']['username']}";

    $this->Email->template = 'group_unsubscribtion';
    $this->set('group', $group);
    $this->set('user', $user);

    if (!$this->Email->send()) {
      Logger::err(sprintf("Could not send new group subscription to {$group['User']['username']} <{$group['User']['email']}>"));
      return false;
    }
    Logger::info("Sent new group subscribtion of user {$user['User']['username']} for group {$group['Group']['name']} to {$group['User']['username']}");
    return true;
  }

  function subscribe($name) {
    $group = $this->Group->findByName($name);
    if (!$group) {
      $this->Session->setFlash(sprintf(__("%s not found", true), __("Group", true)));
      $this->redirect('index');
    }
    if ($group['Group']['is_moderated']) {
      $this->_sendSubscribtionRequest($group);
      $this->redirect('index');
    } else {
      $result = $this->Group->subscribe($group, $this->getUserId());
      $this->Session->setFlash($result['message']);
      if ($result['code'] >= 400 && $result['code'] < 500) {
        $this->redirect("index");
      } else {
        if ($result['code'] == 201) {
          $this->_sendSubscribtion($group);
        }
        $this->redirect("view/$name");
      }
    }
  }

  function confirm($groupName, $userName) {
    $conditions = array('Group.name' => $groupName);
    if ($this->getUserRole() < ROLE_ADMIN) {
      $conditions['Group.user_id'] = $this->getUserId();
    }
    $group = $this->Group->find($conditions);
    $user = $this->User->findByUsername($userName);
    $userId = ($user) ? $user['User']['id'] : false;
    $result = $this->Group->subscribe($group, $userId);
    $this->Session->setFlash($result['message']);
    if ($result['code'] >= 400 && $result['code'] < 500) {
      $this->redirect("index");
    } else {
      $this->_sendConfirmation($group, $user);
      $this->Session->setFlash("Confirmed subscription of {$user['User']['username']}");
      $this->redirect("view/$groupName");
    }
  }

  function unsubscribe($name) {
    $group = $this->Group->findByName($name);
    $result = $this->Group->unsubscribe($group, $this->getUserId());
    $this->Session->setFlash($result['message']);
    if ($result['code'] >= 400 && $result['code'] < 500) {
      $this->redirect("index");
    } else {
      if ($result['code'] == 201) {
        $this->_sendUnsubscribtion($group);
      }
      $this->redirect("view/$name");
    }
  }

  function edit($groupName) {
    if (!empty($this->data)) {
      if ($this->data['Group']['name'] != $groupName && !$this->Group->isNameUnique($this->data)) {
        $this->Session->setFlash(sprintf(__("%s already exists", true), __('Group', true)));
      } elseif (!$this->Group->save($this->data)) {
        $this->Session->setFlash(sprintf(__("Could not save %s", true), __('Group', true)));
      } else {
        $this->Session->setFlash(sprintf(__("%s updated", true), __('Group', true)));
        if ($groupName != $this->data['Group']['name']) {
          $this->redirect("edit/{$this->data['Group']['name']}");
        }
      }
    }
    $conditions = array('Group.name' => $groupName);
    if ($this->getUserRole() < ROLE_ADMIN) {
      $conditions['Group.user_id'] = $this->getUserId();
    }
    $this->data = $this->Group->find($conditions);
    if (!$this->data) {
      $this->Session->setFlash(__("Could not find group", true));
      $this->redirect("index");
    }

    $this->menuItems[] = array(
      'text' => sprintf(__('Group: %s', true), $groupName), 
      'type' => 'text', 
      'submenu' => array(
        'items' => array(
          array(
            'text' => __('Edit', true), 
            'link' => 'edit/'.$groupName
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

  function _getMenuItems() {
    $items = array();
    $items[] = array('text' => __('List groups', true), 'link' => 'index');
    $items[] = array('text' => __('Create group', true), 'link' => 'create');
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
