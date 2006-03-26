<?php

global $prefix;
include_once("$prefix/SectionBase.php");

class SectionFooter extends SectionBase
{


function SectionFooter()
{
    $this->name="footer";
}

function print_content()
{
    echo "<p>&copy; 2006 phTagr by Sebastian Felis</p>";
    echo "<p>";
    echo "Optimized for mozilla firefox: <a href=\"http://www.spreadfirefox.com/?q=affiliates&amp;id=0&amp;t=85\"><img alt=\"Get Firefox!\" title=\"Get Firefox!\" src=\"http://sfx-images.mozilla.org/affiliates/Buttons/80x15/firefox_80x15.png\"/></a>";
    echo "</p>";
}

}
?>
