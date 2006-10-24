<?php

include_once("$phtagr_lib/Base.php");
include_once("$phtagr_lib/Constants.php");

/** @class Config Configuration of a user */
class Config extends Base
{

var $_data;

function Config($userid=0)
{
  $this->_data=array();
  $this->load($userid);
}

/** Read the global preferences from the sql database */
function load($userid=0)
{
  global $db;
  if ($userid>0)
    $this->load(0);

  if ($userid<0)
    return;

  $sql="SELECT name, value
        FROM $db->conf
        WHERE userid=$userid";
  $result=$db->query($sql);
  if (!$result)
    return false;
  while($row = mysql_fetch_array($result, MYSQL_ASSOC)) 
  {
    $name=$row['name'];
    $value=$row['value'];
    $nlen=strlen($name);
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

/** Adds or updates a name-value pair in the configuration table. */
function set($userid, $name, $value)
{
  global $db;
  $sname=mysql_escape_string($name);
  $svalue=mysql_escape_string($value);
  $sql="SELECT *
        FROM $db->conf
        WHERE userid=$userid AND name=\"$sname\"";
  $result=$db->query($sql);

  // Insert new value
  if (mysql_num_rows($result)==0)
    $sql="INSERT INTO $db->conf 
          (userid, name, value) VALUES 
          ($userid,'$sname','$svalue')";    
  // Update old value
  else
    $sql="UPDATE $db->conf 
          SET value='$svalue' 
          WHERE userid=$userid AND name='$sname'";

  $result=$db->query($sql);
  if (!$result)
    return false;

  $this->_data[$name]=$value;
  return true;
}

}
?>
