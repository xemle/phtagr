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
class MenuHelper extends AppHelper
{
  var $helpers = array('html');

  var $_url = false;

/**
  items
    text: Link text
    link: Link
    type: menu types: current, active, text, multi, or false
    onmouseover, onmouseout: add event
    submenu: subitem, array of items

  Example
  array(
    'id' => 'list id',
    'title' => 'Title text',
    'items' => array(
      [0] => array(
        'text' => 'TEXT'
        'type' => 'active'
        ),
      [1] => array(
        'text' => 'Submenu Text',
        'type' => 'text',
        'submenu' => array(
          'items' => array(
            [0] => array(
              'text' => 'Submenu Text',
              'link' => '#submenulink'
              )
            )
          )
        ),
      [2] => array(
        'text' => 'Menu Text'
        'link' => '#link'
        ),
      [3] => array(
        'text' => '#link1 #link2'
        'type' => 'multi'
        'onmouseover' => 'toggleItem('item-3');'
        )
      )
    )
*/
  function _getMenu($data) {
    $data = am(array('id' => false, 'title' => false, 'items' => null, 'toggleid' => false), $data);
    if ($data['id'])
      $out = "<ul id=\"{$data['id']}\">\n";
    else
      $out = "<ul>\n";

    if ($data['title'])
      $out .= "<li id=\"title\">{$data['title']}</li>\n";

    if (empty($data['items']))
      return $out."</ul>\n";

    foreach ($data['items'] as $item) {
      $item = am(array('type' => false, 'text' => false, 'link' => false), $item);
      if (!$item['text']) {
        // Trigger error
        continue; 
      }

      if ($item['link'] == 'false' && $item['type'] == false)
        $item['type'] == 'text';

      $out .= "<li";
      $attrs = array(); 
      if ($item['type'] == 'current') {
        $attrs['id'] = 'current';
        //$item['link'] = false;
      } elseif ($item['type'] == 'active' || 
        ($item['link'] && Router::url($item['link']) == $this->_url)) {
        $attrs['id'] = 'active';
        $item['link'] = false;
      } elseif ($item['type'] == 'multi') {
        $attrs['class'] = 'multi';
        $item['link'] = false;
      }
      foreach (array('onmouseover', 'onmouseout') as $event) {
        if (isset($item[$event]))
          $attrs[$event] = $item[$event];
      }
      foreach ($attrs as $name => $value) {
        $out .= " $name=\"".htmlspecialchars($value)."\"";
      }
      $out .= ">";

      if ($item['link']) {
        $out .= $this->html->link($item['text'], $item['link']); 
      } elseif ($item['type'] == 'text') {
        $out .= "<span>".$item['text']."</span>";
      } else {
        $out .= $item['text'];
      }
      
      if(isset($item['submenu']))
        $out .= $this->_getMenu($item['submenu']);

      $out .= "</li>\n";
    }
    $out .= "</ul>\n";
    return $out;
  }

  function getMainMenu($data) {
    $data = am(array('id' => 'submenu', 'title' => 'Main Menu', 'active' => false), $data);

    // Get current url with action. If the default action 'index' is missing,
    // its adds it to the url
    $this->_url = $this->here;
    if (strpos($this->_url, 'index') === false && 
      strpos($this->action, 'index') !== false) {
      if ($this->_url[strlen($this->_url)-1] != '/')
        $this->_url .= '/';
      $this->_url .= 'index';
    }

    return $this->output($this->_getMenu($data));
  }
}
?>
