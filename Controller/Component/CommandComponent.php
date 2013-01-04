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

class CommandComponent extends Component {

  var $controller = null;
  var $output = array();
  var $lastCommand = '';
  var $redirectError = false;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  public function escapeArgs($args) {
    $escaped = '';
    foreach ($args as $param => $value) {
      if (!is_numeric($param)) {
        $escaped .= ' ' . $param;
      } elseif (strlen($value) == 0) {
        continue;
      }
      $escaped .= ' ' . escapeshellarg($value);
    }
    return $escaped;
  }

  public function run($bin, $args) {
    if (!is_executable($bin)) {
      Logger::err("Command is not exectuable: '$bin'");
      return 1;
    }
    if (is_array($args)) {
      $args = $this->escapeArgs($args);
    }
    $output = array();
    $result = -1;
    $this->lastCommand = $bin . ' ' . $args;
    if ($this->redirectError) {
      $this->lastCommand .= ' 2>&1';
    }
    $t1 = microtime(true);
    exec($this->lastCommand, $output, $result);
    $t2 = microtime(true);
    $this->output = $output;
    Logger::debug("Command '{$this->lastCommand}' returned $result and required ".round($t2-$t1, 4)."ms");
    return $result;
  }
}

?>
