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
    $out = "<ul>\n";
    foreach ($items as $id => $item) {
      if (is_string($item)) {
        $item = array('name' => $item);
      }
      $item = am(array('name' => false, 'id' => "$prefix-$id"), $item);
      $attributes = array('escape' => false);
      $out .= $this->Html->tag('li', 
        $this->Html->link($item['name'], '#' . $item['id']), $attributes);
    }
    $out .= "</ul>\n";
    return $out;
  }

  function open($id, $prefix = 'tab') {
    $out = "<div id=\"$prefix-$id\">";
    return $out;
  }
  
  function close() {
    $out = "</div>\n";
    return $out;
  }
}

?>
