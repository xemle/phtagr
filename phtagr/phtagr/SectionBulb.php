<?php

include_once("$phtagr_lib/SectionBase.php");

class SectionBulb extends SectionBase
{

var $tags;
var $sets;
var $locations;

function SectionBulb()
{
  $this->SectionBase("bulb");
  $this->tags=array();
  $this->sets=array();
  $this->locations=array();
}

function set_data($tags, $sets, $locations)
{
  $this->tags=$tags;
  $this->sets=$sets;
  $this->locations=$locations;
}

function print_content()
{
  if (count($this->tags)==0 && 
    count($this->sets)==0 &&
    count($this->locations)==0)
    return;

  echo "<h2>"._("Summarize")."</h2>\n";
  $url=new Url();
  $url->add_param('section', 'explorer');
  if (count($this->tags)>0)
  {
    echo "\n<h3>"._("Tags:")."</h3>\n<p>";
    arsort($this->tags);
    foreach ($this->tags as $tag => $nums) 
    {
      $url->add_param('tags', $tag);
      $href=$url->to_URL();
      echo "<a href=\"$href\">".htmlentities($tag)."</a> ";
    }
    $url->rem_param('tags');
    echo "</p>\n";
  }

  if (count($this->sets)>0)
  {
    echo "\n<h3>"._("Sets:")."</h3>\n<p>";
    arsort($this->sets);
    foreach ($this->sets as $set => $nums) 
    {
      $url->add_param('sets', $set);
      $href=$url->to_URL();
      echo "<a href=\"$href\">".htmlentities($set)."</a> ";
    }
    $url->rem_param('sets');
    echo "</p>\n";
  }

  if (count($this->locations)>0)
  {
    echo "\n<h3>"._("Locations:")."</h3>\n<p>";
    arsort($this->locations);
    foreach ($this->locations as $loc => $nums) 
    {
      $url->add_param('location', $loc);
      $href=$url->to_URL();
      echo "<a href=\"$href\">".htmlentities($loc)."</a> ";
    }
    echo "</p>\n";
  }
}

}
?>
