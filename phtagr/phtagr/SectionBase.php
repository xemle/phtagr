<?php

include_once("$prefix/Base.php");

class SectionBase extends Base
{

var $sections;

function SectionBase($name='default')
{
  $this->name=$name;
  $this->sections=array();
}
    
function add_section($section) 
{
  array_push($this->sections, $section);
}

/** Add paragraph section */
function p($text)
{
  echo "<p>".$text."</p>\n";
}

function print_content()
{
  // add dummy text
  echo "&nbsp;\n";
}

function print_sections()
{
  $this->div_open($this->name);
  if (count($this->sections))
  { 
    foreach ($this->sections as $sub)
    {
      $sub->print_sections();
    }
  }
  $this->print_content();
  $this->div_close();
  echo "\n";
}

}
?>
