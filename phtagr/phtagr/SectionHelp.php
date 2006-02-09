<?php

global $prefix;
include_once("$prefix/SectionHome.php");

class SectionHelp extends SectionHome
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
