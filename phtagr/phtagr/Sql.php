<?php

global $prefix;
include_once("$prefix/Base.php");

/** 
  @class Sql Handles the SQL connection and queries
*/
class Sql extends Base
{

/** Prefix of tables */
var $prefix; 
/** Table name of users */
var $user;
var $group;
var $usergroup;
/** Tablename of images */
var $image;
/** Tablename of tags */
var $tag;
var $imagetag;
var $set;
var $imageset;
/** Table name of preferences */
var $pref;

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
    $config=getcwd()."/phtagr/vars.inc";

  if (!file_exists($config) || !is_readable($config))
  {
    return false;
  }
  
  $f=fopen($config, "r");
  $data=array();
  while($line=fscanf($f, "%s\n"))
  {
    // do not read comments, which are starting with '#' sign
    if ($line[0]{0}!='#')
    {
      list($name, $value)=split("=", $line[0]);
      $data[$name]=$value;
    }
  }
  fclose($f);
  $this->prefix=$data['db_prefix'];
  $this->user=$data['db_prefix']."user";
  $this->usergroup=$data['db_prefix']."usergroup";
  $this->group=$data['db_prefix']."groups";
  $this->image=$data['db_prefix']."image";
  $this->tag=$data['db_prefix']."tag";
  $this->imagetag=$data['db_prefix']."imagetag";
  $this->set=$data['db_prefix']."sets";
  $this->imageset=$data['db_prefix']."imageset";
  $this->pref=$data['db_prefix']."pref";

  return $data;
}

/** Connect to the sql database 
  @param config Optional filename of configruation file
  @return true on success, false otherwise */
function connect($config='')
{
  $data=$this->read_config($config);
  if ($data==false)
    return false;
    
  $this->link=@mysql_connect(
                $data['db_host'],
                $data['db_user'],
                $data['db_password']);
  if ($this->link)
    return mysql_select_db($data['db_database'], $this->link);
}

/** Test a mySQL connection 
 @return true on success, error string otherwise
*/
function test_database($host, $username, $password, $database)
{
  $prefix=intval(rand(1, 100))."-";
  
  error_reporting(0);
  $link=mysql_connect($host,$username,$password);
  error_reporting(E_ERROR | E_WARNING | E_PARSE);
  if ($link) 
    $err=!mysql_select_db($database, $link);
  else
    return "Could not connect to the database";
    
  // check to create tables
  $sql="CREATE TABLE ${prefix}create-test (
          id INT NOT NULL AUTO_INCREMENT,
          PRIMARY KEY(id))";
  $result=mysql_query($sql);
  if ($result==false)
    return "Could not create a table";

  $sql="DROP TABLE IF EXISTS create-test";
  $result=mysql_query($sql);
  if (!$result)
    return "Could not delete tables";
  
  if ($this->link)
    mysql_close($this->link);
  
  return true;
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

/** creates the phTagr tables an returns true on success */
function create_tables()
{ 
  $sql="CREATE TABLE ".$this->prefix."image (
        id            INT NOT NULL AUTO_INCREMENT,
        userid        INT NOT NULL,
        groupid       INT NOT NULL,
        synced        DATETIME,           /* syncing time between image and the
                                             database */
        created       DATETIME,           /* insert time of the image */
        filename      TEXT NOT NULL,
        bytes         INT NOT NULL,       /* size of image in bytes */
        is_upload     TINYINT UNSIGNED,   /* 0=local, 1=upload */
        gacl          TINYINT UNSIGNED,   /* group access control list */
        oacl          TINYINT UNSIGNED,   /* other access control list */
        aacl          TINYINT UNSIGNED,   /* all access control list */
        
        name          VARCHAR(128),
        date          DATETIME,           /* date of image */
        width         INT UNSIGNED,
        height        INT UNSIGNED,
        orientation   TINYINT,
        caption       TEXT,

        clicks        INT DEFAULT 0,      /* count of detailed view */
        lastview      DATETIME,           /* last time of detailed view */
        ranking       FLOAT DEFAULT 0,    /* image ranking */
        
        INDEX(date),
        INDEX(ranking),
        INDEX(aacl),
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE ".$this->prefix."tag (
        id            INT NOT NULL AUTO_INCREMENT,
        name          VARCHAR(64) NOT NULL,
        
        INDEX(name),
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }
  
  $sql="CREATE TABLE ".$this->prefix."imagetag (
        imageid       INT,
        tagid         INT,

        PRIMARY KEY(imageid,tagid))";
  if (!$this->query($sql)) { return false; }
  
  // 'set' is a reserved word
  $sql="CREATE TABLE ".$this->prefix."sets (
        id            INT NOT NULL AUTO_INCREMENT,
        name          VARCHAR(64) NOT NULL,
        
        INDEX(name),
        PRIMARY KEY (id))";
  if (!$this->query($sql)) { return false; }
  
  $sql="CREATE TABLE ".$this->prefix."imageset (
        imageid       INT,
        setid         INT,

        PRIMARY KEY(imageid,setid))";
  if (!$this->query($sql)) { return false; }
  
  $sql="CREATE TABLE ".$this->prefix."user (
        id            INT NOT NULL AUTO_INCREMENT,
        name          VARCHAR(32) NOT NULL,
        password      VARCHAR(32),
        
        surname       VARCHAR(32),
        forname       VARCHAR(32),
        email         VARCHAR(64),
        
        created       DATETIME,
        updated       TIMESTAMP,
        fsroot        TEXT DEFAULT '',
        quota         INT,
        quota_interval INT,

        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }
  
  // 'group' is a reserved word
  $sql="CREATE TABLE ".$this->prefix."groups (
        id            INT NOT NULL AUTO_INCREMENT,
        userid        INT,
        name          VARCHAR(32) NOT NULL,
        
        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }
   
  $sql="CREATE TABLE ".$this->prefix."usergroups (
        userid        INT,
        groupid       INT,
        
        PRIMARY KEY(userid,groupid))";
  if (!$this->query($sql)) { return false; }
     
  $sql="CREATE TABLE comment (
        imageid       INT NOT NULL,
        user          VARCHAR(32),
        email         VARCHAR(64),
        date          DATETIME,
        comment       TEXT)";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE ".$this->prefix."pref (
        userid        INT NOT NULL,
        groupid       INT NOT NULL,
        name          VARCHAR(64),
        value         VARCHAR(192),
        
        INDEX(userid))";
  if (!$this->query($sql)) { return false; }

  return true;
}

/** Deletes all tabels used by the phtagr instance */
function delete_tables()
{
  $sql="DROP TABLE $this->image,$this->user,$this->group,$this->tag,$this->set,$this->pref,$this->usergroup";
  if (!$this->query($sql)) { return false; }
  return true;
}

/** Delete all image information */
function delete_images()
{
  $sql="DELETE FROM $this->image";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->tag";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->set";
  if (!$this->query($sql)) { return false; }
  return true;
}

}

?>
