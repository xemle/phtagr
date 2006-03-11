<?php

/** This class handles the authentication of an user.

The authentication bases on cookies and sessions. A user has to login first.
After a successful login, the server-side session is initiated and a cookie is
set on ther client side for further authentication. The cookie contains
username and password of the account.

If the session is not available, the cookie is checked for username and
password. 

@class Auth
*/
class Auth 
{

var $is_auth; 
var $is_logout; 
var $user; 
var $userid;
var $root;

function Auth()
{
  $this->_clear_data();
}

/** Resets all authorization data */
function _clear_data()
{
  $this->is_auth=false;
  $this->is_logout=false;
  $this->user='';
  $this->userid='';
}

/** Validates a user with its password
 @return true if the password is valid */
function _check_login($user, $password)
{
  global $db;
  if ($user!='' && $password!='')
  {
    $sql="SELECT id,password 
          FROM $db->user 
          WHERE name='$user'";
    $result=$db->query($sql);
    if ($result)
    {
      $row = mysql_fetch_row($result);
      if ($password == $row[1]) {
        $this->is_auth=true;
        $this->user=$user;
        $this->userid=$row[0];
        $this->root=$row[2];
        return true;
      }
    }
  }
  return false;
}

/** 
 @return true if the user is authorized */
function is_auth()
{
  return $this->is_auth;
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
  else if (isset($_COOKIE[$cockie]))
  {
    if ($this->_check_login($_COOKIE[$cockie]['user'], $_COOKIE[$cockie]['password']))
    {
      $this->_set_session($_REQUEST['user'], $_REQUEST['password']);
    }
  }
}

/** Sets the session parameter */
function _set_session($user, $password)
{
  $_SESSION['user']=$user;
  $_SESSION['password']=$password;
}

/** removes a session */
function _remove_session()
{
  session_destroy();
}

/** Sets the user and password in the cookie */
function _set_cookie($user, $password)
{
  global $db;
  $cookie="phtagr".$db->prefix;
  setcookie($cookie."[user]", $user);   
  setcookie($cookie."[password]", $password);   
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
