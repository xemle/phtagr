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

/** This helper handles the user options which is intialized in app_controller::beforeRender() */
class OptionHelper extends AppHelper
{
  var $options = null;

  /**
   * Intitialize the options
   */
  function beforeRender($viewFile) {
    if (isset($this->request->params['options'])) {
      $this->options = $this->request->params['options'];
    }
  }

  /**
   * Return a option value or a default value if the option does not exist
   *
   * @param name Option name
   * @param default Optional default value
   * @return Option value of default
   */
  function get($name, $default = null) {
    if (isset($this->options[$name])) {
      return $this->options[$name];
    } else {
      return $default;
    }
  }
}
?>
