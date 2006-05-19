<?php

global $prefix;
include_once("$prefix/SectionBase.php");

class SectionMenu extends SectionBase
{

var $menu;

function SectionMenu()
{
  $this->SectionBase("menu");
  $this->menu=array();
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
    echo "  <li><a href=\"$link\">$item</a></li>\n";
  }
  echo "</ul>\n";
}

}
?>
