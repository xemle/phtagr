<?php

if (file_exists ('config.php'))
  include 'config.php';

if (!isset ($phtagr_prefix))
  $phtagr_prefix='.';

$phtagr_lib=$phtagr_prefix.DIRECTORY_SEPARATOR.'phtagr';

include "$phtagr_lib/imagerequest.php";

?> 
