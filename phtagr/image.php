<?php

if (file_exists ('config.php'))
  include 'config.php';

if (!isset ($phtagr_prefix))
  $phtagr_prefix='./phtagr';

include "$phtagr_prefix/imagerequest.php";

?> 
