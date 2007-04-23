<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006,2007 Sebastian Felis, sebastian@phtagr.org
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

include_once("$phtagr_lib/Database.php");

define("LOG_INFO", 3);
define("LOG_WARN", 2);
define("LOG_DEBUG", 1);
define("LOG_TRACE", 0);
define("LOG_ERR", -1);
define("LOG_FATAL", -2);

define("LOG_BUF", 1);
define("LOG_CONSOLE", 2);
define("LOG_FILE", 3);
define("LOG_HTML", 4);
define("LOG_DB", 5);

/** @class Logger
  Class to log messages with different backends. Available backends are
LOG_CONSOLE which prints message directly to the console. LOG_BUF which saves
the log mesages in a internal buffer. LOG_FILE which dumps the log message to a
file, LOG_HTML which logs formats the log message for HTML output. And finally
LOG_DB which writes the logmessage to the database */
class Logger extends Base {

var $_level;
var $_file;
var $_filename;
var $_buf;
var $_lines;
var $_enabled;

/** Initialize the logger
  @param type Type of logging. Possible values are LOG_CONSOLE, LOG_BUF,
LOG_FILE, LOG_HTML, or LOG_DB. Default is LOG_BUF.  @param level Log level
threshold. Default is LOG_INFO
  @param filename Optional filename. Default is an empty string i
  @note By the default, the logger is disabled and has to be enabled by Logger::enable() 
  @note If LOG_DB is used, the global variable $db must be set. */
function Logger($type=LOG_BUF, $level=LOG_INFO, $filename="")
{
  $this->_level=LOG_INFO;
  $this->_type=LOG_BUF;
  $this->_enabled=false;
  $this->_buf=array();
  $this->_lines=array();

  $this->set_level($level);
  $this->set_type($type, $filename);
}

/** Sets the new log threshold 
  @param level new log threshold */
function set_level($level)
{
  if ($level >= LOG_FATAL && $level <= LOG_INFO)
    $this->_level=$level;
}

/** @return Returns the current log threshold */
function get_level()
{
  return $this->_level;
}

/** Enables the logger. By default, the loger is disabled 
  @return True, if the logger could be enabled  */
function enable()
{
  global $db;
  if ($this->_enabled)
    return true;

  if ($this->_type==LOG_FILE && !$this->_open_file())
    return false;
  if ($this->_type==LOG_DB && !$db->is_connected())
    return false;

  $this->_enabled=true;
  return true;
}

/** Disables the logger */
function disable()
{
  if (!$this->_enabled)
    return;

  $this->_enabled=false;
  if ($this->_type==LOG_FILE)
    $this->_close_file();
}

/** @return returns true if the logger is enables */
function is_enabled()
{
  return $this->_enabled;
}

/** Sets a new logger backend
  @param type logging backend type
  @param filename Filename if backend type is LOG_FILE 
  @note If the logger is enabled, it will be disabled and enabled again to
invoke backend finalizations and initialisations */
function set_type($type, $filename="")
{
  if ($type<LOG_CONSOLE || $type>LOG_DB)
    return;

  // parameter checks
  if ($type==LOG_FILE)
  {
    if ($filename=='')
      return;
    $this->_filename=$filename;
  }

  // restarting
  $is_running=$this->_enabled;
  if ($is_running)
    $this->disable();

  $this->_type=$type;

  if ($is_running)
    $this->enable();
}

/** @return Returns current backend type */
function get_type()
{
  return $this->_type;
}

/** @return Returns the internal log buffer, if LOG_BUF is used */
function get_buf()
{
  if ($this->_type==LOG_BUF)
    return $this->_buf;
  return null;
}

/** @return Returns the lines if LOG_HTML is used */
function get_lines()
{
  if ($this->_type==LOG_HTML)
    return $this->_lines;
}

/** Generates the log message and dispatch the logs to the backends.
  @param level Log level. If the level lower than the current threshold (but no
error or fatal error), the function returns immediately 
  @param msg Log message
  @param image Image ID if any
  @param user User ID if any */
function _log($level, $msg, $image, $user)
{
  global $db;
  if (!$this->_enabled || 
    ($this->_level > $level && $level > 0))
    return;
  
  if ($level==LOG_FATAL) $slevel="FATAL";
  elseif ($level==LOG_ERR) $slevel="ERR";
  elseif ($level==LOG_TRACE) $slevel="TRACE";
  elseif ($level==LOG_DEBUG) $slevel="DEBUG";
  elseif ($level==LOG_WARN) $slevel="WARN";
  elseif ($level==LOG_INFO) $slevel="INFO";
  else return;
  
  $bt=debug_backtrace();
  $depth=1;
  $file=@$bt[1]['file'];
  $line=@$bt[1]['line'];

  if (!isset($file))
    $file="(no file)";
  if (!isset($line))
    $line=-1;

  $now=time();
  $time=date("Y-d-m H:i:s", $now);
  if ($this->_type==LOG_CONSOLE || $this->_type==LOG_FILE)
  {
    $line=sprintf("%s [%s] i:%d u:%d %s:%d %s\n",
      $time, $slevel, $image, $user, $file, $line, $msg);
    if ($this->_type==LOG_CONSOLE)
      echo $line;
    else
      $this->_log_file($line);
  }
  elseif ($this->_type==LOG_HTML)
  {
    $this->_log_html($time, $slevel, $image, $user, $file, $line, $msg);
  } 
  elseif ($this->_type==LOG_BUF)
  {
    $log=array('time' => $now, 'level' => $slevel,
               'image' => $image, 'user' => $user,
               'file' => $file, 'line' => $line,
               'msg' => $msg);
    array_push($this->_buf, $log);
  }
  elseif ($this->_type==LOG_DB && $db!=null && $db->is_connected())
  {
    $sfile=mysql_escape_string($file);
    $smsg=mysql_escape_string($msg);
    $sql="INSERT INTO $db->logs (time, level, imageid, userid, file, line, message)
          VALUES (NOW(), $level, $image, $user, '$sfile', $line, '$smsg')";
    $db->query($sql);
  }
}

/** Add span block around the level message */
function _log_html($time, $level, $image, $user, $file, $lineno, $msg)
{
  $line="<span class=\"time\">$time </span>"
    ."<span class=\"$level\">[$level] </span>";
  if ($image>0)
    $line.="<span class=\"image\">i:$image </span>";
  if ($user>0)
    $line.="<span class=\"user\">u:$user </span>";

  $line.="<span class=\"file\">$file:$lineno</span>";

  $msg=htmlentities($msg, ENT_QUOTES, "UTF-8");
  $line.="<span class=\"msg\">$msg</span><br />";
  array_push($this->_lines, $line); 
}

function _open_file()
{
  if ($this->_filename=='')
    return;

  $this->_file=@fopen($this->_filename, 'a');
  if (!$this->_file)
    $this->_file=null;
}

function _close_file()
{
  if ($this->_file!=null)
    fclose($this->_file);
}

function _log_file($line)
{
  if ($this->_file!=null)
    fwrite($this->_file, $line);
}

/** Drop older lines according to their level
  @param info Seconds. Log message older than info seconds are dropped.
  @param warn same as info
  @param debug same as info
  @param trace same as info
  @param error same as info
  @param fatal same as info */
function drop_db_logs($info, $warn, $debug, $trace, $error, $fatal)
{
  global $db;
  if ($this->_type!=LOG_DB || $db==null)
    return;

  if (!is_numeric($info) || $info<0) return;
  if (!is_numeric($warn) || $warn<0) return;
  if (!is_numeric($debug) || $debug<0) return;
  if (!is_numeric($trace) || $trace<0) return;
  if (!is_numeric($error) || $error<0) return;
  if (!is_numeric($fatal) || $fatal<0) return;
    
  $now=time();
  $sql="DELETE FROM $db->logs 
        WHERE level=".LOG_INFO." 
          AND time<'".$db->date_unix_to_mysql($now-$info)."'";
  $db->query($sql);
  
  $sql="DELETE FROM $db->logs 
        WHERE level=".LOG_WARN." 
          AND time<'".$db->date_unix_to_mysql($now-$warn)."'";
  $db->query($sql);
  
  $sql="DELETE FROM $db->logs 
        WHERE level=".LOG_DEBUG." 
          AND time<'".$db->date_unix_to_mysql($now-$debug)."'";
  $db->query($sql);
  
  $sql="DELETE FROM $db->logs 
        WHERE level=".LOG_TRACE." 
          AND time<'".$db->date_unix_to_mysql($now-$trace)."'";
  $db->query($sql);
  
  $sql="DELETE FROM $db->logs 
        WHERE level=".LOG_ERR." 
          AND time<'".$db->date_unix_to_mysql($now-$error)."'";
  $db->query($sql);
  
  $sql="DELETE FROM $db->logs 
        WHERE level=".LOG_FATAL." 
          AND time<'".$db->date_unix_to_mysql($now-$fatal)."'";
  $db->query($sql);
}

function fatal($msg, $image=-1, $user=-1)
{
  $this->_log(LOG_FATAL, $msg, $image, $user);
}

function err($msg, $image=-1, $user=-1)
{
  $this->_log(LOG_ERR, $msg, $image, $user);
}

function trace($msg, $image=-1, $user=-1)
{
  $this->_log(LOG_TRACE, $msg, $image, $user);
}

function debug($msg, $image=-1, $user=-1)
{
  $this->_log(LOG_FATAL, $msg, $image, $user);
}

function warn($msg, $image=-1, $user=-1)
{
  $this->_log(LOG_WARN, $msg, $image, $user);
}

function info($msg, $image=-1, $user=-1)
{
  $this->_log(LOG_INFO, $msg, $image, $user);
}

}
