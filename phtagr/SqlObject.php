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
include_once("$phtagr_lib/Constants.php");

/**
  @class SqlObject Class for a database row
*/
class SqlObject extends Base
{

var $_table;
var $_data;
var $_changes;

/** Constructor of an SQL object
  @param table Table name which should be used
  @param id ID of the table row. If the ID is greater 0, the object is
  initialized by the constructor  */
function SqlObject($table, $id=-1)
{
  $this->_table=$table;
  $this->_data=array();
  $this->_changes=array();
  if ($id>0)
    $this->init_by_id($id);
}

/** Initialize the object by an ID
  @param id Id of the Sql object */
function init_by_id($id)
{
  global $db;
  if (!is_numeric($id))
    return false;

  $sql="SELECT *
        FROM ".$this->_table."
        WHERE id=$id";

  $this->init_by_query($sql);
}

/** Initialize the object via an SQL query.
  @param sql SQL query. There is no check on the query.
  @note Do not use this function until you know what you are doing. */
function init_by_query($sql)
{
  global $db;
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)!=1)
    return false;

  $this->_data=mysql_fetch_assoc($result);
}

/** @return Returns the unique id of the sql object */
function get_id()
{
  return $this->_get_data('id', -1);
}

/** @return Returns the table name of the SQL object */
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
 * all changes, the function commit must be called.
  @param name Name of the column
  @param value Value of the column. 
  @result True on success. False otherwise 
  @note The changed data updates not the internal representation
  @see commit */
function _set_data($name, $value)
{
  global $log, $user;
  if ($this->get_id()<=0)
    return false;

  if ($this->_data[$name]==$value)
  {
    if (isset($this->_changes[$name]))
      unset($this->_changes[$name]);
    return true;
  }
  else
  {
    $log->trace("SQL row change ".$this->_table.":".$name."=".$value, $this->get_id(), $user->get_id());
    $this->_changes[$name]=$value;
  }

  return true;
}

/** @return True if data were modified, false otherwise */
function is_modified()
{
  if (count($this->_changes)>0)
    return true;
  return false;
}

/** Writes all changes to the database. It also updated the internal data of
 * the object 
  @return True if changes where writen */
function commit()
{
  global $db, $log, $user;

  $id=$this->get_id();
  if ($id<=0)
    return false;

  if (count($this->_changes)==0)
    return false;

  $changes='';
  foreach ($this->_changes as $name => $value)
  {
    if ($value==='NULL' || is_null($value))
      $svalue="NULL";
    else if ($value=="NOW()")
      $svalue=$value;
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
  $log->trace("SQL commit row changes ".$this->_table, $this->get_id(), $user->get_id());
  return true;
}

}
?>
