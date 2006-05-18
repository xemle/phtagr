<?php

include_once("$prefix/SectionBase.php");

class PageBase extends SectionBase
{

var $title;

function PageBase($title='phtagr')
{
  $this->SectionBase($title);

  $this->title=$title;
}

function print_header_html()
{
  $theme="default";
  
  echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"
  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en\" xml:lang=\"en\">\n\n";

  echo "<head>\n";
  echo "<title>$this->title</title>\n";
  echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"themes/$theme/style.css\"/>\n";
  if (file_exists("themes/$theme/favicon.ico"))
  {
    echo "<link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"themes/$theme/favicon.ico\" />\n"; 
  }
  echo "<script src=\"js/forms.js\" type=\"text/javascript\"></script>\n";
  echo "</head>\n\n";
}

function print_footer_html()
{
  echo "</html>\n";
}

function layout()
{
  $this->print_header_html();
  echo "<body>\n";
  $this->print_sections();
  echo "</body>\n";
  $this->print_footer_html();
}

}
?>
