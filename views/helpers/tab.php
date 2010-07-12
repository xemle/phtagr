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
class TabHelper extends AppHelper
{
  var $helpers = array('Html');

  function menu($items, $prefix = 'tab') {
    $out = "<ul class=\"tab\">\n";
    foreach ($items as $id => $item) {
      if (is_string($item)) {
        $item = array('name' => $item);
      }
      $item = am(array('name' => false, 'active' => false), $item);
      $attributes = array();
      $attributes['id'] = "$prefix-header-$id";
      if ($item['active']) {
        $attributes['class'] = ' tabActive';
      }
      $attributes['onclick'] = "activateTab($id, '$prefix')";

      $text = "<a name=\"$prefix-name-$id\">{$item['name']}</a>";
      $out .= $this->Html->tag('li', $text, $attributes);
    }
    $out .= "</ul>\n";
    return $out;
  }

  function open($id, $active = false, $prefix = 'tab') {
    $class = "tabContent";
    if (!$active) {
      $class .= " tabHidden";
    }
    $out = "<div class=\"$class\" id=\"$prefix-content-$id\">";
    return $out;
  }
  
  function close() {
    $out = "</div>\n";
    return $out;
  }
}

?>
