<?php

include_once("$phtagr_lib/Search.php");
include_once("$phtagr_lib/Base.php");
include_once("$phtagr_lib/Constants.php");

include_once("$phtagr_lib/FileJpg.php");
include_once("$phtagr_lib/FileAvi.php");

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
var $_file;
var $_previewer;
var $_meta_modified;


/** Creates an Image object 
  @param id Id of the image. */
function Image($id=-1)
{
  global $db;
  $this->SqlObject($db->images, $id);
  $this->_tags=null;
  $this->_sets=null;
  $this->_locations=null;
  $this->_meta_modified=array();

  $this->_file=null;
  $this->_previewer=null;
}

/** Initialize the values from the database into the object 
  @param filename Filename from a specific image
  @return True on success. False on failure
*/
function init_by_filename($filename)
{
  global $db;
  
  if ($filename=='')
    return false;
    
  //$sfilename=str_replace('\\','\\\\',$filename);
  $sfilename=mysql_escape_string($filename);

  $sql="SELECT * 
        FROM $db->images
        WHERE filename='$sfilename'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)==0)
    return false;
    
  unset($this->_data);
  $this->_data=mysql_fetch_array($result, MYSQL_ASSOC);
  return true;
}

/** 
  @param filename Filename. If filename is empty, the filename of the object 
  is taken
  @return Returns and sets the default file handler for import and export */
function get_file_handler($filename='')
{
  global $log;

  if ($this->_file!=null)  
    return $this->_file;
  
  if ($filename=='')
    $filename=$this->get_filename();
  
  $file=null;

  $pos=strrpos($filename, ".");
  if ($pos===false)
    return null;

  $ext=strtolower(substr($filename, $pos+1));
  switch ($ext)
  {
    case "jpg":
    case "jpeg":
      $file=new FileJpg($filename);
      break;
    case "avi":
      $file=new FileAvi($filename);
      break;
    default:
      $log->trace(sprintf(_("Unsupported file tpye '%d'"), $ext));
      break;
  }
  $this->_file=$file;
  return $file;
}

/** Returns and sets the default preview handler */
function get_preview_handler()
{
  if ($this->_previewer!=null)  
    return $this->_previewer;
  
  $file=$this->get_file_handler($filename);
  if ($file==null)
    return null;

  $previewer=$file->get_preview_handler(&$this);
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

/** Returns the filename of the image */
function get_filename()
{
  return stripslashes($this->_get_data('filename', ''));
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
  return $this->_get_data('userid', -1);
}

/** Returns the group ID of the image */
function get_groupid()
{
  return $this->_get_data('groupid', -1);
}

function set_groupid($gid)
{
  $this->_set_data('groupid', $gid);
}

/** Returns the syncronization date of the image
  @param in_unix Return time in unix timestamp if true. If false return the
  mysql time string */
function get_modified($in_unix=false)
{
  global $db;
  $time=$this->_get_data('synced');
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
  $this->_set_data('synced', $date);
}

/** @return True if the image was uploaded. Fals otherwise */
function is_upload()
{
  if ($this->_get_data('is_upload')==1)
    return true;
  else
    return false;
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
  return $this->_get_data('gacl');
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
  return $this->_get_data('macl');
}

function set_aacl($aacl)
{
  if (!is_numeric($aacl) || $aacl<0)
    return;

  $this->_set_data('aacl', $aacl);
}

/** Returns the ACL for anyone */
function get_aacl()
{
  return $this->_get_data('aacl');
}

/** Returns the bytes of the image */
function get_bytes()
{
  return $this->_get_data('bytes');
}

function set_bytes($bytes)
{
  $bytes=($bytes<0)?0:$bytes;
  $this->_set_data('bytes', $bytes);
}

function get_orientation()
{
  $this->_get_data('orientation');
}

function set_orientation($orientation)
{
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

function get_height()
{
  return $this->_get_data('height');
}

function set_height($height)
{
  $this->_set_data('height', $height);
}

function get_width()
{
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
  $aacl=$this->get_aacl() & $mask;
  
  if ($user->is_in_group($this->get_groupid()) && $gacl >= $flag)
    return true;
  
  if ($user->is_member() && $macl >= $flag)
    return true;

  if ($aacl >= $flag)
    return true;
  
  return false;
}

/** Return true if user can edit the image 
  @param user User object. Default is null.*/
function can_edit($user=null)
{
  return $this->_check_acl(&$user, ACL_EDIT, ACL_WRITE_MASK);
}

function can_metadata($user=null)
{
  return $this->_check_acl(&$user, ACL_METADATA, ACL_WRITE_MASK);
}

/** Return true if user can preview the image 
  @param user User object. Default is null.*/
function can_preview($user=null)
{
  return $this->_check_acl(&$user, ACL_PREVIEW, ACL_READ_MASK);
}

function can_highsolution($user=null)
{
  return $this->_check_acl(&$user, ACL_HIGHSOLUTION, ACL_READ_MASK);
}

function can_fullsize($user=null)
{
  return $this->_check_acl(&$user, ACL_FULLSIZE, ACL_READ_MASK);
}

function can_download($user=null)
{
  return $this->_check_acl(&$user, ACL_DOWNLOAD, ACL_READ_MASK);
}

/** Returns the size of an thumbnail in an array. This function keeps the
 * ratio.
  @param size of the longes side. Default is 220. If the size is larger than
  the largest side, the size is cutted.
  @return Array of (width, height, str), wherease the string is the string for
  html img tag. On an error it returns an empty array */
function get_size($size=220)
{
  $height=$this->get_height();
  $width=$this->get_width();
  
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
  $sql="SELECT t.name
        FROM $db->tags AS t, $db->imagetag AS it
        WHERE it.imageid=$id 
          AND it.tagid=t.id
        GROUP BY t.name";
  $result=$db->query($sql);
  if (!$result)
    return $this->_tags;

  while($row = mysql_fetch_row($result)) {
    array_push($this->_tags, stripslashes($row[0]));
  }
  sort($this->_tags);
  return $this->_tags;
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
    if ($tag=='')
      continue;

    if (!in_array($tag, $this->_tags))
    {
      $tagid=$db->tag2id($tag, true);
      if ($tagid<0)
        continue; 
      $sql="INSERT INTO $db->imagetag
            (imageid, tagid) 
            VALUES ($id, $tagid)";
      $db->query($sql);
      array_push($this->_tags, $tag);
      $this->set_meta_modified('tags');
    }
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
      $sql="DELETE FROM $db->imagetag
            WHERE imageid=$id AND tagid=$tagid";
      $db->query($sql);
      array_splice($this->_tags, $key, 1);
      $this->set_meta_modified('tags');
    }
  }
}

/** Returns an array of sets. The sets are sorted by name */
function get_sets()
{
  if ($this->_sets)
    return $this->_sets;

  global $db;
  $id=$this->get_id();
  $this->_sets=array();
  $sql="SELECT s.name
        FROM $db->sets AS s, $db->imageset AS iset
        WHERE iset.imageid=$id 
          AND iset.setid=s.id
        GROUP BY s.name";
  $result=$db->query($sql);
  if (!$result)
    return $this->_sets;

  while($row = mysql_fetch_row($result)) {
    array_push($this->_sets, stripslashes($row[0]));
  }
  sort($this->_sets);
  return $this->_sets;
}

/** @return Returns true if the image has tags */
function has_sets()
{
  $this->get_sets();
  return count($this->_sets)>0?true:false;
}

/** Add sets to the image
  @param sets Array of new sets for the image */
function add_sets($sets)
{
  global $db;
  if ($this->_sets==null)
    $this->get_sets();

  $id=$this->get_id();
  foreach ($sets as $set)
  {
    if ($set=='')
      continue;

    if (!in_array($set, $this->_sets))
    {
      $setid=$db->set2id($set, true);
      if ($setid<0)
        continue; 
      $sql="INSERT INTO $db->imageset
            (imageid, setid) 
            VALUES ($id, $setid)";
      $db->query($sql);
      array_push($this->_sets, $set);
      $this->set_meta_modified('sets');
    }
  }
}

/** Deletes sets from the image.
  @param sets Sets to be removed from the image. If null, it removes all sets. 
  Default is null */
function del_sets($sets=null)
{
  global $db;
  if ($this->_sets==null)
    $this->get_sets();

  if ($sets===null)
    $sets=$this->get_sets();

  $id=$this->get_id();
  foreach ($sets as $set)
  {
    $key=array_search($set, $this->_sets);
    if ($key!==false)
    {
      $setid=$db->set2id($set);
      $sql="DELETE FROM $db->imageset
            WHERE imageid=$id AND setid=$setid";
      $db->query($sql);
      array_splice($this->_sets, $key, 1);
      $this->set_meta_modified('sets');
    }
  }
}

/** Returns the location of a given type 
  @param type Location type 
  @return Value of the location. Null if no location is set */
function get_location($type)
{
  $this->get_locations();
  if ($type<LOCATION_UNDEFINED || $type > LOCATION_COUNTRY)
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
  $sql="SELECT l.name, l.type
        FROM $db->locations as l, $db->imagelocation as il
        WHERE il.imageid=$id 
          AND il.locationid=l.id
        ORDER BY l.type";
  $result = $db->query($sql);
  if (!$result)
    return $this->_locations;

  while($row = mysql_fetch_row($result)) {
    $this->_locations[$row[1]]=stripslashes($row[0]);
  }
  return $this->_locations;
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
  if ($type<LOCATION_UNDEFINED || $type > LOCATION_COUNTRY)
    return false;

  $this->get_locations();
  if ($this->_locations[$type]==$value)
    return true;
  $id=$this->get_id();
  $locationid=$db->location2id($value, $type, true);
  if (isset($this->_locations[$type]))
  {
    $oldlocationid=$db->location2id($this->_locations[$type], $type);
    $sql="UPDATE $db->imagelocation
          SET locationid=$locationid
          WHERE imageid=$id AND locationid=$oldlocationid";
  } else {
    $sql="INSERT INTO $db->imagelocation ( imageid, locationid )
          VALUES ( $id, $locationid )";
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
  if ($type<LOCATION_UNDEFINED || $type>LOCATION_COUNTRY)
    return false;

  $this->get_locations();
  if (!isset($this->_locations[$type]))
    return true;

  if ($value!='')
  {
    if ($this->_locations[$type]!=$value)
      return false;

    $locid=$db->location2id($value, $type);
    $sql="DELETE FROM $db->imagelocation as il
          WHERE il.imageid=$id AND il.locationid=$locid";
  } else {
    $sql="DELETE FROM il
          USING $db->imagelocation as il, $db->locations as l
          WHERE il.imageid=$id AND il.locationid=l.id AND l.type=$type";
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

  if ($user->get_id()!=$this->get_userid() && !$user->is_admin())
    return false;

  $id=$this->get_id();
  $sql="DELETE FROM $db->imagetag
        WHERE imageid=$id";
  $db->query($sql);

  $sql="DELETE FROM $db->imageset
        WHERE imageid=$id";
  $db->query($sql);
  
  $sql="DELETE FROM $db->imagelocation
        WHERE imageid=$id";
  $db->query($sql);

  $sql="DELETE FROM $db->images
        WHERE id=$id";
  $db->query($sql);

  return true;
}


/** Deletes one or all images from a specific user
  @param userid ID of user
  @param id Image ID, optional. If this parameter is set, only a single image 
  @return 0 on success. Global error code otherwise */
function delete_from_user($userid, $id=0)
{
  global $db;
  global $user;

  if (!is_numeric($userid) || $userid<1)
    return ERR_PARAM;
  if ($userid!=$user->get_id() && !$user->is_admin())
    return ERR_NOT_PERMITTED;

  // delete tags
  $sql="DELETE 
        FROM it
        USING $db->imagetag AS it, $db->images AS i
        WHERE i.userid=$userid AND i.id=it.imageid";
  if ($id>0) $sql.=" AND i.id=$id";
  $db->query($sql);

  // delete sets
  $sql="DELETE 
        FROM iset
        USING $db->imageset AS iset, $db->images AS i
        WHERE i.userid=$userid AND i.id=iset.imageid";
  if ($id>0) $sql.=" AND i.id=$id";
  $db->query($sql);

  // delete locations
  $sql="DELETE 
        FROM il
        USING $db->imagelocation AS il, $db->images AS i
        WHERE i.userid=$userid AND i.id=il.imageid";
  if ($id>0) $sql.=" AND i.id=$id";
  $db->query($sql);

  // Delete image data
  $sql="DELETE
        FROM $db->images
        WHERE userid=$userid";
  if ($id>0) $sql.=" AND id=$id";
  $db->query($sql);

  return 0;
}

}

?>
