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
    echo "<h1>phTagr</h2>\n";
}

}
?>
