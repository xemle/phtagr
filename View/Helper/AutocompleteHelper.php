<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
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
