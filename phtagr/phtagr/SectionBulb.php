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
  global $search;
  global $user;
  if (count($this->tags)==0 && 
    count($this->sets)==0 &&
    count($this->locations)==0)
    return;

  $userid=$search->get_userid();
  $src=$user->get_theme_dir().'/globe.png';
  $img="<img src=\"$src\" border=\"0\" alt=\"@\" title=\""._("Search globaly")."\"/>";
  echo "<h2>"._("Summarize")."</h2>\n";
  $url=new Search();
  $url->add_param('section', 'explorer');
  if (count($this->tags)>0)
  {
    echo "\n<h3>"._("Tags:")."</h3>\n<ul>";
    arsort($this->tags);
    foreach ($this->tags as $tag => $nums) 
    {
      echo "<li>";
      $url->add_param('tags', $tag);
      if ($userid>0) 
      {
        $url->set_userid(0);
        echo "<a href=\"".$url->to_URL()."\">$img</a> ";
        $url->set_userid($userid);
      }
      echo "<a href=\"".$url->to_URL()."\">".htmlentities($tag)."</a>";
      echo " <span class=\"hits\">($nums)</span></li>\n";
    }
    $url->rem_param('tags');
    echo "</ul>\n";
  }

  if (count($this->sets)>0)
  {
    echo "\n<h3>"._("Sets:")."</h3>\n<ul>";
    arsort($this->sets);
    foreach ($this->sets as $set => $nums) 
    {
      echo "<li>";
      $url->add_param('sets', $set);
      if ($userid>0) 
      {
        $url->set_userid(0);
        echo "<a href=\"".$url->to_URL()."\">$img</a> ";
        $url->set_userid($userid);
      }
      echo "<a href=\"".$url->to_URL()."\">".htmlentities($set)."</a>";
      echo " <span class=\"hits\">($nums)</span></li>\n";
    }
    $url->rem_param('sets');
    echo "</ul>\n";
  }

  if (count($this->locations)>0)
  {
    echo "\n<h3>"._("Locations:")."</h3>\n<ul>";
    arsort($this->locations);
    foreach ($this->locations as $loc => $nums) 
    {
      echo "<li>";
      $url->add_param('location', $loc);
      if ($userid>0) 
      {
        $url->set_userid(0);
        echo "<a href=\"".$url->to_URL()."\">$img</a> ";
        $url->set_userid($userid);
      }
      echo "<a href=\"".$url->to_URL()."\">".htmlentities($loc)."</a>";
      echo " <span class=\"hits\">($nums)</span></li>\n";
    }
    echo "</ul>\n";
  }
}

}
?>
