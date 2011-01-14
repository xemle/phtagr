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

class MenuComponent extends Object {

	var $components = array('Session');
  var $name = 'MenuComponent';

  var $controller;

  /** Menu data */
  var $menus = array();

  var $currentMenu = 'main';

  function initialize(&$controller) {
    if ($this->controller) {
      return;
    }
    $this->controller =& $controller;
    $this->setCurrentMenu('main');
    /*
		$this->items[] = array('text' => __('Overview', true), 'link' => '/dashboard');
 		$this->items[] = array('text' => __('Account Settings', true), 'link' => '/dashboard/users/modify');
  	$this->items[] = array('text' => __('Groups', true), 'link' => '/dashboard/groups');
		$this->items[] = array('text' => __(' - Search Group', true), 'link' => '/dashboard/groups/search');
		$this->items[] = array('text' => __(' - Create Group', true), 'link' => '/dashboard/groups/create');
		$this->items[] = array('text' => __(' - Manage Memberships', true), 'link' => '/dashboard/groups/manage');
		$this->items[] = array('text' => __('My Media', true), 'link' => "/explorer/user/{$username}");
		$this->items[] = array('text' => __('Upload', true), 'link' => "/browser/quickupload");
    */
    $this->setBasicMainMenu();
  }

  function setBasicMainMenu() {
    $this->addItem(__('Account Settings', true), array('controller' => 'options'), array('id' => 'item-options'));
    $this->addItem(__('Groups', true), array('controller' => 'groups'), array('id' => 'item-groups'));
    $this->addItem(__('Users', true), array('controller' => 'users'), array('id' => 'item-users'));
    $this->addItem(__('Media Files', true), array('controller' => 'browser'), array('id' => 'item-browser'));
  }

  function beforeRender() {
    $this->controller->params['menus'] = $this->menus;
    $this->controller->set('menus_for_layout', $this->menus);
  }

  /** Set the current menu 
    @param name Menu name */
  function setCurrentMenu($name) {
    $this->currentMenu = $name;
    if (!isset($this->menus[$name])) {
      $this->menus[$name] = array('options' => array());
    }
  }

  function _addItemToParent($menu, &$items) {
    foreach ($items as &$item) {
      if (!is_array($item) || count($item) == 0) {
        continue;
      }
      if (isset($item['id']) && $item['id'] == $menu['parent']) {
        $item[] = $menu;
      } else {
        $this->_addItemToParent($menu, $item);
      }   
    }
  }

  /** Add menu item 
    @param title Menu title
    @param url Menu url
    @param options e.g. html link class options */
  function addItem($title, $url, $options = array()) {
    $item = am(array('title' => $title, 'url' => $url), $options);
    $menu =& $this->menus[$this->currentMenu];
    if (isset($options['parent'])) {
      $this->_addItemToParent($item, $menu);
    } else {
      $menu[] = $item;
    }
  }
  
  /** Set menu option for current menu 
    @param name Option name
    @param value Option value */
  function setOption($name, $value) {
    $this->menus[$this->currentMenu]['options'][$name] = $value; 
  }
}
?>
