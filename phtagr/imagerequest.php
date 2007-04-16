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
/** This file handles the image data from a browser request. It checks the
 * acces rights of the image and copies the binary image data to html output.
 * If an error occurs exit silently. */

$time_start=microtime();
session_start();

include_once("$phtagr_lib/User.php");
include_once("$phtagr_lib/Database.php");
include_once("$phtagr_lib/Config.php");
include_once("$phtagr_lib/Logger.php");
include_once("$phtagr_lib/Image.php");


function unauthorized()
{
  header('HTTP/1.1 401 Unauthorized');
  echo "You are not authorized to view this picture. Please login\n";
  exit;
}

function not_found()
{
  header('HTTP/1.1 404 Not Found');
  echo "The image was not found\n";
  exit;
}

function bad_request()
{
  header('HTTP/1.1 400 Bad Request');
  echo "Your request is wrong\n";
  exit;
}

function internal_error()
{
  header('HTTP/1.1 500 Internal Server Error');
  echo "Something went wrong\n";
  exit;
}

// Check the parameter of HTML request 
if (!isset($_REQUEST['id']) || !isset($_REQUEST['type']))
{
  bad_request();
}

// check ID as a positiv value
$id=intval($_REQUEST['id']);
if ($id<=0)
{
  bad_request();
}

// check image type
$type=$_REQUEST['type'];
switch ($type)
{
  case 'mini':
  case 'thumb':
  case 'preview':
  case 'vpreview':
  case 'high':
  case 'full':
    break;
  default:
    bad_request();
}

/** Check the database connection */
$db=new Database();
if (!$db->connect())
{
  internal_error();
}

// Check user login without refresh the cookie
$user=new User();
$user->check_session(false);

$conf=new Config($user->get_id());

$log=new Logger();
if ($conf->get('log.enabled', 0)==1)
{
  $log->set_level($conf->get('log.level', LOG_INFO));
  $log->set_type($conf->get('log.type', LOG_DB),
    $conf->get('log.filename', ''));
  $log->enable();
}

$img=new Image($_REQUEST['id']);
if (!$img)
{
  internal_error();
}

$previewer=$img->get_preview_handler();
if ($previewer==null)
{
  internal_error();
}

$fn='';
switch ($type)
{
  case 'mini':
    if ($img->can_preview(&$user))
      $fn=$previewer->get_filename_mini();
    else
      unauthorized();
    break;
  case 'thumb':
    if ($img->can_preview(&$user))
      $fn=$previewer->get_filename_thumb();
    else
      unauthorized();
    break;
  case 'preview':
    if ($img->can_preview(&$user))
      $fn=$previewer->get_filename_preview();
    else
      unauthorized();
    break;
  case 'vpreview':
    if ($img->can_preview(&$user))
      $fn=$previewer->get_filename_preview_movie();
    else
      unauthorized();
    break;
  case 'high':
    if ($img->can_preview(&$user))
      $fn=$previewer->get_filename_high();
    else
      unauthorized();
    break;
  case 'full':
    if ($img->can_fullsize(&$user))
      $fn=$previewer->get_filename();
    else
      unauthorized();
    break;
  default:
    bad_request();
}

// Getting headers sent by the client. Convert header to lower case since it is
// case insensitive 
if (function_exists('apache_request_headers'))
{
  $headers = apache_request_headers();
  foreach($headers as $h=>$v)
    $headers[strtolower($h)]=$v;
}
else
{
  $headers=array();
  foreach($_SERVER as $h=>$v)
  {
    if(ereg('HTTP_(.+)',$h,$hp))
      $headers[strtolower($hp[1])]=$v;
  }
}

// Checking if the client is validating his cache and if it is current.
if ($_SESSION['withcookie'] && isset($headers['if-modified-since']) && 
    (strtotime($headers['if-modified-since']) == $img->get_modified(true))) 
{
  // Client's cache IS current, so we just respond '304 Not Modified'.
  header('Last-Modified: '.
    gmdate('D, d M Y H:i:s', $img->get_modified(true)).' GMT', true, 304);
  // Allow further caching for 30 days
  header('Cache-Control: max-age=2592000, must-revalidate');
  exit;
}

// Allow only image caching if cookie is avaiable
if ($_SESSION['withcookie'])
{
  // Allow caching
  header('Last-Modified: '.gmdate('D, d M Y H:i:s', 
    $img->get_modified(true)).' GMT', true, 200);
  header('Cache-Control: max-age=2592000');
} else {
  header('Cache-Control: max-age=0');
}

switch ($type)
{
  case 'mini':
    $previewer->create_mini();
    break;
  case 'thumb':
    $previewer->create_thumb();
    break;
  case 'preview':
    $previewer->create_preview();
    break;
  case 'vpreview':
    $previewer->create_preview_movie();
    break;
  case 'high':
    $previewer->create_high();
    break;
  case 'full':
    break;
  default:
    bad_request();
}

$gentime=sprintf("%.3f", abs(microtime()-$time_start));
$log->warn("Image request: $type. Runs for $gentime seconds.", $img->get_id(), $user->get_id());
$log->warn("Image request: $fn");
$log->disable();

if (!file_exists($fn))
{
  not_found();        
} else {
  if ($type!="vpreview")
  {
    header('Content-Type: image/jpg');
  }
  else
  {
    $name=$img->get_name();
    $name=substr($name, 0, strrpos($name, ".")+1)."flv";
    header('Content-Type: video/x-flv');
    header("Content-Disposition: ".
      (!strpos($HTTP_USER_AGENT,"MSIE 5.5")?"attachment; ":"").
      "filename=$name");
  }
  /* Do not include the filesize of the content. I had bad experience with it 
  */
  header("Content-Transfer-Encoding: binary");
  header('Content-Length: '.filesize($fn));
  readfile($fn) or internal_error();
  exit;
}

?>
