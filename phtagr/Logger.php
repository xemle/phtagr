<?php

include_once("$phtagr_lib/Database.php");

define("LOG_INFO", 3);
define("LOG_WARN", 2);
define("LOG_DEBUG", 1);
define("LOG_TRACE", 0);
define("LOG_ERR", -1);
define("LOG_FATAL", -2);

define("LOG_CONSOLE", 1);
define("LOG_BUF", 2);
define("LOG_FILE", 3);
define("LOG_HTML", 4);
define("LOG_DB", 5);

class Logger extends Base {

var $_level;
var $_file;
var $_filename;
var $_buf;
var $_lines;
var $_enabled;

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

function set_level($level)
{
  if ($level >= LOG_FATAL && $level <= LOG_INFO)
    $this->_level=$level;
}

function get_level()
{
  return $this->_level;
}

function enable()
{
  $this->_enabled=true;
}

function disable()
{
  $this->_enabled=false;
}

function is_enabled()
{
  return $this->_enabled;
}

function set_type($type, $filename)
{
  if ($type<LOG_CONSOLE || $type>LOG_DB)
    return;
  if ($type==LOG_FILE)
  {
    if ($filename=='')
      return;
    $this->_filename=$filename;
    $this->_open_file();
  }
  $this->_type=$type;
}

function get_type()
{
  return $this->_type;
}

function get_buf()
{
  return $this->_buf;
}

function get_lines()
{
  return $this->_lines;
}

function flush()
{
  
}

function stop()
{
  if ($this->_type==LOG_FILE)
  {
    if ($this->_file!=null)
      fclose($this->_file);
  }
}

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

  $time=date("Y-d-m H:i:s");
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
    $log=array('time' => time(), 'level' => $slevel,
               'image' => $image, 'user' => $user,
               'file' => $file, 'line' => $line,
               'msg' => $msg);
    array_push($this->_buf, $log);
  }
  elseif ($this->_type==LOG_DB)
  {
    $sfile=mysql_escape_string($file);
    $smsg=mysql_escape_string($msg);
    $sql="INSERT INTO $db->logs (time, level, image, user, file, line, msg)
          VALUES (NOW(), $level, $image, $user, '$sfile', '$msg')";
    $db->query($sql);
  }
}

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
  $this->_file=fopen($this->_filename, 'a');
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
