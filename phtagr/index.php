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
}

include "$phtagr_lib/main.php";
?>
