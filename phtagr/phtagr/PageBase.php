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
  echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"
  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en\" xml:lang=\"en\">\n\n";

  echo "<head>\n";
  echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"themes/default/style.css\"/>\n";
  echo "<title>$this->title</title>\n";
  echo "<script src=\"js/forms.js\" type=\"text/javascript\"></script>\n";
  echo "</head>\n\n";
}

function print_footer_html()
{
  echo "</html>";
}

function layout()
{
  $this->print_header_html();
  echo "<body>\n";
  foreach ($this->sections as $section)
  {
    $section->print_sections();
  }
  echo "</body>\n";
  $this->print_footer_html();
}

}
?>
