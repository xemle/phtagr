<?php

include_once("$phtagr_lib/Base.php");

class SectionBase extends Base
{

/** Name of this section. It is the class type of the surrounding HTML div block */
var $name;
/** Cascaded sections. They will be printed befor the content */
var $sections;
/** Content string */
var $content;

function SectionBase($name='default')
{
  $this->name=$name;
  $this->sections=array();
  $this->content='';
}
    
/** Add a cascated section. 
  @note If you are using PHP 4, objects should be passed by references which is
  done by an ampersand 
  @see print_sections() */
function add_section($section) 
{
  array_push($this->sections, &$section);
}

/** Add paragraph section */
function p($text)
{
  $this->content .= "<p>".$text."</p>\n";
}

/** Print header
  @param title Header title
  @param order Order of the headline. Default is 2 */ 
function h($title, $order=2)
{
  if ($order<1) $order=1;
  if ($order>4) $order=4;
  $this->content .= "<h$order>$title</h$order>\n\n";
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
  $this->div_open($this->name);
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
  $this->comment("end of $this->name");
  echo "\n";
}

}
?>
