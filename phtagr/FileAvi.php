<?php

include_once("$phtagr_lib/FileJpg.php");
include_once("$phtagr_lib/PreviewVideo.php");
include_once("$phtagr_lib/Iptc.php");

/** @class FileAvi
*/
class FileAvi extends FileJpg
{

function FileAvi($filename)
{
  $this->FileJpg($filename);
  $this->create_thumb();
  $this->set_image_filename($this->get_thumb_filename());
}

function get_thumb_filename()
{
  $filename=$this->get_filename();
  $thumb=substr($filename, 0, strrpos($filename, ".")+1)."thm";
  return $thumb;
}

/** Creates an thumbnail of the movie and return true on success */
function create_thumb()
{
  global $conf, $log;
  $filename=$this->get_filename();
  $thumb=$this->get_thumb_filename();

  @clearstatcache();
  if (file_exists($thumb))
    return true;
  if (!is_writeable(dirname($thumb)))
    return false;

  $cmd=$conf->get('bin.ffmpeg', 'ffmpeg')." -i \"$filename\" -t 0.001 -f mjpeg -y \"$thumb\"";
  $lines=array();
  $result=-1;
  exec($cmd, &$lines, $result);
  $log->debug("Execute [returned $result]: ".$cmd);
  if ($result==0)
    return true;
  return false;
}

function is_writeable()
{
  $filename=$this->get_thumb_filename();
  if (is_writeable($filename) &&
    is_writeable(dirname($filename)))
    return true;
  return false;
}

function import($image)
{
  global $db, $log, $conf;

  parent::import($image);

  $filename=$this->get_filename();

  $cmd=$conf->get('bin.ffmpeg', 'ffmpeg')." -i \"$filename\" -t 0.0 2>&1";
  $lines=array();
  $result=-1;
  exec($cmd, &$lines, $result);
  $log->debug("Execute [returned $result]: ".$cmd);
  foreach ($lines as $line)
  {
    $words=preg_split("/[\s,]+/", trim($line));
    if ($words[0]=="Duration:")
    {
      $times=preg_split("/:/", $words[1]);
      $time=$times[0]*3600+$times[1]*60+intval($times[2]);
      $image->set_duration($time);
      $log->debug("Found duration: $time");
    }
    elseif ($words[2]=="Video:")
    {
      list($width, $height)=split("x", $words[5]);
      $image->set_width($width);
      $image->set_height($height);
      $log->debug("Found size: $width x $height");
    }
  }
  
  $image->set_name(basename($filename));
}

function get_preview_handler($image)
{
  $preview=new PreviewVideo($image);
  return $preview;
}

}
?>
