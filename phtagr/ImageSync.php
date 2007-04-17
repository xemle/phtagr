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

include_once("$phtagr_lib/Image.php");

/** @class ImageSync Handels the synchronisation between database and the image
  @todo Improve the lazysync with a better modification granularity. If only
the ACL changes, an image will be exported and only the IPTC writen to an JPEG
file.
 * */ 
class ImageSync extends Image
{

function ImageSync($id=-1)
{
  $this->Image($id);
}

/** Import an image by a filename to the database. If an image with the same
 * filename exists, the function update() is called.
  @param filename Filename of the image
  @param is_upload 1 if the image is uploaded. 0 if the image is local. Default
  is 0.
  @return Returns 0 on success, -1 for failure. On update the return value is
  1. If the file already exists and has no changes, the return value is 2.
  @see update() */
function import($filename, $is_upload=0)
{
  global $db;
  global $user;
  
  if (!file_exists($filename))
    return ERR_FS_NOT_EXISTS;

  // If no file handler exists, return with an error
  $file=$this->get_file_handler($filename);
  if ($file==null)
    return -1;

  $sfilename=mysql_escape_string($filename);
  $sql="SELECT * 
        FROM $db->images
        WHERE filename='$sfilename'";
  $result=$db->query($sql);
  if (!$result)
    return ERR_DB_SELECT;

  // image found in the database. Update it
  if (mysql_num_rows($result)!=0)
  {
    $this->init_by_query($sql);
    $this->_import();
    $this->commit();
    return 1;
  }
  
  $userid=$user->get_id();
  $groupid=$user->get_groupid();

  $gacl=$user->get_gacl();
  $macl=$user->get_macl();
  $aacl=$user->get_aacl();
  
  $sql="INSERT INTO $db->images (
          userid,groupid,created,
          filename,is_upload,
          gacl,macl,aacl,
          clicks,lastview,ranking
        ) VALUES (
          $userid,$groupid,NOW(),
          '$sfilename',$is_upload,
          $gacl,$macl,$aacl,
          0,NOW(),0.0
        )";
  $result=$db->query($sql);
  if (!$result)
    return ERR_DB_INSERT;

  $sql="SELECT *
        FROM $db->images
        WHERE filename='$sfilename'";
  $this->init_by_query($sql);

  $file->import($this);
  $this->set_modified($file->get_filetime(), true);
  $this->commit();
  
  return 0; 
}

/** Import the image data if the file modification time is after the
 * changed time of the image data set or modification of time
  @param force If true, force the update procedure. Default is false.
  @return True if the image was updated. False otherwise */
function _import($force=false)
{
  global $user, $log;

  $file=$this->get_file_handler();
  if ($file==null)
    return false;

  $modified=$this->get_modified(true);
  $time_file=$file->get_filetime();

  // Skip if not forced or if file has the same time
  if (!$force && $time_file<=$modified)
  {
    return false;
  }
  
  // Clear the file stat chache to get updated stats  
  @clearstatcache();
  $log->warn("Importing file ".$this->get_filename(), $this->get_id(), $user->get_id());
  $file->import($this);

  $this->set_modified($file->get_filetime(), true);

  return true;
}

function export()
{
  $this->_export();
}

function _export($force=false)
{
  global $user, $log;

  $file=$this->get_file_handler();
  if ($file==null)
    return;

  @clearstatcache();
  $modified=$this->get_modified(true);
  $time_file=$file->get_filetime();

  $changed=$this->is_modified() || $this->is_meta_modified();
  if (!$force && $time_file>=$modified && !$changed)
  {
    return false;
  }
  
  $this->commit();
  $log->warn("Exporting file ".$this->get_filename(), $this->get_id(), $user->get_id());

  $old_time=$file->get_filetime();
  $file->export($this);
  @clearstatcache();
  $new_time=$file->get_filetime();
  if ($new_time>$old_time)
    $this->set_modified($file->get_filetime(), true);

  return true;
}

/** Synchronize files between the database and the filesystem. If a file not
 * exists delete its data. If a file is newer since the last update, update its
 * data. 
  @param userid Userid which must match current user. If userid -1 and user is
  admin, all files are synchronized. 
  @return Array of count files, updated files, and deleted files. On error, the
  first array value is the global error code */
function sync_files($userid=-1)
{
  global $db;
  global $user;

  $sql="SELECT id,userid,filename
        FROM $db->images";
  if ($userid>0)
  {
    if ($userid!=$user->get_id() && !$user->is_member())
      return array(ERR_NOT_PERMITTED, 0, 0);
    $sql.=" WHERE userid=$userid";
  } else {
    if (!$user->is_admin())
      return array(ERR_NOT_PERMITTED, 0, 0);
  }

  $result=$db->query($sql);
  if (!$result)
    return 0;
    
  @clearstatcache();
  $count=0;
  $updated=0;
  $deleted=0;
  while ($row=mysql_fetch_row($result))
  {
    $id=$row[0];
    $img_userid=$row[1];
    $filename=$row[2];
    $count++;
    
    if (!file_exists($filename))
    {
      $image=new ImageSync($id);
      $image->delete();
      $deleted++;
    }
    else 
    {
      $image=new ImageSync($id);
      if ($image->_import())
      {
        $image->commit();
        $updated++;
      }
      if ($image->_export())
      {
        $image->commit();
        $updated++;
      }
      unset($image);
    }
  }
  return array($count, $updated, $deleted);
}

/** Deletes the file if it was uploaded */
function delete()
{
  global $user, $logs;

  if ($user->get_id()!=$this->get_userid() && !$user->is_admin())
    return;

  $log->info("Deleting '".$this->get_filename()."' from database", $this->get_id(), $user->get_id());

  $previewer=$this->get_preview_handler();
  if ($previewer!=null)
    $previewer->delete_previews();

  if ($this->is_upload())
    @unlink($this->get_filename());

  parent::delete();
}

/** Delets all uploaded files */
function delete_from_user($userid, $id)
{
  global $user;
  global $db;

  if (!is_numeric($userid) || $userid<1)
    return ERR_PARAM;
  if ($userid!=$user->get_id() && !$user->is_admin())
    return ERR_NOT_PERMITTED;

  $previewer=new PreviewerBase();
  $previewer->delete_from_user($userid, $id);

  $userid=$user->get_id();
  $sql="SELECT filename
        FROM $db->images
        WHERE userid=$userid AND is_upload=1";
  $result=$db->query($sql);
  if ($result) {
    while ($row=mysql_fetch_row($result)) {
      @unlink($row[0]);
    }
  }
  return parent::delete_from_user($userid, $id);
}

/** Handle the caption input 
  @param prefix Prefix of forumlar input names
  @param merge True if data should merge. False if data will overwrite the
  current data */
function _handle_request_caption($prefix='', $merge)
{
  if (!isset($_REQUEST[$prefix.'caption']) || 
    $_REQUEST[$prefix.'caption']=='') {
    if ($merge)
      $this->set_caption(null);
    return;
  }
  $this->set_caption($_REQUEST[$prefix.'caption']);
}

/** Checks the values of the date inpute and sets the date of the image
  @param year Valid range is 1000 < year < 3000
  @param month Valid range is 1 <= month <= 12
  @param day Valid range is 1 <= day <= 31
  @param hour Valid range is 0 <= hour < 23
  @param min Valid range is 0 <= min < 60
  @param sec Valid range is 0 <= sec < 60 */
function _check_date($year, $month, $day, $hour, $min, $sec)
{
  $year=$year<1000?1000:($year>3000?3000:$year);
  $month=$month<1?1:($month>12?12:$month);
  $day=$day<1?1:($day>31?31:$day);

  $hour=$hour<0?0:($hour>23?23:$hour);
  $min=$min<0?0:($min>59?59:$min);
  $sec=$sec<0?0:($sec>59?59:$sec);

  $date=sprintf("%04d-%02d-%02d", $year, $month, $day);
  $time=sprintf("%02d-%02d-%02d", $hour, $min, $sec);

  $this->set_date($date.' '.$time);
}

/** Handle the date input 
  @param prefix Prefix of forumlar input names
  @param merge True if data should merge. False if data will overwrite the
  current data 
  @todo Check date input for the database */
function _handle_request_date($prefix='', $merge)
{
  if (!isset($_REQUEST[$prefix.'date']) || 
    $_REQUEST[$prefix.'date']=='') {
    return true;
  }

  $date=$_REQUEST[$prefix.'date'];
  if ($date=='-') {
    return true;
  }

  // Check format of YYYY-MM-DD hh:mm:ss
  if (!preg_match('/^[0-9]{4}(-[0-9]{2}(-[0-9]{2}( [0-9]{2}(:[0-9]{2}(:[0-9]{2})?)?)?)?)?$/', $date))
    return false;

  $this->_check_date(
    intval(substr($date, 0, 4)), 
    intval(substr($date, 5, 2)), 
    intval(substr($date, 8, 2)),
    intval(substr($date, 11, 2)), 
    intval(substr($date, 14, 2)), 
    intval(substr($date, 17, 2)));
  return true;
}

/** Handle the tag input 
  @param prefix Prefix of forumlar input names
  @param merge True if data should merge. False if data will overwrite the
  current data */
function _handle_request_tags($prefix='', $merge)
{
  global $conf;
  $sep=$conf->get('meta.separator', ';');
  $sep=($sep==" ")?"\s":$sep;
  $tags=preg_split("/[$sep]+/", $_REQUEST[$prefix.'tags']);
  if (count($tags)==0)
    return false;

  // distinguish between add and remove operation.
  $add_tags=array();
  $del_tags=array();
  foreach ($tags as $tag)
  {
    $tag=trim($tag);
    if ($tag=='-' || $tag=='')
      continue;

    if ($tag{0}=='-')
      array_push($del_tags, substr($tag, 1));
    else
      array_push($add_tags, $tag);
  }
  if (count($del_tags))
    $this->del_tags($del_tags);

  if (!$merge)
  {
    $db_tags=$this->get_tags();
    $del_tags=array_diff($db_tags, $add_tags);
    if (count($del_tags))
      $this->del_tags($del_tags);
  }
  $this->add_tags($add_tags);
}

/** Handle the set input 
  @param prefix Prefix of forumlar input names
  @param merge True if data should merge. False if data will overwrite the
  current data */
function _handle_request_sets($prefix='', $merge)
{
  global $conf;
  $sep=$conf->get('meta.separator', ';');
  $sep=($sep==" ")?"\s":$sep;
  $sets=preg_split("/[$sep]+/", $_REQUEST[$prefix.'sets']);
  if (count($sets)==0)
    return false;

  // distinguish between add and remove operation.
  $add_sets=array();
  $del_sets=array();
  foreach ($sets as $set)
  {
    $set=trim($set);
    if ($set=='-' || $set=='')
      continue;

    if ($set{0}=='-')
      array_push($del_sets, substr($set, 1));
    else
      array_push($add_sets, $set);
  }
  if (count($del_sets))
    $this->del_sets($del_sets);
  if (!$merge)
  {
    $db_sets=$this->get_sets();
    $del_sets=array_diff($db_sets, $add_sets);
    if (count($del_sets))
      $this->del_sets($del_sets);
  }
  $this->add_sets($add_sets);
}

/** Handle the location input 
  @param prefix Prefix of forumlar input names
  @param merge True if data should merge. False if data will overwrite the
  current data */
function _handle_request_location($prefix='', $merge)
{
  $loc=array(LOCATION_CITY => $_REQUEST[$prefix.'city'],
    LOCATION_SUBLOCATION => $_REQUEST[$prefix.'sublocation'],
    LOCATION_STATE => $_REQUEST[$prefix.'state'],
    LOCATION_COUNTRY => $_REQUEST[$prefix.'country']);

  for ($i=LOCATION_CITY ; $i<= LOCATION_COUNTRY ; $i++) {
    if ($loc[$i]!='') {
      $this->set_location($loc[$i], $i);
    } else if (!$merge) {
      $this->del_location(null, $i);
    }
  }
}

function handle_request()
{
  global $conf;
  $this->_import(false);

  if (!isset($_REQUEST['edit']))
    return false;

  $edit=$_REQUEST['edit'];
  if ($edit=='multi')
  {
    $prefix='edit_';
    $this->_handle_request_caption($prefix, true);
    $this->_handle_request_date($prefix, true);
    $this->_handle_request_tags($prefix, true);
    $this->_handle_request_sets($prefix, true);
    $this->_handle_request_location($prefix, true);
  } else if ($edit=='js_meta') {
    $prefix='js_';
    $this->_handle_request_date($prefix, false);
    $this->_handle_request_tags($prefix, false);
    $this->_handle_request_sets($prefix, false);
    $this->_handle_request_location($prefix, false);
  } else if ($edit=='js_caption') {
    $prefix='js_';
    $this->_handle_request_caption($prefix, false);
  }

  // Commit changes to update the values
  $changed=$this->is_modified() || $this->is_meta_modified();  
  if (!$changed)
  {
    return;
  }

  // Dont export on lazy sync but update modified
  if ($conf->query($this->get_userid(), 'image.lazysync', 0)==1)
  {
    $this->set_modified(time(), true);
    $this->commit();
  }
  else
  {
    $this->_export(false);
    $this->commit();
  }

  $previewer=$this->get_preview_handler();
  if ($previewer!=null)
    $previewer->touch_previews();
}

}
?>
