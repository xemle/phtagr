<?php
/** This file handles the image data from a browser request. It checks the
 * acces rights of the image and copies the binary image data to html output.
 * If an error occurs exit silently. */

session_start();

include "$phtagr_lib/User.php";
include "$phtagr_lib/Sql.php";
include "$phtagr_lib/Config.php";
include "$phtagr_lib/Thumbnail.php";


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

// Check user login without refresh the cookie
$user=new User();
$user->check_session(false);

$conf=new Config($user->get_id());

$img=new Thumbnail($_REQUEST['id']);
if (!$img)
{
  internal_error();
}

$fn='';
switch ($type)
{
  case 'mini':
    if ($img->can_preview(&$user))
      $fn=$img->get_filename_mini();
    else
      unauthorized();
    break;
  case 'thumb':
    if ($img->can_preview(&$user))
      $fn=$img->get_filename_thumb();
    else
      unauthorized();
    break;
  case 'preview':
    if ($img->can_preview(&$user))
      $fn=$img->get_filename_preview();
    else
      unauthorized();
    break;
  case 'high':
    if ($img->can_preview(&$user))
      $fn=$img->get_filename_hight();
    else
      unauthorized();
    break;
  case 'full':
    if ($img->can_fullsize(&$user))
      $fn=$img->get_filename();
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
    (strtotime($headers['if-modified-since']) == $img->get_synced(true))) 
{
  // Client's cache IS current, so we just respond '304 Not Modified'.
  header('Last-Modified: '.
    gmdate('D, d M Y H:i:s', $img->get_synced(true)).' GMT', true, 304);
  // Allow further caching for 30 days
  header('Cache-Control: max-age=2592000, must-revalidate');
  exit;
}

// Allow only image caching if cookie is avaiable
if ($_SESSION['withcookie'])
{
  // Allow caching
  header('Last-Modified: '.gmdate('D, d M Y H:i:s', 
    $img->get_synced(true)).' GMT', true, 200);
  header('Cache-Control: max-age=2592000');
} else {
  header('Cache-Control: max-age=0');
}

switch ($type)
{
  case 'mini':
    $img->create_mini();
    break;
  case 'thumb':
    $img->create_thumb();
    break;
  case 'preview':
    $img->create_preview();
    break;
  case 'high':
    $img->create_high();
    break;
  case 'full':
    break;
  default:
    bad_request();
}

if (!file_exists($fn))
{
  not_found();        
} else {
  header('Content-Type: image/jpg');
  /* Do not include the filesize of the content. I had bad experience with it 
  */
  header('Content-Length: '.filesize($fn));
  header("Content-Transfer-Encoding: binary");
  //print file_get_contents($fn);
  @readfile($fn) or internal_error();
  //echo "\n";
  exit;
}

?>
