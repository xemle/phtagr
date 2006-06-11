<?php

global $prefix;
include_once("$prefix/SectionBase.php");

class SectionHeaderLeft extends SectionBase
{

function SectionHeaderLeft()
{
  $this->name="headerleft";
}

function print_content()
{
  global $user;
  echo "<h1>phTagr";
  if ($user->is_member())
    echo ": ".$user->get_username();
  echo "</h1>\n";
}

}
?>
