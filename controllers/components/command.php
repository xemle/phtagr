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

class CommandComponent extends Object {

  var $controller = null;
  var $output = array();
  var $lastCommand = '';
  var $redirectError = false;

  function initialize(&$controller) {
    $this->controller =& $controller;
  }

  function escapeArgs($args) {
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

  function run($bin, $args) {
    if (!is_executable($bin)) {
      Logger::err("Invalid program $bin");
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
    $t1 = getMicrotime();
    exec($this->lastCommand, &$output, &$result);
    $t2 = getMicrotime();
    $this->output = $output;
    Logger::debug("Command '{$this->lastCommand}' returned $result and required ".round($t2-$t1, 4)."ms");
    return $result;
  }
}

?>
