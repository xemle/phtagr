#!/usr/bin/php5
<?php
/*
 Thanks to Christian Tratz, who has written a nice IPTC howto on
 http://www.codeproject.com/bitmap/iptc.asp
*/
$phtagr_prefix=dirname(dirname(__file__));
$phtagr_lib=$phtagr_prefix.DIRECTORY_SEPARATOR."phtagr";

include_once("$phtagr_lib/Iptc.php");
include_once("$phtagr_lib/Logger.php");

$log=new Logger(LOG_CONSOLE, LOG_INFO);
$log->enable();

$filename=$argv[1];

function check_jpg_segments($iptc) 
{
  global $log;
  $segs=$iptc->get_jpeg_segments();
  if ($segs==null)
  {
  	$log->warn("No JPEG segments found!");
  	return;
  }
  $seg_prev=null;
  $offset=0;
  foreach ($segs as $i => $seg)
  {
    if ($seg_prev)
      $offest=$seg_prev['pos']+$seg_prev['size']-$seg['pos'];
    $log->warn(sprintf("Segment[%d]: type=%s, pos=%d, size=%d, offset=%d", $i, $seg['type'], $seg['pos'], $seg['size'], $offset));
    if ($offset!=0)
      $log->err("Offset mismatch at segment $i");
    $seg_prev=$seg;
  }
}

function check_ps3_segments($iptc) 
{
  global $log;
  $segs=$iptc->get_ps3_segments();
  if ($segs==null)
  {
  	$log->warn("No JPEG segments found!");
  	return;
  }
  $seg_prev=null;
  $offset=0;
  foreach ($segs as $i => $seg)
  {
    if ($seg_prev)
      $offest=$seg_prev['pos']+$seg_prev['size']-$seg['pos'];
    $log->warn(sprintf("Segment[%d]: type=%s, pos=%d, size=%d, offset=%d", $i, $seg['type'], $seg['pos'], $seg['size'], $offset));
    if ($offset!=0)
      $log->err("Offset mismatch at segment $i");
    $seg_prev=$seg;
  }
}

$iptc_orig=new Iptc();
$log->info("Load Image '$filename'");
$iptc_orig->load_from_file($filename);
if ($iptc_orig->get_errno()!=0)
{
  $log->err("Error ".$iptc_orig->get_errno()." occurs. ".$iptc_orig->get_errmsg());
  $log->disable();
  exit();
}

if ($iptc_orig->has_iptc_bug())
  $log->info("File contains IPTC bug!");
check_jpg_segments($iptc_orig);
check_ps3_segments($iptc_orig);

$log->info("Insert keyword record");
$keyword="phtagr";
$iptc_orig->del_record('2:025', $keyword);
$iptc_orig->add_record('2:025', $keyword);
$log->info("Save changes to temporary file");
$iptc_orig->save_to_file(false);

exit;
?>
