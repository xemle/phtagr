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

define("L_FATAL",  -3);
define("L_ERR",    -2);
define("L_WARN",   -1);
define("L_NOTICE",  0);
define("L_INFO",    1);
define("L_VERBOSE", 2);
define("L_DEBUG",   3);
define("L_TRACE",   4);

define("LOG_BUF",     0);
define("LOG_SESSION", 1);
define("LOG_HTML",    2);
define("LOG_CONSOLE", 3);
define("LOG_FILE",    4);

/** @class Logger
  Class to log messages with different backends. Available backends are
LOG_CONSOLE which prints message directly to the console. LOG_BUF which saves
the log mesages in a internal buffer. LOG_FILE which dumps the log message to a
file, LOG_HTML which logs formats the log message for HTML output. And finally
LOG_DB which writes the logmessage to the database */
class Logger extends Object {

  var $_level = L_INFO;
  var $_type = LOG_BUF;
  var $_file = false;
  var $_filename = false;
  var $_buf = array();
  var $_lines = array();
  var $_enabled = false;

  function &getInstance() {
    static $instance = null;
    if (!$instance) {
      $instance =& new Logger();
      $instance->__loadBootstrap();
    }
    return $instance;
  }

  function __loadBootstrap() {
    $this->setLevel(Configure::read('Logger.level'));
    $this->setType(LOG_FILE, Configure::read('Logger.file'));

    if (Configure::read('Logger.enable') === true) {
      $this->enable();
    }
  }

  /** Sets the new log threshold 
    @param level new log threshold */
  function setLevel($level) {
    if ($level >= L_FATAL && $level <= L_TRACE) {
      $this->_level=$level;
    }
  }

  /** @return Returns the current log threshold */
  function getLevel() {
    return $this->_level;
  }

  /** Enables the logger. By default, the loger is disabled 
    @return True, if the logger could be enabled  */
  function enable() {
    //global $db;
    if ($this->_enabled) {
      return true;
    }

    if ($this->_type == LOG_FILE && !$this->_openFile()) {
      return false;
    }
    if ($this->_type==LOG_SESSION) { 
      if (!isset($_SESSION)) {
        return false;
      }
      $_SESSION['log_buf']=array();
    }

    $this->_enabled=true;
    return true;
  }

  /** Disables the logger */
  function disable() {
    if (!$this->_enabled) {
      return;
    }

    $this->_enabled = false;
    if ($this->_type == LOG_FILE) {
      $this->_closeFile();
    }
  }

  /** @return returns true if the logger is enables */
  function isEnabled() {
    return $this->_enabled;
  }

  /** Sets a new logger backend
    @param type logging backend type
    @param filename Filename if backend type is LOG_FILE 
    @note If the logger is enabled, it will be disabled and enabled again to
  invoke backend finalizations and initialisations */
  function setType($type, $filename = false) {
    if ($type < LOG_BUF || $type > LOG_FILE) {
      return;
    }

    // parameter checks
    if ($type == LOG_FILE) {
      if (!$filename || !is_writeable(dirname($filename))) {
        return;
      }
      $this->_filename = $filename;
    }

    // restarting
    $isRunning = $this->_enabled;
    if ($isRunning) {
      $this->disable();
    }

    $this->_type = $type;

    if ($isRunning) {
      $this->enable();
    }
  }

  /** @return Returns current backend type */
  function getType() {
    return $this->_type;
  }

  /** @return Returns the internal log buffer, if LOG_BUF is used */
  function getBuffer() {
    if ($this->_type == LOG_BUF)
      return $this->_buf;
    if ($this->_type == LOG_SESSION && isset($_SESSION['log_buf']))
      return $_SESSION['log_buf'];
    return false;
  }

  /** @return Returns the lines if LOG_HTML is used */
  function getLines() {
    if ($this->_type == LOG_HTML) {
      return $this->_lines;
    } 
    return false;
  }

  /** Generates the log message and dispatch the logs to the backends.
    @param level Log level. If the level lower than the current threshold (but no
    error or fatal error), the function returns immediately 
    @param msg Log message */
  function _write($level, $msg) {
    if (!$this->_enabled || 
      ($level < $this->_level && $level >= 0))
      return;

    switch ($level) {
      case L_FATAL:   $slevel = 'FATAL';    break;
      case L_ERR:     $slevel = 'ERR';      break;
      case L_WARN:    $slevel = 'WARN';     break;
      case L_NOTICE:  $slevel = 'NOTICE';   break;
      case L_INFO:    $slevel = 'INFO';     break;
      case L_VERBOSE: $slevel = 'VERBOSE';  break;
      case L_DEBUG:   $slevel = 'DEBUG';    break;
      case L_TRACE:   $slevel = 'TRACE';    break;
      default: $slevel = 'UNDEF';
    }

    $bt = @debug_backtrace();
    $depth = 2;

    $file = $bt[$depth]['file'];
    $file = substr($file, strrpos($file, DS)+1);
    if (isset($bt[$depth+1]['class'])) {
      $file .= "@".$bt[$depth+1]['class'];
    }
    if (isset($bt[$depth+1]['function'])) {
      $file .= "::".$bt[$depth+1]['function']."()";
    }

    $line = $bt[$depth]['line'];

    if (!isset($file)) {
      $file="(no file)";
    }
    if (!isset($line)) {
      $line = 0;
    }

    if (is_array($msg) || is_object($msg)) {
      $msg = print_r($msg, true);
    }

    $now = time();
    $time = date("Y-m-d H:i:s", $now);
    if ($this->_type == LOG_CONSOLE || $this->_type == LOG_FILE) {
      $space = str_repeat(' ', max(0, 7-strlen($slevel)));
      $line=sprintf("%s [%s]%s %s:%d %s\n",
        $time, $slevel, $space, $file, $line, $msg);
      if ($this->_type == LOG_CONSOLE) {
        echo $line;
      } else {
        $this->_logFile($line);
      }
    } elseif ($this->_type == LOG_HTML) {
      $this->_logHtml($time, $slevel, $imageid, $userid, $file, $line, $msg);
    } elseif ($this->_type == LOG_BUF || $this->_type == LOG_SESSION) {
      $log=array('time' => $now, 'level' => $slevel,
                 'file' => $file, 'line' => $line,
                 'msg' => $msg);
      if ($this->_type == LOG_BUF) {
        $this->_buf[] = $log;
      }
      if ($this->_type == LOG_SESSION && isset($_SESSION['log_buf'])) {
        $_SESSION['log_buf'][] = $log;
      }
    }
  }

  /** Add span block around the level message */
  function _logHtml($time, $level, $image, $user, $file, $lineno, $msg) {
    $line="<span class=\"time\">$time </span>"
      ."<span class=\"$level\">[$level] </span>";

    $line.="<span class=\"file\">$file:$lineno</span>";

    $msg=htmlentities($msg, ENT_QUOTES, "UTF-8");
    $line.="<span class=\"msg\">$msg</span><br />";
    $this->_lines[] = $line;
  }

  function _openFile() {
    if ($this->_filename=='') {
      return false;
    }

    $this->_file = fopen($this->_filename, 'a');
    if (!$this->_file) {
      return false;
      $this->_file=null;
    }
    return true;
  }

  function _closeFile() {
    if ($this->_file != null) {
      fclose($this->_file);
    }
  }

  function _logFile($line) {
    if ($this->_file!=null) {
      fwrite($this->_file, $line);
    }
  }

  function fatal($msg) {
    $logger = self::getInstance();
    $logger->write($msg, L_FATAL);
  }

  function err($msg) {
    $logger = self::getInstance();
    $logger->write($msg, L_ERR);
  }

  function warn($msg) {
    $logger = self::getInstance();
    $logger->write($msg, L_WARN);
  }

  function notice($msg) {
    $logger = self::getInstance();
    $logger->write($msg, L_NOTICE);
  }

  function info($msg) {
    $logger = self::getInstance();
    $logger->write($msg, L_INFO);
  }

  function verbose($msg) {
    $logger = self::getInstance();
    $logger->write($msg, L_VERBOSE);
  }

  function debug($msg) {
    $logger = self::getInstance();
    $logger->write($msg, L_DEBUG);
  }

  function trace($msg) {
    $logger = self::getInstance();
    $logger->write($msg, L_TRACE);
  }

  function write($msg, $level=L_INFO) {
    $this->_write($level, $msg);
  }
  
  function bt() {
    $steps = @debug_backtrace();
    array_pop($steps);
    $trace = array();
    foreach ($steps as $step) {
      $step = am(array('file' => 'null', 'line' => '0', 'function' => ''), $step);
      $trace[] = $step['file'].':'.$step['line'].' '.$step['function'].'()';
    }
    $this->debug($trace);
  }
}

?>
