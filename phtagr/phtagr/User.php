<?php

include_once("$phtagr_prefix/Base.php");
include_once("$phtagr_prefix/Constants.php");

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
  return ACL_PREVIEW;
}

/** Return the default ACL for other phtagr users */
function get_oacl()
{
  return ACL_PREVIEW;
}

/** Return the default ACL for all */
function get_aacl()
{
  return ACL_PREVIEW;
}

/* Return true if the given user is member of a group */
function is_in_group($groupid=-1)
{
  global $db;

  $sql="SELECT userid
        FROM $db->usergroup
        WHERE userid=".$this->get_userid()."
          AND groupid=$groupid";
  $result=$db->query($sql);
  if (mysql_num_rows($result)>0)
    return true;
  return false;
}

/** Return true if the current user is an super user and has admin rights */
function is_admin()
{
  if ($this->get_username()=='admin')
    return true;
  return false;
}

/** Return if the given user has the same user id than an object 
  @param userid Userid of the given object
*/
function is_owner($image=null)
{
  if ($image==null)
    return false;
    
  if ($this->is_admin())
    return true;

  if ($this->get_userid()==
    $image->get_userid())
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

/** Checks the acl of an image 
  @param image Image object
  @param flag ACL bit mask
  @return True if user is allow to do the action defined by the flag */
function _check_image_acl($image, $flag)
{
  if (!isset($image))
    return false;
    
  // Admin is permitted always
  if ($this->is_admin())
    return true;
  
  if ($image->get_userid()==$this->get_userid())
    return true;
    
  // If acls are calculated within if statement, I got wrong evaluation.
  $gacl=$image->get_gacl() & $flag;
  $oacl=$image->get_oacl() & $flag;
  $aacl=$image->get_aacl() & $flag;
  
  if ($this->is_in_group($image->get_groupid()) && $gacl > 0)
    return true;
  
  if ($this->is_member() && $oacl > 0)
    return true;

  if ($aacl > 0)
    return true;
  
  return false;
}

/** Return true if user can edit the image 
  @param image Image object. Default is null.*/
function can_edit($image=null)
{
  return $this->_check_image_acl(&$image, ACL_EDIT);
}

function can_metadata($image=null)
{
  return $this->_check_image_acl(&$image, ACL_METADATA);
}

/** Return true if user can upload a file with the given size
/** Return true if user can preview the image 
  @param image Image object. Default is null.*/
function can_preview($image=null)
{
  return $this->_check_image_acl(&$image, ACL_PREVIEW);
}

function can_highsolution($image=null)
{
  return $this->_check_image_acl(&$image, ACL_HIGHSOLUTION);
}

function can_fullsize($image=null)
{
  return $this->_check_image_acl(&$image, ACL_FULLSIZE);
}

function can_download($image=null)
{
  return $this->_check_image_acl(&$image, ACL_DOWNLOAD);
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
  if ($size<10)
  return false;
  
  if ($this->is_admin())
    return true;

  return false;
}

/** Checks if the session is valid. 
 @param docookierefresh If false, no not send a new cookie to refresh the
 timeout. Default is true.
 @return true If the session is valid */
function check_session($docookierefresh=true)
{
  global $db;
  $cookie="phtagr".$db->prefix;
    
  if ($_REQUEST['section']=='account')
  {
    if ($_REQUEST['action']=='login')
    {
      if ($this->_check_login($_REQUEST['user'], $_REQUEST['password']) && 
          $docookierefresh)
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
    if ($this->_check_login($username, $password) &&
        $docookierefresh)
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
