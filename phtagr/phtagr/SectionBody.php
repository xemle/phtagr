<?php

global $prefix;
include_once("$prefix/SectionBase.php");

class SectionBody extends SectionBase
{

function SectionBase()
{
    $this->name="home";
}
    
function print_section()
{
    $this->div_open('body');
    $this->div_open($this->name);
    $this->print_content();
    $this->div_close();
    $this->div_close();
    echo "\n";
}

}
?>
