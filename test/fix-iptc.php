#!/usr/bin/php5
<?php
$phtagr_prefix=dirname(dirname(__file__));
$phtagr_lib=$phtagr_prefix.DIRECTORY_SEPARATOR."phtagr";

include_once("$phtagr_lib/Iptc.php");
include_once("$phtagr_lib/Logger.php");

$log=new Logger(LOG_CONSOLE, LOG_INFO);
$log->enable();

while ($argv[1])
{
  switch ($argv[1])
  {
    default:
      $filename=$argv[1];
  }
  array_shift($argv);
}

if (!file_exists($filename))
{
  echo __file__." filename\n";
  $log->disable();
  exit;
}

$iptc_orig=new Iptc();
$log->debug("Load Image '$filename'");
$iptc_orig->load_from_file($filename);
if ($iptc_orig->get_errno()!=0)
{
  $log->err("Error ".$iptc_orig->get_errno()." occurs: ".$iptc_orig->get_errmsg());
  $log->disable();
  exit;
}

if (!$iptc_orig->has_iptc_bug())
{
  $log->info("Bug does not affected file $filename");
  $log->disable();
  exit;
}

$log->info("Fix iptc bug in $filename");
// change iptc info to force rewrite!
$iptc_orig->add_record('2:025', "remove iptc bug");
$iptc_orig->del_record('2:025', "remove iptc bug");
$iptc_orig->save_to_file(true);
if ($iptc_orig->get_errno()<0)
{
  $log->err("Error ".$iptc_orig->get_errno()." occurs: ".$iptc_orig->get_errmsg());
  $log->disable();
  exit;
}
$log->disable();

exit;
?>
