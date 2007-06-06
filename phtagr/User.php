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

include_once("$phtagr_lib/SqlObject.php");
include_once("$phtagr_lib/Constants.php");
include_once("$phtagr_lib/Image.php");

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
class User extends SqlObject
{

function User($id=-1)
{
  global $db;
  $this->SqlObject($db->users, $id);
  $this->init_session();
}

/** 
  @param idorname User ID or user name
  @returns True if user exists with the id or the given name */
function exists($idorname)
{
  global $db;
  if (is_numeric($idorname))
  {
    if ($idorname<1)
      return false;
    $sql="SELECT COUNT(*)".
         " FROM $db->users". 
         " WHERE id=$idorname";
  } else {
    if ($idorname=='')
      return false;
    $sname=mysql_escape_string($name);
    $sql="SELECT COUNT(*)".
         " FROM $db->users".
         " WHERE name='$name'";
  }
  $result=$db->query($sql);
  if (!$result)
    return false;
  $row=mysql_fetch_row($result);
  if ($row[0]==1)
    return true;

  return false;
}

/** 
  @param withguests True if also guests are counted 
  @return The number of total users */
function get_num_users($withguests=false)
{
  global $db;
  $sql="SELECT COUNT(*)".
       " FROM $db->users";
  if ($withguests)
    $sql.=" WHERE type!=".USER_GUEST ;
  $result=$db->query($sql);
  if (!$result)
    return -1;
  $row=mysql_fetch_row($result);
  return $row[0];
}

/** 
  @param name User name
  @return Returns the id of a given username. If no user found, it returns -1 */
function get_id_by_name($name)
{
  global $db;
  $sname=mysql_escape_string($name);
  $sql="SELECT id".
       " FROM $db->users".
       " WHERE name='$sname'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)<1)
    return -1;
  $row=mysql_fetch_row($result);
  return $row[0];
}

/** @param id Id of the user
  @return Name of the user. Returns '' if user does not exists */
function get_name_by_id($id)
{
  global $db;
  $id=intval($id);
  if ($id<=0)
    return '';

  $sql="SELECT name".
       " FROM $db->users".
       " WHERE id=$id";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)<1)
    return '';
  $row=mysql_fetch_row($result);
  return $row[0];
}

/** 
  @return Type of user */
function get_type()
{
  return $this->_get_data('type');
}

/** Sets the user type. Only admin user are permitted to change the types
  @param type new type of user */
function set_type($type)
{
  global $user;
  if ($type<USER_ADMIN || 
      $type>USER_GUEST)
    return false;

  // Allow only admins to set a user to admin
  if ($type==USER_ADMIN && !$user->is_admin())
    return false;

  $this->_set_data('type', $type);
}

/** Returns the creator id. The creator id can be used to identify the guest
 * owner.
  @return The user id of the creator. */
function get_creator()
{
  return $this->_get_data('creator');
}

/** Sets the creator ID
  @param creator User ID of the creator. */
function set_creator($creator)
{
  $this->_set_data('creator', $creator);
}

/** @return Returns the name of the user */
function get_name()
{
  return $this->_get_data('name', 'anonymous');
}

/** Match a password against the own password
  @param passwd Password to check
  @return True, if password matches the current */
function match_passwd($passwd)
{
  if (strlen($passwd)==0)
    return false;

  $cur_passwd=$this->_get_data('password');
  if ($passwd!=$cur_passwd)
    return false;
  return true;
}

/** Change the password of the user. Only Members are allowed to change the
 * password of the user. If the user is a guest, only the create can change the
 * password. 
  @param oldpasswd Current Password
  @param passwd New password
  @result Returns 0 on success. An global error code otherwise
  @note This function resets the authentication cookie */
function passwd($oldpasswd, $passwd)
{
  global $db, $user, $log;

  // Deny anonymous and guests to change the password
  if ($user->get_id()<=0 || $user->get_type()==USER_GUEST) 
    return ERR_NOT_PERMITTED;

  // Permit only guest owner 
  if (!$user->is_admin() && $this->is_guest() &&
      $this->get_creator()!=$user->get_id())
    return ERR_NOT_PERMITTED;

  if (!$this->match_passwd($oldpasswd))
    return ERR_PASSWD_MISMATCH;

  $result=$this->_check_password($passwd);
  if ($result<0)
    return $result;

  $spasswd=mysql_escape_string($passwd);
  $sql="UPDATE $db->users".
       " SET password='$spasswd',cookie=''".
       " WHERE id=".$this->get_id();
  $result=$db->query($sql);
  if (!$result)
    return ERR_DB_UPDATE;

  $log->info("Changing password by '".$user->get_name()."' (".$user->get_id().")", -1, $this->get_id());
  return 0;
}

/** returns the userid */
function get_groupid()
{
  return 0;
}

/** @return The expired date of the account */
function get_expire()
{
  return $this->_get_data('expire');
}

/** Set the expire date. The date must be in the future until now
  @param date Date in Format of YYYY[-MM[-DD]] or UNIX time stamp
  @return True if date could be set. False otherwise*/
function set_expire($date)
{
  if (is_numeric($date) && strlen($date)!=4)
    $sec=$date;
  else
  {
    // Check format of YYYY-MM-DD hh:mm:ss
    if (!preg_match('/^[0-9]{4}(-[0-9]{2}(-[0-9]{2}?)?)?$/', $date))
      return false;

    $y=intval(substr($date, 0, 4));
    $m=intval(substr($date, 5, 2));
    $d=intval(substr($date, 8, 2));
    if ($y<2000 || $y>2050) $y=2006;
    if ($m<1 || $m>12) $m=1;
    if ($d<1 || $d>31) $d=1;
    
    $sec=mktime(0, 0, 0,$m, $d, $y);
    // Error?
    if ($sec<0 || $sec===false)
      return false;
  }

  // Date before now?
  if ($sec<time())
    return false;

  $this->_set_data('expire', date("Y-m-d", $sec));
  return true;
}

/* @return Returns the current cookie value in the database. If no cookie is set null is returned. */
function _get_cookie()
{
  return $this->_get_data('cookie', null);
}

/** @return Returns the first name of the user */
function get_firstname()
{
  return $this->_get_data('firstname', 'anonymous');
}

/** Set a new first name for the user
  @param name New first name */
function set_firstname($name)
{
  return $this->_set_data('firstname', $name);
}

/** @return Returns the last name of the user */
function get_lastname()
{
  return $this->_get_data('lastname', 'anonymous');
}

/** Sets the new last name for the user
  @param name New last name */
function set_lastname($name)
{
  return $this->_set_data('lastname', $name);
}

/** @return Returns the current email address of the user */
function get_email()
{
  return $this->_get_data('email', '');
}

/** Set a new email address of the user 
  @param email New email address */
function set_email($email)
{
  return $this->_set_data('email', $email);
}

/** @return Returns the current quota of the user */
function get_quota()
{
  return $this->_get_data('quota', 0);
}

/** Set a new quota of the user 
  @param quota New quota address */
function set_quota($quota)
{
  return $this->_set_data('quota', $quota);
}

/** @return Returns the current quota slice in bytes. The user can upload a
 * quota slice in a quoat interval. */
function get_qslice()
{
  return $this->_get_data('qslice', 0);
}

/** Set a new quota slice of the user 
  @param qslice New quota slice in bytes */
function set_qslice($qslice)
{
  return $this->_set_data('qslice', $qslice);
}

/** @return Returns the current quota interval for a quota slice  */
function get_qinterval()
{
  return $this->_get_data('qinterval', 0);
}

/** Set a new quota interval of quota slice
  @param qinterval New quota interval in seconds */
function set_qinterval($qinterval)
{
  return $this->_set_data('qinterval', $qinterval);
}

/** Checks the username for validity. 
  The Username must start with an letter, followed by letters, numbers, or
  special characters (-, _, ., @). All letters must be lowered.
  
  @param name Username to check
  @return true if the name is valid. Otherwise it returns a global error code
  */
function _check_name($name)
{
  global $db;

  if (strlen($name)<4 || strlen($name)>32)
    return ERR_USER_NAME_LEN; 
    
  if (!preg_match('/^[a-z][a-z0-9\-_\.\@]+$/', $name))
    return ERR_USER_NAME_INVALID;
  
  if ($this->exists($name))
    return ERR_USER_ALREADY_EXISTS;

  return 0;
}

/** Returns a string of special chars which are allowed for the password 
  @return String of special chars */
function get_special_chars()
{
  return "~!@#$%^&*()_\}{}[]?><,./-=+";
}

/** Checks the vality of the password. At least 6 and maximum of 32 chars. The
 * password must cotain at least 3 lower chars or numbers, 2 upper chars and 1
 * other characters
  @param pwd Password
  @return True on success. False and global error code */
function _check_password($pwd)
{
  if (strlen($pwd)<6 || strlen($pwd)>32)
    return ERR_USER_PWD_LEN;
    
  $upper=0; $lower=0; $num=0; $special=0;
  for ($i=0; $i<strlen($pwd); $i++)
  {
    $c=$pwd{$i};
    if ($c>='A' and $c<='Z')
      $upper++;
    else if ($c>='a' and $c<='z')
      $lower++;
    else if ($c>='0' and $c<='9')
      $num++;
    else
      $special++;
  }
  if ($upper<2 || $lower+$num<3 || $special<1)
    return ERR_USER_PWD_INVALID;

  return 0;
}

/** Sets the language of the page 
  @param lang New language */
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
  bind_textdomain_codeset($domain, 'UTF-8');

  $_SESSION['lang']=$lang;
  return true;
}

/** Validates a user with its password
 @return true if the password is valid */
function _check_login($name, $pwd)
{
  global $db;
  if ($name=='' || $pwd=='')
    return false;
    
  $sname=mysql_escape_string($name);
  $spwd=mysql_escape_string($pwd);
  $sql="SELECT id".
       " FROM $db->users".
       " WHERE name='$sname' AND password='$spwd'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)!=1)
    return false;

  $row=mysql_fetch_row($result);
  $this->init_by_id($row[0]);
  $_SESSION['userid']=$row[0];

  return true;
}

/** Creates a new user 
  @param name Name of the user
  @param pwd Password of the user
  @param type Possible values USER_ADMIN, USER_MEMBER, USER_GUEST. Default is USER_MEMBER
  @return the user id on success. On failure it returns a global error code */
function create($name, $pwd, $type=USER_MEMBER)
{
  global $db, $user, $log;

  $err=$this->_check_name($name);
  if ($err<0)
    return $err;

  $err=$this->_check_password($pwd);
  if ($err<0)
    return $err;

  if ($type<USER_ADMIN || $type>USER_GUEST)
    return ERR_USER_GERNERAL;

  if ($type==USER_AMDIN && 
    !($user->is_admin() || $this->get_num_users()==0))
  {
    return ERR_NOT_PERMITTED;
  }

  $sname=mysql_escape_string($name);
  $spwd=mysql_escape_string($pwd);
  $sql="INSERT INTO $db->users".
       " (name, password, type)".
       " VALUES ('$sname', '$spwd', $type)";
  $id=$db->query_insert($sql);
  if ($id<0)
    return ERR_USER_INSERT;

  $u=new User($id);
  $err=$u->_init_data();
  if ($err<0)
    return $err;

  $log->info("Create user '$name' ($id)", -1, $user->get_id());
  return $id;
}

/** Creates a new guest account of the current user 
  @param name Guest's name
  @param pwd Guest's password 
  @return ID of the guest account or an global error (ERROR < 0) */
function create_guest($name, $pwd)
{
  global $user, $log;

  $id=$this->create($name, $pwd, USER_GUEST);
  if ($id<0)
    return $id;
  $guest=new User($id);
  $guest->set_creator($this->get_id());
  $guest->commit();
  unset($guest);

  $log->info("Create guest '$name' ($id)", -1, $user->get_id());
  return $id;
}

/** Initialize a new user 
  @return On failure it returns a global Error code */
function _init_data()
{
  global $db;
  global $phtagr_data;

  $id=$this->get_id();
  if ($id<=0)
    return ERR_GERNERAL;

  $upload=$this->get_upload_dir();
  if (!$upload)
  {
    $fs=new Filesystem();
    if (!$fs->mkdir($upload))
    return ERR_FS_GENERAL;
  }
}

/** @return the default ACL for the group */
function get_gacl()
{
  global $conf;
  return $conf->get('image.gacl', ACL_PREVIEW | ACL_EDIT);
}

/** @return the default ACL for phtagr members */
function get_macl()
{
  global $conf;
  return $conf->get('image.macl', ACL_PREVIEW);
}

/** @return the default ACL for all */
function get_aacl()
{
  global $conf;
  return $conf->get('image.aacl', ACL_PREVIEW);
}

/** Evaluates, if a given user is member of a given group
  @param groupid ID of the group
  @return true if the given user is member of a group */
function is_in_group($groupid=-1)
{
  global $db;

  $sql="SELECT userid ".
       " FROM $db->usergroup".
       " WHERE userid=".$this->get_id().
       " AND groupid=$groupid";
  $result=$db->query($sql);
  if (mysql_num_rows($result)>0)
    return true;
  return false;
}

/** @return true if the current user is an super user and has admin rights */
function is_admin()
{
  if ($this->get_name()=='admin' ||
    $this->get_type()==USER_ADMIN)
    return true;
  return false;
}

/* @return true if the given user has an phtagr account */
function is_member()
{
  if ($this->is_admin() ||
    $this->get_type()==USER_MEMBER)
    return true;
  return false;
}

/** @return True if user is a guest. If user is an admin, it returns also true */
function is_guest()
{
  if ($this->is_admin() ||
    $this->get_type()==USER_GUEST)
    return true;
  return false;
}

/* Return true if the given user is anonymous */
function is_anonymous()
{
  if ($this->get_id()==-1)
    return true;
  return false;
}

/** 
  @return The number of guests of the user */
function get_num_guests()
{
  global $db;
  $sql="SELECT COUNT(*)".
       " FROM $db->users";
  $sql.=" WHERE creator=".$this->get_id()." AND type=".USER_GUEST ;
  $result=$db->query($sql);
  if (!$result)
    return -1;
  $row=mysql_fetch_row($result);
  return $row[0];
}

/** @return Returns the hash of all guests account ids. The index is the id,
 * the value is the name of the guest */
function get_guests()
{
  global $db;
  $guests=array();

  $sql="SELECT id,name".
       " FROM $db->users".
       " WHERE type=".USER_GUEST." AND creator=".$this->get_id();
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)<1)
    return $guests;

  while ($row=mysql_fetch_row($result))
    $guests[$row[0]]=$row[1];
  return $guests;
}

/** @return Returns an hash of ids with group names of the users group */
function get_groups()
{
  global $db;
  $groups=array();
  $sql="SELECT id,name". 
       " FROM $db->groups".
       " WHERE owner=".$this->get_id();
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)<0)
    return $groups;
  while ($row=mysql_fetch_row($result))
    $groups[$row[0]]=$row[1];

  return $groups;
}

/** @return Returns the memberships of the current user */
function get_memberlist($onlyguests=true)
{
  global $db;
  $members=array();
  $sql="SELECT g.id,g.name".
       " FROM $db->usergroup AS ug, $db->groups AS g".
       " WHERE ug.groupid=g.id AND ug.userid=".$this->get_id();
  if ($onlyguest)
    $sql.=" AND g.creator=".$this->get_creator();
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)<0)
    return $members;
  while ($row=mysql_fetch_row($result))
    $members[$row[0]]=$row[1];

  return $members;
}

/** Get the count of the own images 
  @param only_uploads Consider only uploaded files
  @param since UNIX timestamp from the oldest image. This parameter is
  optional. If this parameter is not set, it returns the bytes of all images 
  @return Number of images */
function get_image_count($only_uploads=false, $since=-1)
{
  global $db;
  if (!$this->is_member())
    return -1;

  $id=$this->get_id();
  $sql="SELECT COUNT(*)".
       " FROM $db->images".
       " WHERE userid=$id";
  if ($only_uploads)
    $sql.=" AND is_upload=1";
  if ($since>0)
    $sql.=" AND created>FROM_UNIXTIME($since)";

  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)<1)
    return -1;
  $row=mysql_fetch_row($result);
  return intval($row[0]);
}

/** Returns all the bytes of the images from the user. 
  @param only_uploads If true, only uploaded images are counted. If false, all
  images are considered. Default is false.
  @param since UNIX timestamp from the oldest image. This parameter is
  optional. If this parameter is not set, it returns the bytes of all images 
  @return Number of bytes. On an error it returns -1. */
function get_image_bytes($only_uploads=false, $since=-1)
{
  global $db;
  if (!$this->is_member())
    return -1;

  $id=$this->get_id();
  $sql="SELECT SUM(bytes)".
       " FROM $db->images".
       " WHERE userid=$id";
  if ($only_uploads)
    $sql.=" AND is_upload=1";
  if ($since>0)
    $sql.=" AND created>FROM_UNIXTIME($since)";

  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)<1)
    return -1;
  $row=mysql_fetch_row($result);
  return intval($row[0]);
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
  global $conf;
  if ($this->is_admin())
    return true;
  
  $roots=$conf->get('path.fsroot[]', null);
  if ($roots!=null && count($roots)>0)
    return true;
  return false;
}

/** Return true if user can upload a file in general.*/
function can_upload()
{
  if ($this->is_admin())
    return true;

  // Check if user exhausts his quota
  if ($this->is_member())
  {
    $quota=$this->get_quota();
    $used=$this->get_image_bytes(true);
    if ($used<$quota)
      return true;
  }

  return false;
}

/** Returns the used quota in percent 
  @return Value between 0.0 and 1.0*/
function get_quota_used()
{
  $quota=$this->get_quota();
  $used=$this->get_image_bytes(true);
  if ($quota==0)
    return 0.0;
  if ($used>=$quota)
    return 1.0;
  return $used/$quota;
}

/** @return Returns the maxium bytes, which can be uploaded */
function get_upload_max()
{
  if ($this->get_id()<0)
    return 0;

  $quota=$this->get_quota();
  $qslice=$this->get_qslice();
  $qinterval=$this->get_qinterval();

  // Check the absolute quota
  $quota_free=$quota-$this->get_image_bytes(true);
  if ($quota_free<=0)
    return 0;

  // Check the last upload interval as quota slice
  $slice_free=$qslice-$this->get_image_bytes(true, time()-$qinterval);
  if ($slice_free<=0)
    return 0;

  if ($slice_free<$quota_free)
    return $slice_free;
  else
    return $quota_free;
}

/** Return true if user can upload a file with the given size
  @param size Size of current uploaded file. This size is mandatory. 
  @return False if the upload is promitted. False otherwise */
function can_upload_size($size=0)
{
  if ($size<10)
  return false;
  
  $max=$this->get_upload_max();
  if ($size<=$max)
    return true;

  return false;
}

/** @return The upload directory of the user */
function get_upload_dir()
{
  global $phtagr_data;
  if ($this->get_id()<=0)
    return false;

  $name=$this->get_name();
  $path=$phtagr_data.DIRECTORY_SEPARATOR.'users'.DIRECTORY_SEPARATOR.$name;
  return $path;
}

/** @return The theme directory of the user */
function get_theme_dir()
{
  global $phtagr_htdocs;
  global $conf;
  $path=$phtagr_htdocs.'/themes/'.$conf->get('theme', 'default');
  return $path;
}

/** Sets the initial timestamps and arrays for the session */
function init_session()
{
  global $log;
  // initiating the session
  if (!isset($_SESSION['created']))
  {
    $log->warn("Create new session", -1, $this->get_id());
    $_SESSION['created']=time();
    $_SESSION['img_viewed']=array();
    $_SESSION['img_voted']=array();
    $_SESSION['nrequests']=0;
    $_SESSION['nqueries']=0;
    
    // Save remote identification
    $_SESSION['user_agent']=$_SERVER['HTTP_USER_AGENT'];
    $_SESSION['remote_addr']=$_SERVER['REMOTE_ADDR'];
  } else {
    // remote check
    if ($_SESSION['user_agent']!=$_SERVER['HTTP_USER_AGENT'] ||
      $_SESSION['remote_addr']!=$_SERVER['REMOTE_ADDR'])
    {
      $this->_delete_session();
      return;
    }

    if (isset($_SESSION['lang']))
      $this->_set_lang($_SESSION['lang']);
  }
  $_SESSION['update']=time();
  $_SESSION['nrequests']++;
  if (isset($_COOKIE[session_name()]))
    $_SESSION['withcookie']=true;
  else
    $_SESSION['withcookie']=false;
}

/** Checks if the session is valid. 
 @param setcookie If false, no not send a new cookie to refresh the
 timeout. Default is true.
 @return true If the session is valid 
 @note A cookie is also set, if the request contains a 'remember' with true */
function check_session($setcookie=true)
{
  global $db, $conf;
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
      $this->_delete_session();
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
  } else if (isset($_SESSION['userid'])) {
    $id=$_SESSION['userid'];
    $this->init_by_id($id);
  }

  // Load user's configuration
  if ($conf->get_userid()==0 && $this->get_id()>0)
    $conf->load($this->get_id());

  if (isset($_REQUEST['lang']))
  {
    $this->_set_lang($_REQUEST['lang']);
  }
}

/** Removes a session. It clears all the data and reinitialize it */
function _delete_session()
{
  global $log;
  foreach ($_SESSION as $key => $value)
    unset($_SESSION[$key]);

  $log->warn("Delete session", -1, $this->get_id());
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
        FROM $db->users
        WHERE cookie='".$scookie."'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)!=1)
    return false;

  $row=mysql_fetch_row($result);
  $this->init_by_id($row[0]);
  $_SESSION['userid']=$row[0];

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
    $sql="UPDATE $db->users
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
  if ($db->prefix!='')
    return "phtagr.".$db->prefix;
  else
    return "phtagr";
}

/** Creates a new cookie value. It is a MD5 hash computed by username, password
 * and a random number.
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

/** Delte all data from a user
  @param id Id of the user.
  @todo ensure to delete all data from the user */
function _delete_user_data($id)
{
  global $db;
  global $conf;

  // Deleting cached previes, uploads and image sql data
  $img=new ImageSync();
  $img->delete_from_user($id);

  $g=new Group();
  $g->delete_from_user($id);

  $conf->delete_from_user($id);

  // @todo delete users upload directory
  
  // Delete the user data
  $sql="DELETE FROM $db->users".
       " WHERE id=$id";

  $result=$db->query($sql);
  return true;
}

/** Delete a user and all its data
  @return 0 on success, a global error code otherwise
  @note the admin account could not be deleted */
function delete()
{
  global $user, $log;

  $log->info("Delete user '".$this->get_name()."' by '".$user->get_name()."' (".$user->get_id().")", -1, $this->get_id());

  $permit=false;

  // Allow admin, but not itself
  if ($user->is_admin() && $this->get_id()>1) 
    $permit=true;
  // Allow guest creator
  if ($this->get_type()==USER_GUEST &&
    $this->get_creator()==$user->get_id())
    $permit=true;
  // Allow itself
  if ($this->is_member() && $user->get_id()==$this->get_id())
    $permit=true;

  if (!$permit)
    return ERR_NOT_PERMITTED;

  $this->_delete_user_data($this->get_id());
  return 0;
}

}
?>
