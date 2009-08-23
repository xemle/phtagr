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

/** This helper handles the user options which is intialized in app_controller::beforeRender() */
class OptionHelper extends AppHelper
{
  var $options = null;

  /** Intitialize the options */
  function beforeRender() {
    if (isset($this->params['options'])) {
      $this->options = $this->params['options'];
    }
  }

  /** Return a option value or a default value if the option does not exist
    @param name Option name
    @param default Optional default value
    @return Option value of default */
  function get($name, $default = null) {
    if (isset($this->options[$name])) {
      return $this->options[$name];
    } else {
      return $default;
    }
  }
}
?>
