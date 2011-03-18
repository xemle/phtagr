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
class GuestsController extends AppController {
  var $name = 'Guests';
  var $uses = array('Group', 'User', 'Guest');
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
    $this->data = $this->Guest->find('all', array('conditions' => array('Guest.creator_id' => $userId)));
  }

  function autocomplete() {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      $this->redirect(null, '404');
    } 
    $userId = $this->getUserId();
    uses('sanitize');
    $sanitize = new Sanitize();
    $escName = $sanitize->escape($this->data['Group']['name']);
    $groups = $this->Group->find('all', array('conditions' => "Group.user_id = $userId AND Group.name LIKE '%$escName%'"));
    $this->data = $groups;
    $this->layout = "bare";
  }

  function add() {
    if (!empty($this->data)) {
      $userId = $this->getUserId();
      $this->data['Guest']['creator_id'] = $userId;
      $this->data['Guest']['role'] = ROLE_GUEST;
      if ($this->Guest->hasAny(array("Guest.username" => $this->data['Guest']['username']))) {
        $this->Session->setFlash(__("Sorry. Username is already taken", true));
      } elseif ($this->Guest->save($this->data, true, array('username', 'password', 'role', 'creator_id', 'email', 'quota'))) {
        $guestId = $this->Guest->getLastInsertID();
        $guest = $this->Guest->findById($guestId);
        $user = $this->getUser();
        Logger::info("User '{$user['User']['username']}' ({$user['User']['id']}) created a guest account '{$guest['Guest']['username']}' ({$guest['Guest']['id']})");
        $this->Session->setFlash(sprintf(__("Guest account '%s' was successfully created", true), $this->data['Guest']['username']));
        $this->redirect("edit/$guestId");
      } else {
        $this->Session->setFlash(__("Sorry. Guest account could not created", true));
      }
    }
  }

  function _addGuestMenu($guest) {
    $this->menuItems[] = array(
      'text' => sprintf(__('Guest: %s', true), $guest['Guest']['username']), 
      'type' => 'text', 
      'submenu' => array(
        'items' => array(
          array(
            'text' => __('Edit', true), 
            'link' => 'edit/'.$guest['Guest']['id']
            ),
          array(
            'text' => __('Links', true), 
            'link' => 'links/'.$guest['Guest']['id']
            )
          )
        )
      );
  }

  function edit($guestId) {
    $guestId = intval($guestId);
    $userId = $this->getUserId();
    
    if (!$this->Guest->hasAny(array('id' => $guestId, 'creator_id' => $userId))) {
      $this->Session->setFlash(__("Sorry. Could not find requested guest", true));
      Logger::debug("Sorry. Could not find requested guest '$guestId' of user '$userId'");
      $this->redirect("index");
    }

    if (!empty($this->data)) {
      $this->Guest->id = $guestId;
      $this->Guest->set($this->data);
      if ($this->Guest->save(null, true, array('username', 'password', 'email', 'expires', 'quota'))) {
        $this->Session->setFlash(__("Guest data were saved", true));
        $auth = max(0, min(3, $this->data['Comment']['auth']));
        $this->Option->setValue('comment.auth', $auth, $guestId);
      } else {
        Logger::err("Could not save guest");
        Logger::trace($this->Guest->validationErrors);
        $this->Session->setFlash(__("Updates could not be saved!", true));
      }
    }
    $this->data = $this->Guest->findById($guestId);
    unset($this->data['Guest']['password']);
    $this->data['Comment']['auth'] = $this->Option->getValue($this->data, 'comment.auth', COMMENT_AUTH_NONE);
    $this->set('userId', $userId);
    $this->_addGuestMenu($this->data);
  }

  /**
    @todo Reset all group information of image */
  function delete($guestId) {
    $userId = $this->getUserId();
    $guest = $this->Guest->find(array('Guest.id' => $guestId, 'Creator.id' => $userId));
    if (!$guest) {
      $this->Session->setFlash(__("Could not find requested guest", true));
    } else {
      $user = $this->getUser();
      Logger::info("User '{$user['User']['username']}' ({$user['User']['id']}) deleted guest account '{$guest['Guest']['username']}' ({$guest['Guest']['id']})");
      $this->Session->setFlash(sprintf(__("Guest account '%s' deleted!", true), $guest['Guest']['username']));
      $this->Guest->delete($guestId);
    }
    $this->redirect("index");
  }

  function addGroup($groupId) {
    if (!empty($this->data)) {
      $userId = $this->getUserId();
      $group = $this->Group->find(array('Group.name' => $this->data['Group']['name'], 'Group.user_id' => $userId));
      $guest = $this->Guest->find(array('Guest.id' => $groupId, 'Creator.id' => $userId));

      if (!$guest) {
        $this->Session->setFlash("The given user with id '$groupId' could not be found!");
        $this->redirect("index");
      } elseif (!$group) {
        $this->Session->setFlash("The group '{$this->data['Group']['name']}' does not exists!");
      } else {
        $list = Set::extract($guest, "Member.{n}.id");
        $list[] = $group['Group']['id'];
        $guest['Member']['Member'] = array_unique($list);
        unset($guest['Guest']['password']);
        $this->Guest->set($guest);
        if ($this->Guest->save()) {
          Logger::info("Added group '{$group['Group']['name']}' ({$group['Group']['id']}) to guest '{$guest['Guest']['username']}' ({$guest['Guest']['id']})");
          $this->Session->setFlash("The group '{$this->data['Group']['name']}' was added to your guest '{$guest['Guest']['username']}'");
        } else {
          $this->Session->setFlash("The group '{$this->data['Group']['name']}' could not be added to your guest '{$guest['Guest']['username']}'");
        }
      }
      $this->redirect("edit/$groupId");
    }
  }

  function deleteGroup($guestId, $groupId) {
    $guestId = intval($guestId);
    $groupId = intval($groupId);
    $userId = $this->getUserId();

    $guest = $this->Guest->find(array('Guest.id' => $guestId, 'Creator.id' => $userId));
    if (!$guest) {
      $this->Session->setFlash("Could not find guest!");
      $this->redirect("index");
    } else {
      $list = Set::extract($guest, "Member.{n}.id");
      $key = array_search($groupId, $list);
      if ($key === false) {
        $this->Session->setFlash("Could not find group of guest '{$guest['Guest']['username']}'");
      } else {
        unset($list[$key]);
        $guest['Member']['Member'] = array_unique($list);
        unset($guest['Guest']['password']);
        $this->Guest->id = $guestId;
        $this->Guest->set($guest);
        if (!$this->Guest->save()) {
          Logger::err("Could not save guest");
          Logger::trace($this->Guest->validationErrors);
          $this->Session->setFlash("Could not save guest");
        } else {
          $group = $this->Group->findById($groupId);
          Logger::info("Deleted group '{$group['Group']['name']}' ({$group['Group']['id']}) from guest '{$guest['Guest']['username']}' ({$guest['Guest']['id']})");
          $this->Session->setFlash("Group '{$group['Group']['name']}' was successfully deleted from guest '{$guest['Guest']['username']}'");
        }
      }
      $this->redirect("edit/$guestId");
    }
  }

  function links($guestId, $action = null) {
    $this->requireRole(ROLE_USER);

    $userId = $this->getUserId();
    $guest = $this->Guest->find(array('Guest.id' => $guestId, 'Creator.id' => $userId));
    if (!$guest) {
      $this->Session->setFlash("Could not find guest!");
      Logger::err("Could not find guest $guestId for user $userId");
      $this->redirect("index");
    }

    if ($action == 'renew' || empty($guest['Guest']['key'])) {
      // reuse function of User model
      $this->User->generateKey(&$guest);
      $guest['Guest']['key'] = $guest['User']['key'];
      $this->Guest->id = $guestId;
      if (!$this->Guest->save($guest, false, array('key'))) {
        Logger::err("Could not save user data");
        Logger::debug($this->Guest->validationErrors);
      }
    }
    $this->data = $this->Guest->findById($guestId);
    $this->_addGuestMenu($this->data);
  }

  function _getMenuItems() {
    $items = array();
    $items[] = array('text' => 'List Guests', 'link' => 'index');
    $items[] = array('text' => 'Add Guest', 'link' => 'add');
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
