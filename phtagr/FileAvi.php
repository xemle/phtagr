<?php

include_once("$phtagr_lib/FileBase.php");
include_once("$phtagr_lib/PreviewVideo.php");
include_once("$phtagr_lib/Iptc.php");

/** @class FileAvi
*/
class FileAvi extends FileBase
{

function FileAvi($filename)
{
  $this->FileBase($filename);
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

function export($image)
{
  if (!$this->is_writeable())
    return;
  return;
}

function get_preview_handler($image)
{
  $preview=new PreviewVideo($image);
  return $preview;
}

}
?>
