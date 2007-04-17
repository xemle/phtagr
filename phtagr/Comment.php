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

include_once("$phtagr_lib/Search.php");
include_once("$phtagr_lib/Base.php");
include_once("$phtagr_lib/Constants.php");

/** 
  @class Comment Models the comment data object.
*/
class Comment extends SqlObject
{


/** Creates an Image object 
  @param id Id of the image. */
function Comment($id=-1)
{
  global $db;
  $this->SqlObject($db->comments, $id);
}

function get_id()
{
  return $this->_get_data('id', -1);
}

function get_imageid()
{
  return $this->_get_data('imageid');
}

function set_imageid($imageid)
{
  $this->_set_data('imageid', $imageid);
}

function get_userid()
{
  return $this->_get_data('userid');
}

function set_userid($userid)
{
  $this->_set_data('userid', $userid);
}

function get_name()
{
  return $this->_get_data('name');
}

function set_name($name)
{
  $this->_set_data('name', $name);
}

function get_email()
{
  return $this->_get_data('email');
}

function set_email($email)
{
  $this->_set_data('email', $email);
}

/** Return the date of the image
  @param in_unix If true return the unix timestamp. Otherwise return the sql
  time string */
function get_date($in_unix=false)
{
  global $db;

  $date=$this->_get_data('date');
  if ($in_unix)
    return $db->date_mysql_to_unix($date);
  else
    return $date;
}

function set_date($date, $in_unix=false)
{
  global $db;

  if ($in_unix)
    $date=$db->date_unix_to_mysql($date);

  $this->_set_data('date', $date);
}

function get_comment()
{
  return stripslashes($this->_get_data('comment'));
}

function set_comment($comment)
{
  $this->_set_data('comment', $comment);
}

function get_reply()
{
  return $this->_get_data('reply');
}

function set_reply($reply)
{
  $this->_set_data('reply', $reply);
}

function get_auth()
{
  return $this->_get_data('auth');
}

function set_auth($auth)
{
  $this->_set_data('auth', $auth);
}

function get_notify()
{
  return $this->_get_data('notify');
}

function set_notify($notify)
{
  $this->_set_data('notify', $notify);
}

/** Creates an new comment and returns the comment ID. If the current user has
 * an ID it will be added as well.
  @param imageid ID of the image
  @param name Name of the commentator
  @param email Email of the commentator
  @param comment New comment
  @return New comment id. On error it returns -1 */
function create($imageid, $name, $email, $comment)
{
  global $db, $log, $user;

  if (!is_numeric($imageid) || $imageid<=0)
    return -1;
  $userid=$user->get_id();
  $sname=mysql_escape_string($name);
  $semail=mysql_escape_string($email);
  $scomment=mysql_escape_string($comment);

  $sql="INSERT INTO $db->comments
        (imageid, userid, date, name, email, comment)
        VALUES ($imageid, $userid, NOW(), '$sname', '$semail', '$scomment')";
  $result=$db->query($sql);
  if (!$result)
    return -1;

  $sql="SELECT LAST_INSERT_ID()";
  $result=$db->query($sql);
  $log->warn("Add new comment: '$comment'", $imageid, $user->get_id());
  if ($result && mysql_num_rows($result)>0)
  {
    $row=mysql_fetch_row($result);
    $this->init_by_id($row[0]);
    $log->trace("New comment has ID: ".$row[0], $imageid, $user->get_id());
    return $row[0];
  }
  return -1;
}

function delete($auth)
{
  global $db, $user, $log;

  if (($user->get_id()!=$this->get_userid() && !$user->is_admin())
    || $auth!=$this->get_auth())
    return false;

  $id=$this->get_id();
  $sql="DELETE FROM $db->comments
        WHERE id=$id";
  $db->query($sql);
  $log->warn("Deteting comment $id", $this->get_imageid(), $user->get_id());
}

/** Return a list of comment ids of a given image
  @param imageid Image id
  @return Array of comment ids. If nothing is found, it returns an empty array
*/
function get_comment_ids($imageid)
{
  global $db, $log, $user;

  if (!is_numeric($imageid) || $imageid<=0)
    return array();

  $sql="SELECT id
        FROM $db->comments
        WHERE imageid=$imageid";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)==0)
    return array();

  $ids=array();
  while ($row=mysql_fetch_row($result))
  {
    array_push($ids, $row[0]);
  }
  $log->trace("Request array of comments: ".mysql_num_rows($result), $imageid, $user->get_id());
  return $ids;
}

function handle_request($image)
{
  global $db, $user, $log;

  if ($image->get_id()<0)
    return -1;

  if (!$image->can_comment($user))
    return -1;

  $name=$_REQUEST['name'];
  $email=$_REQUEST['email'];
  $comment=$_REQUEST['comment'];
  $userid=$user->get_id();

  if (strlen($comment)==0)
    return -1;

  // comment is from a user
  if ($user->get_id()>0)
  {
    $name=$user->get_name();
    $email=$user->get_email();
  }
  // anonymous comment
  elseif (strlen($name)==0 || strlen($email)==0)
  {
    return -1;
  }

  $commentid=$this->create($image->get_id(), $name, $email, $comment);

  return $commentid;
}

}
