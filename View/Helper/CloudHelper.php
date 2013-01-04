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
class CloudHelper extends AppHelper
{
  var $helpers = array('Html');

  /**
   * Prints a tag cloud
   *
   * @param data Cloud data
   * @param urlPrefix Prefix of URL
   * @return Cloud html
   */
  function cloud($data, $urlPrefix = false) {
    if (count($data) == 0) {
      return;
    }
    $max = max($data);
    $min = min($data);
    $sizes = array('xxs', 'xs', 's', 'm', 'l', 'xl', 'xxl');
    $width = min($max - $min, 7);

    $out = '';
    ksort($data);
    foreach($data as $name => $hits) {
      if ($max - $min > 0) {
        $percentage = ($hits - $min) / ($max - $min);
      } else {
        $percentage = 1;
      }
      $index = max(0, ceil($percentage * $width + (7 - $width) / 2) - 1);
      $out .= $this->Html->link($name, $urlPrefix.$name, array('class' => $sizes[$index]));
    }

    return $out;
  }
}
?>