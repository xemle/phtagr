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
var $categories;
var $imagecategory;
var $locations;
var $imagelocation;
var $comments;
var $logs;

function Database()
{
  global $db_prefix;
  $this->_link=null;
  $this->_prefix="";
  $this->set_table_prefix($db_prefix);
}

function set_table_prefix($prefix)
{
  $this->_prefix=$prefix;
  $this->users=$prefix."users";
  $this->usergroup=$prefix."groups_users";
  $this->groups=$prefix."groups";
  $this->images=$prefix."images";
  $this->tags=$prefix."tags";
  $this->imagetag=$prefix."images_tags";
  $this->categories=$prefix."categories";
  $this->imagecategory=$prefix."categories_images";
  $this->locations=$prefix."locations";
  $this->imagelocation=$prefix."images_locations";
  $this->comments=$prefix."comments";
  $this->configs=$prefix."configs";
  $this->logs=$prefix."logs";
}

/** @return Returns the current table prefix */
function get_table_prefix()
{
  return $this->_prefix();
}

/** @return Returns the database connection resource */
function get_connection()
{
  return $this->_link;
}

/** Connect to the sql database 
  @param config Optional filename of configruation file
  @return true on success, false otherwise */
function connect($config='')
{
  if ($config=='')
    $config=getcwd().DIRECTORY_SEPARATOR."config.php";

  if (!file_exists($config) || !is_readable($config))
    return false;
 
  $db_prefix="";
  include "$config";

  if (!function_exists('mysql_connect'))
  {
    $this->error("mySQL function 'mysql_connect' does not exists. Please check your PHP5 installation");
    return false;
  }

  $this->_link=@mysql_connect(
                $db_host,
                $db_user,
                $db_password);
  if (!$this->_link)
    return false;

  if (!mysql_select_db($db_database, $this->_link))
    return false;

  $this->set_table_prefix($db_prefix);
  
  $this->query("SET NAMES 'utf8'");
  $this->query("SET CHARACTER SET 'utf8'");
  return true;
}

/** @return Returns true, if the database is connected */
function is_connected()
{
  return ($this->_link!=null)?true:false;
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
  $sql="CREATE TABLE ${prefix}create_test (".
       " id INT NOT NULL AUTO_INCREMENT,".
       " PRIMARY KEY(id))";
  $result=mysql_query($sql);
  if ($result==false)
    return "Could not create a test table";

  $sql="DROP TABLE IF EXISTS ${prefix}create_test";
  $result=mysql_query($sql);
  if (!$result)
    return "Could not delete test tables";
  
  if ($this->_link)
    mysql_close($this->_link);
  
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
    $this->categories,
    $this->imagecategory,
    $this->locations,
    $this->imagelocation,
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
  @param sql Sql statement
  @param log_error If true, log the sql query as error. Default is false
  @result On failure print an error and return false
 * */
function query($sql, $log_error=false)
{
  global $log;
  if (!$this->_link) return null;
  
  $result=@mysql_query($sql, $this->_link);
  if (!$result && !$log_error && $log)
  {
    $log->err("Could not run Query: '$sql'");
    return false;
  }
  $_SESSION['nqueries']++;
  return $result;
}

/** Query a single value or cell 
  @param sql SQL query
  @param log_error If true, log the sql query as error. Default is false
  @return Single value. Null if the query could not proceed */
function query_cell($sql, $log_error=false)
{
  $result=$this->query($sql, $log_error);
  if ($result===false)
    return null;

  $row=mysql_fetch_array($result);
  mysql_free_result($result);
  return $row[0];
}

/** Queries a row and returns it as associative array
  @param sql SQL statement
  @param log_error If true, log the sql query as error. Default is false
  @return Returns an row of associative array. */
function query_row($sql, $log_error=false)
{
  $result=$this->query($sql, $log_error);
  if ($result===false)
    return array();

  $row=mysql_fetch_array($result, MYSQL_ASSOC);
  mysql_free_result($result);
  return $row;
}

/** Queries a column and returns it as array
  @param sql SQL statement
  @param log_error If true, log the sql query as error. Default is false
  @return Returns a column of the query */
function query_column($sql, $log_error=false)
{
  $result=$this->query($sql, $log_error);
  if ($result===false)
    return array();

  $column=array();
  while ($row=mysql_fetch_array($result))
    array_push($column, $row[0]);

  mysql_free_result($result);
  return $column;
}

/** Queries a table and returns an list of associative rows
  @param sql SQL statement
  @param log_error If true, log the sql query as error. Default is false
  @return Returns a array of associative row arrays */
function query_table($sql, $log_error=false)
{
  $result=$this->query($sql, $log_error);
  if ($result===false)
    return array();

  $table=array();
  while ($row=mysql_fetch_array($result, MYSQL_ASSOC))
    array_push($table, $row);

  mysql_free_result($result);
  return $table;
}

/** Insert sql statement and return the auto generated id
  @param sql Insert sql statement
  @param log_error If true, log the sql query as error. Default is false
  @return last autoincrement id
  @see mysql_insert_id */
function query_insert($sql, $log_error=false)
{
  $result=$this->query($sql, $log_error);
  if ($result===false)
    return -1;
  return mysql_insert_id($this->_link);
}

/** Delete sql statement and return the count of affected rows
  @param sql Delete sql statement
  @param log_error If true, log the sql query as error. Default is false
  @return count of affected rows. -1 on error
  @see mysql_affected_rows */
function query_delete($sql, $log_error=false)
{
  $result=$this->query($sql, $log_error);
  if ($result===false)
    return -1;
  return mysql_affected_rows($this->_link);
}

/** Update sql statement and return the count of affected rows
  @param sql Delete sql statement
  @param log_error If true, log the sql query as error. Default is false
  @return count of affected rows
  @see mysql_affected_rows */
function query_update($sql, $log_error=false)
{
  return $this->query_delete($sql, $log_error);
}


/** Gets the tag id of a tag name 
  @param tag Name of the tag
  @param create If the tag name does not exists and this flag is true, the tag
  name will be created 
  @return -1 if the tagnam was not found, id otherwise */
function tag2id($tag, $create=false)
{
  $stag=mysql_escape_string($tag);

  $sql="SELECT id".
       " FROM $this->tags".
       " WHERE name='$stag'";
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
function category2id($category, $create=false)
{
  $scat=mysql_escape_string($category);
  $sql="SELECT id". 
       " FROM $this->categories".
       " WHERE name='$scat'";
  $result=$this->query($sql);
  if (!$result)
  {
    return -1;
  }
  else if (mysql_num_rows($result)==0)
  {
    if ($create)
    {
      $sql="INSERT INTO $this->categories (name) VALUES('$scat')";
      $result=$this->query($sql);
      if ($result)
        return $this->category2id($category);
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
  if ($type==LOCATION_ANY && $create==false)
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
  @return Array of IDs of a location. If the location is not found, is returns { -1 }. */
function location2ids($location)
{
  $slocation=mysql_escape_string($location);
  $sql="SELECT id".
       " FROM $this->locations".
       " WHERE name='$slocation'";
  $result=$this->query($sql);
  $ids=array();
  while($row=mysql_fetch_row($result))
    array_push($ids, $row[0]);
  if (count($ids)==0)
    array_push($ids, -1);
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
  $unix=abs(intval($sec));
  return strftime("%Y-%m-%d %H:%M:%S", $unix);
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
        role          TINYINT UNSIGNED,

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

        INDEX(id),
        INDEX(cookie),
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->configs (
        user_id       INT NOT NULL,
        name          VARCHAR(64),
        value         VARCHAR(192),
        
        INDEX(user_id),
        INDEX(name))";
  if (!$this->query($sql)) { return false; }
  
  $sql="CREATE TABLE $this->groups (
        id            INT NOT NULL AUTO_INCREMENT,
        user_id       INT NOT NULL,       /* User ID of the owner */
        name          VARCHAR(32) NOT NULL,
        
        INDEX(id),
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }
   
  $sql="CREATE TABLE $this->usergroup (
        user_id       INT NOT NULL,
        group_id      INT NOT NULL,
        
        INDEX(user_id),
        INDEX(group_id),
        PRIMARY KEY(user_id,group_id))";
  if (!$this->query($sql)) { return false; }
  
  $sql="CREATE TABLE $this->images (
        id            INT NOT NULL AUTO_INCREMENT,
        user_id       INT NOT NULL,
        group_id      INT DEFAULT 0,
        modified      DATETIME,           /* Syncing time between image and the
                                             database */
        created       DATETIME,           /* Insert time of the image */
        path          TEXT NOT NULL,
        file          VARCHAR(128) NOT NULL,
        bytes         INT NOT NULL,       /* Size of image in bytes */
        flag          TINYINT UNSIGNED DEFAULT 0,   /* 64=upload, 128=imported, 192=upload+imported */
        gacl          TINYINT UNSIGNED DEFAULT 0,   /* Group/Friend access control list */
        macl          TINYINT UNSIGNED DEFAULT 0,   /* Member access control list */
        pacl          TINYINT UNSIGNED DEFAULT 0,   /* All access control list */
        
        name          VARCHAR(128),
        date          DATETIME,           /* Date of image */
        width         INT UNSIGNED DEFAULT 0,
        height        INT UNSIGNED DEFAULT 0,
        orientation   TINYINT UNSIGNED DEFAULT 1,
        caption       TEXT DEFAULT NULL,

        clicks        INT DEFAULT 0,      /* Count of detailed view */
                                          /* Last time of detailed view */
        lastview      DATETIME NOT NULL DEFAULT '2006-01-08 11:00:00',
        ranking       FLOAT DEFAULT 0,    /* image ranking */

        voting        FLOAT DEFAULT 0,
        votes         INT DEFAULT 0,      /* Nuber of votes */

        longitude     FLOAT DEFAULT NULL,
        latitude      FLOAT DEFAULT NULL,

        duration      INT DEFAULT -1,     /* duration of a video in seconds */

        hue           FLOAT DEFAULT NULL, /* 0-359 */
        saturation    FLOAT DEFAULT NULL, /* 0-255 */
        luminosity    FLOAT DEFAULT NULL, /* 0-255 */

        data          BLOB,               /* For optinal data */
        
        INDEX(id),
        INDEX(date),
        INDEX(ranking),
        INDEX(voting),
        INDEX(pacl),
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->tags (
        id            INT NOT NULL AUTO_INCREMENT,
        name          VARCHAR(64) NOT NULL,
        
        INDEX(name),
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->imagetag (
        image_id      INT NOT NULL,
        tag_id        INT NOT NULL,

        INDEX(image_id),
        PRIMARY KEY(image_id,tag_id))";
  if (!$this->query($sql)) { return false; }
  
  $sql="CREATE TABLE $this->categories (
        id            INT NOT NULL AUTO_INCREMENT,
        name          VARCHAR(64) NOT NULL,
        
        INDEX(name),
        PRIMARY KEY (id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->imagecategory (
        image_id      INT NOT NULL,
        category_id   INT NOT NULL,

        INDEX(category_id),
        PRIMARY KEY(image_id,category_id))";
  if (!$this->query($sql)) { return false; }
  
  $sql="CREATE TABLE $this->locations (
        id            INT NOT NULL AUTO_INCREMENT,
        name          VARCHAR(64) NOT NULL,
        type          TINYINT UNSIGNED,    /* Country, State, City, ... */
        
        INDEX(name),
        PRIMARY KEY (id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->imagelocation (
        image_id      INT NOT NULL,
        location_id   INT NOT NULL,

        INDEX(location_id),
        PRIMARY KEY(image_id,location_id))";
  if (!$this->query($sql)) { return false; }
    
  $sql="CREATE TABLE $this->comments (
        id            INT NOT NULL AUTO_INCREMENT, 
        image_id      INT NOT NULL,
        reply         INT DEFAULT NULL,
        auth          VARCHAR(64) DEFAULT NULL,
                                          /* Name of the commentator */
        name          VARCHAR(32) NOT NULL,
                                          /* User ID, if commentator is a
                                           * phTagr user */
        user_id       INT NOT NULL DEFAULT 0,
        email         VARCHAR(64) NOT NULL DEFAULT '',
        notify        TINYINT UNSIGNED DEFAULT 0,
        url           VARCHAR(128) NOT NULL DEFAULT '',
        date          DATETIME NOT NULL DEFAULT 0,

        comment       TEXT NOT NULL,
        
        INDEX (image_id),
        PRIMARY KEY (id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE $this->logs (
        time          DATETIME,
        level         TINYINT,
        image_id      INT DEFAULT NULL,
        user_id       INT DEFAULT NULL,
        file          BLOB,
        line          INT,
        message       BLOB,

        INDEX (time),
        INDEX (level),
        INDEX (image_id),
        INDEX (user_id))";
  if (!$this->query($sql)) { return false; }

  $this->init_tables();
  return true;
}

/** Inalizes the databases. Currently, it justs writes the table version */
function init_tables()
{
  $sql="INSERT INTO $this->configs (user_id, name, value)".
       " VALUES (0, 'db.version', '".DB_VERSION."')";
  $this->query_insert($sql);
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
  $sql="DELETE FROM $this->categories";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->imagecategory";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->locations";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->imagelocation";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->comments";
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
  $sql="DELETE FROM $this->tags".
       " WHERE id NOT IN (".
       "   SELECT tag_id".
       "   FROM $this->imagetag".
       " )";
  $this->query($sql);
  $affected+=mysql_affected_rows($this->_link);

  // sets
  $sql="DELETE FROM $this->sets".
       " WHERE id NOT IN (".
       "   SELECT category_id".
       "   FROM $this->imagecategory".
       " )";
  $this->query($sql);
  $affected+=mysql_affected_rows($this->_link);

  // locations
  $sql="DELETE FROM $this->locations".
       " WHERE id NOT IN (".
       "   SELECT location_id".
       "   FROM $this->imagelocation".
       " )";
  $this->query($sql);
  $affected+=mysql_affected_rows($this->_link);

  return $affected;
}

}

?>
