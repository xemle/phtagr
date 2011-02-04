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
class AutocompleteHelper extends AppHelper
{
  var $helpers = array('Html', 'Form', 'Js');

  function autoComplete($fieldId, $url, $options = array()) {
    $names = explode('.', $fieldId);
    foreach ($names as $i => $name) {
      $names[$i] = Inflector::camelize($name);
    }
    $id = ':input[id=' . implode('', $names) . ']';
    $jsOptions = $this->Js->object($options);
    $out = "(function($) {
$('$id').cakeAutoComplete('$fieldId', '" . Router::url($url) . "', $jsOptions);
})(jQuery)";
    return $this->Html->scriptBlock($out);
  }
}
?>
