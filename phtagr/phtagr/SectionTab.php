<?php

include_once("$phtagr_lib/SectionBase.php");

/**
  @class SectionTab lets one organize information in tabbed pages
*/
class SectionTab extends SectionBase
{

var $title;
var $tabs;
var $selected;
var $target;
var $param;

/**
  @param _title The title of the tab
  @param action The base link which will be the target of each tab link.
  @param id The parameter of the request which will contain the id
            of the tab.

The idea behind this class is to make it easy to use tabbed pages.
This is a small example on how to do it:

$tabs=new SectionTab("actions","index.php?section=admin", "tabs");
$tabs->add_tab ("delete all", DELETE_ALL);
$tabs->add_tab ("recover all", RECOVER_ALL);
$tabs->print_content ();

if ($tabs->selected == DELETE_ALL)
  delete_all ();
else if ($tabs->selected == RECOVER_ALL)
  recover_all ();

*/
function SectionTab($_title, $target, $param="TAB")
{
  $this->title = $_title;
  $this->SectionBase("tab");
  $this->tabs=array();
  $this->selected=null;
  $this->target=$target;
  $this->param=$param;

  if (isset($_REQUEST[$param]))
  {
    $this->selected=$_REQUEST[$param];
  }
}

/** Adds a new tab
  @param title The title of the tab
  @para id The id of the tab
*/
function add_tab($title, $id)
{
  if ( (sizeof($this->tabs)==0) && ($this->selected==null))
  {
    $this->selected=$id;
  }

  $this->tabs[$title]=$id;
}

function print_content()
{
  echo "<div class=\"tab\">\n"; 
  echo "<h2>". $this->title .":</h2>\n";
  echo "<ul>\n";
  foreach ($this->tabs as $tab => $id)
  {
    if ($id == $this->selected)
      echo "  <li><a href=\"".$this->target."&".$this->param."=$id\" class=\"current\">$tab</a></li>\n";
    else
      echo "  <li><a href=\"".$this->target."&".$this->param."=$id\">$tab</a></li>\n";
    
  }
  echo "</ul>\n";
  echo "</div>\n";
}

}
?>
