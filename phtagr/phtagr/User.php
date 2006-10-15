<?php

include_once("$phtagr_lib/Base.php");
include_once("$phtagr_lib/Constants.php");

/** This class handles the authentication of an user.

The authentication bases on cookies and sessions. A user has to login first.
After a successful login, the server-side session is initiated and a cookie is
set on ther client side for further authentication. The cookie contains
username and password of the account.

If the session is not available, the cookie is checked for username and
password. 

There are three types of users. Anonymous, members and guest. Anonymous is
user, who has not signed in. A member is a user who owns some images. A
guest is a user, who does not own images but can review all images of his
assigned group (and public images). A guest is a kind of read-only member for special groups.

A member owns a set of groups, which he can modify. A member can assign other
members or guest to his groups. 

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
    $_SESSION['img_voted']=array();
    $_SESSION['nrequests']=0;
  } else {
    if (isset($_SESSION['lang']))
      $this->_set_lang($_SESSION['lang']);
  }
  $_SESSION['update']=time();
  $_SESSION['nrequests']++;
  if (isset($_COOKIE['PHPSESSID']))
    $_SESSION['withcookie']=true;
  else
    $_SESSION['withcookie']=false;
}

/** Validates a user with its password
 @return true if the password is valid */
function _check_login($name, $password)
{
  global $db;
  if ($name=='' || $password=='')
    return false;
    
  $sname=mysql_escape_string($name);
  $spassword=mysql_escape_string($password);
  $sql="SELECT id,name,password
        FROM $db->user 
        WHERE name='$sname' AND password='$spassword'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)!=1)
    return false;

  $row=mysql_fetch_assoc($result);
  $this->_is_auth=true;
  $_SESSION['username']=$row['name'];
  $_SESSION['userid']=$row['id'];
  return true;
}

/** Sets the language of the page */
function _set_lang($lang)
{
  global $phtagr_prefix;

  if ($lang=='')
    $lang='en_US';

  $locale_dir=$phtagr_prefix.DIRECTORY_SEPARATOR.'locale';
  $dir=$locale_dir.DIRECTORY_SEPARATOR.$lang;

  if (!is_dir($dir))
    return false;

  putenv("LANG=$lang");
  setlocale(LC_ALL, $lang);
  $domain='messages';
  bindtextdomain($domain, $locale_dir);
  textdomain($domain);
  //bind_textdomain_codeset($domain, 'UTF-8');

  $_SESSION['lang']=$lang;
  return true;
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

/* Return true if the given user has an phtagr account */
function is_member()
{
  return !$this->is_anonymous();
}

function is_guest()
{
  global $pref;
  if (isset($pref['user.guest']))
    return true;
  else 
    return false;
}

/* Return true if the given user is anonymous */
function is_anonymous()
{
  if ($_SESSION['userid']==-1)
    return true;
  return false;
}

/** @return Returns true if user is allowed to create a file */
function can_create_file()
{
  if ($this->is_admin())
    return true;

  return false;
}

/** @return Returns true if user is allowed to delete a file */
function can_delete_file()
{
  if ($this->is_admin())
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

/** Return true if user can upload a file in general.*/
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
  $cookie_name=$this->_get_cookie_name();
    
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
  else if (isset($_COOKIE[$cookie_name]))
  {
    if ($this->_check_cookie($_COOKIE[$cookie_name]) &&
        $docookierefresh)
    {
      // refresh cookie
      $this->_update_cookie($cookie_name, $_COOKIE[$cookie_name]);
    }
  }
  if (isset($_REQUEST['lang']))
  {
    $this->_set_lang($_REQUEST['lang']);
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

/* @return Returns the current cookie value in the database */
function _get_db_cookie()
{
  global $db;
  $sql="SELECT cookie
        FROM $db->user
        WHERE name='".$this->get_username()."'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)!=1)
    return null;

  $row=mysql_fetch_row($result);
  return $row[0];
}

/** Checks the cookie with the cookie from the database
  @param cookie HTTP cookie
  @return True if the cookie matches the stored cookie. False otherwise */
function _check_cookie($cookie)
{
  global $db;

  if (strlen($cookie)<10)
    return false;

  $db_cookie=$this->_get_db_cookie();
  if ($db_cookie==$cookie)
    return true;

  return false;
}

/** Sets the cookie of the current user. If a cookie value is already set in
 * the database, the value is set to the cookie. The cookie is valid for one
 * year.*/
function _set_cookie($username, $password)
{
  global $db;
  $name=$this->_get_cookie_name();
  $value=$this->_get_db_cookie();
  //$this->debug("name: $name, value=$value");
  if ($value==null)
  {
    $value=$this->_create_cookie($username, $password);
    $svalue=mysql_escape_string($value);
    $sql="UPDATE $db->user
          SET cookie='$svalue'
          WHERE name='$username'";
    $db->query($sql);
  }
  $this->_update_cookie($name, $value);
}

function _update_cookie($name, $value)
{
  setcookie($name, $value, time()+31536000, '/');
}

/** @return Returns the name of the cookie */
function _get_cookie_name()
{
  global $db;
  return "phtagr".$db->prefix;
}

/** Creates a new cookie value. It is a MD5 hash computed by username, password
 * and a random number.
  @param username Name of the user
  @param password User's password
  @return MD5 hash of username, password and random data */
function _create_cookie($username, $password)
{
  $sec=time();
  srand($sec);
  for ($i=0; $i<64; $i++)
    $s.=chr(rand(0, 255));
  return substr(md5($username.$s.$password),0,64);
}

/** Removes a cookie from the client */
function _remove_cookie()
{
  global $db;
  $name=$this->_get_cookie_name();
  setcookie($name, "", time() - 3600, '/');   

  $sql="UPDATE $db->user
        SET cookie=NULL
        WHERE name='".$this->get_username()."'";
  $db->query($sql);
}

}
?>
