<?php

include_once("$phtagr_lib/Base.php");

/** 
  @class Database Handles the SQL database operation like connection, creation,
  queries and queries clean ups
  @todo Rename table names from sigular to plural forms
*/
class Database extends Base
{

/** Table name of users */
var $users;
var $groups;
var $usergroup;
/** Table name of configurations */
var $configs;
/** Tablename of images */
var $images;
/** Tablename of tags */
var $tags;
var $imagetag;
var $sets;
var $imageset;
var $locations;
var $imagelocation;
var $comments;
var $messages;
var $logs;

function Database()
{
  global $db_prefix;
  $this->link=null;
  $this->_set_table_names($db_prefix);
}

function _set_table_names($prefix)
{
  $this->prefix=$prefix;
  $this->users=$prefix."users";
  $this->usergroup=$prefix."usergroup";
  $this->groups=$prefix."groups";
  $this->images=$prefix."images";
  $this->tags=$prefix."tags";
  $this->imagetag=$prefix."imagetag";
  $this->sets=$prefix."sets";
  $this->imageset=$prefix."imageset";
  $this->locations=$prefix."locations";
  $this->imagelocation=$prefix."imagelocation";
  $this->comments=$prefix."comments";
  $this->messages=$prefix."messages";
  $this->configs=$prefix."configs";
  $this->log=$prefix."logs";
}

/** Connect to the sql database 
  @param config Optional filename of configruation file
  @return true on success, false otherwise */
function connect($config='')
{
  if ($config=='')
    $config=getcwd().DIRECTORY_SEPARATOR."config.php";

  if (!file_exists($config) || !is_readable($config))
  {
    $this->error(_("Could not find the configuration file config.php. Please install phTagr properly"));
    return false;
  }
 
  include "$config";

  if (!function_exists('mysql_connect'))
  {
    $this->error("mySQL function 'mysql_connect' does not exists. Please check your PHP5 installation");
    return false;
  }

  $this->link=@mysql_connect(
                $db_host,
                $db_user,
                $db_password);
  if (!$this->link)
    return false;

  if (!mysql_select_db($db_database, $this->link))
    return false;

  $this->query("SET NAMES 'utf8'");
  $this->query("SET CHARACTER SET 'utf8'");
  return true;
}

/** Test a mySQL connection 
 @return NULL on success, error string otherwise
*/
function test_database($host, $username, $password, $database)
{
  $prefix=intval(rand(1, 100))."_";
  
  if (!function_exists('mysql_connect') ||
    !function_exists('mysql_select_db') ||
    !function_exists('mysql_query'))
  {
    $this->error(_("mySQL functions are missing. Install PHP properly"));
    return null;
  }

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
  
  return null;
}

/* @return Array of all used or required table names */
function _get_table_names()
{
  return array(
    $this->users,
    $this->groups,
    $this->usergroup,
    $this->configs,
    $this->images,
    $this->tags,
    $this->imagetag,
    $this->sets,
    $this->imageset,
    $this->locations,
    $this->imagelocation,
    $this->messages,
    $this->comments,
    $this->logs);
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
  $_SESSION['nqueries']++;
  return $result;
}

/** Gets the tag id of a tag name 
  @param tag Name of the tag
  @param create If the tag name does not exists and this flag is true, the tag
  name will be created 
  @return -1 if the tagnam was not found, id otherwise */
function tag2id($tag, $create=false)
{
  $stag=mysql_escape_string($tag);

  $sql="SELECT id 
        FROM $this->tags 
        WHERE name='$stag'";
  $result=$this->query($sql);
  if (!$result)
  {
    return -1;
  }
  else if (mysql_num_rows($result)==0)
  {
    if ($create)
    {
      $sql="INSERT INTO $this->tags (name) VALUES('$stag')";
      $result=$this->query($sql);
      if ($result)
        return $this->tag2id($tag);
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

/** Gets the set id of a set name 
  @param set Name of the set
  @param create If the set name does not exists and this flag is true, the set
  name will be created 
  @return -1 if the setnam was not found, id otherwise */
function set2id($set, $create=false)
{
  $sset=mysql_escape_string($set);
  $sql="SELECT id 
        FROM $this->sets 
        WHERE name='$sset'";
  $result=$this->query($sql);
  if (!$result)
  {
    return -1;
  }
  else if (mysql_num_rows($result)==0)
  {
    if ($create)
    {
      $sql="INSERT INTO $this->sets (name) VALUES('$sset')";
      $result=$this->query($sql);
      if ($result)
        return $this->set2id($set);
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
  $sql="SELECT name
        FROM $this->tags
        WEHERE id=$id";
  $result=$this->query($sql);
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
  $slocation=mysql_escape_string($location);
  if ($type==LOCATION_UNDEFINED && $create==false)
    $sql="SELECT id FROM $this->locations WHERE name='$slocation'";
  else
    $sql="SELECT id FROM $this->locations WHERE name='$slocation' AND type=$type";
  $result=$this->query($sql);
  if (!$result)
  {
    return -1;
  }
  else if (mysql_num_rows($result)==0)
  {
    if ($create)
    {
      $sql="INSERT INTO $this->locations (name, type) VALUES('$slocation', $type)";
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

/** @location Untyped location name
  @return Array of IDs of a location */
function location2ids($location)
{
  $slocation=mysql_escape_string($location);
  $sql="SELECT id
        FROM $this->locations
        WHERE name='$slocation'";
  $result=$this->query($sql);
  $ids=array();
  while($row=mysql_fetch_row($result))
  {
    array_push($ids, $row[0]);
  }
  return $ids;
}


/** Converts the mysql time stamp to the unix timestamp 
  @param date Date could have the format of 'YYYY-MM-DD HH:MM:SS' or 
  'YYYY-MM-DD' or 'HH:MM:SS'
  @return Unix timestamp. On error it returns -1. */
function date_mysql_to_unix($date)
{
  $time=false;

  if (strlen($date)==10 &&
    preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})$/', $date, $m)) 
    $time=mktime(0, 0, 0, $m[2], $m[3], $m[1]);

  if (strlen($date)==8&&
    preg_match('/^(\\d{2}):(\\d{2}):(\\d{2})$/', $date, $m)) 
    $time=mktime($m[1], $m[2], $m[3], 0, 0, 0);

  if (strlen($date)==19 && 
    preg_match('/^(\\d{4})-(\\d{2})-(\\d{2}) (\\d{2}):(\\d{2}):(\\d{2})$/', $date, $m))
    $time=mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]);

  if ($time===false)
    return -1;

  return $time;
}

/** Converts the unix time to mysql time stamp
  @param sec Seconds sind 1.1.1970 
  @return mysql time string */
function date_unix_to_mysql($sec)
{
  if (!is_numeric($sec))  
    $sec=0;
  if ($sec<0)
    $sec=0;
  return strftime("%Y-%m-%d %H:%M:%S", $sec);
}

/** Creates the phTagr tables
  @return Returns true on success. False otherwise */
function create_tables()
{ 
  $sql="CREATE TABLE $this->users (
        id            INT NOT NULL AUTO_INCREMENT,
        name          VARCHAR(32) NOT NULL,
        password      VARCHAR(32) NOT NULL,
        
        created       DATETIME NOT NULL DEFAULT 0,
        creator       INT DEFAULT 0,
        updated       TIMESTAMP,
        expire        DATETIME DEFAULT NULL,
        type          TINYINT UNSIGNED,

        firstname     VARCHAR(32),
        lastname      VARCHAR(32) NOT NULL,
        email         VARCHAR(64),
        
        cookie        VARCHAR(64) DEFAULT NULL,
        cookie_expire DATETIME DEFAULT NULL,

        quota         INT DEFAULT 0,    /* Absolut quota in bytes */
        qslice        INT DEFAULT 0,    /* Upload slice in bytes */
        qinterval     INT DEFAULT 0,    /* Upload quota interval in seconds.
                                           The user is allowed to upload qslice
                                           bytes in qinterval seconds. */
        data          BLOB,             /* For optional and individual values */

        INDEX(cookie),
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->configs (
        userid        INT NOT NULL,
        name          VARCHAR(64),
        value         VARCHAR(192),
        
        INDEX(userid),
        INDEX(name))";
  if (!$this->query($sql)) { return false; }
  
  $sql="CREATE TABLE $this->groups (
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
  
  $sql="CREATE TABLE $this->images (
        id            INT NOT NULL AUTO_INCREMENT,
        userid        INT NOT NULL,
        groupid       INT DEFAULT 0,
        synced        DATETIME,           /* Syncing time between image and the
                                             database */
        created       DATETIME,           /* Insert time of the image */
        filename      TEXT NOT NULL,
        bytes         INT NOT NULL,       /* Size of image in bytes */
        is_upload     TINYINT UNSIGNED,   /* 0=local, 1=upload */
        gacl          TINYINT UNSIGNED,   /* Group/Friend access control list */
        macl          TINYINT UNSIGNED,   /* Member access control list */
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

        duration      INT DEFAULT -1,     /* duration of a video in seconds */

        hue           FLOAT,              /* 0-359 */
        saturation    FLOAT,              /* 0-255 */
        luminosity    FLOAT,              /* 0-255 */

        data          BLOB,               /* For optinal data */
        
        INDEX(date),
        INDEX(ranking),
        INDEX(voting),
        INDEX(aacl),
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->tags (
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
  $sql="CREATE TABLE $this->sets (
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
  
  $sql="CREATE TABLE $this->locations (
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
    
  $sql="CREATE TABLE $this->comments (
        id            INT NOT NULL AUTO_INCREMENT, 
        imageid       INT NOT NULL,
        reply         INT DEFAULT NULL,
        auth          VARCHAR(64) DEFAULT NULL,
                                          /* Name of the commentator */
        name          VARCHAR(32) NOT NULL,
                                          /* User ID, if commentator is a
                                           * phTagr user */
        userid        INT NOT NULL DEFAULT 0,
        email         VARCHAR(64) NOT NULL DEFAULT '',
        notify        TINYINT UNSIGNED DEFAULT 0,
        url           VARCHAR(128) NOT NULL DEFAULT '',
        date          DATETIME NOT NULL DEFAULT 0,

        comment       TEXT NOT NULL,
        
        INDEX (imageid),
        PRIMARY KEY (id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->messages (
        id            INT NOT NULL AUTO_INCREMENT,
        from_id       INT NOT NULL,
        to_id         INT NOT NULL,
        date          DATETIME NOT NULL,
        expire        DATETIME DEFAULT NULL,
        type          TINYINT UNSIGNED DEFAULT 0,
        private       BLOB,
        subject       VARCHAR(128),
        body          BLOB,

        INDEX (to_id),
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->logs (
        time          DATETIME,
        level         TINYINT,
        image         INT DEFAULT NULL,
        user          INT DEFAULT NULL,
        file          BLOB,
        line          INT,
        message       BLOB,

        INDEX (time),
        INDEX (level))";
  if (!$this->query($sql)) { return false; }

  $this->init_tables();
  return true;
}

/** Inalizes the databases. Currently, it justs writes the table version */
function init_tables()
{
  $sql="INSERT INTO $this->configs (userid, name, value)
        VALUES ('0', 'db.version', '".DB_VERSION."')";
  $this->query($sql);
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
  $sql="DELETE FROM $this->images";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->tags";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->imagetag";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->sets";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->imageset";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->locations";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->imagelocation";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->comments";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->messages";
  if (!$this->query($sql)) { return false; }
  return true;
}

/** Deletes all unassigned meta data from tags, sets, and locations. It deletes
 * only these values, which are not assigned to any images. 
 @return Count of deleted data */
function delete_unassigned_data() 
{
  $affected=0;
  // tags
  $sql="DELETE FROM $this->tags 
        WHERE id NOT IN (
          SELECT tagid
          FROM $this->imagetag
        )";
  $this->query($sql);
  $affected+=mysql_affected_rows($this->link);

  // sets
  $sql="DELETE FROM $this->sets
        WHERE id NOT IN (
          SELECT setid
          FROM $this->imageset
        )";
  $this->query($sql);
  $affected+=mysql_affected_rows($this->link);

  // locations
  $sql="DELETE FROM $this->locations
        WHERE id NOT IN (
          SELECT locationid
          FROM $this->imagelocation
        )";
  $this->query($sql);
  $affected+=mysql_affected_rows($this->link);

  return $affected;
}

}

?>
