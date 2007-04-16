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

/** @class Config 
  Configuration of a user.
  This class holds the parameter and configuration of an user. A parameter
  has a userid, a name and a value. Default parameters have the userid 0. If
  the name ends with brackest '[]', the parameter is an array. */
class Config extends Base
{

/** Current user id */
var $_userid;
/** Array of parameters */
var $_data;

function Config($userid=0)
{
  $this->_data=array();
  $this->load($userid);
}

/** Read the global preferences from the sql database. If the user id is not
 * zero, the default values are loaded first and than the user parameters. 
  @param userid User id for the paramter. */
function load($userid=0)
{
  global $db;
  if ($userid!=0)
    $this->load(0);
  $this->_userid=$userid;

  if ($userid<0)
    return;

  $sql="SELECT name, value
        FROM $db->configs
        WHERE userid=$userid";
  $result=$db->query($sql);
  if (!$result)
    return false;
  while($row = mysql_fetch_array($result, MYSQL_ASSOC)) 
  {
    $name=$row['name'];
    $value=$row['value'];
    $nlen=strlen($name);

    // Distinguish beween single values and arrays
    if (substr($name, $nlen-2, 2)=='[]')
    {
      if (!isset($this->_data[$name]))
        $this->_data[$name]=array();
      array_push($this->_data[$name], $value);
    } else {
      $this->_data[$name]=$value;
    }
  }
}

/** Returns a configuration value.
  @param name Name of the configuration parameter 
  @param default Default value, if the value is not found
  @result Returns the value if it exists. If a value does not exist it returns
  the default value. */
function get($name, $default=null)
{
  if (isset($this->_data[$name]))
    return ($this->_data[$name]);

  return $default;
}

/** Queries a parameter from the database directly
  @param userid User ID
  @param name Parameter name
  @param default Default parameter, if the parameter does not exists 
  @return Return the parameter is exists. Otherwise the given default name */
function query($userid, $name, $default)
{
  global $db;
  $sname=mysql_escape_string($name);
  $sql="SELECT value 
        FROM $db->configs
        WHERE (userid=0 OR userid=$userid) AND name='$sname'
        ORDER BY userid DESC
        LIMIT 0,1";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)==0)
    return $default;

  $row=mysql_fetch_row($result);
  return $row[0];
}

/** Adds or updates a name-value pair in the configuration table. 
  @param userid User ID of the parameter
  @param name Parameter name
  @param value Parameter value
  @return True on success, false otherwise */
function _set($userid, $name, $value)
{
  global $db;
  if ($value===null)
    return false;

  $sname=mysql_escape_string($name);
  $svalue=mysql_escape_string($value);
  $sql="SELECT value
        FROM $db->configs
        WHERE userid=$userid AND name=\"$sname\"";
  $result=$db->query($sql);

  // Insert new value
  if (mysql_num_rows($result)==0)
    $sql="INSERT INTO $db->configs 
          (userid, name, value) VALUES 
          ($userid,'$sname','$svalue')";    
  // Update single parameter 
  elseif (substr($name, -2)!='[]')
  {
    // Check existing value
    $row=mysql_fetch_row($result);
    if ($row[0]==$value)
      return true;

    $sql="UPDATE $db->configs 
          SET value='$svalue' 
          WHERE userid=$userid AND name='$sname'";
  }
  // Update array
  else
  {
    // Check existing values
    while ($row=mysql_fetch_row($result))
    {
      if ($row[0]==$value)
        return true;
    }
    $sql="INSERT INTO $db->configs 
          (userid, name, value) VALUES 
          ($userid,'$sname','$svalue')";    
  }

  $result=$db->query($sql);
  if (!$result)
    return false;

  $this->_data[$name]=$value;
  return true;
}

/** Sets a parameter of an user. 
  @param name Parameter name
  @param value Parameter value
  @return True on success, false otherwise
  @note Anonymous users are not allowed to set a parameter */
function set($name, $value)
{
  if ($this->_userid<=0)
    return false;

  return $this->_set($this->_userid, $name, $value);
}

/** Sets a default value. 
  @param name Name of the parameter
  @param value Parameter value
  @return True on success, false otherwise
  @note Only admins are permitted to set default values */
function set_default($name, $value)
{
  global $user;
  if (!$user->is_admin())
    return false;

  return $this->_set(0, $name, $value);
}

/** Removes a parameter
  @param userid 
  @param name Parameter name
  @param value Optional parameter value. This is usefull for array parameters.
  Default is null.
  @return True on successful deletion. False otherwise */
function _del($userid, $name, $value=null)
{
  global $db;
  $sname=mysql_escape_string($name);
  $sql="DELETE FROM $db->configs
        WHERE userid=$userid AND name='$sname'";
  if ($value!=null)
  {
    $svalue=mysql_escape_string($value);
    $sql.=" AND value='$svalue'";
  }
  $result=$db->query($sql);
  if (!$result)
    return false;
  return true;
}

/** Deletes a parameter
  @param name Parameter name
  @param value Optional parameter value. This is usefull for array parameters.
  Default is null.
  @return True on successful deletion. False otherwise */
function del($name, $value)
{
  if ($this->_userid<=0)
    return false;

  return $this->_del($this->_userid, $name, $value);
}

/** Deletes a parameter
  @param name Parameter name
  @param value Optional parameter value. This is usefull for array parameters.
  Default is null.
  @return True on successful deletion. False otherwise
  @note Only an admin user is allowd to remove a default value */
function del_default($name, $value=null)
{
  global $user;
  if (!$this->is_admin())
    return false;

  return $this->_del(0, $name, $value);
}

/** Deletes all configuration of a user 
  @param userid Id of the user */
function delete_from_user($userid)
{
  global $db;
  global $user;

  // Delete all preferences
  $sql="DELETE FROM $db->configs
        WHERE userid=$userid";
  $db->query($sql);

  // If I delete myself, delete also my configuration
  if ($this->_userid==$id)
    $this->_data=array();
}

}
?>
