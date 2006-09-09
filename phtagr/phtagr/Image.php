<?php

include_once("$phtagr_prefix/Search.php");
include_once("$phtagr_prefix/Base.php");
include_once("$phtagr_prefix/Constants.php");

/** 
  @class Image Create thumbnails, image previews, synchronize 
*/
class Image extends Base
{

/** Array of the database values from table image */
var $_data;

/** Creates an Image object 
  @param id Id of the image. */
function Image($id=-1)
{
  $_data=null;
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
    $this->error("File '$filename' does not exists");
    return -1;
  } 
  
  $filenamesql=str_replace('\\','\\\\',$filename);
  
  $sql="SELECT * 
        FROM $db->image
        WHERE filename='$filenamesql'";
  $result=$db->query($sql);
  if (!$result)
    return false;

  // image found in the database. Update it
  if (mysql_num_rows($result)!=0)
  {
    $this->_data=mysql_fetch_array($result, MYSQL_ASSOC);
    if ($this->update())
      return 1;
      
    return 2;
  }
  
  $userid=$user->get_userid();
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
          '$filenamesql',$is_upload,
          $gacl,$oacl,$aacl,
          0,NOW(),0.0
        )";
  $result=$db->query($sql);
  if (!$result)
    return -1;
  $sql="SELECT *
        FROM $db->image
        WHERE filename='$filenamesql'";
  
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
  return $this->_get_data('caption');
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
    
  $exif = @exif_read_data($this->get_filename(), 0, true);
  if (!$exif)
    return false;
    
  if (isset($exif['EXIF']['DateTimeOriginal']))
    $date="'".$exif['EXIF']['DateTimeOriginal']."'";
  else
    $date="NOW()";
  
  if (isset($exif['IFD0']['Orientation']))
    $orientation=$exif['IFD0']['Orientation'];
  else
    $orientation=1;
    
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
  if ($tags!=NULL)
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

/** Read the caption from the IPTC segment and insert it to the database
  @param iptc The Iptc object of the current image */
function _insert_iptc_caption($iptc=null)
{
  global $db;
  if (!isset($this->_data) || !isset($iptc))
    return false;

  $id=$this->get_id();
  
  $caption=$iptc->get_record('2:120');
  if ($caption!=NULL)
  {
    $sql="UPDATE $db->image
          SET caption='$caption'
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
  if ($date!=NULL)
  {
    // Convert IPTC date/time to sql timestamp "YYYY-MM-DD hh:mm:ss"
    // IPTC date formate is YYYYMMDD
    $date=substr($date, 0, 4)."-".substr($date, 4, 2)."-".substr($date, 6, 2);
    $time=$iptc->get_record('2:060');
    // IPTC time format is hhmmss[+offset]
    if ($time!=NULL)
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
  if ($city!=NULL)
  {
    $locationid=$db->location2id($city, LOCATION_CITY, true);
    $sql="INSERT INTO $db->imagelocation ( imageid, locationid )
          VALUES ( $id, $locationid )";
    $result = $db->query($sql);
    if (!$result)
      return false;
  }
  $sublocation=$iptc->get_record('2:092');
  if ($sublocation!=NULL)
  {
    $locationid=$db->location2id($sublocation, LOCATION_SUBLOCATION, true);
    $sql="INSERT INTO $db->imagelocation ( imageid, locationid )
          VALUES ( $id, $locationid )";
    $result = $db->query($sql);
    if (!$result)
      return false;
  }
  $state=$iptc->get_record('2:095');
  if ($state!=NULL)
  {
    $locationid=$db->location2id($state, LOCATION_STATE, true);
    $sql="INSERT INTO $db->imagelocation ( imageid, locationid )
          VALUES ( $id, $locationid )";
    $result = $db->query($sql);
    if (!$result)
      return false;
  }
  $country=$iptc->get_record('2:101');
  if ($country!=NULL)
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

  $ranking=0.8*$ranking+500/(1+time()-$lastview);     

  $sql="UPDATE $db->image
        SET ranking=$ranking
        WHERE id=$id";
  $result=$db->query($sql);
  
  $sql="UPDATE $db->image
        SET clicks=clicks+1, lastview=NOW()
        WHERE id=$id";
  $result = $db->query($sql);
}

/** Print the caption of an image. 
  @param id ID of current image
  @param caption String of the caption
  @param docut True if a long caption will be shorted. False if the whole
  caption will be printed. Default true */
function print_caption($docut=true)
{
  global $user;
  $id=$this->get_id();
  $caption=$this->get_caption();
  
  $can_edit=$user->can_edit(&$this);
  
  echo "<div class=\"caption\" id=\"caption-$id\">";
  // the user can not edit the image
  if (!$can_edit)
  {
    if ($caption!="")
      echo $this->_cut_caption($id, &$caption);

    echo "</div>\n";
    return;
  }
  
  // The user can edit the image
  if ($caption != "") 
  {
    if ($docut=true)
      $text=$this->_cut_caption($id, &$caption);
    else
      $text=&$caption;
    $caption64=base64_encode($caption);
    echo "$text <span class=\"jsbutton\" onclick=\"add_form_caption($id, '$caption64') \">[edit]</span>";
  }
  else
  {
    echo " <span onclick=\"add_form_caption($id, '')\">Click here to add a caption</span>";
  }
  
  echo "</div>\n";
}

/** Cut the caption by words. If the length of the caption is longer than 20
 * characters, the caption will be cutted into words and reconcartenated to the
 * length of 20.  */
function _cut_caption($id, $caption)
{
  if (strlen($caption)< 60) 
    return $caption;

  $words=split(" ", $caption);
  foreach ($words as $word)
  {
    if (strlen($result) > 40)
      break;

    $result.=" $word";
  }
  $result="<span id=\"caption-text-$id\">".$result;
  $b64=base64_encode($caption);
  $result.=" <span class=\"jsbutton\" onclick=\"print_caption($id, '$b64')\">[...]</span>";
  $result.="</span>";
  return $result;
}

function print_row_clicks()
{
  $ranking=sprintf("%.3f", $this->get_ranking());
  echo "  <tr><th>Clicks:</th><td>".$this->get_clicks()
    ." (Populariy: $ranking)</td></tr>\n";
}

function print_row_voting()
{
  $id=$this->get_id();
  $voting=sprintf("%.1f", $this->get_voting());
  $votes=$this->get_votes();
  echo "  <tr><th>Voting:</th><td>";

  if ($votes>0) 
    echo "$voting ($votes votes) ";
  else
    echo "No votes ";

  if (!isset($_SESSION['img_voted'][$id]))
  {
    echo "<select size=\"1\" name=\"voting\">\n"
      ."  <option selected=\"selected\" value=\"none\">None</option>\n";
    for ($i=0; $i<=VOTING_MAX; $i++)
    {
      switch ($i) {
      case 0: $s=" (worst)"; break;
      case VOTING_MAX: $s=" (best)"; break;
      default: $s=""; 
      }
      echo "  <option value=\"$i\">$i$s</option>\n";
    }
    echo "</select>&nbsp;<input type=\"submit\" value=\"Vote!\" />";
  }
  echo "</td></tr>\n";
}

function print_row_filename()
{
  echo "  <tr><th>File:</th><td>".$this->get_filename()."</td></tr>\n";
}

function print_row_acl()
{
  $id=$this->get_id();
  $gacl=$this->get_gacl();
  $oacl=$this->get_oacl();
  $aacl=$this->get_aacl();
  echo "  <tr><th>ACL:</th><td id=\"acl-$id\">$gacl,$oacl,$aacl";
  echo " <span class=\"jsbutton\" onclick=\"add_form_acl('$id',$gacl,$oacl,$aacl)\">[edit]</span>";
  echo "</td></tr>\n";
}

function print_row_date()
{
  $sec=$this->_sqltime2unix($this->get_date());
  
  echo "  <tr>
    <th>Date:</th>
    <td>";
  $date=date("Y-m-d H:i:s", $sec);
  $search_date=new Search();
  $search_date->date_start=$sec-(60*30*3);
  $search_date->date_end=$sec+(60*30*3);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "<a href=\"$url\">$date</a>\n";

  // day
  $search_date->date_start=$sec-(60*60*12);
  $search_date->date_end=$sec+(60*60*12);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "[<span class=\"day\"><a href=\"$url\">d</a></span>";
  // week 
  $search_date->date_start=$sec-(60*60*12*7);
  $search_date->date_end=$sec+(60*60*12*7);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "<span class=\"week\"><a href=\"$url\">w</a></span>";
  // month 
  $search_date->date_start=$sec-(60*60*12*30);
  $search_date->date_end=$sec+(60*60*12*30);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "<span class=\"month\"><a href=\"$url\">m</a></span>]";
  echo "\n    </td>\n  </tr>\n";
}

function print_row_tags()
{
  global $db;
  global $user;

  $id=$this->get_id();
  $sql="SELECT t.name
        FROM $db->tag as t, $db->imagetag as it
        WHERE it.imageid=$id 
          AND it.tagid=t.id
        GROUP BY t.name";
  $result = $db->query($sql);
  $tags=array();
  while($row = mysql_fetch_row($result)) {
    array_push($tags, $row[0]);
  }
  sort($tags);
  $num_tags=count($tags);
  
  echo "  <tr>
    <th>Tags:</th>
    <td id=\"tag-$id\">";  

  for ($i=0; $i<$num_tags; $i++)
  {
    echo "<a href=\"index.php?section=explorer&amp;tags=" . $tags[$i] . "\">" . $tags[$i] . "</a>";
    if ($i<$num_tags-1)
        echo ", ";
  }
  if ($user->can_edit($this))
  {
    $list='';
    for ($i=0; $i<$num_tags; $i++)
    {
      $list.=$tags[$i];
      if ($i<$num_tags-1)
        $list.=" ";
    }
    echo " <span class=\"jsbutton\" onclick=\"add_form_tags('$id','$list')\">[edit]</span>";
  }
  echo "</td>
  </tr>\n";
}

function print_row_location()
{
  global $db;
  global $user;

  $id=$this->get_id();
  $sql="SELECT l.name,l.type
        FROM $db->location as l, $db->imagelocation as il
        WHERE il.imageid=$id 
          AND il.locationid=l.id
        ORDER BY l.type";
  $result = $db->query($sql);
  $location=array();
  
  $city='';
  $sublocation='';
  $state='';
  $country='';

  while($row = mysql_fetch_row($result)) {
    switch($row[1]) {
    case LOCATION_CITY:
      $city=$row[0];
      break;
    case LOCATION_SUBLOCATION:
      $sublocation=$row[0];
      break;
    case LOCATION_STATE:
      $state=$row[0];
      break;
    case LOCATION_COUNTRY:
      $country=$row[0];
      break;
    } 
    array_push($location, array($row[1], $row[0]));
  }
   
  echo "  <tr>
    <th>Location:</th>
    <td id=\"location-$id\">";  

  $num_location=count($location);
  for ($i=0; $i<$num_location; $i++)
  {
    echo "<a href=\"index.php?section=explorer&amp;location=".$location[$i][1] . "\">" . $location[$i][1] . "</a>";
    if ($i<$num_location-1)
        echo ", ";
  }
  if ($user->can_edit($this))
  {
    $list='';
    for ($i=0; $i<$num_location; $i++)
    {
      $list.=$tags[$i];
      if ($i<$num_tags-1)
        $list.=" ";
    }
    echo " <span class=\"jsbutton\" onclick=\"add_form_location('$id','$city','$sublocation', '$state', '$country')\">[edit]</span>";
  }
  echo "</td>
  </tr>\n";
}
function print_preview($search=null) 
{
  global $db;
  global $user;
  
  $id=$this->get_id();
  $name=$this->get_name();
  
  echo "\n<div class=\"name\">$name</div>\n";
  echo "<div class=\"thumb\">&nbsp;";
  
  $link="index.php?section=image&amp;id=$id";
  if ($search!=null)
    $link.=$search->to_URL();
  
  $size=$this->get_size(220);

  echo "<a href=\"$link\"><img src=\"./image.php?id=$id&amp;type=thumb\" alt=\"$name\" title=\"$name\" ".$size[2]."/></a>\n";
  
  $this->print_caption();
  
  echo "</div>\n";  

  echo "<table class=\"imginfo\">\n";
  if ($user->is_owner(&$this))
  {
    $this->print_row_filename();
    $this->print_row_acl();
  }
  $this->print_row_date();
  
  $this->print_row_tags();
  $this->print_row_location();
  if ($user->can_select($id))
  {
    echo "  <tr>
    <th>Select:</th>
    <td><input type=\"checkbox\" name=\"images[]\" value=\"$id\" onclick=\"uncheck('selectall')\" /></td>
  </tr>\n";
  }
  
  echo "</table>\n";
} 
}

?>
