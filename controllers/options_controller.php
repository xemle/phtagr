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
class OptionsController extends AppController {

  var $name = 'Options';
  var $helpers = array('Form');
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

      $this->Session->setFlash(__("Settings saved", true));
    }
    $tree = $this->Option->getTree($userId);
    $this->Option->addDefaultAclTree(&$tree);
    $this->data = $tree;

    $this->set('userId', $userId);
    $groups = $this->Group->find('all', array('conditions' => "Group.user_id = $userId", 'order' => array('Group.name' => 'ASC')));
    if ($groups) {
      $groups = Set::combine($groups, '{n}.Group.id', '{n}.Group.name');
    } else {
      $groups = array();
    }
    $groups[-1] = __('[No Group]', true);
    $this->set('groups', $groups);
  }

  function profile() {
    $this->requireRole(ROLE_USER);

    $userId = $this->getUserId();
    if (!empty($this->data)) {
      $this->User->id = $userId;
      if (!$this->User->save($this->data, true, array('firstname', 'lastname', 'password', 'email', 'visible_level'))) {
        Logger::err("Could not update user profile");
        $this->Session->setFlash(__("Could not save profile!", true));
      } else {
        Logger::info("User $userId profile updated");
        $this->Session->setFlash(__("Profile saved", true));
      }
      $browser = Set::extract("/Option/user/browser/full", $this->data);
      $this->Option->setValue('user.browser.full', $browser[0], $userId);
    }
    $this->data = $this->User->findById($userId);
    $this->data['Option'] = $this->Option->getTree($userId);
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
        Logger::err("Could not save user data");
        Logger::debug($this->User->validationErrors);
      }
    }
    $this->data = $this->User->findById($userId);
  }

  function getMenuItems() {
    $items = array();
    if ($this->hasRole(ROLE_USER)) {
      $items[] = array('text' => __('Profile', true), 'link' => '/options/profile');
      $items[] = array('text' => __('Guest Accounts', true), 'link' => '/guests');
      $items[] = array('text' => __('Groups', true), 'link' => '/groups');
    }
    $items[] = array('text' => __('Access Rights', true), 'link' => '/options/acl');
    $items[] = array('text' => __('RSS', true), 'link' => '/options/rss');
    return $items;
  }

  function beforeRender() {
    parent::beforeRender();
    $items = $this->getMenuItems();
    $menu = array('items' => $items, 'active' => $this->here);
    $this->set('mainMenu', $menu);
  }
}
?>
