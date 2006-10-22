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
  $this->_data=array();
  $this->init_session();
}

/** Sets the initial timestamps and arrays for the session */
function init_session()
{
  // initiating the session
  if (!isset($_SESSION['created']))
  {
    $_SESSION['created']=time();
    $_SESSION['img_viewed']=array();
    $_SESSION['img_voted']=array();
    $_SESSION['nrequests']=0;
    $_SESSION['nqueries']=0;
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
  $sql="SELECT id
        FROM $db->user 
        WHERE name='$sname' AND password='$spassword'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)!=1)
    return false;

  $row=mysql_fetch_row($result);
  $this->_init_by_id($row[0]);
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

function _init_by_id($id)
{
  global $db;
  if (!is_numeric($id))
    return false;

  $sql="SELECT *
        FROM $db->user
        WHERE id=$id";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)!=1)
    return false;

  $this->_data=mysql_fetch_assoc($result);
}

/** 
  @param name Name of the db column
  @param default Default value, if column name not exists. Default value is
  null.
  @return If the value is not set, returns default */
function _get_data($name, $default=null)
{
  if (isset($this->_data[$name]))
    return $this->_data[$name];
  else 
    return $default;
}

/** Returns the userid of the current session 
  @return The value for an member is greater 0, an anonymous user has the ID
  -1.*/
function get_id()
{
  return $this->_get_data('id', -1);
}

function get_name()
{
  return $this->_get_data('name', 'anonymous');
}

/** returns the userid */
function get_groupid()
{
  return $this->get_id();
}

/* @return Returns the current cookie value in the database */
function _get_cookie()
{
  $this->_get_data('cookie', null);
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
        WHERE userid=".$this->get_id()."
          AND groupid=$groupid";
  $result=$db->query($sql);
  if (mysql_num_rows($result)>0)
    return true;
  return false;
}

/** Return true if the current user is an super user and has admin rights */
function is_admin()
{
  if ($this->get_name()=='admin')
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
  if ($this->get_id()==-1)
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
 @param setcookie If false, no not send a new cookie to refresh the
 timeout. Default is true.
 @return true If the session is valid 
 @note A cookie is also set, if the request contains a 'remember' with true */
function check_session($setcookie=true)
{
  global $db;
  $cookie_name=$this->_get_cookie_name();
    
  if ($_REQUEST['section']=='account')
  {
    if ($_REQUEST['action']=='login')
    {
      if ($_REQUEST['remember'])
        $setcookie=true;
      else 
        $setcookie=false;

      if ($this->_check_login($_REQUEST['user'], $_REQUEST['password']) && 
          $setcookie)
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

/** Checks the cookie with the cookie from the database. If a given cookie is
 * found, the user is authenticated and will be initialized.
  @param cookie HTTP cookie
  @return True if the cookie matches the stored cookie. False otherwise */
function _check_cookie($cookie)
{
  global $db;

  if (strlen($cookie)<10)
    return false;

  $scookie=mysql_escape_string($cookie);
  $sql="SELECT id
        FROM $db->user
        WHERE cookie='".$scookie."'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)!=1)
    return false;

  $row=mysql_fetch_row($result);
  $this->_init_by_id($row[0]);

  return true;
}

/** Sets the cookie of the current user. If a cookie value is already set in
 * the database, the value is set to the cookie. The cookie is valid for one
 * year.
  @note A cookie is only set, if the user is already inialized and the ID is
  greater 0. */
function _set_cookie()
{
  global $db;

  $id=$this->get_id();
  if ($id<=0)
    return;

  $name=$this->_get_cookie_name();
  $value=$this->_get_cookie();
  //$this->debug("name: $name, value=$value");
  if ($value==null)
  {
    $value=$this->_create_cookie();
    $svalue=mysql_escape_string($value);
    $sql="UPDATE $db->user
          SET cookie='$svalue'
          WHERE id=$id";
    $db->query($sql);
  }
  $this->_update_cookie($name, $value);
}

/** Updates the the cookie 
  @param name Cookie name
  @param value Cookie value 
  @param time valid time in seconds in UNIX time. If this value is 0, the time
  will be set to current time plus one year. Default is 0. */
function _update_cookie($name, $value, $time=0)
{
  if ($time==0)
    $time=time()+31536000;
  setcookie($name, $value, $time, '/');
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
function _create_cookie()
{
  $sec=time();
  srand($sec);
  for ($i=0; $i<64; $i++)
    $s.=chr(rand(0, 255));
  $name=$this->get_name();
  $pwd=$this->_get_data('password');
  return substr(md5($name.$s.$pwd),0,64);
}

/** Removes a cookie from the client.
  @note It does not removes the cookie from the database */
function _remove_cookie()
{
  $name=$this->_get_cookie_name();
  setcookie($name, "", time() - 3600, '/');   
}

}
?>
