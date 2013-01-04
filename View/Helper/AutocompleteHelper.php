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
class AutocompleteHelper extends AppHelper
{
  var $helpers = array('Html', 'Form', 'Js');

  function _getInputId($input) {
    $names = explode('.', $input);
    foreach ($names as $i => $name) {
      $names[$i] = Inflector::camelize($name);
    }
    return implode('', $names);
  }

  /**
   * Use phtagr's autocomplete for cake
   *
   * @param input Input field
   * @param url Autocomplete url
   * @param options
   *  - targetField - Field if input and autocomplete field differs
   *  - split - Set true for comma separated autocomplete
   */
  function autoComplete($input, $url, $options = array()) {
    $targetField = $input;
    if (isset($options['targetField'])) {
      $targetField = $options['targetField'];
      unset($options['targetField']);
    }
    $inputId = $this->_getInputId($input);
    $jsOptions = $this->Js->object($options);
    $out = "(function($) {
$(':input[id=$inputId]').cakeAutoComplete('$targetField', '" . Router::url($url) . "', $jsOptions);
})(jQuery)";
    return $this->Html->scriptBlock($out);
  }
}
?>
