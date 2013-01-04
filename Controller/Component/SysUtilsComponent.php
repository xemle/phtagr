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

class SysUtilsComponent extends Component {

  var $controller = null;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  public function slashify($path)
  {
    $len = strlen($path);
    if ($len > 0 && $path[$len-1] != '/')
      $path .= '/';
    return $path;
  }

  public function unslashify($path)
  {
    $len = strlen($path);
    while ($len > 0 && $path[$len-1] == '/') {
      $len--;
    }
    return substr($path, 0, $len);
  }

  public function mergepath($parent, $child)
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
