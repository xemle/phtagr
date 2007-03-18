<?php

include_once("$phtagr_lib/SectionBase.php");

class SectionHelp extends SectionBase
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
