<?php

include_once("$prefix/Base.php");

/** This class handles the authentication of an user.

The authentication bases on cookies and sessions. A user has to login first.
After a successful login, the server-side session is initiated and a cookie is
set on ther client side for further authentication. The cookie contains
username and password of the account.

If the session is not available, the cookie is checked for username and
password. 

@class User
*/
class User extends Base
{

var $_is_auth; 
var $is_logout; 
var $username; 
var $usernameid;
var $root;

function User()
{
  $this->_clear_data();
}

/** Resets all authorization data */
function _clear_data()
{
  $this->_is_auth=false;
  $this->is_logout=false;
  $this->username='';
  $this->userid='';
}

/** Validates a user with its password
 @return true if the password is valid */
function _check_login($username, $password)
{
  global $db;
  if ($username!='' && $password!='')
  {
    $sql="SELECT id,password 
          FROM $db->user 
          WHERE name='$username'";
    $result=$db->query($sql);
    if ($result)
    {
      $row = mysql_fetch_row($result);
      if ($password == $row[1]) {
        $this->_is_auth=true;
        $this->username=$username;
        $this->userid=$row[0];
        $this->root=$row[2];
        return true;
      }
    }
  }
  return false;
}

/** Return true if the user is allready authenticated and logged in */
function is_auth()
{
  return $this->_is_auth;
}

/** Return true if the current user is an super user and has admin rights */
function is_admin()
{
  if ($this->username=="admin")
    return true;
  return false;
}
/** Return true if the user can browse the filesystem */
function can_browse()
{
  if ($this->username=='admin')
    return true;

  return false;
}

/** Return true if the user can select an image */
function can_select($imageid=-1)
{
  return true;
}

/** Return true if user can edit the image 
  @param imageid Image id. Default -1.*/
function can_edit($imageid=-1)
{
  if ($this->username=='admin')
    return true;

  return false;
}

/** Return true if user can upload a file in general.
  @param size Size of current uploaded file. This size is mandatory. */
function can_upload()
{
  if ($this->username=='admin')
    return true;

  return false;
}

/** Return true if user can upload a file with the given size
  @param size Size of current uploaded file. This size is mandatory. */
function can_upload_size($size=0)
{
  if (!isset($size))
    return false;
    
  if ($this->username=='admin')
    return true;

  return false;
}

/** Checks if the session is valid. 
 @return true If the session is valid */
function check_session()
{
  global $db;
  $cookie="phtagr".$db->prefix;
  
  if ($_REQUEST['section']=='account')
  {
    if ($_REQUEST['action']=='login')
    {
      if ($this->_check_login($_REQUEST['user'], $_REQUEST['password']))
      {
        $this->_set_session($_REQUEST['user'], $_REQUEST['password']);
        $this->_set_cookie($_REQUEST['user'], $_REQUEST['password']);
      }
    }
    
    else if ($_REQUEST['action']=='logout')
    {
      $this->_clear_data();
      $this->_remove_session();
      $this->_remove_cookie();
      $this->is_logout=true;
    }
  }
  else if (isset($_SESSION['user']) && isset($_SESSION['password']))
  {
    $this->_check_login($_SESSION['user'], $_SESSION['password']);
  }
  else if (isset($_COOKIE[$cookie]))
  {
    list ($username, $password)=split(' ', $_COOKIE[$cookie]);
    $password=base64_decode($password);
    if ($this->_check_login($username, $password))
    {
      $this->_set_session($username, $password);
      // refresh cookie
      $this->_set_cookie($username, $password);
    }
  }
}

/** Sets the session parameter */
function _set_session($username, $password)
{
  $_SESSION['user']=$username;
  $_SESSION['password']=$password;
}

/** Removes a session */
function _remove_session()
{
  session_destroy();
}

/** Sets the user and password in the cookie. The values are valid for
 one year.*/
function _set_cookie($username, $password)
{
  global $db;
  $cookie="phtagr".$db->prefix;
  setcookie($cookie, $username.' '.base64_encode($password), time()+31536000);   
}

/** Removes a cookie from the client */
function _remove_cookie()
{
  global $db;
  $cookie="phtagr".$db->prefix;
  setcookie($cookie, "", time() - 3600);   
}

}
?>
