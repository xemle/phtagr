<?php
// index.php for phtagr
//
// This file includes the real content over $phtagr_prefix/main.php

if (file_exists ('config.php'))
  include 'config.php';

$cwd=getcwd();

if (!isset($phtagr_prefix))
  $phtagr_prefix='.';

$phtagr_lib=$phtagr_prefix.DIRECTORY_SEPARATOR.'phtagr';

if (!isset($phtagr_data))
  $phtagr_data=$cwd.DIRECTORY_SEPARATOR."data";

if (!isset($phtagr_htdocs))
{
  $phtagr_htdocs=dirname($_SERVER['PHP_SELF']);
  $len=strlen($phtagr_htdocs);
  if ($phtagr_htdocs{$len-1}=='/')
  {
    $phtagr_htdocs=substr($phtagr_htdocs, 0, $len-1);
  }
}

include "$phtagr_lib/main.php";
?>
