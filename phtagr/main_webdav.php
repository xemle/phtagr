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


global $phtagr_lib;
require_once("$phtagr_lib/WebdavServer.php");
require_once("$phtagr_lib/Logger.php");
require_once("$phtagr_lib/Database.php");
require_once("$phtagr_lib/Config.php");
require_once("$phtagr_lib/User.php");

$db=new Database();
$db->connect();
if ($db->is_connected())
{
  $conf=new Config(0);
  $log=new Logger();
  if ($conf->get('log.enabled', 0)==1)
  { 
    $log->set_level($conf->get('log.level', L_INFO));
    $log->set_type($conf->get('log.type', LOG_DB),
      $conf->get('log.filename', ''));
    // drop old messages
    $log->drop_db_logs(3600*60, 3600*7, 3600, 1800, 3600*7, 3600*3);
    $log->enable();
  }
  $user=new User(-1, true);
  $user->check_session();
}
else
{ 
  $conf=new Config(0);
  $log=new Logger(LOG_SESSION, L_WARN);
  $log->enable();
  $user=new User();
}

if (!function_exists('apache_request_headers'))
{
  $log->err("apache_request_headers() does not exists. Maybe it is caused by running apache's suphp module");
  exit;
}

if (empty($_SERVER['PATH_INFO']) && !empty($_SERVER['ORIG_PATH_INFO']))
{
  $log->trace("Set PATH_INFO to ".$_SERVER['ORIG_PATH_INFO']);
  $_SERVER['PATH_INFO']=$_SERVER['ORIG_PATH_INFO'];
}

$server=new WebdavServer();
//$server->base="/var/www/phtagr";
ob_start();
#$path=$_SERVER['REQUEST_URI'];
$path=$_SERVER["DOCUMENT_ROOT"] . ereg_replace("webdav.php",".",$_SERVER['SCRIPT_NAME']);

$log->warn($_SERVER['REQUEST_METHOD']." ".$_SERVER['REQUEST_URI']);
$server->ServeRequest($path);
while (@ob_end_flush());
?>
