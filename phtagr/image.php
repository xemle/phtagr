<?php
/** This file handles the image data from a browser request. It checks the
 * acces rights of the image and copies the binary image data to html output.
 * If an error occurs exit silently. */

$prefix='./phtagr';
include "$prefix/User.php";
include "$prefix/Sql.php";
include "$prefix/Image.php";

// Check the parameter of HTML request 
if (!isset($_REQUEST['id']) || !isset($_REQUEST['type']))
{
  exit;
}

// check ID as a positiv value
$id=intval($_REQUEST['id']);
if ($id<=0)
{
  exit;
}

// check image type
$type=$_REQUEST['type'];
switch ($type)
{
  case 'mini':
  case 'thumb':
  case 'preview':
  case 'high':
  case 'full':
    break;
  default:
    exit;
}

/** Check the database connection */
$db=new Sql();
if (!$db->connect() || !isset($_REQUEST['id']) || !isset($_REQUEST['type']))
{
  exit;
}

session_start();

$user=new User();
$user->check_session();

$pref=$db->read_pref($user->get_userid());

$img=new Image($_REQUEST['id']);
if (!$img)
{
  return;
}

$fn='';
switch ($type)
{
  case 'mini':
    if ($user->can_preview(&$img))
      $fn=$img->create_mini();
    break;
  case 'thumb':
    if ($user->can_preview(&$img))
      $fn=$img->create_thumbnail();
    break;
  case 'preview':
    if ($user->can_preview(&$img))
      $fn=$img->create_preview();
    break;
  case 'high':
    if ($user->can_preview(&$img))
      $fn=$img->create_preview();
    break;
  case 'full':
    if ($user->can_fullsize(&$img))
      $fn=$img->get_filename();
    break;
  default:
    exit;
}

if (!file_exists($fn))
{
  exit;
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
if (isset($headers['if-modified-since']) && (strtotime($headers['if-modified-since']) == filemtime($fn))) {
  // Client's cache IS current, so we just respond '304 Not Modified'.
  header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($fn)).' GMT', true, 304);
  // Allow caching for 30 days
  header('Cache-Control: max-age=2592000, must-revalidate');
} else {
  // Image not cached or cache outdated, we respond '200 OK' and output the image.
  header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($fn)).' GMT', true, 200);
  header('Content-Length: '.filesize($fn));
  header('Content-Type: image/jpg');
  // Allow caching
  header('Cache-Control: max-age=2592000, must-revalidate');
  print file_get_contents($fn);
}

?>
