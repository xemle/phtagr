<?php

if (file_exists ('config.php'))
  include 'config.php';

if (!isset ($phtagr_prefix))
  $phtagr_prefix='./phtagr';

if (!isset ($phtagr_url_prefix))
{
  $phtagr_url_prefix=substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], DIRECTORY_SEPARATOR));
}

include "$phtagr_prefix/main.php";

?>
