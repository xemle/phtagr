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

function User()
{
  $this->init_session();
}

/** Sets the initial timestamps and arrays for the session */
function init_session()
{
  // initiating the session
  if (!isset($_SESSION['created']))
  {
    $_SESSION['created']=time();
    $_SESSION['userid']=-1;
    $_SESSION['username']='anonymous';
    $_SESSION['img_viewed']=array();
  }  
  $_SESSION['update']=time();
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
        $_SESSION['username']=$username;
        $_SESSION['userid']=$row[0];
        return true;
      }
    }
  }
  return false;
}

/** Returns the userid of the current session 
  @return The value for an member is greater 0, an anonymous user has the ID
  -1.*/
function get_userid()
{
  if (isset($_SESSION['userid']))
    return $_SESSION['userid'];
  return -1;
}

/** returns the userid */
function get_groupid()
{
  return $this->get_userid();
}

function get_username()
{
  return $_SESSION['username'];
}

/** Return the default ACL for the group */
function get_gacl()
{
  return 0xff;
}

/** Return the default ACL for other phtagr users */
function get_oacl()
{
  return 0xff;
}

/** Return the default ACL for all */
function get_aacl()
{
  return 0xff;
}

/* Return true if the given user is member of a group */
function is_in_group($groupid=-1)
{
  return false;
}

/** Return true if the current user is an super user and has admin rights */
function is_admin()
{
  if ($_SESSION['username']=='admin')
    return true;
  return false;
}

/** Return if the given user has the same user id than an object 
  @param userid Userid of the given object
*/
function is_owner($userid=-1)
{
  if ($userid!=-1 && 
    $_SESSION['userid']==$userid)
    return true;
  return false;
}

/* Return true if the given user has an phtagr account */
function is_member()
{
  return !$this->is_anonymous();
}

/* Return true if the given user is anonymous */
function is_anonymous()
{
  if ($_SESSION['userid']==-1)
    return true;
  return false;
}

/** Return true if the user can browse the filesystem */
function can_browse()
{
  if ($this->is_admin())
    return true;

  return false;
}

/** Return true if the user can select an image */
function can_select($image=null)
{
  return true;
}

/** Return true if user can edit the image 
  @param imageid Image id. Default -1.*/
function can_edit($image=null)
{
  if ($this->is_admin())
    return true;
  
  if ($image==null)
    return false;
  
  if ($image->get_userid()==$this->get_userid())
    return true;
    
  if ($image->get_gacl()&1>0 &&
    $this->is_in_group($image->get_groupid()))
    return true;
  
  return false;
}

/** Return true if user can upload a file in general.
  @param size Size of current uploaded file. This size is mandatory. */
function can_upload()
{
  if ($this->is_admin())
    return true;

  return false;
}

/** Return true if user can upload a file with the given size
  @param size Size of current uploaded file. This size is mandatory. */
function can_upload_size($size=0)
{
  if (!isset($size))
    return false;
    
  if ($this->is_admin())
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
        $this->_set_cookie($_REQUEST['user'], $_REQUEST['password']);
      }
    }
    
    else if ($_REQUEST['action']=='logout')
    {
      $this->_remove_session();
      $this->_remove_cookie();
    }
  }
  else if (isset($_COOKIE[$cookie]))
  {
    list ($username, $password)=split(' ', $_COOKIE[$cookie]);
    $password=base64_decode($password);
    if ($this->_check_login($username, $password))
    {
      // refresh cookie
      $this->_set_cookie($username, $password);
    }
  }

}

/** Removes a session */
function _remove_session()
{
  session_destroy();
  
  foreach ($_SESSION as $key => $value)
    unset($_SESSION[$key]);

  $this->init_session();
}

/** Sets the user and password in the cookie. The values are valid for
 one year.*/
function _set_cookie($username, $password)
{
  global $db;
  $cookie="phtagr".$db->prefix;
  setcookie($cookie, $username.' '.base64_encode($password), time()+31536000, '/');
}

/** Removes a cookie from the client */
function _remove_cookie()
{
  global $db;
  $cookie="phtagr".$db->prefix;
  setcookie($cookie, "", time() - 3600, '/');   
}

}
?>
