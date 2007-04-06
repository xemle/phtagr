<?php

include_once("$phtagr_lib/Base.php");
include_once("$phtagr_lib/Filesystem.php");

/** Create Preview base
*/
class PreviewBase extends Base
{

var $_image;

/** @param id Image id */
function PreviewBase($image)
{
  $this->_image=$image;
}

function get_image()
{
  return $this->_image;
}

/** Returns the cache path for the image. The images are separated into
 * subdirectories as cache pages to avoid to many files per directory. These
 * cache pages are created on demand. */
function get_cache_path()
{
  global $phtagr_data;
  $path.=$phtagr_data.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;

  $image=$this->get_image();
  $page=intval($image->get_id() / 1000);
  $path.=sprintf("%04d", $page).DIRECTORY_SEPARATOR;

  if (!file_exists($path) || !is_dir($path)) {
    $fs=new Filesystem();
    $fs->mkdir($path, true);
  }
  return $path;
}

/** @return Returns the filename of the mini image */
function get_filename_mini()
{
  $id=$this->_image->get_id();

  $file=sprintf("img%07d.mini.jpg",$id);
  return $this->get_cache_path().$file;
}

/** @return Returns the filename of the thumb image */
function get_filename_thumb()
{
  $id=$this->_image->get_id();

  $file=sprintf("img%07d.thumb.jpg",$id);
  return $this->get_cache_path().$file;
}

/** @return Returns the filename of the preview */
function get_filename_preview()
{
  $id=$this->_image->get_id();

  $file=sprintf("img%07d.preview.jpg",$id);
  return $this->get_cache_path().$file;
}

/** @return Returns the filename of the hight solution image */
function get_filename_high()
{
  $id=$this->_image->get_id();

  $file=sprintf("img%07d.high.jpg",$id);
  return $this->get_cache_path().$file;
}

/** Initate the image operations 
  @param src Filename of the soure image */
function init($src)
{
}

/** Resize the original
  @param width New width
  @param height New height
  @param expand Enlarge a smaller image to the new size if true. Default is
  false */
function resize($width, $height, $expand=false)
{
}

/** Crop the image. The crop region must be inside the image. If the region is
 * not inside the image, the values are ajusted.
  @param width Width of the cropped region
  @param height Height of the cropped region
  @param left Left offset. Default is 0.
  @param top Top offset. Default is 0. */
function crop($width, $height, $left=0, $top=0)
{
}

/** Set to quality of the output image 
  @param quality Value between 0 (worset) and 100 (best). Default is 85 */
function set_quality($quality=85)
{
}

/** Save the modified image to $dst
  @param dst Filename of the modified image
  @return false Returns false on error */
function save_to($dst)
{
}

/** Create a mini square image with size of 75x75 pixels. 
  @param inherit If true, create the preview image from the preview image.
  Default is false.
  @return False on error
*/
function create_mini($inherit=false) 
{
  global $user, $log;
  $image=$this->get_image();

  if ($inherit)
    $this->create_thumb($inherit);

  // Get the mini filename
  $mini=$this->get_filename_mini();

  $height=$image->get_height();
  $width=$image->get_width();
  
  if ($height<=0 || $width<=0)
    return false;
  
  if (file_exists($mini) &&
    filemtime($mini) >= $image->get_modified()) 
    return true;

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
    $this->init($image->get_filename());
  } else { 
    $this->init($this->get_filename_thumb());
  }
  $this->resize($w, $h);
  $this->crop(75, 75, $l, $t);
  $this->set_quality(85);
  $log->warn("Creating image mini", $image->get_id(), $user->get_id());
  return $this->save_to($mini);
}

/** Create a thumbnail image 
  @param inherit If true, create the preview image from the preview image.
  Default is false.
  @return False on error */
function create_thumb($inherit=false) 
{
  global $user, $log;
  $image=$this->get_image();

  if ($inherit)
    $this->create_preview($inherit);

  // Get the thumbnail filename
  $thumb=$this->get_filename_thumb();

  if (file_exists($thumb) && 
    filemtime($thumb) >= $image->get_modified()) 
    return true;

  if (!$inherit)
  {
    $this->init($image->get_filename());
  } else { 
    $this->init($this->get_filename_preview());
  }
  $this->resize(220, 220);
  $this->set_quality(85);
  $log->warn("Creating image thumb", $image->get_id(), $user->get_id());
  return $this->save_to($thumb);
}

/** Create a preview image 
  @param inherit If true, create the preview image from the high solution
  image. Default is false.
  @return False on error */
function create_preview($inherit=false) 
{
  global $user, $log;
  $image=$this->get_image();

  if ($inherit)
    $this->create_high();

  // Get the preview filename
  $preview=$this->get_filename_preview();

  if (file_exists($preview) && 
    filemtime($preview) >= $image->get_modified()) 
    return true;

  if (!$inherit)
  {
    $this->init($image->get_filename());
  } else { 
    $this->init($this->get_filename_high());
  }
  $this->resize(600, 600);
  $this->set_quality(90);
  $log->warn("Creating image preview", $image->get_id(), $user->get_id());
  return $this->save_to($preview);
}

/** Create a high solution image 
  @return False on error */
function create_high() 
{
  global $user, $log;
  $image=$this->get_image();

  // Get the high filename
  $high=$this->get_filename_high();

  if (file_exists($high) && 
    filemtime($high) >= $image->get_modified()) 
    return true;

  $this->init($this->get_filename());
  $this->resize(1024, 1024);
  $this->set_quality(90);
  $log->warn("Creating image high", $image->get_id(), $user->get_id());
  return $this->save_to($high);
}

/** @return Returns an array with all preview filenames */
function get_filenames()
{
  $files=array();
  array_push($files, $this->get_filename_mini());
  array_push($files, $this->get_filename_thumb());
  array_push($files, $this->get_filename_preview());
  array_push($files, $this->get_filename_high());
  return $files;
}

/** Renews all timestamps of the previews. This function is usefull, if meta
 * data changes but not the image itself 
  @return True if touch was successfull. */
function touch_previews()
{
  if (!function_exists("touch"))
    return false;

  $files=$this->get_filenames();
  foreach ($files as $file)
  {
    if (file_exists($file))
      @touch($file);
  }
  return true;
}

/** Create all previe images */
function create_previews()
{
  $this->create_mini(true);
}

/** Delete all previes of the image */
function delete_previews()
{
  global $user, $log;
  $image=$this->get_image();

  $files=$this->get_filenames();
  foreach ($files as $file)
  {
    if (file_exists($file))
      unlink($file);
  }
  $log->warn("Deleting all previews", $image->get_id(), $user->get_id());
}

/** Create all preview images 
  @param userid Optional user ID. If set, only previews of this user are
  created. Otherwise all previews of all users are created. This requires admin
  rights. 
  @todo Move this function out of this class */
function create_all_previews($userid=-1)
{
  global $db, $user;

  $sql="SELECT id
        FROM $db->images";
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
    
    $img=new Image($id);
    if (!$img)
      continue;
    $previewer=$img->get_preview_handler();
    if (!$previewer)
    {
      unset($img);
      continue;
    }
    $previewer->create_previews();
  }
  return $count;
}

/** Delete all user data 
  @param userid ID of the specific user
  @param id Image ID, if only one image should be delted. 
  @return 0 on success, global error code otherwise 
  @note This function does not check the user's authorization */
function delete_from_user($userid, $id=0)
{
  global $db, $user, $log;

  $sql="SELECT id
        FROM $db->images
        WHERE userid=$userid";
  if ($id>0) $sql.=" AND id=$id";
  $result=$db->query($sql);
  if (!$result)
    return -1;
  $log->warn("Deleting all preview of a user", -1, $user->get_id());
  while ($row=mysql_fetch_row($result))
  {
    $img_id=$row[0];
    $img=new Image($img_id);
    if (!$img)
      continue;
    $previewer=$img->get_preview_handler();
    if (!$previewer)
    {
      unset($img);
      continue;
    }
    $previewer->delete();
  }

  return 0;
}

}

?>
