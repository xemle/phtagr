<?php

global $prefix;
include_once("$prefix/SectionBody.php");

class SectionOptions extends SectionBody
{

function SectionOptions()
{
  $this->name="options";
}

function print_options($dir)
{
  global $sql;
  echo "Here you can change options";
  echo "<br>";
  echo "<br>";
  echo "Upload directory: ".$sql->upload_dir;
}

function print_content()
{
  echo "<h2>Options</h2>\n";
  $this->print_options($this->path);
}

}

?>
