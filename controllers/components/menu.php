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
    $this->setBasicMainMenu();
  }

  function setBasicMainMenu() {
    $controllers = array(
      'options' => __('Account Settings', true),
      'groups' => __('Groups', true),
      'users' => __('Users', true),
      'guests' => __('Guests', true),
      'browser' => __('Media Files', true)
      );
    foreach ($controllers as $ctrl => $text) {
      $options = array('id' => 'item-' . $ctrl);
      if (strtolower($this->controller->name) == $ctrl) {
        $options['active'] = true;
      }
      $this->addItem($text, array('controller' => $ctrl), $options);
    } 
  }

  function &getData(&$data, $key) {
    $p =& $data[$key];
    return $p;
  }

  function beforeRender() {
    $this->setCurrentMenu('main');
    if (isset($this->controller->subMenu)) {
      $name = strtolower($this->controller->name);
      $parentId = 'item-' . $name;
      $parentItem =& $this->getItem($parentId);
      foreach ($this->controller->subMenu as $action => $title) {
        $options = array('parent' => $parentId);
        if ($this->controller->action == $action) {
          $options['active'] = true;
        }
        $item = am(array('title' => $title, 'url' => array('controller' => $name, 'action' => $action)), $options);
        $parentItem[] = $item;
      }
    }
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

  /** Add menu item 
    @param title Menu title
    @param url Menu url
    @param options e.g. html link class options */
  function addItem($title, $url, $options = array()) {
    $item = am(array('title' => $title, 'url' => $url), $options);
    $menu =& $this->menus[$this->currentMenu];
    if (isset($options['parent'])) {
      $parent =& $this->getItem($options['parent']);
      if ($parent) {
        $parent[] = $item;
      }
    } else {
      $menu[] = $item;
    }
  }

  function &_findItem($id, &$items) {
    $keys = array_keys($items);
    $found = false;
    foreach($keys as $key) {
      if (is_array($items[$key])) {
        $found =& $this->_findItem($id, &$items[$key]);
        if ($found) {
          return $found;
        }
      } else if ($key == 'id' && $items[$key] == $id) {
        return $items;
      }
    }
    return $found;
  }

  function &getItem($id, $menuName = null) {
    if (!$menuName) {
      $menu =& $this->menus[$this->currentMenu];
    } else {
      $menu =& $this->menus[$menuName];
    }
    return $this->_findItem($id, &$menu);    
  } 

  /** Set menu option for current menu 
    @param name Option name
    @param value Option value */
  function setOption($name, $value) {
    $this->menus[$this->currentMenu]['options'][$name] = $value; 
  }
}
?>
