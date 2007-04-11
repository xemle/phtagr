<?php

include_once("$phtagr_lib/PreviewBase.php");

/** Create previews of an image via the shell command convert 
  @class PreviewImage
*/
class PreviewImage extends PreviewBase
{

var $_cmd;
var $_src;

function PreviewImage($image)
{
  $this->PreviewBase($image);
  $this->_cmd="";
  $this->_src="";
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
  $image=$this->get_image();
  $image_width=$image->get_width();
  $image_height=$image->get_height();

  if (!$expand)
  {
    $width=$width<=$image_width?$width:$image_width;
    $height=$height<=$image_height?$height:$image_height;
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
  $image=$this->get_image();
  $image_width=$image->get_width();
  $image_height=$image->get_height();

  $left=$left<0?0:$left;
  $left=$left>=$image_width?$image_width-1:$left;
  
  $top=$top<0?0:$top;
  $top=$top>=$image_height?$image_height-1:$top;
  
  if ($width>$image_width-$left)
    $width=$image_width-left;
  if ($height>$image_height-$top)
    $height=$image_height-top;

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
  global $log, $user;
  $image=$this->get_image();

  if ($image->get_id()<=0)
    return false;

  $this->_cmd.=" \"".$this->_src."\" \"$dst\"";
  $lines=array();
  $result=-1;
  exec($this->_cmd, &$lines, &$result);
  $log->warn("Execute [returned: $result]: ".$this->_cmd, $image->get_id(), $user->get_id());
  if ($result!=0)
    return false;

  @chmod($dst, 0644);
  return true;
}


}

?>
