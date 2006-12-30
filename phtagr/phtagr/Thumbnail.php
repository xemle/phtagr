<?php

include_once("$phtagr_lib/Search.php");
include_once("$phtagr_lib/ImageSync.php");

/** Create thumbnails and image previews 
  @class Thumbnail 
*/
class Thumbnail extends ImageSync
{

var $_cmd;
var $_src;

function Thumbnail($id=-1)
{
  $this->ImageSync($id);
  $this->_cmd="";
  $this->_src="";
}

/** Returns the cache path for the image. The images are separated into
 * subdirectories as cache pages to avoid to many files per directory. These
 * cache pages are created on demand. */
function _get_cache_path()
{
  global $phtagr_data;
  $path.=$phtagr_data.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;

  $page=intval($this->get_id() / 1024);
  $path.=$page.DIRECTORY_SEPARATOR;

  if (!file_exists($path) || !is_dir($path))
    @mkdir($path, 0755, true);
  return $path;
}

/** @return Returns the filename of the mini image */
function get_filename_mini()
{
  $file=sprintf("img%d.mini.jpg",$this->get_id());
  return $this->_get_cache_path().$file;
}

/** @return Returns the filename of the thumb image */
function get_filename_thumb()
{
  $file=sprintf("img%d.thumb.jpg",$this->get_id());
  return $this->_get_cache_path().$file;
}

/** @return Returns the filename of the preview */
function get_filename_preview()
{
  $file=sprintf("img%d.preview.jpg",$this->get_id());
  return $this->_get_cache_path().$file;
}

/** @return Returns the filename of the hight solution image */
function get_filename_high()
{
  $file=sprintf("img%d.high.jpg",$this->get_id());
  return $this->_get_cache_path().$file;
}

/** Initate the image operations 
  @param src Filename of the soure image */
function init($src)
{
  $this->_src=$src;
  $this->_cmd="convert";
}

/** Resize the original
  @param width New width
  @param height New height
  @param expand Enlarge a smaller image to the new size if true. Default is
  false */
function resize($width, $height, $expand=false)
{
  if (!$expand)
  {
    $width=$width<=$this->get_width()?$width:$this->get_width();
    $height=$height<=$this->get_height()?$height:$this->get_height();
  }
  $this->_cmd.=" -resize ${width}x$height";
}

/** Crop the image. The crop region must be inside the image. If the region is
 * not inside the image, the values are ajusted.
  @param width Width of the cropped region
  @param height Height of the cropped region
  @param left Left offset. Default is 0.
  @param top Top offset. Default is 0. */
function crop($width, $height, $left=0, $top=0)
{
  $left=$left<0?0:$left;
  $left=$left>=$this->get_width()?$this->get_width()-1:$left;
  
  $top=$top<0?0:$top;
  $top=$top>=$this->get_height()?$this->get_height()-1:$top;
  
  if ($width>$this->get_width()-$left)
    $width=$this->get_width()-left;
  if ($height>$this->get_height()-$top)
    $height=$this->get_height()-top;

  $this->_cmd.=" -crop ${width}x${height}+$left+$top";
}

/** Set to quality of the output image 
  @param quality Value between 0 (worset) and 100 (best). Default is 85 */
function set_quality($quality=85)
{
  if ($quality<0 || $quality > 100)
    $quality=85;
    
  $this->_cmd.=" -quality $quality";
}

/** Save the modified image to $dst
  @param dst Filename of the modified image
  @return false Returns false on error */
function save_to($dst)
{
  if ($this->get_id()<=0)
    return false;

  $this->_cmd.=" \"".$this->_src."\" \"$dst\"";
  system ($this->_cmd, $retval);
  if ($retval!=0)
  {
    $this->error(sprintf(_("Could not execute command '%s'. Exit with code %d"), $this->_cmd, $retval));
    return false;
  }

  @chmod($dst, 0644);
  return true;
}

/** Create a mini square image with size of 75x75 pixels. 
  @param inherit If true, create the preview image from the preview image.
  Default is false.
  @return False on error
*/
function create_mini($inherit=false) 
{
  // Get the mini filename
  $mini=$this->get_filename_mini();

  $height=$this->get_height();
  $width=$this->get_width();
  
  if ($height<=0 || $width<=0)
    return false;
  
  if (! file_exists($mini) || 
    filectime($mini) < $this->get_synced(true)) 
  {
    if ($width<$height) {
      $w=105;
      $h=intval(95*$height/$width);
      $l=10;
      $t=intval(($h-75)/2);
    } else {
      $w=intval(95*$width/$height);
      $h=105;
      $l=intval(($w-75)/2);
      $t=10;
    }
    if (!$inherit)
    {
      $this->init($this->get_filename());
    } else { 
      $this->create_thumb($inherit);
      $this->init($this->get_filename_thumb());
    }
    $this->resize($w, $h);
    $this->crop(75, 75, $l, $t);
    $this->set_quality(85);
    return $this->save_to($mini);
  }
  return true;
}

/** Create a thumbnail image 
  @param inherit If true, create the preview image from the preview image.
  Default is false.
  @return False on error */
function create_thumb($inherit=false) 
{
  // Get the thumbnail filename
  $thumb=$this->get_filename_thumb();

  if (! file_exists($thumb) || 
    filectime($thumb) < $this->get_synced(true)) 
  {
    if (!$inherit)
    {
      $this->init($this->get_filename());
    } else { 
      $this->create_preview($inherit);
      $this->init($this->get_filename_preview());
    }
    $this->resize(220, 220);
    $this->set_quality(85);
    return $this->save_to($thumb);
  }
  return true;
}

/** Create a preview image 
  @param inherit If true, create the preview image from the high solution
  image. Default is false.
  @return False on error */
function create_preview($inherit=false) 
{
  // Get the preview filename
  $preview=$this->get_filename_preview();

  if (! file_exists($preview) || 
    filectime($preview) < $this->get_synced(true)) 
  {
    if (!$inherit)
    {
      $this->init($this->get_filename());
    } else { 
      $this->create_high();
      $this->init($this->get_filename_high());
    }
    $this->resize(600, 600);
    $this->set_quality(90);
    return $this->save_to($preview);
  }
  return true;
}

/** Create a high solution image 
  @return False on error */
function create_high() 
{
  // Get the high filename
  $high=$this->get_filename_high();

  if (! file_exists($high) || 
    filectime($high) < $this->get_synced(true)) 
  {
    $this->init($this->get_filename());
    $this->resize(1024, 1024);
    $this->set_quality(90);
    return $this->save_to($high);
  }
  return true;
}

/** @return Returns an array with all preview filenames */
function _get_filenames()
{
  $files=array();
  array_push($files, $this->get_filename_mini());
  array_push($files, $this->get_filename_thumb());
  array_push($files, $this->get_filename_preview());
  array_push($files, $this->get_filename_high());
  return $files;
}

/** Renews all timestamps of the previews. This function is usefull, if meta
 * data changes but not the image itself */
function touch_previews()
{
  if (!function_exists("touch"))
    return;

  $files=$this->_get_filenames();
  foreach ($files as $file)
  {
    if (file_exists($file))
      @touch($file);
  }
}

/** Create all previe images */
function create_previews()
{
  $this->create_mini(true);
}

/** Create all preview images 
  @param userid Optional user ID. If set, only previews of this user are
  created. Otherwise all previews of all users are created. This requires admin
  rights. */
function create_all_previews($userid=-1)
{
  global $db;
  global $user;

  $sql="SELECT id
        FROM $db->image";
  if ($userid>0)
  {
    if ($userid!=$user->get_id() && !$user->is_admin())
      return ERR_NOT_PERMITTED;
    $sql.=" AND userid=$userid";
  } else {
    if (!$user->is_admin())
      return ERR_NOT_PERMITTED;
  }
  $result=$db->query($sql);
  if (!$result)
    return;
    
  while ($row=mysql_fetch_row($result))
  {
    $id=$row[0];
    $count++;
    
    $img=new Thumbnail($id);
    $img->create_previews();
    unset($img);
  }
  return $count;
}

/** Delete all previes of the image */
function delete_previews()
{
  $files=$this->_get_filenames();
  foreach ($files as $file)
  {
    if (file_exists($file))
      unlink($file);
  }
}

/** Delete all user data 
  @param userid ID of the specific user
  @param id Image ID, if only one image should be delted. 
  @return 0 on success, global error code otherwise */
function delete_from_user($userid, $id=0)
{
  global $db;
  global $user;

  if (!is_numeric($userid) || $userid<1)
    return ERR_PARAM;
  if ($userid!=$user->get_id() && !$user->is_admin())
    return ERR_NOT_PERMITTED;

  $sql="SELECT id
        FROM $db->image
        WHERE userid=$userid";
  if ($id>0) $sql.=" AND id=$id";
  $result=$db->query($sql);
  if (!$result)
    return;
  while ($row=mysql_fetch_row($result))
  {
    $img_id=$row[0];
    $thumb=new Thumbnail($img_id);
    $thumb->delete_previews();
    unset($thumb);
  }

  return parent::delete_from_user($userid, $id);
}

}

?>
