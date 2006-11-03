<?php

include_once("$phtagr_lib/Base.php");
include_once("$phtagr_lib/Constants.php");

/**
@class Group
*/
class SqlObject extends Base
{

var $_table;
var $_data;
var $_changes;

function SqlObject($table, $id=-1)
{
  $this->_table=$table;
  $this->_data=array();
  $this->_changes=array();
  if ($id>0)
    $this->_init_by_id($id);
}

function _init_by_id($id)
{
  global $db;
  if (!is_numeric($id))
    return false;

  $sql="SELECT *
        FROM ".$this->_table."
        WHERE id=$id";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)!=1)
    return false;

  $this->_data=mysql_fetch_assoc($result);
}

/** Returns the userid of the current session 
  @return The value for an member is greater 0, an anonymous user has the ID
  -1.*/
function get_id()
{
  return $this->_get_data('id', -1);
}

function get_table_name()
{
  return $this->_table;
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

  $sql="UPDATE ".$this->_table."
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

}
?>
