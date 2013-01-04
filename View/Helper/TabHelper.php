<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
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