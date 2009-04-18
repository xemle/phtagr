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

class SysUtilsComponent extends Object {

  var $controller = null;

  function initialize(&$controller) {
    $this->controller = $controller;
  }

  function slashify($path)
  {
    $len = strlen($path);
    if ($len > 0 && $path[$len-1] != '/')
      $path .= '/';
    return $path;
  }

  function unslashify($path)
  {
    $len = strlen($path);
    while ($len > 0 && $path[$len-1] == '/') {
      $len--;
    }
    return substr($path, 0, $len);
  }

  function mergepath($parent, $child)
  {
    if (strlen($child) == 0) {
      return $this->slashify($parent);
    } else {
      if ($child[0] == '/')
        return $this->unslashify($parent).$child;
      else
        return $this->slashify($parent).$child;
    }
  }

}

?>
