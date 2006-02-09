<?php

global $prefix;
include_once("$prefix/SectionBase.php");

class SectionMenu extends SectionBase
{

var $menu;

function SectionMenu()
{
    $this->name="menu";
}

function add_menu_item($item, $link)
{
    $this->menu[$item]=$link;
}

function print_content()
{
    echo "<h2>Menu</h2>\n";
    echo "<ul>\n";
    foreach ($this->menu as $item => $link)
    {
        echo "\t<li><a href=\"$link\">$item</a></li>\n";
    }
    echo "</ul>\n";
    //echo "<pre>"; print_r($this->menu);echo "</pre>";
}

}
?>
