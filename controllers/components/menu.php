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

  var $controller = null;

  var $items = array();

  function initialize(&$controller) {
		$this->controller = $controller;
		$username = $this->Session->read('User.username');

		$this->items[] = array('text' => __('Overview', true), 'link' => '/dashboard');
 		$this->items[] = array('text' => __('Account Settings', true), 'link' => '/dashboard/users/modify');
  	$this->items[] = array('text' => __('Groups', true), 'link' => '/dashboard/groups');
		$this->items[] = array('text' => __(' - Search Group', true), 'link' => '/dashboard/groups/search');
		$this->items[] = array('text' => __(' - Create Group', true), 'link' => '/dashboard/groups/create');
		$this->items[] = array('text' => __(' - Manage Memberships', true), 'link' => '/dashboard/groups/manage');
		$this->items[] = array('text' => __('My Media', true), 'link' => "/explorer/user/{$username}");
		$this->items[] = array('text' => __('Upload', true), 'link' => "/browser/quickupload");

		// TODO add a link for the system administration
	}
  
  /** Set menu output for layout */
  function setMenu() {
		$this->controller->set('mainMenu', array('items' => $this->items, 'active' => $this->controller->here));
  }

  /** Clears all menu */
  function clear() {
    $this->items = array();
  }

  function add($title, $link) {
		$this->items[] = array('text' => $title, 'link' => $link);
  }
}
?>
