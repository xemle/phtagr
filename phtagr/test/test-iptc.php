#!/usr/bin/php
<?php
/*
 Thanks to Christian Tratz, who has written a nice IPTC howto on
 http://www.codeproject.com/bitmap/iptc.asp
*/
$phtagr_prefix='../phtagr';
include_once("$phtagr_prefix/Iptc.php");

$filename=$argv[1];

$img=new Iptc();
echo "Load Image...\n";
$img->load_from_file($filename);
if ($img->get_errno()!=0)
{
  echo $img->error."\n";
} else {
  echo "Reinsert keyword record...\n";
  $img->rem_record('2:025', "Keyword");
  $img->add_record('2:025', "Keyword");
  echo "Save changes...\n";
  $img->save_to_file(false);
  echo "Done.\n";
}
print_r($img);

exit;
?>
