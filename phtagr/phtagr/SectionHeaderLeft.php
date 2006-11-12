<?php

include_once("$phtagr_lib/SectionBase.php");

class SectionHeaderLeft extends SectionBase
{

function SectionHeaderLeft()
{
  $this->SectionBase("headerleft");
}

function print_content()
{
  global $user;
  echo "<h1>phTagr";
  if (!$user->is_anonymous())
    echo ": ".$user->get_name();
  echo "</h1>\n";
}

}
?>
