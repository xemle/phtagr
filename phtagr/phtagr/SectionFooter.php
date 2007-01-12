<?php

include_once("$phtagr_lib/SectionBase.php");

class SectionFooter extends SectionBase
{


function SectionFooter($title="footer")
{
  $this->SectionBase($title);
}

function print_content()
{
  echo "<p>&copy; 2006,2007 phTagr by Sebastian Felis</p>";
  echo "<p>Optimized for Mozilla <a href=\"http://www.mozilla.org\">Firefox</a>.</p>";
}

}
?>
