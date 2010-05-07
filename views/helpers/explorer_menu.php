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

class ExplorerMenuHelper extends AppHelper
{
  var $helpers = array('html', 'search', 'menu', 'piclens');

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
    if (!$data) {
      return false;
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

  function _getSlideshowItem() {
    $id = 'menu-slideshow';

    $extra = " <div class=\"actionlist\" id=\"$id\">";
    $link = "javascript:startSlideshow('high');";
    $icon = $this->html->image('icons/star.png', array('alt' => 'high', 'title' => __("Show media in high quality (if available)", true)));
    $extra .= $this->html->link($icon, $link, false, false, false);
    $extra .= "</div>";

    $text = $this->html->link(__('Start Slideshow', true), "javascript:startSlideshow('');", false, false, false);
    $item = array(
      'text' => $text.$extra,
      'type' => 'multi',
      'onmouseover' => "toggleVisibility('$id', 'inline');",
      'onmouseout' => "toggleVisibility('$id', 'inline');"
      );
    return $item;
  }

  function _getAssociationExtra($association, $value, $id) {
    $out = " <div class=\"actionlist\" id=\"$id\">";

    $plural = Inflector::pluralize($association);
    $addLink = $this->search->getUri(false, array($plural => $value), array($plural => '-'.$value, 'page'));
    $addIcon = $this->html->image('icons/add.png', array('alt' => '+', 'title' => "Include $association $value"));
    $out .= $this->html->link($addIcon, $addLink, false, false, false);

    $delLink = $this->search->getUri(false, array($plural => '-'.$value), array($plural => $value, 'page'));
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
    if (!$counts) {
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
    $out = $this->html->link(__("Order", true), $link);

    $id = 'order-item';
    $out .= " <div class=\"actionlist\" id=\"$id\">";
    
    $icon = $this->html->image('icons/date_previous.png', array('alt' => 'date asc', 'title' => __("Show oldest first", true)));
    $link = $this->search->getUri(false, array('sort' => '-date'), 'page');
    $out .= $this->html->link($icon, $link, false, false, array('escape' => false));
    
    $icon = $this->html->image('icons/add.png', array('alt' => 'newest', 'title' => __("Show newest first", true)));
    $link = $this->search->getUri(false, array('sort' => 'newest'), 'page');
    $out .= $this->html->link($icon, $link, false, false, array('escape' => false));
    
    $icon = $this->html->image('icons/heart.png', array('alt' => 'pouplarity', 'title' => __("Show popular first", true)));
    $link = $this->search->getUri(false, array('sort' => 'popularity'), 'page');
    $out .= $this->html->link($icon, $link, false, false, array('escape' => false));
    
    $icon = $this->html->image('icons/images.png', array('alt' => 'random', 'title' => __("Show random order", true)));
    $link = $this->search->getUri(false, array('sort' => 'random'), 'page');
    $out .= $this->html->link($icon, $link, false, false, array('escape' => false));
    
    $icon = $this->html->image('icons/pencil.png', array('alt' => 'changes', 'title' => __("Show changes first", true)));
    $link = $this->search->getUri(false, array('sort' => 'changes'), 'page');
    $out .= $this->html->link($icon, $link, false, false, array('escape' => false));
    
    $icon = $this->html->image('icons/eye.png', array('alt' => 'views', 'title' => __("Show last views first", true)));
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
    $out = $this->html->link(__("Pagesize", true), $link);

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
    $out = '';
    $data = $this->data;
    $this->search->initialize();
    $items = array();
    $this->_id = 0;

    $search = '/explorer/search';
    $items[] = array('text' => $this->html->link(__('Advanced Search', true), $search));

    $items[] = $this->_getSlideshowItem();
    $out .= $this->piclens->slideshow();

    $subMenu = $this->_getAssociationSubMenu('tag');
    if ($subMenu !== false) {
      $items[] = array('text' => __('Tags', true), 'type' => 'text', 'submenu' => array('items' => $subMenu));
    }

    $subMenu = $this->_getAssociationSubMenu('category');
    if ($subMenu !== false) {
      $items[] = array('text' => __('Categories', true), 'type' => 'text', 'submenu' => array('items' => $subMenu));
    }

    $subMenu = $this->_getAssociationSubMenu('location');
    if ($subMenu !== false) {
      $items[] = array('text' => __('Locations', true), 'type' => 'text', 'submenu' => array('items' => $subMenu));
    }

    $subItems = array();
    $subItems[] = $this->_getOrderItem();
    $subItems[] = $this->_getPageItem();
    $items[] = array('text' => __('Options', true), 'type' => 'text', 'submenu' => array('items' => $subItems));

    $menu = array('items' => $items);
    return $out.$this->menu->getMainMenu($menu);
  }
}
?>
