<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2008 Sebastian Felis, sebastian@phtagr.org
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
class ExplorerMenuHelper extends AppHelper
{
  var $helpers = array('Html', 'Search', 'Menu');

  var $_id;

  function _getSubMenu($data, $field) {
    $fields = Inflector::pluralize($field);
    if (!isset($data[$fields]) || !count($data[$fields]))
      return false;

    $tmp = $this->Search->getSearch();
    $userId = $this->Search->get('user');

    $subMenu = array();

    foreach($data[$fields] as $name => $count) {
      $this->Search->set('page', 1);

      $id = "item-".$this->_id++;

      // field
      $link = "/explorer/$field/$name";
      if ($userId)
        $link .= "/user:$userId";
      $text = ' '.$this->Html->link($name, $link);
      $text .= " ($count)";

      $text .= " <div class=\"actionlist\" id=\"$id\">";

      // include field
      $this->Search->add($fields, $name);
      $text .= $this->Html->link(
        $this->Html->image('icons/add.png', array('alt' => '+', 'title' => "Include $field $name")),
        $this->Search->getUri(), null, false, false);
      $this->Search->remove($fields, $name);

      // exclude field
      $this->Search->add($fields, '-'.$name);
      $text .= $this->Html->link(
        $this->Html->image('icons/delete.png', array('alt' => '-', 'title' => "Exclude $field $name")),
        $this->Search->getUri(), null, false, false);
      $this->Search->remove($fields, '-'.$name);

      // global link
      if ($userId) {
        $tmp2 = $this->Search->getSearch();
        $this->Search->clear();
        $this->Search->add($fields, $name);
        $text .= $this->Html->link(
          $this->Html->image('icons/world.png', array('alt' => 'global', 'title' => "Explore global $field $name")),
          "$field/$name", null, false, false);
        $this->Search->setSearch($tmp2);
      }
      $text .= "</div>";

      $subMenu[] = array(
        'text' => $text, 
        'type' => 'multi', 
        'onmouseover' => "toggleVisibility('$id', 'inline');",
        'onmouseout' => "toggleVisibility('$id', 'inline');");

      // reset changed search parameters
      $this->Search->setSearch($tmp);
    }
    return $subMenu;
  }

  function _getSearchOrderMenu() {
    $tmp = $this->Search->getSearch();
    $subMenu = array();
    
    $orders = array(
        'date' => 'Date', 
        'newest' => 'Newest', 
        'changes' => 'Changes', 
        'random' => 'Random'
      );
    foreach ($orders as $order => $name) {
      $this->Search->set('sort', $order);
      $subMenu[] = array(
          'text' => $this->Html->link($name, $this->Search->getUri()),
          'type' => 'multi'
        );
    }
    $this->Search->setSearch($tmp);
    return $subMenu;
  }

  function getMainMenu($data) {
    $this->Search->initialize();
    $items = array();
    $this->_id = 0;

    $subMenu = $this->_getSubMenu($data, 'tag');
    if ($subMenu !== false)
      $items[] = array('text' => 'Tags', 'type' => 'text', 'submenu' => array('items' => $subMenu));
    
    $subMenu = $this->_getSubMenu($data, 'category');
    if ($subMenu !== false)
      $items[] = array('text' => 'Categories', 'type' => 'text', 'submenu' => array('items' => $subMenu));

    $subMenu = $this->_getSubMenu($data, 'location');
    if ($subMenu !== false)
      $items[] = array('text' => 'Locations', 'type' => 'text', 'submenu' => array('items' => $subMenu));

    $items[] = array('text' => 'Order By', 'type' => 'text', 'submenu' => array('items' => $this->_getSearchOrderMenu()));

    $menu = array('items' => $items);
    return $this->Menu->getMainMenu($menu);
  }
}
?>
