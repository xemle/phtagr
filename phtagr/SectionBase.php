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

include_once("$phtagr_lib/Base.php");

class SectionBase extends Base
{

/** The class type of the surrounding HTML div block */
var $_class;
/** Cascaded sections. They will be printed befor the content */
var $sections;
/** Content string */
var $content;

/** @param class DIV class of the section */
function SectionBase($class='default')
{
  $this->_class=$class;
  $this->sections=array();
  $this->content='';
}
    
function set_class($class)
{
  $this->_class=$class;
}
/** Add a cascated section. 
  @note If you are using PHP 4, objects should be passed by references which is
  done by an ampersand 
  @see print_sections() */
function add_section($section) 
{
  array_push($this->sections, &$section);
}


/** This function should be overwritten on complex outputs 
  @see print_sections */
function print_content()
{
  echo $this->content;
}

/** Print the cascades sections and the sections. The output is surrounded by HTML div
  section in the class of section's name */
function print_sections()
{
  $this->div_open($this->_class);
  if (count($this->sections))
  { 
    foreach ($this->sections as $sub)
    {
      if (!isset($sub)) continue;
      $sub->print_sections();
    }
  }
  $this->print_content();
  $this->div_close(true);
  $this->comment("end of ".$this->_class);
  echo "\n";
}

function _get_attr($attr)
{
  if ($attr==null || count($attr)==0)
    return "";
  $output="";
  foreach ($attr as $key => $value)
    $output.=" $key=\"".$this->escape_html($value)."\"";
  return $output;
}

/** Returns the output or prints it
  @param output String to print or return
  @param return The output string will be returned if true, otherwise the
output will be printed via echo */
function output($output, $return=false)
{
  if ($return)
    return $output;
  else
    echo $output;
}

/** Print header
  @param title Header title
  @param order Order of the headline. Default is 2 */ 
function h1($title, $return=false)
{
  $output="<h1>".$this->escape_html($title)."</h1>";
  return $this->output($output, $return);
}

/** Add paragraph section 
  @param text Text of the paragraph. The will be not escaped
  @param attr Attributes of paragraph node 
  @param return If true, returns the string, otherwise it will print the output
  directly */
function p($text, $attr=null, $return=false)
{
  $output="<p".$this->_get_attr($attr).">".$text."</p>\n";
  return $this->output($output, $return);
}

function label($text, $return=false)
{
  $output="<label>".$this->escape_html($text)."</label>\n";
  return $this->output($output, $return);
}

function input_hidden($name, $value, $return=false)
{
  $output="<input type=\"hidden\"".
          " name=\"".$this->escape_html($name)."\"".
          " value=\"".$this->escape_html($value)."\"/>\n";
  return $this->output($output, $return);
}

function input_text($name, $value="", $size=0, $max_len=0, $return=false)
{
  $size=intval($size);
  $max_len=intval($max_len);

  $output="<input type=\"text\"".
          " name=\"".$this->escape_html($name)."\"";
  $output.=($size>0)?" size=\"$size\"":"";
  $output.=($max_len>0)?" maxlength=\"$max_len\"":"";

  if ($value!="")
    $output.=" value=\"".$this->escape_html($value)."\"";
  $output.="/>";
  return $this->output($output, $return);
}

function input_password($name, $return=false)
{
  $output="<input type=\"password\"".
          " name=\"".$this->escape_html($name)."\"/>\n";
  return $this->output($output, $return);
}

function input_checkbox($name, $value, $is_checked=false, $return=false)
{
  $output="<input type=\"checkbox\" ";
  $output.="name=\"".$this->escape_html($name)."\"";
  $output.="value=\"".$this->escape_html($value)."\"";
  $output.=($is_checked)?" checked=\"checked\"":"";
  $output.="/>\n";
  return $this->output($output, $return);
}

function input_radio($name, $value, $is_checked=false, $return=false)
{
  $output="<input type=\"radio\" ";
  $output.="name=\"".$this->escape_html($name)."\"";
  $output.="value=\"".$this->escape_html($value)."\"";
  $output.=($is_checked)?" checked=\"checked\"":"";
  $output.="/>\n";
  return $this->output($output, $return);
}

function input_submit($value, $return=false)
{
  $output="<input type=\"submit\" ";
  $output.="value=\"".$this->escape_html($value)."\"/>\n";
  return $this->output($output, $return);
}

function input_reset($value, $return=false)
{
  $output="<input type=\"reset\" ";
  $output.="value=\"".$this->escape_html($value)."\"/>\n";
  return $this->output($output, $return);
}

function textarea($name, $cols=0, $rows=0, $text="", $return=false)
{
  $cols=intval($cols);
  $rows=intval($rows);
  $output="<textarea".
          " name=\"".$this->escape_html($name)."\"";
  $output.=($cols>0)?" cols=\"$cols\"":"";
  $output.=($rows>0)?" rows=\"$rows\"".">":"";

  $output.=$this->escape_html($text);
  $output.="</textarea>\n";
  return $this->output($output, $return);
}

function option($name, $value, $is_selected=false, $return=false)
{
  $output="<option ";
  $output.="value=\"".$this->escape_html($value)."\"";
  $output.=($is_selected?" selected=\"selected\"":"");
  $output.=">";
  $output.=$this->escape_html($name);
  $output.="</option>\n";
  return $this->output($output, $return);
}

function fieldset_collapsable($legend, $id=null, $collapsable=false, $collapsed=false, $return=false)
{
  $output="<fieldset";
  $output.=($id!=null)?" id=\"fs-$id\"":"";
  if ($collapsable)
  {
    if ($collapsed)
      $output.=" class=\"formularCollapsed\"";
    else
      $output.=" class=\"formular\"";
  }
  $output.=">";
  $output.="<legend>$legend";
  if ($collapsable && $id!=null)
  {
    if ($collapsed)
    {
      $output.="<a id=\"btn-collapse-$id\" href=\"javascript:void()\" onclick=\"collapseFormular('$id');\" style=\"display: none\">&lt;</a>";
      $output.="<a id=\"btn-expand-$id\" href=\"javascript:void()\" onclick=\"expandFormular('$id');\">&gt;</a>";
    }
    else
    {
      $output.="<a id=\"btn-collapse-$id\" href=\"javascript:void()\" onclick=\"collapseFormular('$id');\">&lt;</a>";
      $output.="<a id=\"btn-expand-$id\" href=\"javascript:void()\" onclick=\"expandFormular('$id');\" style=\"display: none\">&gt;</a>";
    }
  }
  $output.="<ol";
  $output.=($id!=null)?" id=\"inputs-$id\">":">";
  return $this->output($output, $return);
}

}
?>
