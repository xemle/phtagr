<?php

global $prefix;
include "$prefix/vars.inc";

class Sql
{

var $host;
var $user;
var $password;
var $database;
var $cache;
var $link;

function Sql()
{
    global $db_host;
    global $db_database;
    global $db_user;
    global $db_password;
    global $db_cache;
    
    $this->host=$db_host;
    $this->database=$db_database;
    $this->user=$db_user;
    $this->password=$db_password;
    $this->cache=$db_cache;
    $this->link=NULL;
}

function connect()
{
    $this->link=mysql_connect($this->host,$this->user,$this->password);
    if ($this->link) mysql_select_db($this->database, $this->link);
}

/** Test a sql connection 
 @return true on success, false on failure.
*/
function test_db($host, $user, $password, $database)
{
    error_reporting(0);
    $link=mysql_connect($host,$user,$password);
    if ($this->link) 
    {
      $success = mysql_select_db($this->database, $this->link);
    }
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    if ($this->link && $success)
    {
      mysql_close($this->link);
      return true;
    }
    return false;
}


/** Sql query an return the result. On failure print an error and return NULL
 * */
function query($sql, $quiet=false)
{
    $result = mysql_query($sql, $this->link);
    if (!$result && !$quiet)
    {
        echo "<div class='error'>Could not run Query: '$sql'</div><br/>";
        return NULL;
    }
    return $result;
}

/** creates the phTagr tables an returns true on success */
function create_tables()
{ 
    $sql="CREATE TABLE image (
          id            INT NOT NULL AUTO_INCREMENT,
          filename      TEXT NOT NULL,
          synced        TIMESTAMP,
          userid        INT NOT NULL,
          name          VARCHAR(128),
          date          DATETIME,
          width         INT UNSIGNED,
          height        INT UNSIGNED,
          orientation   TINYINT,
          camera        VARCHAR(128),
          caption       TEXT,
          clicks        INT NOT NULL,
          acl           TINYINT UNSIGNED,
          
          PRIMARY KEY(id,userid))";
    if (!$this->query($sql)) { return false; }

    $sql="CREATE TABLE tag (
          imageid       INT NOT NULL,
          name          VARCHAR(64) NOT NULL)";
    if (!$this->query($sql)) { return false; }

    $sql="CREATE TABLE user (
          id            INT NOT NULL AUTO_INCREMENT,
          name          VARCHAR(32) NOT NULL,
          password      VARCHAR(32),
          surname       VARCHAR(32),
          forname       VARCHAR(32),
          email         VARCHAR(64),
          created       DATETIME,
          updated       TIMESTAMP,
          login         TIMESTAMP,
          sessionkey    VARCHAR(16),
          root          TEXT,

          PRIMARY KEY(id))";
    if (!$this->query($sql)) { return false; }
       
    /*
    $sql="CREATE TABLE comment (
          imageid       INT NOT NULL,
          userid        INT NOT NULL,
          comment       TEXT)";
    if (!$this->query($sql)) { return false; }
    $sql="CREATE TABLE pref (
          name          VARCHAR(64),
          value         VARCHAR(192)";
    if (!$this->query($sql)) { return false; }
     */
    
    return true;
}

function delete_tables()
{
    $sql="DROP TABLE image,user,tag";
    if (!$this->query($sql)) { return false; }
    return true;
}

}

?>
