<?php
/** This file handles the image data from a browser request. It checks the
 * acces rights of the image and copies the binary image data to html output.
 * If an error occurs exit silently. */

include "$phtagr_prefix/User.php";
include "$phtagr_prefix/Sql.php";
include "$phtagr_prefix/Image.php";

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
  case 'high':
  case 'full':
    break;
  default:
    bad_request();
}

/** Check the database connection */
$db=new Sql();
if (!$db->connect())
{
  internal_error();
}

session_start();

$user=new User();
$user->check_session();

$pref=$db->read_pref($user->get_userid());

$img=new Image($_REQUEST['id']);
if (!$img)
{
  internal_error();
}

$fn='';
switch ($type)
{
  case 'mini':
    if ($user->can_preview(&$img))
      $fn=$img->create_mini();
    else
      unauthorized();
    break;
  case 'thumb':
    if ($user->can_preview(&$img))
      $fn=$img->create_thumbnail();
    else
      unauthorized();
    break;
  case 'preview':
    if ($user->can_preview(&$img))
      $fn=$img->create_preview();
    else
      unauthorized();
    break;
  case 'high':
    if ($user->can_preview(&$img))
      $fn=$img->create_preview();
    else
      unauthorized();
    break;
  case 'full':
    if ($user->can_fullsize(&$img))
      $fn=$img->get_filename();
    else
      unauthorized();
    break;
  default:
    bad_request();
}

if (!file_exists($fn))
{
  internal_error();
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
