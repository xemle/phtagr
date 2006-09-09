<?php

global $prefix;
include_once("$phtagr_prefix/Base.php");

/** 
  @class Sql Handles the SQL database operation like connection, creation,
  queries and queries clean ups 
*/
class Sql extends Base
{

/** Table name of users */
var $user;
var $group;
var $usergroup;
/** Table name of preferences */
var $pref;
/** Tablename of images */
var $image;
/** Tablename of tags */
var $tag;
var $imagetag;
var $set;
var $imageset;
var $location;
var $imagelocation;
var $comment;

function Sql()
{
  $this->link=NULL;
  $this->prefix='';
}

/** Reads the configuration file for the mySQL database 
  @param config Optional filename of configruation file
  @return Array of data values on success, false otherwise 
 */
function read_config($config='')
{
  if ($config=='')
    $config=getcwd().DIRECTORY_SEPARATOR."config.php";

  if (!file_exists($config) || !is_readable($config))
    return false;
 
  include "$config";

  $this->prefix=$db_prefix;
  $this->user=$db_prefix."user";
  $this->usergroup=$db_prefix."usergroup";
  $this->group=$db_prefix."groups";
  $this->image=$db_prefix."image";
  $this->tag=$db_prefix."tag";
  $this->imagetag=$db_prefix."imagetag";
  $this->set=$db_prefix."sets";
  $this->imageset=$db_prefix."imageset";
  $this->location=$db_prefix."location";
  $this->imagelocation=$db_prefix."imagelocation";
  $this->comment=$db_prefix."comment";
  $this->pref=$db_prefix."pref";

  return true;
}

/** Connect to the sql database 
  @param config Optional filename of configruation file
  @return true on success, false otherwise */
function connect($config='')
{
  if (!$this->read_config($config))
    return false;
  
  if ($config=='')
    $config=getcwd().DIRECTORY_SEPARATOR."config.php";

  if (!file_exists($config) || !is_readable($config))
    return false;
 
  include "$config";

  $this->link=@mysql_connect(
                $db_host,
                $db_user,
                $db_password);
  if ($this->link)
    return mysql_select_db($db_database, $this->link);
 
  return false;
}

/** Test a mySQL connection 
 @return NULL on success, error string otherwise
*/
function test_database($host, $username, $password, $database)
{
  $prefix=intval(rand(1, 100))."_";
  
  error_reporting(0);
  $link=mysql_connect($host,$username,$password);
  error_reporting(E_ERROR | E_WARNING | E_PARSE);
  if ($link) 
    $err=!mysql_select_db($database, $link);
  else
    return "Could not connect to the database";
    
  // check to create tables
  $sql="CREATE TABLE ${prefix}create_test (
          id INT NOT NULL AUTO_INCREMENT,
          PRIMARY KEY(id))";
  $result=mysql_query($sql);
  if ($result==false)
    return "Could not create a test table";

  $sql="DROP TABLE IF EXISTS ${prefix}create_test";
  $result=mysql_query($sql);
  if (!$result)
    return "Could not delete test tables";
  
  if ($this->link)
    mysql_close($this->link);
  
  return NULL;
}

/* @return Array of all used or required table names */
function _get_table_names()
{
  return array(
    $this->user,
    $this->group,
    $this->usergroup,
    $this->pref,
    $this->image,
    $this->tag,
    $this->imagetag,
    $this->set,
    $this->imageset,
    $this->location,
    $this->imagelocation,
    $this->comment);
}

/** Checks whether the required tables for phTagr already exist
  @result If none exist we return 0, if all exist 1, if some of the required
  exist -1.
 */
function tables_exist()
{
  $tables = $this->_get_table_names();

  $n_existing=0;
  foreach ($tables as $tbl)
  {
    $sql="SHOW TABLES LIKE '$tbl'";
    $result=$this->query($sql);
    if ($result && mysql_num_rows($result)==1)
      $n_existing++;
  }
  
  if (!$n_existing)
    return 0;
  if ($n_existing==count($tables))
    return 1;
  
  return -1;
}

/** Sql query an return the result. 
 @result On failure print an error and return NULL
 * */
function query($sql, $quiet=false)
{
  if (!$this->link) return null;
  
  $result=@mysql_query($sql, $this->link);
  if (!$result && !$quiet)
  {
    $this->error("Could not run Query: '$sql'");
    return NULL;
  }
  return $result;
}

/** Read the global preferences from the sql database */
function read_pref($userid=-1)
{
  // read global preferences first
  $sql="SELECT * 
        FROM $this->pref
        WHERE userid=0";
  $result=$this->query($sql);
  if (!$result)
    return NULL;

  $pref=array();
  while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $pref[$row['name']]=$row['value'];
  }
  
  // read user preferences
  $sql="SELECT * 
        FROM $this->pref
        WHERE userid=$userid";
  $result=$this->query($sql);
  if ($result)
  {
    while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $pref[$row['name']]=$row['value'];
    }
  }
  
  return $pref;
}

/** Gets the tag id of a tag name 
  @param tagname name of the tag
  @param create If the tag name does not exists and this flag is true, the tag
  name will be created 
  @return -1 if the tagnam was not found, id otherwise */
function tag2id($tagname, $create=false)
{
  $sql="SELECT id FROM $this->tag WHERE name='$tagname'";
  $result=$this->query($sql);
  if (!$result)
  {
    return -1;
  }
  else if (mysql_num_rows($result)==0)
  {
    if ($create)
    {
      $sql="INSERT INTO $this->tag (name) VALUES('$tagname')";
      $result=$this->query($sql);
      if ($result)
        return $this->tag2id($tagname);
      else 
        return -1;
    }
    else
    {
      return -1;
    }
  }
  $row=mysql_fetch_row($result);
  return $row[0];
}

/** Returns the name of a tag by an ID
  @param id Id of the tag
  @return Name of the tag, null if it does not exists */
function id2tag($id)
{
  global $db;
  $sql="SELECT name
        FROM $db->tag
        WEHERE id=$id";
  $result=$db->query($sql);
  if (!$result)
  {
    return null;
  }
  else
  {
    $row=mysql_fetch_row($result);
    return $row[0];
  }
}

/** Gets the id of a location 
  @param location name of the location
  @param type Type of the location
  @param create If the tag name does not exists and this flag is true, the tag
  name will be created 
  @return -1 if the location was not found, id otherwise */
function location2id($location, $type, $create=false)
{
  if ($type==LOCATION_UNDEFINED && $create==false)
    $sql="SELECT id FROM $this->location WHERE name='$location'";
  else
    $sql="SELECT id FROM $this->location WHERE name='$location' AND type=$type";
  $result=$this->query($sql);
  if (!$result)
  {
    return -1;
  }
  else if (mysql_num_rows($result)==0)
  {
    if ($create)
    {
      $sql="INSERT INTO $this->location (name, type) VALUES('$location', $type)";
      $result=$this->query($sql);
      if ($result)
        return $this->location2id($location, $type);
      else 
        return -1;
    }
    else
    {
      return -1;
    }
  }
  $row=mysql_fetch_row($result);
  return $row[0];
}

/** Creates the phTagr tables
  @return Returns true on success. False otherwise */
function create_tables()
{ 
  $sql="CREATE TABLE $this->user (
        id            INT NOT NULL AUTO_INCREMENT,
        name          VARCHAR(32) NOT NULL,
        password      VARCHAR(32) NOT NULL,
        
        firstname     VARCHAR(32),
        lastname      VARCHAR(32) NOT NULL,
        email         VARCHAR(64),
        
        created       DATETIME NOT NULL DEFAULT NOW(),
        updated       TIMESTAMP,
        fsroot        TEXT DEFAULT '',
        quota         INT,              /* Users quota in bytes */
        quota_interval INT,             /* Upload quota interval in seconds.
                                           The user is allowed to upload quota
                                           bytes in quota_interval seconds. */
        quota_max     INT,              /* Maximum upload limit in bytes */
        data          BLOB,             /* For optional and individual values */

        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->pref (
        userid        INT NOT NULL,
        groupid       INT NOT NULL,
        name          VARCHAR(64),
        value         VARCHAR(192),
        
        INDEX(userid),
        INDEX(groupid))";
  if (!$this->query($sql)) { return false; }
  
  $sql="CREATE TABLE $this->group (
        id            INT NOT NULL AUTO_INCREMENT,
        owner         INT NOT NULL,       /* User ID of the owner */
        name          VARCHAR(32) NOT NULL,
        
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }
   
  $sql="CREATE TABLE $this->usergroup (
        userid        INT NOT NULL,
        groupid       INT NOT NULL,
        
        PRIMARY KEY(userid,groupid))";
  if (!$this->query($sql)) { return false; }
  
  $sql="CREATE TABLE $this->image (
        id            INT NOT NULL AUTO_INCREMENT,
        userid        INT NOT NULL,
        groupid       INT NOT NULL,
        synced        DATETIME,           /* Syncing time between image and the
                                             database */
        created       DATETIME,           /* Insert time of the image */
        filename      TEXT NOT NULL,
        bytes         INT NOT NULL,       /* Size of image in bytes */
        is_upload     TINYINT UNSIGNED,   /* 0=local, 1=upload */
        gacl          TINYINT UNSIGNED,   /* Group/Friend access control list */
        oacl          TINYINT UNSIGNED,   /* Other/Member access control list */
        aacl          TINYINT UNSIGNED,   /* All access control list */
        
        name          VARCHAR(128),
        date          DATETIME,           /* Date of image */
        width         INT UNSIGNED,
        height        INT UNSIGNED,
        orientation   TINYINT,
        caption       TEXT,

        clicks        INT DEFAULT 0,      /* Count of detailed view */
                                          /* Last time of detailed view */
        lastview      DATETIME NOT NULL DEFAULT '2006-01-08 11:00:00',
        ranking       FLOAT DEFAULT 0,    /* image ranking */

        voting        FLOAT DEFAULT 0,
        votes         INT DEFAULT 0,      /* Nuber of votes */

        longitude     FLOAT,
        latitude      FLOAT,

        data          BLOB,               /* For optinal data */
        
        INDEX(date),
        INDEX(ranking),
        INDEX(voting),
        INDEX(aacl),
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->tag (
        id            INT NOT NULL AUTO_INCREMENT,
        name          VARCHAR(64) NOT NULL,
        
        INDEX(name),
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->imagetag (
        imageid       INT NOT NULL,
        tagid         INT NOT NULL,

        PRIMARY KEY(imageid,tagid))";
  if (!$this->query($sql)) { return false; }
  
  // 'set' is a reserved word
  $sql="CREATE TABLE $this->set (
        id            INT NOT NULL AUTO_INCREMENT,
        name          VARCHAR(64) NOT NULL,
        
        INDEX(name),
        PRIMARY KEY (id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->imageset (
        imageid       INT NOT NULL,
        setid         INT NOT NULL,

        PRIMARY KEY(imageid,setid))";
  if (!$this->query($sql)) { return false; }
  
  $sql="CREATE TABLE $this->location (
        id            INT NOT NULL AUTO_INCREMENT,
        name          VARCHAR(64) NOT NULL,
        type          TINYINT UNSIGNED,    /* Country, State, City, ... */
        
        INDEX(name),
        PRIMARY KEY (id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->imagelocation (
        imageid       INT NOT NULL,
        locationid    INT NOT NULL,

        PRIMARY KEY(imageid,locationid))";
  if (!$this->query($sql)) { return false; }
    
  $sql="CREATE TABLE $this->comment (
        imageid       INT NOT NULL,
                                          /* Name of the commentator */
        name          VARCHAR(32) NOT NULL,
                                          /* User ID, if commentator is a
                                           * phTagr user */
        userid        INT NOT NULL DEFAULT 0,
        email         VARCHAR(64) NOT NULL DEFAULT '',
        url           VARCHAR(128) NOT NULL DEFAULT '',
        date          DATETIME NOT NULL DEFAULT NOW(),

        comment       TEXT NOT NULL,
        
        PRIMARY KEY(imageid))";
  if (!$this->query($sql)) { return false; }

  return true;
}

/** Deletes all tabels used by the phtagr instance */
function delete_tables()
{
  $tables=$this->_get_table_names();
  $sql="DROP TABLES IF EXISTS ";
  for($i=0; $i<count($tables); $i++)
  {
    $sql.=$tables[$i];
    if ($i<count($tables)-1)
      $sql.=",";
  }
  if (!$this->query($sql)) { return false; }
  return true;
}

/** Delete all image information from the databases */
function delete_images()
{
  $sql="DELETE FROM $this->image";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->tag";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->imagetag";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->set";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->imageset";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->location";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->imagelocation";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->comment";
  if (!$this->query($sql)) { return false; }
  return true;
}

}

?>
