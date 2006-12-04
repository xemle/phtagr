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

  // Get current search and reset positions
  $add_url=clone $search;
  $add_url->set_page_num(0);
  $add_url->set_pos(0);

  $url=new Search();
  $url->add_param('section', 'explorer');
  if (count($this->tags)>0)
  {
    echo "\n<h3>"._("Tags:")."</h3>\n<ul>";
    arsort($this->tags);
    foreach ($this->tags as $tag => $nums) 
    {
      echo "<li>";
      $url->add_tag($tag);
      // Add global search
      if ($userid>0) 
      {
        $url->set_userid(0);
        echo "<a href=\"".$url->to_URL()."\">$img</a> ";
        $url->set_userid($userid);
      }
      if (!$add_url->has_tag($tag))
      {
        $add_url->add_tag($tag);
        echo "<a href=\"".$add_url->to_URL()."\">+</a> ";
        $add_url->del_tag($tag);
      } else {
        echo "+ ";
      }
      echo "<a href=\"".$url->to_URL()."\">".htmlentities($tag)."</a>";
      echo " <span class=\"hits\">($nums)</span></li>\n";
      $url->del_tag($tag);
    }
    echo "</ul>\n";
  }

  if (count($this->sets)>0)
  {
    echo "\n<h3>"._("Sets:")."</h3>\n<ul>";
    arsort($this->sets);
    foreach ($this->sets as $set => $nums) 
    {
      echo "<li>";
      $url->add_set($set);
      // Add global search
      if ($userid>0) 
      {
        $url->set_userid(0);
        echo "<a href=\"".$url->to_URL()."\">$img</a> ";
        $url->set_userid($userid);
      }
      if (!$add_url->has_set($set))
      {
        $add_url->add_set($set);
        echo "<a href=\"".$add_url->to_URL()."\">+</a> ";
        $add_url->del_set($set);
      } else {
        echo "+ ";
      }
      echo "<a href=\"".$url->to_URL()."\">".htmlentities($set)."</a>";
      echo " <span class=\"hits\">($nums)</span></li>\n";
      $url->del_set($set);
    }
    echo "</ul>\n";
  }

  if (count($this->locations)>0)
  {
    echo "\n<h3>"._("Locations:")."</h3>\n<ul>";
    arsort($this->locations);
    foreach ($this->locations as $loc => $nums) 
    {
      echo "<li>";
      $url->set_location($loc);
      if ($userid>0) 
      {
        $url->set_userid(0);
        echo "<a href=\"".$url->to_URL()."\">$img</a> ";
        $url->set_userid($userid);
      }
      if (!$add_url->has_location($loc))
      {
        $loc_old=$add_url->get_location();
        $add_url->set_location($loc);
        echo "<a href=\"".$add_url->to_URL()."\">+</a> ";
        $add_url->set_location($loc_old);
      } else {
        echo "+ ";
      }
      echo "<a href=\"".$url->to_URL()."\">".htmlentities($loc)."</a>";
      echo " <span class=\"hits\">($nums)</span></li>\n";
      $url->del_location($loc);
    }
    echo "</ul>\n";
  }
}

}
?>
