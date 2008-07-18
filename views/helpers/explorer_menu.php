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
  var $helpers = array('html', 'query', 'menu');

  var $_id;

  function _getSubMenu($data, $field) {
    $fields = Inflector::pluralize($field);
    if (!isset($data[$fields]) || !count($data[$fields]))
      return false;

    $tmp = $this->query->getQuery();
    $userId = $this->query->get('user');

    $subMenu = array();

    foreach($data[$fields] as $name => $count) {
      $this->query->set('page', 1);

      $id = "item-".$this->_id++;

      // field
      $link = "/explorer/$field/$name";
      if ($userId)
        $link .= "/user:$userId";
      $text = ' '.$this->html->link($name, $link);
      $text .= " ($count)";

      $text .= " <div class=\"actionlist\" id=\"$id\">";

      // include field
      $this->query->add($fields, $name);
      $text .= $this->html->link(
        $this->html->image('icons/add.png', array('alt' => '+', 'title' => "Include $field $name")),
        $this->query->getUri(), null, false, false);
      $this->query->del($fields, $name);

      // exclude field
      $this->query->add($fields, '-'.$name);
      $text .= $this->html->link(
        $this->html->image('icons/delete.png', array('alt' => '-', 'title' => "Exclude $field $name")),
        $this->query->getUri(), null, false, false);
      $this->query->del($fields, '-'.$name);

      // global link
      if ($userId) {
        $tmp2 = $this->query->getQuery();
        $this->query->clear();
        $this->query->add($fields, $name);
        $text .= $this->html->link(
          $this->html->image('icons/world.png', array('alt' => 'global', 'title' => "Explore global $field $name")),
          "$field/$name", null, false, false);
        $this->query->setQuery($tmp2);
      }
      $text .= "</div>";

      $subMenu[] = array(
        'text' => $text, 
        'type' => 'multi', 
        'onmouseover' => "toggleVisibility('$id', 'inline');",
        'onmouseout' => "toggleVisibility('$id', 'inline');");

      // reset changed search parameters
      $this->query->setQuery($tmp);
    }
    return $subMenu;
  }

  function _getQueryOrderMenu() {
    $tmp = $this->query->getQuery();
    $subMenu = array();
    
    $orders = array(
        'date' => 'Date', 
        'newest' => 'Newest', 
        'changes' => 'Changes', 
        'random' => 'Random'
      );
    foreach ($orders as $order => $name) {
      $this->query->set('sort', $order);
      $subMenu[] = array(
          'text' => $this->html->link($name, $this->query->getUri()),
          'type' => 'multi'
        );
    }
    $this->query->setQuery($tmp);
    return $subMenu;
  }

  function getMainMenu($data) {
    $this->query->initialize();
    $items = array();
    $this->_id = 0;

    $search = 'search';
    if ($this->query->get('myimage')) {
      $search .= '/user:'.$this->query->get('user');
    }
    $items[] = array('text' => $this->html->link('Advance Search', $search));
    $items[] = array('text' => $this->html->link('Start Slideshow', 'javascript:startSlideshow();'));

    $subMenu = $this->_getSubMenu($data, 'tag');
    if ($subMenu !== false)
      $items[] = array('text' => 'Tags', 'type' => 'text', 'submenu' => array('items' => $subMenu));
    
    $subMenu = $this->_getSubMenu($data, 'category');
    if ($subMenu !== false)
      $items[] = array('text' => 'Categories', 'type' => 'text', 'submenu' => array('items' => $subMenu));

    $subMenu = $this->_getSubMenu($data, 'location');
    if ($subMenu !== false)
      $items[] = array('text' => 'Locations', 'type' => 'text', 'submenu' => array('items' => $subMenu));

    if ($this->params['action'] != 'search') {
      $items[] = array('text' => 'Order By', 'type' => 'text', 'submenu' => array('items' => $this->_getQueryOrderMenu()));
    }

    $menu = array('items' => $items);
    return $this->menu->getMainMenu($menu);
  }
}
?>
