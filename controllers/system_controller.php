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
class SystemController extends AppController {

  var $name = 'System';
  var $helpers = array('formular', 'form');
  var $uses = array('Option');

  function beforeFilter() {
    parent::beforeFilter();

    $this->requireRole(ROLE_SYSOP, array('redirect' => '/'));
  }

  function _set($userId, $path, $data) {
    $value = Set::extract($data, $path);
    $this->Option->setValue($path, $value, $userId);
  }

  function index() {
    if (!empty($this->data)) {
      // TODO check valid acl
      $this->_set(0, 'bin.exiftool', $this->data);
      $this->_set(0, 'bin.convert', $this->data);
      $this->_set(0, 'bin.ffmpeg', $this->data);
      $this->_set(0, 'bin.flvtool2', $this->data);

      $this->_set(0, 'google.map.key', $this->data);
      // debug
      $this->set('commit', $this->data);
      $this->Session->setFlash("Settings saved");
    }
    $tree = $this->Option->getTree(0);
    $this->Logger->trace($tree);
    $this->data = $tree;
  }

  function getMenuItems() {
    $items = array();
    $items[] = array('text' => 'User Accounts', 'link' => '/admin/users');
    $items[] = array('text' => 'Commands', 'link' => '/system/index');
    return $items;
  }

  function beforeRender() {
    $items = $this->getMenuItems();
    $menu = array('items' => $items, 'active' => $this->here);
    $this->set('mainMenu', $menu);
  }
}
?>
