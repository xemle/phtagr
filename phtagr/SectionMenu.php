<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006,2007 Sebastian Felis, sebastian@phtagr.org
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the 
 * License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/Url.php");

/** @class SectionMenu Class for a listed menu. Each item is link */
class SectionMenu extends SectionBase
{

/** Title of the menu */
var $_title;
/** Base url of the items */
var $_url;
/** Parameter name of the menu items */
var $_item_param;
/** List of menu items */
var $_items;
/** Index of current item */
var $_cur;
/** The current search */
var $_search;

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
  $this->_search=new Search();
  $this->_search->from_url();
}

/** param title Sets the title of the menu 
  @param title String of title
  @note If title is an empty string, the title is not printed */
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
function del_param($name)
{
  return $this->_url->del_param($name);
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
  $search=$this->get_search();
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

/** Add a submenu to an given item 
  @param id ID of the item
  @param submenu Menu object 
    @return True on success, false otherwise */
function add_submenu($id, $submenu)
{
  if (!isset($this->_items[$id]))
    return false;
  if ($submenu==null)
    return false;
  if (get_class($submenu)!="SectionMenu" &&
    !is_subclass_of($submenu, "SectionMenu"))
    return false;

  $this->_items[$id]['_sub']=$submenu;
  return true;
}

/** @return Returns the submenu of an item */
function get_submenu($id)
{
  if (!isset($this->_items[$id])||!isset($this->_items[$id]['_sub']))
    return null;

  return $this->_items[$id]['_sub'];
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

function get_search()
{
  return $this->_search;
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
    $href=$li->get_url();

    echo "  <li><a href=\"$href\"$cur>$name</a>";
    if (isset($item['_sub']))
    {
      echo "\n";
      $item['_sub']->print_content();
    }
    echo "</li>\n";
  }
  echo "</ul>\n";
}

}
?>
