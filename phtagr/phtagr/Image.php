<?php

include_once("$phtagr_lib/Search.php");
include_once("$phtagr_lib/Base.php");
include_once("$phtagr_lib/Constants.php");

/** 
  An image is assigned to a user and a group. With the access control lists
  (ACL) the privacy of the image can be set precisely. 

  The owner can modify the image and can set the permissions of the image. The
  owner assigns the image to a group and the ACLs. The ACL defines access
  rights for groups, members and public. Therefore, a image can be accessed by
  everyone, for members only, for group members or only the owner itself.

  @class Image Models the image data object.
*/
class Image extends Base
{

/** Array of the database values from table image */
var $_data;
var $_tags;
var $_sets;
var $_locations;

/** Creates an Image object 
  @param id Id of the image. */
function Image($id=-1)
{
  $_data=null;
  $_tags=null;
  $_sets=null;
  $_locations=null;
  $this->init_by_id($id);
}

/** Initialize the values from the database into the object 
  @param id ID from a specific image
  @return True on success. False on failure
*/
function init_by_id($id)
{
  global $db;
  
  if ($id<=0)
    return false;
    
  $sql="SELECT * 
        FROM $db->image
        WHERE id=$id";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)==0)
    return false;
    
  unset($this->_data);
  $this->_data=mysql_fetch_array($result, MYSQL_ASSOC);
  return true;
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
    
  $filenamesql=str_replace('\\','\\\\',$filename);

  $sql="SELECT * 
        FROM $db->image
        WHERE filename='$filenamesql'";
  $result=$db->query($sql);
  if (!$result || mysql_num_rows($result)==0)
    return false;
    
  unset($this->_data);
  $this->_data=mysql_fetch_array($result, MYSQL_ASSOC);
  return true;
}

/** Insert an image by a filename to the database. If an image with the same
 * filename exists, the function update() is called.
  @param filename Filename of the image
  @param is_upload 1 if the image is uploaded. 0 if the image is local. Default
  is 0.
  @return Returns 0 on success, -1 for failure. On update the return value is
  1. If the file already exists and has no changes, the return value is 2.
  @see update() */
function insert($filename, $is_upload=0)
{
  global $db;
  global $user;
  
  if (!file_exists($filename))
  {
    $this->error(sprintf(_("File '%s' does not exists"), $filename));
    return -1;
  } 
  
  $sfilename=mysql_escape_string($filename);
  
  $sql="SELECT * 
        FROM $db->image
        WHERE filename='$sfilename'";
  $result=$db->query($sql);
  if (!$result)
    return -1;

  // image found in the database. Update it
  if (mysql_num_rows($result)!=0)
  {
    $this->_data=mysql_fetch_array($result, MYSQL_ASSOC);
    $this->update();
    return 1;
  }
  
  $userid=$user->get_id();
  $groupid=$user->get_groupid();

  $gacl=$user->get_gacl();
  $oacl=$user->get_oacl();
  $aacl=$user->get_aacl();
  
  $sql="INSERT INTO $db->image (
          userid,groupid,synced,created,
          filename,is_upload,
          gacl,oacl,aacl,
          clicks,lastview,ranking
        ) VALUES (
          $userid,$groupid,NOW(),NOW(),
          '$sfilename',$is_upload,
          $gacl,$oacl,$aacl,
          0,NOW(),0.0
        )";
  $result=$db->query($sql);
  if (!$result)
    return -1;
  $sql="SELECT *
        FROM $db->image
        WHERE filename='$sfilename'";
  
  $result=$db->query($sql);
  if (!$result)
    return -1;
  
  $this->_data=mysql_fetch_array($result, MYSQL_ASSOC);

  $this->reinsert();
  
  return 0; 
}

/** Update the image data if the file modification time is after the
 * synchronization time of the image data set. 
  @param force If true, force the update procedure. Default is false.
  @return True if the image was updated. False otherwise */
function update($force=false)
{
  global $db;
  
  $synced=$this->get_synced(true);
  $ctime=filectime($this->get_filename());
  if (!$force && $ctime < $synced)
  {
    //$this->debug("Synced: $ctime $synced");
    return false;
  }
  
  $this->reinsert();

  $sql="UPDATE $db->image 
        SET synced=NOW()
        WHERE id=".$this->get_id();
  $result=$db->query($sql);
  if ($result)
    return true;
}

/** Reinsert the image data to the database. It will remove all tags and the
 * caption of the image.
  @return True on success, false on failure 
  @note This function does not change the synchronization timestamp of the
  database. */
function reinsert()
{
  global $db;
  if (!isset($this->_data))
    return false;
  
  $this->remove_tags();
  $this->remove_sets();
  $this->remove_locations();
  $this->remove_caption();

  $this->_insert_static();
  $this->_insert_exif();
  $this->_insert_iptc();
  
  return true; 
}

/** Returns the database value to the given fieldname.
  @return null if the given field is not set */
function _get_data($name)
{
  if (isset($this->_data[$name]))
    return $this->_data[$name];
  else
    return null;
}

/** Returns the id of the image */
function get_id()
{
  return $this->_get_data('id');
}

/** Returns the filename of the image */
function get_filename()
{
  return $this->_get_data('filename');
}

/** Returns the name of the image */
function get_name()
{
  return $this->_get_data('name');
}

/** Returns the user ID of the image */
function get_userid()
{
  return $this->_get_data('userid');
}

/** Returns the group ID of the image */
function get_groupid()
{
  return $this->_get_data('groupid');
}

/** Returns the syncronization date of the image
  @param in_unix Return time in unix timestamp if true. If false return the
  mysql time string */
function get_synced($in_unix=false)
{
  $synced=$this->_get_data('synced');
  if ($in_unix)
    return $this->_sqltime2unix($synced);
  else
    return $synced;
}

/** @return True if the image was uploaded. Fals otherwise */
function is_upload()
{
  if ($this->_get_data('is_upload')==1)
    return true;
  else
    return false;
}

/** Returns the group ACL */
function get_gacl()
{
  return $this->_get_data('gacl');
}

/** Returns the ACL for other phtagr users*/
function get_oacl()
{
  return $this->_get_data('oacl');
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

function get_caption()
{
  return stripslashes($this->_get_data('caption'));
}

/** Return the date of the image
  @param in_unix If true return the unix timestamp. Otherwise return the sql
  time string */
function get_date($in_unix=false)
{
  $date=$this->_get_data('date');
  if ($in_unix)
    return $this->_sqltime2unix($date);
  else
    return $date;
}

function get_height()
{
  return $this->_get_data('height');
}

function get_width()
{
  return $this->_get_data('width');
}

/** Returns the count of views */
function get_clicks()
{
  return $this->_get_data('clicks');
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
  @param voting Vote of the image
  @return True on success, false otherwise */
function new_vote($voting) 
{
  global $db;
  $voting=intval($voting);
  if ($voting<0 || $voting>VOTING_MAX)
    return false;

  $id=$this->get_id();
  $sql="UPDATE $db->image 
        SET voting=((voting*votes+$voting)/(votes+1)), votes=votes+1
        WHERE id=$id";
  if (!$db->query($sql))
    return false;

  return true;
}

/** Return the time of the last click
  @param in_unix If true return the unix timestamp. Otherwise return the sql
  time string */
function get_lastview($in_unix=false)
{
  $lastview=$this->_get_data('lastview');
  if ($in_unix)
    return $this->_sqltime2unix($lastview);
  else
    return $lastview;
}

/** Return true if the user can select an image */
function can_select($user=null)
{
  return true;
}

/** Return if the given user has the same user id than an object 
  @param image image object
*/
function is_owner($user=null)
{
  if ($user==null)
    return false;
    
  if ($user->is_admin())
    return true;

  if ($this->get_userid()==
    $user->get_id())
    return true;
  return false;
}

/** Checks the acl of an image 
  @param image Image object
  @param flag ACL bit mask
  @return True if user is allow to do the action defined by the flag */
function _check_acl($user, $flag)
{
  if (!isset($user))
    return false;
    
  // Admin is permitted always
  if ($user->is_admin())
    return true;
  
  if ($user->get_id()==$this->get_userid())
    return true;
    
  // If acls are calculated within if statement, I got wrong evaluation.
  $gacl=$this->get_gacl() & $flag;
  $oacl=$this->get_oacl() & $flag;
  $aacl=$this->get_aacl() & $flag;
  
  if ($user->is_in_group($this->get_groupid()) && $gacl > 0)
    return true;
  
  if ($user->is_member() && $oacl > 0)
    return true;

  if ($aacl > 0)
    return true;
  
  return false;
}

/** Return true if user can edit the image 
  @param image Image object. Default is null.*/
function can_edit($user=null)
{
  return $this->_check_acl(&$user, ACL_EDIT);
}

function can_metadata($user=null)
{
  return $this->_check_acl(&$user, ACL_METADATA);
}

/** Return true if user can upload a file with the given size
/** Return true if user can preview the image 
  @param user User object. Default is null.*/
function can_preview($user=null)
{
  return $this->_check_acl(&$user, ACL_PREVIEW);
}

function can_highsolution($user=null)
{
  return $this->_check_acl(&$user, ACL_HIGHSOLUTION);
}

function can_fullsize($user=null)
{
  return $this->_check_acl(&$user, ACL_FULLSIZE);
}

function can_download($user=null)
{
  return $this->_check_acl(&$user, ACL_DOWNLOAD);
}

/** Returns the size of an thumbnail in an array. This function keeps the
 * ratio.
  @param Size of the longes side. Default is 220. If the size is larger than
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

/** Read static values from an image and insert them into the database. The
 * static values are filesize, name, width and height */
function _insert_static()
{
  global $db;
  if (!isset($this->_data))
    return false;

  $filename=$this->get_filename();
  
  $bytes=filesize($filename);
  $name=basename($filename);
  $name=preg_replace("/'/s", "\\'", $name);

  $size=getimagesize($filename);
  if ($size)
  {
    $width=$size[0];
    $height=$size[1];
  }
  else
  {
    $width=0;
    $height=0;
  }

  $sql="UPDATE $db->image 
        SET bytes=$bytes,name='$name',
          width=$width,height=$height
        WHERE id=".$this->get_id();
  $result=$db->query($sql);
  if (!$result)
    return false;

  return true;
}

/** Reads the exif data. Currently, it reads only the time of the shot and the image orientation */
function _insert_exif()
{
  global $db;

  if (!isset($this->_data))
    return false;
    
  $date="NOW()";
  $orientation=1;
  if (function_exists('exif_read_data'))
  {
    $exif = @exif_read_data($this->get_filename(), 0, true);
    
    if (isset($exif['EXIF']) && isset($exif['EXIF']['DateTimeOriginal']))
      $date="'".$exif['EXIF']['DateTimeOriginal']."'";
   
    if (isset($exif['IFD0']) && isset($exif['IFD0']['Orientation']))
      $orientation=$exif['IFD0']['Orientation'];
  }
   
  $sql="UPDATE $db->image 
        SET date=$date,orientation=$orientation
        WHERE id=".$this->get_id();
  $result = $db->query($sql);
  if (!$result)
    return false;

  return true;
}

/** Read the iptc from the image and insert the values to the database */
function _insert_iptc()
{
  if (!isset($this->_data))
    return false;

  $iptc=new Iptc();
  $iptc->load_from_file($this->get_filename());
  if ($iptc->get_errno()<0)
  {
    echo $iptc->get_errmsg();
    return false;
  }

  $this->_insert_iptc_tags(&$iptc);
  $this->_insert_iptc_sets(&$iptc);
  $this->_insert_iptc_caption(&$iptc);
  $this->_insert_iptc_date(&$iptc);
  $this->_insert_iptc_location(&$iptc);
  
  return true;
}

/** Read the tags from the IPTC segment and insert the tags to the database 
  @param iptc The Iptc object of the current image */
function _insert_iptc_tags($iptc=null)
{
  global $db;
  if (!isset($this->_data) || !isset($iptc))
    return false;

  $id=$this->get_id();
  
  $tags=$iptc->get_records('2:025');
  if ($tags!=null)
  {
    foreach ($tags as $index => $tag)
    {
      $tagid=$db->tag2id($tag, true);
      $sql="INSERT INTO $db->imagetag ( imageid, tagid )
            VALUES ( $id, $tagid )";
      $result = $db->query($sql);
      if (!$result)
        return false;
    }
  }
  return true;    
}


/** Read the sets from the IPTC segment and insert the sets to the database 
  @param iptc The Iptc object of the current image */
function _insert_iptc_sets($iptc=null)
{
  global $db;
  if (!isset($this->_data) || !isset($iptc))
    return false;

  $id=$this->get_id();
  
  $sets=$iptc->get_records('2:020');
  if ($sets!=null)
  {
    foreach ($sets as $index => $set)
    {
      $setid=$db->set2id($set, true);
      $sql="INSERT INTO $db->imageset ( imageid, setid )
            VALUES ( $id, $setid )";
      $result = $db->query($sql);
      if (!$result)
        return false;
    }
  }
  return true;    
}

/** Read the caption from the IPTC segment and insert it to the database
  @param iptc The Iptc object of the current image */
function _insert_iptc_caption($iptc=null)
{
  global $db;
  if (!isset($this->_data) || !isset($iptc))
    return false;

  $id=$this->get_id();
  
  $caption=$iptc->get_record('2:120');
  $scaption=mysql_escape_string($caption);
  if ($caption!=null)
  {
    $sql="UPDATE $db->image
          SET caption='$scaption'
          WHERE id=$id";
    $result = $db->query($sql);
    if (!$result)
      return false;
  }
  return true;
}

/** Read the date from the IPTC segment and insert it to the database
  @param iptc The Iptc object of the current image */
function _insert_iptc_date($iptc=null)
{
  global $db;
  if (!isset($this->_data) || !isset($iptc))
    return false;

  $id=$this->get_id();
  // Extract IPTC date and time
  $date=$iptc->get_record('2:055');
  if ($date!=null)
  {
    // Convert IPTC date/time to sql timestamp "YYYY-MM-DD hh:mm:ss"
    // IPTC date formate is YYYYMMDD
    $date=substr($date, 0, 4)."-".substr($date, 4, 2)."-".substr($date, 6, 2);
    $time=$iptc->get_record('2:060');
    // IPTC time format is hhmmss[+offset]
    if ($time!=null)
    {
      $time=" ".substr($time, 0, 2).":".substr($time, 2, 2).":".substr($time, 4, 2);
    }
    $date=$date.$time;
    $sql="UPDATE $db->image
          SET date='$date'
          WHERE id=$id";
    $result = $db->query($sql);
    if (!$result)
      return false;
  }
  return true;
} 


/** Read the location data from the IPTC segment and insert it to the database
  @param iptc The Iptc object of the current image */
function _insert_iptc_location($iptc=null)
{
  global $db;
  if (!isset($this->_data) || !isset($iptc))
    return false;

  $id=$this->get_id();
  
  // Remove old stuff
  $sql="DELETE FROM $db->imagelocation 
        WHERE imageid=$id";
  $result=$db->query($sql);
  
  // Extract IPTC city
  $city=$iptc->get_record('2:090');
  if ($city!=null)
  {
    $locationid=$db->location2id($city, LOCATION_CITY, true);
    $sql="INSERT INTO $db->imagelocation ( imageid, locationid )
          VALUES ( $id, $locationid )";
    $result = $db->query($sql);
    if (!$result)
      return false;
  }
  $sublocation=$iptc->get_record('2:092');
  if ($sublocation!=null)
  {
    $locationid=$db->location2id($sublocation, LOCATION_SUBLOCATION, true);
    $sql="INSERT INTO $db->imagelocation ( imageid, locationid )
          VALUES ( $id, $locationid )";
    $result = $db->query($sql);
    if (!$result)
      return false;
  }
  $state=$iptc->get_record('2:095');
  if ($state!=null)
  {
    $locationid=$db->location2id($state, LOCATION_STATE, true);
    $sql="INSERT INTO $db->imagelocation ( imageid, locationid )
          VALUES ( $id, $locationid )";
    $result = $db->query($sql);
    if (!$result)
      return false;
  }
  $country=$iptc->get_record('2:101');
  if ($country!=null)
  {
    $locationid=$db->location2id($country, LOCATION_COUNTRY, true);
    $sql="INSERT INTO $db->imagelocation ( imageid, locationid )
          VALUES ( $id, $locationid )";
    $result = $db->query($sql);
    if (!$result)
      return false;
  }
  return true;
} 

/** Remove tags from the database 
  @return true on success, false on failure */
function remove_tags()
{
  global $db;
  if (!isset($this->_data))
    return false;
    
  $sql="DELETE FROM $db->imagetag
        WHERE imageid=".$this->get_id();
  $result = $db->query($sql);
  if (!$result)
    return false;

  return true;
}

/** Remove sets from the database 
  @return true on success, false on failure */
function remove_sets()
{
  global $db;
  if (!isset($this->_data))
    return false;
    
  $sql="DELETE FROM $db->imageset
        WHERE imageid=".$this->get_id();
  $result = $db->query($sql);
  if (!$result)
    return false;

  return true;
}

/** Remove locations from the database 
  @return true on success, false on failure */
function remove_locations()
{
  global $db;
  if (!isset($this->_data))
    return false;
    
  $sql="DELETE FROM $db->imagelocation
        WHERE imageid=".$this->get_id();
  $result = $db->query($sql);
  if (!$result)
    return false;

  return true;
}

/** Remove caption from the database 
  @return true on success, false on failure */
function remove_caption()
{
  global $db;
  if (!isset($this->_data))
    return false;
  
  // remove caption
  $sql="UPDATE $db->image
        SET caption=NULL
        WHERE id=".$this->get_id();
  $result = $db->query($sql);
  if (!$result)
    return false;

  return true;
}

/** Removes an image from the database
  @return True on success, false otherwise */
function remove_from_db()
{
  global $db;
  $ret=true;
  $ret&=$this->remove_tags();
  $ret&=$this->remove_sets();
  $ret&=$this->remove_locations();
  $sql="DELETE FROM $db->image
        WHERE id=".$this->get_id();
  $result=$db->query($sql);
  if (!$result)
    $ret=false;
  return $ret;
}
/** Convert the SQL time string to unix time stamp.
  @param string The time string has the format like "2005-04-06 09:24:56", the
  result is 1112772296
  @return Unix time in seconds */
function _sqltime2unix($string)
{
  if (strlen($string)!=19)
    return 0;

  $s=strtr($string, ":", " ");
  $s=strtr($s, "-", " ");
  $a=split(' ', $s);
  $time=mktime($a[3],$a[4],$a[5],$a[1],$a[2],$a[0]);
  return $time;
}

/** Update the ranking value of the image. This is calculated by the current
 * ranking value and the interval to the last view */
function update_ranking()
{
  global $db;

  $id=$this->get_id();
  $ranking=$this->get_ranking();
  $lastview=$this->get_lastview(true);

  $ranking=0.8*$ranking+100/log((1+time()-$lastview));     

  $sql="UPDATE $db->image
        SET ranking=$ranking
        WHERE id=$id";
  $result=$db->query($sql);
  
  $sql="UPDATE $db->image
        SET clicks=clicks+1, lastview=NOW()
        WHERE id=$id";
  $result = $db->query($sql);
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
        FROM $db->tag AS t, $db->imagetag AS it
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

/** Returns an array of sets. The sets are sorted by name */
function get_sets()
{
  if ($this->_sets)
    return $this->_sets;

  global $db;
  $id=$this->get_id();
  $this->_sets=array();
  $sql="SELECT s.name
        FROM $db->set AS s, $db->imageset AS iset
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

/** Returns an array of location. The locations are sorted by name */
function get_locations()
{
  if ($this->_locations)
    return $this->_locations;

  global $db;
  $this->_locations=array();
  $id=$this->get_id();
  $sql="SELECT l.name, l.type
        FROM $db->location as l, $db->imagelocation as il
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


}

?>
