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

include_once("$phtagr_lib/SectionBase.php");

class SectionBulb extends SectionBase
{

var $tags;
var $sets;
var $locations;

function SectionBulb()
{
  $this->SectionBase("bulb");
  $this->_tags=array();
  $this->_sets=array();
  $this->_locs=array();
}

function set_data($tags, $sets, $locations)
{
  $this->_tags=$tags;
  $this->_sets=$sets;
  $this->_locs=$locations;
}

function print_content()
{
  global $user;

  $search=new Search();
  $search->from_url();
  $userid=$search->get_userid();
  $src=$user->get_theme_dir().'/globe.png';
  $img="<img src=\"$src\" border=\"0\" alt=\"@\" title=\""._("Search globaly")."\"/>";
  echo "<h2>"._("Navigator")."</h2>\n";

  // Get current search and reset positions
  $add_url=clone $search;
  $add_url->set_page_num(0);
  $add_url->set_pos(0);

  $url=new Search();
  $url->add_param('section', 'explorer');
  if (count($this->_tags)>0)
  {
    echo "\n<h3>"._("Tags:")."</h3>\n<ul>";
    arsort($this->_tags);
    foreach ($this->_tags as $tag => $nums) 
    {
      echo "<li>";
      $url->add_tag($tag);
      // Add global search
      if ($userid>0) 
      {
        $url->set_userid(0);
        echo "<a href=\"".$url->get_url()."\">$img</a> ";
        $url->set_userid($userid);
      }
      if (!$add_url->has_tag($tag))
      {
        $add_url->add_tag($tag);
        echo "<a href=\"".$add_url->get_url()."\">+</a>/";
        $add_url->del_tag($tag);
        $add_url->add_tag("-".$tag);
        echo "<a href=\"".$add_url->get_url()."\">-</a> ";
        $add_url->del_tag("-".$tag);
      } else {
        echo "+/- ";
      }
      echo "<a href=\"".$url->get_url()."\">".$this->escape_html($tag)."</a>";

      if ($nums>1)
        echo " <span class=\"hits\">($nums)</span>";
      echo "</li>\n";
      $url->del_tag($tag);
    }
    echo "</ul>\n";
  }

  if (count($this->_sets)>0)
  {
    echo "\n<h3>"._("Sets:")."</h3>\n<ul>";
    arsort($this->_sets);
    foreach ($this->_sets as $set => $nums) 
    {
      echo "<li>";
      $url->add_set($set);
      // Add global search
      if ($userid>0) 
      {
        $url->set_userid(0);
        echo "<a href=\"".$url->get_url()."\">$img</a> ";
        $url->set_userid($userid);
      }
      if (!$add_url->has_set($set))
      {
        $add_url->add_set($set);
        echo "<a href=\"".$add_url->get_url()."\">+</a>/";
        $add_url->del_set($set);
        $add_url->add_set("-".$set);
        echo "<a href=\"".$add_url->get_url()."\">-</a> ";
        $add_url->del_set("-".$set);
      } else {
        echo "+/- ";
      }
      echo "<a href=\"".$url->get_url()."\">".$this->escape_html($set)."</a>";
      if ($nums>1)
        echo " <span class=\"hits\">($nums)</span>";
      echo "</li>\n";
      $url->del_set($set);
    }
    echo "</ul>\n";
  }

  if (count($this->_locs)>0)
  {
    echo "\n<h3>"._("Locations:")."</h3>\n<ul>";
    arsort($this->_locs);
    foreach ($this->_locs as $loc => $nums) 
    {
      echo "<li>";
      $url->add_location($loc);
      // Add global search
      if ($userid>0) 
      {
        $url->set_userid(0);
        echo "<a href=\"".$url->get_url()."\">$img</a> ";
        $url->set_userid($userid);
      }
      if (!$add_url->has_location($loc))
      {
        $add_url->add_location($loc);
        echo "<a href=\"".$add_url->get_url()."\">+</a>/";
        $add_url->del_location($loc);
        $add_url->add_location("-".$loc);
        echo "<a href=\"".$add_url->get_url()."\">-</a> ";
        $add_url->del_location("-".$loc);
      } else {
        echo "+/- ";
      }
      echo "<a href=\"".$url->get_url()."\">".$this->escape_html($loc)."</a>";
      if ($nums>1)
        echo " <span class=\"hits\">($nums)</span>";
      echo "</li>\n";
      $url->del_location($loc);
    }
    echo "</ul>\n";
  }

  echo "<h3>"._("Sort by:")."</h3>\n<ul>\n";
  $order=array('date' => _("Date"), 
              '-date' => _("Date desc"),
              'popularity' => _("Popularity"),
              'voting' => _("Voting"),
              'newest' => _("Newest"),
              'changes' => _("Changes"),
              'random' => _("Random"));
  foreach ($order as $key => $text) {
    $url->set_orderby($key);
    $add_url->set_orderby($key);
    echo "  <li>";
    // Add global search
    if ($userid>0) 
    {
      $url->set_userid(0);
      echo "<a href=\"".$url->get_url()."\">$img</a> ";
      $url->set_userid($userid);
    }
    echo "<a href=\"".$add_url->get_url()."\">+</a> ";
    echo "<a href=\"".$url->get_url()."\">$text</a></li>\n";
    $url->del_orderby();
    $add_url->del_orderby();
  }
  echo "</ul>\n";
}

}
?>
