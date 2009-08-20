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

class ExplorerMenuHelper extends AppHelper
{
  var $helpers = array('html', 'search', 'menu');

  var $_id;

  /** Count the association
    @param association name
    @return Array of accociation name as key and their count as value */
  function _countAssociation($association) {
    $result = array();
    if (isset($this->data['Media'])) {
      $data = array($this->data);
    } else {
      $data =& $this->data;
    }
    foreach ($data as $media) {
      $values = Set::extract("/$association/name", $media);
      foreach ($values as $value) {
        if (!isset($result[$value])) {
          $result[$value] = 1;
        } else {
          $result[$value]++;
        }
      }
    }
    arsort($result);
    return $result;
  }

  function _getAssociationExtra($association, $value, $id) {
    $out = " <div class=\"actionlist\" id=\"$id\">";

    $plural = Inflector::pluralize($association);
    $addLink = $this->search->getUri(false, array($plural => $value), array($plural => '-'.$value));
    $addIcon = $this->html->image('icons/add.png', array('alt' => '+', 'title' => "Include $association $value"));
    $out .= $this->html->link($addIcon, $addLink, false, false, false);

    $delLink = $this->search->getUri(false, array($plural => '-'.$value), array($plural => $value));
    $delIcon = $this->html->image('icons/delete.png', array('alt' => '-', 'title' => "Exclude $association $value"));
    $out .= $this->html->link($delIcon, $delLink, false, false, false);

    if ($this->action == 'user') {
      $worldLink = "/explorer/$association/$value";
      $worldIcon = $this->html->image('icons/world.png', array('alt' => '-', 'title' => "View all media with $association $value"));
      $out .= $this->html->link($worldIcon, $worldLink, false, false, false);
    }

    $out .= "</div>";
    return $out;
  }

  function _getAssociationSubMenu($association) {
    $counts = $this->_countAssociation(Inflector::camelize($association));
    if (count($counts) == 0) {
      return false;
    }

    $subMenu = array();
    $base = '/explorer';
    if ($this->action == 'user') {
      $base .= '/user/'.$this->params['pass'][0];
    }
    foreach($counts as $name => $count) {
      $id = "item-".$this->_id++;
      $link = $this->html->link($name, "$base/$association/$name");
      $extra = $this->_getAssociationExtra($association, $name, $id);
      $subMenu[] = array(
        'text' => "$link ($count) $extra",
        'type' => 'multi',
        'onmouseover' => "toggleVisibility('$id', 'inline');",
        'onmouseout' => "toggleVisibility('$id', 'inline');"
        );
    }
    return $subMenu;
  }

  function _getOrderItem() {
    $link = $this->search->getUri(false, array('sort' => 'date'), 'page');
    $out = $this->html->link("Order", $link);

    $id = 'order-item';
    $out .= " <div class=\"actionlist\" id=\"$id\">";
    
    $icon = $this->html->image('icons/date_previous.png', array('alt' => 'date asc', 'title' => "Show oldest first"));
    $link = $this->search->getUri(false, array('sort' => '-date'), 'page');
    $out .= $this->html->link($icon, $link, false, false, array('escape' => false));
    
    $icon = $this->html->image('icons/add.png', array('alt' => 'newest', 'title' => "Show newest first"));
    $link = $this->search->getUri(false, array('sort' => 'newest'), 'page');
    $out .= $this->html->link($icon, $link, false, false, array('escape' => false));
    
    $icon = $this->html->image('icons/heart.png', array('alt' => 'pouplarity', 'title' => "Show popular first"));
    $link = $this->search->getUri(false, array('sort' => 'popularity'), 'page');
    $out .= $this->html->link($icon, $link, false, false, array('escape' => false));
    
    $icon = $this->html->image('icons/images.png', array('alt' => 'random', 'title' => "Show random order"));
    $link = $this->search->getUri(false, array('sort' => 'random'), 'page');
    $out .= $this->html->link($icon, $link, false, false, array('escape' => false));
    
    $icon = $this->html->image('icons/pencil.png', array('alt' => 'changes', 'title' => "Show changes first"));
    $link = $this->search->getUri(false, array('sort' => 'changes'), 'page');
    $out .= $this->html->link($icon, $link, false, false, array('escape' => false));
    
    $icon = $this->html->image('icons/eye.png', array('alt' => 'views', 'title' => "Show last views first"));
    $link = $this->search->getUri(false, array('sort' => 'viewed'), 'page');
    $out .= $this->html->link($icon, $link, false, false, array('escape' => false));

    $out .= "</div>";

    $subMenu = array(
      'text' => $out,
      'type' => 'multi',
      'onmouseover' => "toggleVisibility('$id', 'inline');",
      'onmouseout' => "toggleVisibility('$id', 'inline');"
      );
    return $subMenu;
  }

  function _getPageItem() {
    $link = $this->search->getUri(false, array('show' => '12'), 'page');
    $out = $this->html->link("Pagesize", $link);

    $pos = $this->search->getPage(1) * $this->search->getShow(1);
    $sizes = array(6, 12, 24, 60, 120, 240);
    $links = array();
    foreach ($sizes as $size) {
      $page = ceil($pos / $size);
      $link = $this->search->getUri(false, array('show' => $size, 'page' => $page));
      $links[] = $this->html->link($size, $link, false, false, array('escape' => false));
    }

    $id = 'page-item';
    $out .= " <div class=\"actionlist\" id=\"$id\">";
    $out .= implode(", ", $links);
    $out .= "</div>";

    $subMenu = array(
      'text' => $out,
      'type' => 'multi',
      'onmouseover' => "toggleVisibility('$id', 'inline');",
      'onmouseout' => "toggleVisibility('$id', 'inline');"
      );
    return $subMenu;
  }

  function getMainMenu() {
    $data = $this->data;
    $this->search->initialize();
    $items = array();
    $this->_id = 0;

    $search = '/explorer/search';
    $items[] = array('text' => $this->html->link('Advance Search', $search));
    $items[] = array('text' => $this->html->link('Start Slideshow', 'javascript:startSlideshow();'));

    $subMenu = $this->_getAssociationSubMenu('tag');
    if ($subMenu !== false) {
      $items[] = array('text' => 'Tags', 'type' => 'text', 'submenu' => array('items' => $subMenu));
    }

    $subMenu = $this->_getAssociationSubMenu('category');
    if ($subMenu !== false)a {
      $items[] = array('text' => 'Categories', 'type' => 'text', 'submenu' => array('items' => $subMenu));
    }

    $subMenu = $this->_getAssociationSubMenu('location');
    if ($subMenu !== false) {
      $items[] = array('text' => 'Locations', 'type' => 'text', 'submenu' => array('items' => $subMenu));
    }

    $subItems = array();
    $subItems[] = $this->_getOrderItem();
    $subItems[] = $this->_getPageItem();
    $items[] = array('text' => 'Options', 'type' => 'text', 'submenu' => array('items' => $subItems));

    $menu = array('items' => $items);
    return $this->menu->getMainMenu($menu);
  }
}
?>
