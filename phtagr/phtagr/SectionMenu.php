<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/Url.php");

/** @class SectionMenu Class for a listed menu. Each item is link */
class SectionMenu extends SectionBase
{

var $_title;
var $_url;
var $_item_param;
var $_items;
var $_cur;

/** @param class DIV class of the menu
  @param title Title of the menu (Header level 2) */
function SectionMenu($class='menu', $title='menu')
{
  $this->SectionBase($class);
  $this->_title=$title;
  $this->_url=new Url();
  $this->_item_param='';
  $this->_items=array();
  $this->_cur=null;
}

/** param title Sets the title of the menu */
function set_title($title)
{
  $this->_title=$title;
}

/** Add a general parameter to the base link
  @param name Name of the parameter
  @param value Value of the parameter 
  @return True on success, false otherwise */
function add_param($name, $value)
{
  return $this->_url->add_param($name, $value);
}

/** @param name Removes a parameter of name */
function rem_param($name)
{
  return $this->_url->rem_param($name);
}

/** 
  @param name Set parameter of the items */
function set_item_param($name)
{
  $this->_item_param=$name;
}

/** Add an item to the menu. 
  @param id ID of the item. This will be used as parameter values to
  distinguish the items.
  @param name Link name of the item
  @param iscurrent Set this to true if the item is current one. Default is
  false. The current item is also evaluated through the given URL and the
  item_parameter. If the $id maches the item_param, this value is also true*/
function add_item($id, $name, $iscurrent=false)
{
  global $search;
  $cur=$search->get_param($this->_item_param, '');
  $this->_items[$id]['_name']=$name;
  if ($iscurrent || $id===$cur) 
    $this->_cur=$id;
}

/** Add a parameter for an item 
  @param id ID of the parameter
  @param param Name of the additional parameter. Prefix of underscore '_' is
  not allowed
  @param value Value of the additional parameter 
  @return True on success */
function add_item_param($id, $param, $value)
{
  if (!isset($this->_items[$id]))
    return false;
  if ($param{0}=='_' || $value=='')
    return false;
  $this->_items[$id][$param]=$value;
  return true;
}

/** @return Returns the current ID of the menu. Null if none is set or found */
function get_current()
{
  if ($this->_cur!=null)
    return $this->_cur;
}

/** Set the current item 
  @param id ID of the item 
  @return True on success, false otherwise */
function set_current($id)
{
  if (isset($this->_items[$id]))
  {
    $this->_cur=$id;
    return true;
  }
  return false;
}

function print_content()
{
  if ($this->_title!='')
    echo "<h2>".$this->_title."</h2>\n";

  echo "<ul>\n";
  foreach ($this->_items as $id => $item)
  {
    $name=$item['_name'];
    $cur='';
    if ($this->_cur==$id)
      $cur=" class=\"current\"";

    $li=clone $this->_url;
    $li->add_param($this->_item_param, $id);
    foreach ($item as $p => $v)
    {
      if ($p{0}!='_')
        $li->add_param($p, $v);
    }
    $href=$li->to_URL();

    echo "  <li><a href=\"$href\"$cur>$name</a></li>\n";
  }
  echo "</ul>\n";
}

}
?>
