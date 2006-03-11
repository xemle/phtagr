<?php

global $prefix;
include_once("$prefix/SectionBody.php");

class SectionHelp extends SectionBody
{


function SectionHelp()
{
  $this->name="help";
}

function print_content()
{
  echo "<h2>Help</h2>\n";
}

}
?>
