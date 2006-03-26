<?php

include_once("$prefix/Base.php");

class PageBase extends Base
{

var $title;
var $sections;

function PageBase()
{
  $this->title="phTagr";
  $this->sections=array();
}

function add_section($section)
{
  array_push($this->sections, $section);
}

function print_header_html()
{
  echo "<html>\n";
  echo "<head>\n";
  echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"themes/default/style.css\"/>\n";
  echo "<title>$this->title</title>\n";
  echo "<script language=\"JavaScript\" src=\"js/forms.js\" type=\"text/javascript\"></script>\n";
  echo "</head>\n\n";
}

function print_footer_html()
{
  echo "</html>";
}

function layout()
{
  $this->print_header_html();
  foreach ($this->sections as $section)
  {
    $section->print_sections();
  }
  $this->print_footer_html();
}

}
?>
