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
include_once("$phtagr_lib/SqlObject.php");
include_once("$phtagr_lib/Constants.php");

include_once("$phtagr_lib/FileJpg.php");
include_once("$phtagr_lib/FileMovie.php");

/** 
  An image is assigned to a user and a group. With the access control lists
  (ACL) the privacy of the image can be set precisely. 

  The owner can modify the image and can set the permissions of the image. The
  owner assigns the image to a group and the ACLs. The ACL defines access
  rights for groups, members and public. Therefore, a image can be accessed by
  everyone, for members only, for group members or only the owner itself.

  @class Image Models the image data object.
*/
class Image extends SqlObject
{

var $_tags;
var $_sets;
var $_locations;
var $_file_handler;
var $_previewer;
var $_meta_modified;


/** Creates an Image object 
  @param id Id of the image. */
function Image($id=-1)
{
  global $db;
  $this->SqlObject($db->images, $id);
  $this->_tags=null;
  $this->_cats=null;
  $this->_locations=null;
  $this->_meta_modified=array();

  $this->_file_handler=null;
  $this->_previewer=null;
}

function _slashify($path)
{
  if ($path[strlen($path)]!='/')
    return $path.'/';
  return $path;
}

/** Splits the filename into path and file (basename) component
  @return Array of path and file (basename) */
function _split_filename($filename)
{
  return array($this->_slashify(dirname($filename)), basename($filename));
}

/** @return The id by the filename or false */
function get_id_by_filename($filename)
{
  global $db;
  
  if ($filename=='')
    return false;
    
  list($path, $file)=$this->_split_filename($filename);
  
  $spath=mysql_escape_string($path);
  $sfile=mysql_escape_string($file);

  $sql="SELECT id". 
       " FROM $db->images".
       " WHERE path='$spath' AND file='$sfile'";
  $id=$db->query_cell($sql);
  if ($id===null)
    return false;
  return $id;
}

/** Initialize the values from the database into the object 
  @param filename Filename from a specific image
  @return True on success. False on failure
*/
function init_by_filename($filename)
{
  global $db;
  
  $id=$this->get_id_by_filename($filename);
  
  if ($id===false)
    return false;

  $this->init_by_id($id); 
  return true;
}

/** 
  @param filename Filename. If filename is empty, the filename of the object 
  is taken
  @return Returns and sets the default file handler for import and export */
function get_file_handler($filename='')
{
  global $log;

  if ($this->_file_handler!=null)  
    return $this->_file_handler;
  
  if ($filename=='')
    $filename=$this->get_filename();
  
  $handler=null;

  $pos=strrpos($filename, ".");
  if ($pos===false)
  {
    $log->trace("Could not fetch file handler. File extension not found: $filename");
    return null;
  }

  $ext=strtolower(substr($filename, $pos+1));
  switch ($ext)
  {
    case "jpg":
    case "jpeg":
      $handler=new FileJpg($filename);
      break;
    case "mov":
    case "mpeg":
    case "mpg":
    case "avi":
      $handler=new FileMovie($filename);
      break;
    default:
      $log->warn(_("Unsupported file tpye '$ext': $filename"));
      return null;
      break;
  }
  $this->_file_handler=$handler;
  return $handler;
}

/** Returns and sets the default preview handler */
function get_preview_handler()
{
  if ($this->_previewer!=null)  
    return $this->_previewer;
  
  $handler=$this->get_file_handler();
  if ($handler==null)
    return null;

  $previewer=$handler->get_preview_handler(&$this);
  if ($previewer==null)
    return null;

  $this->_previewer=$previewer;
  return $this->_previewer;
}

/** Returns the id of the image */
function get_id()
{
  return $this->_get_data('id', -1);
}

/** Returns the base name of the image filename */
function get_file()
{
  return stripslashes($this->_get_data('file', ''));
}

/** Returns the path of the image filename */
function get_path()
{
  return stripslashes($this->_get_data('path', ''));
}

/** Returns the filename of the image */
function get_filename()
{
  return $this->get_path().$this->get_file();
}

/** Returns the name of the image */
function get_name()
{
  return stripslashes($this->_get_data('name', ''));
}

function set_name($name)
{
  $this->_set_data('name', $name);
}

/** @return Returns true, if the file is an video */
function is_video()
{
  if ($this->get_duration()>0)
    return true;
  return false;
}

/** Returns the user ID of the image */
function get_userid()
{
  return $this->_get_data('user_id', -1);
}

/** Returns the group ID of the image */
function get_groupid()
{
  return $this->_get_data('group_id', -1);
}

function set_groupid($gid)
{
  $this->_set_data('group_id', $gid);
}

/** Returns the syncronization date of the image
  @param in_unix Return time in unix timestamp if true. If false return the
  mysql time string */
function get_modified($in_unix=false)
{
  global $db;
  $time=$this->_get_data('modified');
  if ($in_unix)
    return $db->date_mysql_to_unix($time);
  else
    return $time;
}

/** Sets the synchronisation date to now 
  @param date Mysql date string or UNIX timestamp.
  @param in_unix If true, time is in UNIX format. Otherwise it is an mysql time
string*/
function set_modified($date, $in_unix=false)
{
  global $db;
  if ($in_unix)
    $date=$db->date_unix_to_mysql($date);
  $this->_set_data('modified', $date);
}

function get_flag()
{
  return $this->_get_data('flag', 0);
}

/** @return True if the image was uploaded. Fals otherwise */
function is_external()
{
  $flag=$this->get_flag() & IMAGE_FLAG_IMAGE_FLAG_EXTERNAL;
  return $flag>0?true:false;
}

function set_gacl($gacl)
{
  if (!is_numeric($gacl) || $gacl<0)
    return;

  $this->_set_data('gacl', $gacl);
}

/** Returns the group ACL */
function get_gacl()
{
  return $this->_get_data('gacl', 0);
}

function set_macl($macl)
{
  if (!is_numeric($macl) || $macl<0)
    return;

  $this->_set_data('macl', $macl);
}

/** Returns the ACL for other phtagr users*/
function get_macl()
{
  return $this->_get_data('macl', 0);
}

function set_pacl($pacl)
{
  if (!is_numeric($pacl) || $pacl<0)
    return;

  $this->_set_data('pacl', $pacl);
}

/** Returns the ACL for anyone */
function get_pacl()
{
  return $this->_get_data('pacl', 0);
}

/** Returns the bytes of the image */
function get_bytes()
{
  return $this->_get_data('bytes');
}

function set_bytes($bytes)
{
  $bytes=intval($bytes);
  $bytes=($bytes<0)?0:$bytes;
  $this->_set_data('bytes', $bytes);
}

function get_orientation()
{
  return $this->_get_data('orientation');
}

function set_orientation($orientation)
{
  $orientation=intval($orientation);
  $orientation=($orientation<1)?1:(($orientation>8)?1:$orientation);
  $this->_set_data('orientation', $orientation);
}

function get_caption()
{
  return stripslashes($this->_get_data('caption'));
}

/** Sets a caption
  @param caption Set the new caption. To remove the caption, set value to null
  */
function set_caption($caption)
{
  $this->_set_data('caption', $caption);
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

/** @return Returns true if the image will be autorotated. In this case the
 * image is saved horizontal, but the orientation flag is set to an vertical
 * image */
function is_autorotated()
{
  global $conf;
  if ($conf->query($this->get_userid(), 'image.autorotate', 1)==0)
    return false;
  
  $orientation=$this->get_orientation();
  if ($orientation==6 || $orientation==8)
    return true;

  false;
}

/** @param autorotated If true, consider auto rotation. Default is true */
function get_height($autorotated=true)
{
  if ($autorotated && $this->is_autorotated())
    return $this->_get_data('width');
  return $this->_get_data('height');
}

function set_height($height)
{
  $this->_set_data('height', $height);
}

/** @param autorotated If true, consider auto rotation. Default is true */
function get_width($autorotated=true)
{
  if ($autorotated && $this->is_autorotated())
    return $this->_get_data('height');
  return $this->_get_data('width');
}

function set_width($width)
{
  $this->_set_data('width', $width);
}

function get_duration()
{
  return $this->_get_data('duration');
}

function set_duration($sec)
{
  if (!is_numeric($sec) || $sec<-1)
    return;

  $this->_set_data('duration', intval($sec));
}
/** Returns the count of views */
function get_clicks()
{
  return $this->_get_data('clicks');
}

function add_click()
{
  $this->_set_data('clicks', $this->get_clicks()+1);
}

function get_ranking()
{
  return $this->_get_data('ranking');
}

/** Returns the current voting values */
function get_voting()
{
  return $this->_get_data('voting');
}

/** Returns the current number of votes */
function get_votes()
{
  return $this->_get_data('votes');
}

/** Set a now vote to the image 
  @param voting Vote of the imagev
  @return True on success, false otherwise 
  @note This function will commit all changes */
function add_voting($voting) 
{
  global $db;
  $voting=intval($voting);
  if ($voting<0 || $voting>VOTING_MAX)
    return false;

  $_voting=$this->_get_data('voting');
  $_votes=$this->_get_data('votes');
  
  $_voting=(($_voting*$_votes+$voting)/($_votes+1));
  $_votes+=1;
  
  $this->_set_data('voting', $_voting);
  $this->_set_data('votes', $_votes);
  $this->commit();

  return true;
}

/** Return the time of the last click
  @param in_unix If true return the unix timestamp. Otherwise return the sql
  time string */
function get_lastview($in_unix=false)
{
  global $db;
  $lastview=$this->_get_data('lastview');
  if ($in_unix)
    return $db->date_mysql_to_unix($lastview);
  else
    return $lastview;
}

/** Return true if the user can select an image */
function can_select($user=null)
{
  return true;
}

/** Return if the given user has the same user id than an object 
  @param user User object
*/
function is_owner($user=null)
{
  if ($user==null)
    return false;
    
  if ($this->get_userid()==$user->get_id())
    return true;

  return false;
}

/** Checks the acl of an image. 
  @param user User object
  @param flag ACL value
  @param mask ACL bis mask 
  @return True if user is allow to do the action defined by the flag. Admins
  and image owners are allows in any case. Otherwise the ACL values are
  checked. */
function _check_acl($user, $flag, $mask)
{
  if (!isset($user))
    return false;
    
  // Admin is permitted always
  if ($user->is_admin())
    return true;
  
  if ($user->get_id()==$this->get_userid())
    return true;
    
  // If acls are calculated within if statement, I got wrong evaluation.
  $gacl=$this->get_gacl() & $mask;
  $macl=$this->get_macl() & $mask;
  $pacl=$this->get_pacl() & $mask;
  
  if ($user->is_in_group($this->get_groupid()) && $gacl >= $flag)
    return true;
  
  if ($user->is_member() && $macl >= $flag)
    return true;

  if ($pacl >= $flag)
    return true;
  
  return false;
}

/** Return true if user can edit the tags
  @param user User object. Default is null.*/
function can_write_tag($user=null)
{
  return $this->_check_acl(&$user, ACL_WRITE_TAG, ACL_WRITE_MASK);
}

function can_write_meta($user=null)
{
  return $this->_check_acl(&$user, ACL_WRITE_META, ACL_WRITE_MASK);
}

function can_write_caption($user=null)
{
  return $this->_check_acl(&$user, ACL_WRITE_CAPTION, ACL_WRITE_MASK);
}

/** Return true if user can preview the image 
  @param user User object. Default is null.*/
function can_read_preview($user=null)
{
  return $this->_check_acl(&$user, ACL_READ_PREVIEW, ACL_READ_MASK);
}

function can_read_highsolution($user=null)
{
  return $this->_check_acl(&$user, ACL_READ_HIGHSOLUTION, ACL_READ_MASK);
}

function can_read_original($user=null)
{
  return $this->_check_acl(&$user, ACL_READ_ORIGINAL, ACL_READ_MASK);
}


/** @param user Current user
  @return True if tue user is allowed to comment this image. 
  @todo Currently this function is equal to can_preview(). Add flags for the
comment */
function can_comment($user)
{
  return $this->_check_acl(&$user, ACL_READ_PREVIEW, ACL_READ_MASK);
}

/** Returns the size of an thumbnail in an array. This function keeps the
 * ratio.
  @param size of the longes side. Default is 220. If the size is larger than
  the largest side, the size is cutted.
  @param autorotated If true, consider auto rotation. Default is true 
  @return Array of (width, height, str), wherease the string is the string for
  html img tag. On an error it returns an empty array 
  @note This function will call get_height() and get_width() */
function get_size($size=220, $autorotate=true)
{
  global $conf;

  $height=$this->get_height($autorotate);
  $width=$this->get_width($autorotate);
  
  if ($height > $width && $height>0)
  {
    if ($size>$height)
      $size=$height;
      
    $w=intval($size*$width/$height);
    $h=$size;
  }
  else if ($width > $height && $width > 0)
  {
    if ($size>$width)
      $size=$width;
      
    $h=intval($size*$height/$width);
    $w=$size;
  }
  else
  {
    return array(0,0,'');
  }
  
  $s="width=\"$w\" height=\"$h\"";
  
  return array($w, $h, $s);
}

/** Update the ranking value of the image. This is calculated by the current
 * ranking value and the interval to the last view 
  @note This function will commit all changes */
function update_ranking()
{
  global $db;

  $id=$this->get_id();
  $ranking=$this->get_ranking();
  $lastview=$this->get_lastview(true);

  $ranking=0.8*$ranking+100/log((1+time()-$lastview));     

  $this->_set_data('ranking', $ranking);
  $this->_set_data('clicks', $this->_get_data('clicks', 0)+1);
  $this->_set_data('lastview', "NOW()");
  $this->commit();
} 

/** @param name Name of the meta data. If null, every meta data is considered.
  Default is null.
  @return Returns true if the meta data modified */
function is_meta_modified($name=null)
{
  $modified=false;
  if ($name==null)
  {
    foreach ($this->_meta_modified as $name => $c)
      $modified |= $c;
  } else {
    return (true == $this->_meta_modified[$name]);
  }
  return $modified;
}

/** @param name Name of the meta data like 'tags' or 'sets'
  @param value Ste True or false. Default is true */
function set_meta_modified($name, $value=true)
{
  $this->_meta_modified[$name]=$value;
}

/** Returns an array of tags. The tags are sorted by name */
function get_tags()
{
  if ($this->_tags)
    return $this->_tags;

  global $db;
  $id=$this->get_id();
  $this->_tags=array();
  $sql="SELECT t.name".
       " FROM $db->tags AS t, $db->imagetag AS it".
       " WHERE it.image_id=$id AND it.tag_id=t.id".
       " GROUP BY t.name";
  $result=$db->query($sql);
  if (!$result)
    return $this->_tags;

  while($row = mysql_fetch_row($result)) {
    array_push($this->_tags, stripslashes($row[0]));
  }
  sort($this->_tags);
  return $this->_tags;
}

/** Searches the array of a given value and returns the corresponding key if
 * successfull. The search is case insensitive and trims the needle.
  @param needle Needle to search
  @param haysack Haysack
  @return Returns the key of the needle or null on if the needle was not found.
*/
function _array_isearch($needle, $haysack)
{
  if ($haysack===null || !is_array($haysack))
    return null;
  $needle=trim(strtolower($needle));
  foreach ($haysack as $k => $h)
  {
    if ($needle==strtolower($h))
      return $k;
  }
  return null;
}

/** Case insensitive search for a given tag
  @param tag Search for the tag.
  @return True if given tag already exists */
function has_tag($tag)
{
  if ($this->_array_isearch($tag, $this->_tags)!==null)
    return true;
  return false;
}

/** @return Returns true if the image has tags */
function has_tags()
{
  $this->get_tags();
  return count($this->_tags)>0?true:false;
}

/** Add tags to the image
  @param tags Array of new tags for the image */
function add_tags($tags)
{
  global $db;
  if ($this->_tags==null)
    $this->get_tags();

  $id=$this->get_id();
  foreach ($tags as $tag)
  {
    if ($tag=='' || $this->has_tag($tag))
      continue;

    $tagid=$db->tag2id($tag, true);
    if ($tagid<0)
      continue; 
    $sql="INSERT INTO $db->imagetag".
         " (image_id, tag_id)".
         " VALUES ($id, $tagid)";
    $db->query($sql);
    array_push($this->_tags, $tag);
    $this->set_meta_modified('tags');
  }
}

/** Deletes tags from the image.
  @param tags Tags to be removed from the image. If null it removes all tags. 
  Default is null */
function del_tags($tags=null)
{
  global $db;
  if ($this->_tags==null)
    $this->get_tags();
  
  if ($tags===null)
    $tags=$this->get_tags();

  $id=$this->get_id();
  foreach ($tags as $tag)
  {
    $key=array_search($tag, $this->_tags);
    if ($key!==false)
    {
      $tagid=$db->tag2id($tag);
      $sql="DELETE FROM $db->imagetag".
           " WHERE image_id=$id AND tag_id=$tagid";
      $db->query($sql);
      array_splice($this->_tags, $key, 1);
      $this->set_meta_modified('tags');
    }
  }
}

/** Returns an array of sets. The sets are sorted by name */
function get_categories()
{
  if ($this->_cats)
    return $this->_cats;

  global $db;
  $id=$this->get_id();
  $this->_cats=array();
  $sql="SELECT c.name".
       " FROM $db->categories AS c, $db->imagecategory AS ic".
       " WHERE ic.image_id=$id AND ic.category_id=c.id".
       " GROUP BY c.name";
  $result=$db->query($sql);
  if (!$result)
    return $this->_cats;

  while($row = mysql_fetch_row($result)) {
    array_push($this->_cats, stripslashes($row[0]));
  }
  sort($this->_cats);
  return $this->_cats;
}

/** Case insensitive search for a given set
  @param set Search for the set.
  @return True if given set already exists */
function has_category($cat)
{
  if ($this->_array_isearch($cat, $this->_cats)!==null)
    return true;
  return false;
}

/** @return Returns true if the image has tags */
function has_categories()
{
  $this->get_categories();
  return count($this->_cats)>0?true:false;
}

/** Add sets to the image
  @param sets Array of new sets for the image */
function add_categories($cats)
{
  global $db;
  if ($this->_cats==null)
    $this->get_categories();

  $id=$this->get_id();
  foreach ($cats as $cat)
  {
    if ($cat=='' || $this->has_category($cat))
      continue;

    $catid=$db->category2id($cat, true);
    if ($catid<0)
      continue; 
    $sql="INSERT INTO $db->imagecategory".
         " (image_id, category_id)".
         " VALUES ($id, $catid)";
    $db->query($sql);
    array_push($this->_cats, $cat);
    $this->set_meta_modified('categories');
  }
}

/** Deletes sets from the image.
  @param sets Sets to be removed from the image. If null, it removes all sets. 
  Default is null */
function del_categories($cats=null)
{
  global $db;
  if ($this->_cats==null)
    $this->get_categories();

  if ($cats===null)
    $cats=$this->get_categories();

  $id=$this->get_id();
  foreach ($cats as $cat)
  {
    $key=array_search($cat, $this->_cats);
    if ($key!==false)
    {
      $catid=$db->category2id($cat);
      $sql="DELETE FROM $db->imagecategory".
           " WHERE image_id=$id AND category_id=$catid";
      $db->query($sql);
      array_splice($this->_cats, $key, 1);
      $this->set_meta_modified('categories');
    }
  }
}

/** Returns the location of a given type 
  @param type Location type 
  @return Value of the location. Null if no location is set */
function get_location($type)
{
  $this->get_locations();
  if ($type<LOCATION_ANY || $type > LOCATION_COUNTRY)
    return false;

  return $this->_locations[$type];
}

/** Returns an array of location. The locations are sorted by type */
function get_locations()
{
  if ($this->_locations)
    return $this->_locations;

  global $db;
  $this->_locations=array();
  $id=$this->get_id();
  $sql="SELECT l.name, l.type".
       " FROM $db->locations as l, $db->imagelocation as il".
       " WHERE il.image_id=$id AND il.location_id=l.id".
       " ORDER BY l.type";
  $result = $db->query($sql);
  if (!$result)
    return $this->_locations;

  while($row = mysql_fetch_row($result)) {
    $this->_locations[$row[1]]=stripslashes($row[0]);
  }
  return $this->_locations;
}

/** Case insensitive search for a given location
  @param location Search for the location.
  @param type Type of location. Default is LOCATION_ANY and search for
any location type
  @return True if given location already exists */
function has_location($location, $type=LOCATION_ANY)
{
  $key=$this->_array_isearch($location, $this->_locations);
  if ($key===null)
    return false;
  if ($type==LOCATION_ANY)
    return true;
  if ($key==$type)
    return true;
  return false;
}

/** @return Returns true if the image has tags */
function has_locations()
{
  $this->get_locations();
  return count($this->_locations)>0?true:false;
}

/** Set a location and overwrites an existing one 
  @param value Location value. If value is null, the given location type is
  deleted
  @param type Location type */
function set_location($value, $type)
{
  global $db;
  if ($value==null || $value=='')
    return false;
  if ($type<LOCATION_ANY || $type > LOCATION_COUNTRY)
    return false;

  $this->get_locations();
  if ($this->_locations[$type]==$value)
    return true;
  $id=$this->get_id();
  $locationid=$db->location2id($value, $type, true);
  if (isset($this->_locations[$type]))
  {
    $oldlocationid=$db->location2id($this->_locations[$type], $type);
    $sql="UPDATE $db->imagelocation".
         " SET locationid=$locationid".
         " WHERE image_id=$id AND location_id=$oldlocationid";
  } else {
    $sql="INSERT INTO $db->imagelocation ( image_id, location_id )".
         " VALUES ( $id, $locationid )";
  }
  $result=$db->query($sql);
  if (!$result)
    return false;

  $this->_locations[$type]=$value;
  $this->set_meta_modified('locations');
  return true;
}

/** Deletes a location
  @param value Value of the location. Can be an empty string. If a value is
  given, the value must match the current one to delete it. If an empty string
  is given, the type is deleted 
  @param type Type of the location.
  @return True on success, false otherwise */
function del_location($value, $type)
{
  global $db;
  $id=$this->get_id();
  if ($type<LOCATION_ANY || $type>LOCATION_COUNTRY)
    return false;

  $this->get_locations();
  if (!isset($this->_locations[$type]))
    return true;

  if ($value!='')
  {
    if ($this->_locations[$type]!=$value)
      return false;

    $locid=$db->location2id($value, $type);
    $sql="DELETE FROM $db->imagelocation as il".
         " WHERE il.image_id=$id AND il.location_id=$locid";
  } else {
    $sql="DELETE FROM il".
         " USING $db->imagelocation as il, $db->locations as l".
         " WHERE il.image_id=$id AND il.location_id=l.id AND l.type=$type";
  }
  $result=$db->query($sql);
  if (!$result)
    return false;

  unset($this->_locations[$type]);
  $this->set_meta_modified('locations');
  return true;
}

/** Delets an array of location
  @param Array of location. If null, it deletes all locations. Default is null */
function del_locations($locations=null)
{
  if ($locations===null)
    $locations=$this->get_locations();

  for ($type=LOCATION_CITY ; $type<=LOCACTION_COUNTRY ; $type++)
  {
    if (isset($locations[$type]))
      $this->del_location($locations[$type]);
  }
}
/** Sets the location and overwrites the current one */
function set_locations($locations)
{
  for ($type=LOCATION_CITY ; $type<=LOCACTION_COUNTRY ; $type++)
  {
    if ($locations[$type]==null)
      $this->del_location('', $type);
    else
      $this->set_location($locations[$type], $type);
  }
}

/** Deletes the image from the database
  @return True on success, false otherwise */
function delete()
{
  global $db;
  global $user;
  global $log;

  if ($user->get_id()!=$this->get_userid() && !$user->is_admin())
    return false;

  $id=$this->get_id();
  if ($id<=0)
    return false;

  $log->info("Delete file '".$this->get_filename()."' from the database", $id);

  $sql="DELETE FROM $db->imagetag".
       " WHERE image_id=$id";
  $db->query($sql);

  $sql="DELETE FROM $db->imagecategory".
       " WHERE image_id=$id";
  $db->query($sql);
  
  $sql="DELETE FROM $db->imagelocation".
       " WHERE image_id=$id";
  $db->query($sql);

  $sql="DELETE FROM $db->comments".
       " WHERE image_id=$id";
  $db->query($sql);

  $sql="DELETE FROM $db->images".
       " WHERE id=$id";
  $db->query($sql);

  return true;
}


/** Deletes one or all images from a specific user
  @param userid ID of user
  @param imageid Image ID, optional. If this parameter is set, delete only a single image 
  @return The number of deleted images. -1 otherwise */
function delete_from_user($userid, $imageid=0)
{
  global $db;
  global $user;
  global $log;

  if (!is_numeric($userid) || $userid<1)
    return -1;
  if ($userid!=$user->get_id() && !$user->is_admin())
  {
    $log->err("User is not permitted to delete all image of user (userid=$userid)");
    return -1;
  }

  $msg="Delete images of user (user_id=$userid)";
  if ($imageid>0)
    $msg.=" and image (image_id=$imageid)";
  $log->info($msg);

  // delete tags
  $sql="DELETE FROM it".
       " USING $db->imagetag AS it, $db->images AS i".
       " WHERE i.user_id=$userid AND i.id=it.image_id";
  if ($imageid>0) $sql.=" AND i.id=$imageid";
  $db->query($sql);

  // delete sets
  $sql="DELETE FROM ic".
       " USING $db->imagecategory AS ic, $db->images AS i".
       " WHERE i.user_id=$userid AND i.id=ic.image_id";
  if ($imageid>0) $sql.=" AND i.id=$imageid";
  $db->query($sql);

  // delete locations
  $sql="DELETE FROM il".
       " USING $db->imagelocation AS il, $db->images AS i".
       " WHERE i.user_id=$userid AND i.id=il.image_id";
  if ($imageid>0) $sql.=" AND i.id=$imageid";
  $db->query($sql);

  // delete comments
  $sql="DELETE FROM c".
       " USING $db->comments AS c, $db->images AS i".
       " WHERE i.userid=$userid AND i.id=c.image_id";
  if ($imageid>0) $sql.=" AND i.id=$imageid";
  $db->query($sql);
 
  // Delete image data
  $sql="DELETE".
       " FROM $db->images".
       " WHERE user_id=$userid";
  if ($imageid>0) $sql.=" AND id=$imageid";
  return $db->query_delete($sql);
}

}

?>
