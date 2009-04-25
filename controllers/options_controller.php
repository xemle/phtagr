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
class OptionsController extends AppController {

  var $name = 'Options';
  var $helpers = array('formular', 'form');
  var $uses = array('Option', 'Group');

  function beforeFilter() {
    parent::beforeFilter();

    $this->requireRole(ROLE_GUEST, array('redirect' => '/'));
  }

  function _set($userId, $path, $data) {
    $value = Set::extract($data, $path);
    $this->Option->setValue($path, $value, $userId);
  }

  function acl() {
    $this->requireRole(ROLE_USER);

    $userId = $this->getUserId();
    if (isset($this->data)) {
      // TODO check valid acl
      $this->_set($userId, 'acl.group', $this->data);

      // check values
      if ($this->data['acl']['write']['meta'] > $this->data['acl']['write']['tag'])
        $this->data['acl']['write']['meta'] = $this->data['acl']['write']['tag'];
      if ($this->data['acl']['read']['original'] > $this->data['acl']['read']['preview'])
        $this->data['acl']['read']['original'] = $this->data['acl']['read']['preview'];

      $this->_set($userId, 'acl.write.tag', $this->data);
      $this->_set($userId, 'acl.write.meta', $this->data);

      $this->_set($userId, 'acl.read.original', $this->data);
      $this->_set($userId, 'acl.read.preview', $this->data);

      $this->Session->setFlash("Settings saved");
    }
    $tree = $this->Option->getTree($userId);
    $this->Option->addDefaultAclTree(&$tree);
    $this->data = $tree;

    $this->set('userId', $userId);
    $groups = $this->Group->findAll("Group.user_id = $userId", null, array('Group.name' => 'ASC'));
    if ($groups) {
      $groups = Set::combine($groups, '{n}.Group.id', '{n}.Group.name');
    } else {
      $groups = array();
    }
    $groups[-1] = '[No Group]';
    $this->set('groups', $groups);
  }

  function profile() {
    $this->requireRole(ROLE_USER);

    $userId = $this->getUserId();
    if (!empty($this->data)) {
      $this->User->id = $userId;
      $this->Logger->debug($this->data);
      if (!$this->User->save($this->data, true, array('firstname', 'lastname', 'password', 'email'))) {
        $this->Logger->err("Could not update user profile");
        $this->Session->setFlash("Could not save profile!");
      } else {
        $this->Logger->info("User $userId profile updated");
        $this->Session->setFlash("Profile saved");
      }
    }
    $this->data = $this->User->findById($userId);
    unset($this->data['User']['password']);
  }

  function rss($action = null) {
    $this->requireRole(ROLE_USER);

    $userId = $this->getUserId();
    $user = $this->User->findById($userId);
    if ($action == 'renew' || empty($user['User']['key'])) {
      $this->User->generateKey(&$user);
      $this->User->id = $userId;
      if (!$this->User->save($user, false, array('key'))) {
        $this->Logger->err("Could not save user data");
        $this->Logger->debug($this->User->validationErrors);
      }
    }
    $this->set('data', $this->User->findById($userId));
  }

  function getMenuItems() {
    $items = array();
    if ($this->hasRole(ROLE_USER)) {
      $items[] = array('text' => 'Profile', 'link' => '/options/profile');
      $items[] = array('text' => 'Guest Accounts', 'link' => '/guests');
      $items[] = array('text' => 'Groups', 'link' => '/groups');
    }
    $items[] = array('text' => 'Access Rights', 'link' => '/options/acl');
    $items[] = array('text' => 'RSS', 'link' => '/options/rss');
    return $items;
  }

  function beforeRender() {
    $items = $this->getMenuItems();
    $menu = array('items' => $items, 'active' => $this->here);
    $this->set('mainMenu', $menu);
  }
}
?>
