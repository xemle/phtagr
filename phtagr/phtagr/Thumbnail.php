<?php

include_once("$phtagr_prefix/Search.php");
include_once("$phtagr_prefix/Image.php");

/** 
  @class Thumbnail Create thumbnails, image previews
*/
class Thumbnail extends Image
{

var $cmd;
var $src;

function Thumbnail($id)
{
  $this->Image($id);
}

/** @return Returns the filename of the mini image */
function get_filename_mini()
{
  global $pref;
  $file=sprintf("img%d.mini.jpg",$this->get_id());
  return $pref['cache'].DIRECTORY_SEPARATOR.$file;
}

/** @return Returns the filename of the thumb image */
function get_filename_thumb()
{
  global $pref;
  $file=sprintf("img%d.thumb.jpg",$this->get_id());
  return $pref['cache'].DIRECTORY_SEPARATOR.$file;
}

/** @return Returns the filename of the preview */
function get_filename_preview()
{
  global $pref;
  $file=sprintf("img%d.preview.jpg",$this->get_id());
  return $pref['cache'].DIRECTORY_SEPARATOR.$file;
}

/** @return Returns the filename of the hight solution image */
function get_filename_high()
{
  global $pref;
  $file=sprintf("img%d.high.jpg",$this->get_id());
  return $pref['cache'].DIRECTORY_SEPARATOR.$file;
}

/** Initate the image operations 
  @param src Filename of the soure image */
function init($src)
{
  $this->src=$src;
  $this->cmd="convert";
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
  $this->cmd.=" -resize ${width}x$height";
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

  $this->cmd.=" -crop ${width}x${height}+$left+$top";
}

/** Set to quality of the output image 
  @param quality Value between 0 (worset) and 100 (best). Default is 85 */
function setQuality($quality=85)
{
  if ($quality<0 || $quality > 100)
    $quality=85;
    
  $this->cmd.=" -quality $quality";
}

/** Save the modified image to $dst
  @param dst Filename of the modified image
  @return false Returns false on error */
function saveTo($dst)
{
    $this->cmd.=" '$this->src' '$dst'";
    system ($this->cmd, $retval);
    if ($retval!=0)
    {
      $this->error("Could not execute command '".$this->cmd."'. Exit with code $retval");
      return false;
    }

    chmod($dst, 0644);
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
    $this->setQuality(85);
    return $this->saveTo($mini);
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
    $this->setQuality(85);
    return $this->saveTo($thumb);
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
    $this->setQuality(90);
    return $this->saveTo($preview);
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
    $this->setQuality(90);
    return $this->saveTo($high);
  }
  return true;
}

/** Create all previe images */
function create_all_previews()
{
  $this->create_mini(true);
}

}

?>
