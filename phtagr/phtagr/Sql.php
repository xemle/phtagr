<?php

global $prefix;

class Sql
{

var $tag;
var $image;
var $pref;
var $user;
var $prefix;
var $cache;

function Sql()
{
  $this->link=NULL;
  $this->prefix='';
  $this->cache="/var/www/silef.de/phtagr/cache";
}

/** Reads the configuration file for the mySQL database 
 @return Array of data values on success, false otherwise */
function read_config()
{
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
  $this->tag=$data['db_prefix']."tag";
  $this->image=$data['db_prefix']."image";
  $this->user=$data['db_prefix']."user";
  $this->pref=$data['db_prefix']."pref";
  return $data;
}

/** Connect to the sql database 
 @return true on success */
function connect()
{
  $data=$this->read_config();
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
function test_database($host, $user, $password, $database)
{
  $prefix=intval(rand(1, 100))."-";
  
  error_reporting(0);
  $link=mysql_connect($host,$user,$password);
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
  $result=@mysql_query($sql, $this->link);
  if (!$result && !$quiet)
  {
    echo "<div class='error'>Could not run Query: '$sql'</div><br/>";
    return NULL;
  }
  return $result;
}

/** Read the global preferences from the sql database */
function read_pref()
{
  $sql="SELECT * FROM $this->pref";
  $result=$this->query($sql);
  if (!$result)
    return NULL;

  $pref=array();
  while($row = mysql_fetch_row($result)) {
    $pref[$row[0]]=$row[1];
  }
  return $pref;
}

/** creates the phTagr tables an returns true on success */
function create_tables()
{ 
  $sql="CREATE TABLE ".$this->prefix."image (
        id            INT NOT NULL AUTO_INCREMENT,
        filename      TEXT NOT NULL,
        synced        DATETIME,
        userid        INT NOT NULL,
        acl           TINYINT UNSIGNED,
        name          VARCHAR(128),
        date          DATETIME,
        width         INT UNSIGNED,
        height        INT UNSIGNED,
        orientation   TINYINT,
        camera        VARCHAR(128),
        caption       TEXT,
        clicks        INT NOT NULL,
        lastview      DATETIME,
        ranking       FLOAT DEFAULT 0,
        
        PRIMARY KEY(id,userid))";
  if (!$this->query($sql)) { return false; }

  $sql="CREATE TABLE ".$this->prefix."tag (
        imageid       INT NOT NULL,
        name          VARCHAR(64) NOT NULL)";
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
        login         TIMESTAMP,
        fsroot        TEXT DEFAULT '',

        PRIMARY KEY(id))";
  if (!$this->query($sql)) { return false; }
     
  /*
  $sql="CREATE TABLE comment (
        imageid       INT NOT NULL,
        userid        INT NOT NULL,
        comment       TEXT)";
  if (!$this->query($sql)) { return false; }
  */
  $sql="CREATE TABLE ".$this->prefix."pref (
        name          VARCHAR(64),
        value         VARCHAR(192))";
  if (!$this->query($sql)) { return false; }
  
  return true;
}

function delete_tables()
{
  $sql="DROP TABLE $this->image,$this->user,$this->tag,$this->pref";
  if (!$this->query($sql)) { return false; }
  return true;
}

function delete_images()
{
  $sql="DELETE FROM $this->image";
  if (!$this->query($sql)) { return false; }
  $sql="DELETE FROM $this->tag";
  if (!$this->query($sql)) { return false; }
  return true;
}

}

?>
