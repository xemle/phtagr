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

function User($id=-1)
{
  $this->_data=array();
  $this->_changes=array();
  $this->init_session();
  if ($id>0)
    $this->_init_by_id($id);
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

/** Stores the data for the database temporary to save database accesses. After
 * all changes, the function commit_changes must be called.
  @param name Name of the column
  @param value Value of the column
  @result True on success. False otherwise 
  @note The changed data updates not the internal representation
  @see commit_changes */
function _set_data($name, $value)
{
  if ($this->get_id()<=0)
    return false;

  if ($this->_data[$name]==$value)
  {
    if (isset($this->_changes[$name]))
      unset($this->_changes[$name]);
    return true;
  }

  $this->_changes[$name]=$value;
  return true;
}

/** Writes all changes to the database. It also updated the internal data of
 * the object 
  @return True if changes where writen */
function commit_changes()
{
  global $db;

  $id=$this->get_id();
  if ($id<=0)
    return false;

  if (count($this->_changes)==0)
    return false;

  $changes='';
  foreach ($this->_changes as $name => $value)
  {
    if ($value=="NULL")
      $svalue="NULL";
    else
      $svalue="'".mysql_escape_string(strval($value))."'";
    $changes.=",$name=$svalue";
  }
  $changes=substr($changes,1);

  $sql="UPDATE $db->user
        SET $changes
        WHERE id=$id";
  $result=$db->query($sql);
  if (!$result)
    return false;

  // Successful changes. Update to the data structure and delete changes
  foreach ($this->_changes as $name => $value)
    $this->_data[$name]=$value;
  $this->_changes=array();
  return true;
}

/** 
  @param name User name
  @return Returns the id of a given username. If no user found, it returns -1 */
function get_id_by_name($name)
{
  global $db;
  $sname=mysql_escape_string($name);
  $sql="SELECT id
        FROM $db->user
        WHERE name='$sname'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)<1)
    return -1;
  $row=mysql_fetch_row($result);
  return $row[0];
}

/** Returns the userid of the current session 
  @return The value for an member is greater 0, an anonymous user has the ID
  -1.*/
function get_id()
{
  return $this->_get_data('id', -1);
}

/** @return Returns the name of the user */
function get_name()
{
  return $this->_get_data('name', 'anonymous');
}

/** returns the userid */
function get_groupid()
{
  return $this->get_id();
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
  @param name New email address */
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
  @param name New quota address */
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
  @param name New quota slice in bytes */
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
  @param name New quota interval in seconds */
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
  
  $id=$this->get_id_by_name($name);
  if ($id>0)
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

/** Validates a user with its password
 @return true if the password is valid */
function _check_login($name, $pwd)
{
  global $db;
  if ($name=='' || $pwd=='')
    return false;
    
  $sname=mysql_escape_string($name);
  $spwd=mysql_escape_string($pwd);
  $sql="SELECT id
        FROM $db->user 
        WHERE name='$sname' AND password='$spwd'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)!=1)
    return false;

  $row=mysql_fetch_row($result);
  $this->_init_by_id($row[0]);
  return true;
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

/** Creates a new user 
  @param name Name of the user
  @param pwd Password of the user
  @return the user id on success. On failure it returns a global error code */
function create($name, $pwd)
{
  global $db;

  $err=$this->_check_name($name);
  if ($err<0)
    return $err;

  $err=$this->_check_password($pwd);
  if ($err<0)
    return $err;

  $sname=mysql_escape_string($name);
  $spwd=mysql_escape_string($pwd);
  $sql="INSERT INTO $db->user
        (name, password) 
        VALUES ('$sname', '$spwd')";
  $result=$db->query($sql);
  if (!$result)
    return ERR_USER_INSERT;

  $id=$this->get_id_by_name($name);
  $u=new User($id);
  $err=$u->_init_data();
  if ($err<0)
    return $err;

  return $id;
}

/** Initialize a new user 
  @return On failure it returns a global Error code */
function _init_data()
{
  global $conf;
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

  $conf->set($id, 'image.gacl', ACL_FULLSIZE|ACL_EDIT);
  $conf->set($id, 'image.oacl', ACL_PREVIEW);
  $conf->set($id, 'image.aacl', ACL_PREVIEW);
}

/** Return the default ACL for the group */
function get_gacl()
{
  global $conf;
  return $conf->get('image.aacl', ACL_FULLSIZE|ACL_EDIT);
}

/** Return the default ACL for other phtagr users */
function get_oacl()
{
  global $conf;
  return $conf->get('image.oacl', ACL_PREVIEW);
}

/** Return the default ACL for all */
function get_aacl()
{
  global $conf;
  return $conf->get('image.aacl', ACL_PREVIEW);
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
  if ($this->get_id()>0)
    return true;
  return false;
}

function is_guest()
{
  return false;
}

/* Return true if the given user is anonymous */
function is_anonymous()
{
  if ($this->get_id()==-1)
    return true;
  return false;
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
  $sql="SELECT COUNT(*)
        FROM $db->image
        WHERE userid=$id";
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
  $sql="SELECT SUM(bytes)
        FROM $db->image
        WHERE userid=$id";
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
  if ($this->is_admin())
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

function get_upload_dir()
{
  global $phtagr_data;
  if ($this->get_id()<=0)
    return false;

  $name=$this->get_name();
  $path=$phtagr_data.DIRECTORY_SEPARATOR.'users'.DIRECTORY_SEPARATOR.$name;
  return $path;
}

function get_theme_dir()
{
  global $phtagr_htdocs;
  global $conf;
  $path=$phtagr_htdocs.'/themes/'.$conf->get('theme', 'default');
  return $path;
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
  if ($db->prefix!='')
    return "phtagr.".$db->prefix;
  else
    return "phtagr";
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

/** Delte all data from a user
  @todo ensure to delete all data from the user */
function _delete_user_data($id)
{
  global $db;

  // delete all tags
  $sql="DELETE 
        FROM $db->imagetag
        USING $db->imagetag AS it, $db->image AS i
        WHERE i.userid=$id AND i.id=it.imageid";
  $db->query($sql);

  // delete all sets
  $sql="DELETE 
        FROM $db->imageset
        USING $db->imageset AS iset, $db->image AS i
        WHERE i.userid=$id AND i.id=iset.imageid";
  $db->query($sql);

  // delete all locations
  $sql="DELETE 
        FROM $db->imagelocation
        USING $db->imagelocation AS il, $db->image AS i
        WHERE i.userid=$id AND i.id=il.imageid";
  $db->query($sql);

  // delete all groups
  $sql="DELETE 
        FROM $db->group AS g
        WHERE g.owner=$id";
  $db->query($sql);

  // reset all comments
  $sql="UPDATE $db->comment AS c
        SET c.userid=-1
        WHERE c.userid=$id";
  $db->query($sql);

  // Delete cached image data
  $sql="SELECT id 
        FROM $db->image
        WHERE id=$id";
  $result=$db->query($sql);
  if (!$result)
    return;

  while ($row=mysql_fetch_assoc($result))
  {
    $img=new Thumb($row[0]);
    // @todo delete all cached data
  }
  
  // Delete all image data
  $sql="DELETE FROM $db->image
        WHERE id=$id";
  $result=$db->query($sql);

  // Delete all preferences
  $sql="DELETE FROM $db->conf
        WHERE userid=$id";
  $result=$db->query($sql);
  
  // @todo delete the group of the user
  // @todo delete users upload directory
  
  // Delete the user data
  $sql="DELETE $db->user
        FROM $db->user
        WHERE id=$id";

  $result=$db->query($sql);
  return true;
}


}
?>
