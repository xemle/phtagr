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

/** Handle groups of a user
  @class Group
*/
class Group extends Base
{

var $_id;
var $_name;
var $_members;

function Group($id=-1)
{
  $this->_members=array();
  $this->_name='';
  $this->_owner=-1;
  $this->_id=-1;
  $this->init_by_id($id);
}

function init_by_id($id)
{
  global $db;
  if ($id<=0)
    return false;

  $sql="SELECT name,user_id".
       " FROM $db->groups".
       " WHERE id=$id";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)<1)
    return false;
  $row=mysql_fetch_assoc($result);
  $this->_name=$row['name'];
  $this->_owner=$row['user_id'];
  $this->_members=array();
  $this->_id=$id;

  // Fetch all members
  $sql="SELECT u.id,u.username".
       " FROM $db->users AS u, $db->usergroup AS ug".
       " WHERE u.id=ug.user_id AND ug.group_id=$id".
       " ORDER BY u.username";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)<1)
    return true;

  $this->_members=array();
  while ($row=mysql_fetch_row($result))
    $this->_members[$row[0]]=$row[1];
}

function get_id_by_name($name)
{
  global $user;
  global $db;

  $sname=mysql_escape_string($name);
  $sql="SELECT id".
       " FROM $db->groups".
       " WHERE user_id=".$user->get_id().
       "   AND name='$sname'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)<1)
    return -1;
  $row=mysql_fetch_array($result);
  return $row[0];
}

function _check_name($name)
{
  if (strlen($name)<4 || strlen($name)>32)
    return ERR_USER_NAME_LEN;

  if (!preg_match('/^[a-z][a-z0-9\-_\.\@]+$/', $name))
    return ERR_USER_NAME_INVALID;
  return 0;
}

/** Creates a new group. The group owner is the current user
  @param name Name of the new group
  @return On success it returns the id of the new group. On failure it returns
  a negative error code */
function create($name)
{
  global $user;
  global $db;

  $err=$this->_check_name($name);
  if ($err<0) return $err;
  $id=$this->get_id_by_name($name);
  if ($id>=0)
    return $id;

  $sname=mysql_escape_string($name);
  $sql="INSERT INTO $db->groups".
       " (user_id, name) VALUES (".$user->get_id().",'$sname')";
  $id=$db->query_insert($sql);
  if ($id<0)
    return -1;

  return $id;
}

function get_id()
{
  return $this->_id;
}

function get_name()
{
  return $this->_name;
}

function get_owner()
{
  return $this->_owner;
}

function get_num_members()
{
  return count($this->_members);
}

function get_members()
{
  return $this->_members;
}

/** 
  @paran name_or_id Name or ID of a phTagr member 
  @return Returns true if the name or ID is member of the current group */
function has_member($name_or_id)
{
  if (is_numeric($name_or_id))
  {
    if (isset($this->_member[$name_or_id]))
      return true;
  }
  else 
  {
    foreach ($this->_members as $id => $n)
      if ($n==$name)
        return true;
  }
  return false;
}

/** Adds a new member to the group.
  @param name Name or ID of the phTagr user
  @return true on success, false otherwise */
function add_member($name_or_id)
{
  global $db, $user;

  if (is_numeric($name_or_id))
    $uid=$name_or_id;
  else
    $uid=$user->get_id_by_name($name_or_id);
  if ($uid<=0)
    return false;
  
  // Authorization check
  if (!$user->is_admin() && $this->get_owner()!=$user->get_id())
    return false;

  // Is user already group member?
  if (isset($this->_members[$uid]))
    return true;

  // Add user
  $sql="INSERT INTO $db->usergroup".
       " (user_id, group_id) VALUES ($uid, ".$this->get_id().")";
  $result=$db->query($sql);
  if (!$result)
    return false;
  $this->_members[$uid]=$name;  
  return true;
}

/** Removes a member from the group
  @param name Name or ID of the phTagr user
  @return True on success, false otherwise */
function del_member($name_or_id)
{
  global $db, $user;
  if (is_numeric($name_or_id))
    $id=$name_or_id;
  else
    $id=$user->get_id_by_name($name_or_id);
  if ($id<=0)
    return false;

  // Authorization check
  if (!$user->is_admin() && $this->get_owner()!=$user->get_id())
    return false;

  // Is user really a group member?
  if (!isset($this->_members[$id]))
    return true;

  // Delete user
  $sql="DELETE FROM $db->usergroup".
       " WHERE user_id=$id AND group_id=".$this->get_id();
  $result=$db->query($sql);
  if (!$result)
    return false;
  unset($this->_members[$id]);
  return true;
}

function delete()
{
  global $user;
  global $db;
  $id=$this->get_id();

  // Authorization check
  if (!$user->is_admin() && $this->get_owner()!=$user->get_id())
    return false;

  $new_gid=$user->get_groupid();
  if ($new_gid==$id)
    $new_id=0;

  $sql="DELETE FROM $db->usergroup".
       " WHERE group_id=$id";
  $result=$db->query($sql);
  $sql="DELETE FROM $db->groups".
       " WHERE id=$id";
  $result=$db->query($sql);
  // reset images which are affected with this group
  $sql="UPDATE $db->images".
       " SET group_id=$new_gid".
       " WHERE group_id=$id";
  $result=$db->query($sql);
  // reset group object
  $this->_id=-1;
  $this->_name='';
  $this->_members=array();
}

/** Deletes all group data of a specific user */
function delete_from_user($id)
{
  global $db, $user;

  if (!is_numeric($id) || $id<1)
    return;

  // Delete user memberships 
  $sql="DELETE FROM $db->usergroup".
       " WHERE user_id=$id";
  $db->query($sql);

  // Delete all groups from user
  $sql="DELETE FROM $db->usergroup".
       " WHERE group_id IN (".
       "   SELECT id".
       "   FROM $db->groups".
       "   WHERE owner=$id".
       " )";
  $db->query($sql);

  // delete all groups
  $sql="DELETE".
       " FROM $db->groups".
       " WHERE owner=$id";
  $db->query($sql);
}

}
?>
