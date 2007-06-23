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
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Search.php");

/**
  @class SectionHome Prints the initial page with tags and popular images.
*/
class SectionHome extends SectionBase
{


function SectionHome()
{
  $this->name="home";
}

/** @param cloud hash of hits where the keys are name
  @param param Search parameter name of the cloud names 
  @todo dont use direct font size style. Use css classes instead */
function _print_cloud($cloud, $param)
{
  global $log;
  if (!count($cloud))
    return;

  $hits=array_values($cloud);
  rsort($hits);
  $max=intval($hits[0]);
  $min=intval($hits[count($hits)-1]);

  $search=new Search();
  $search->add_param('section', 'explorer');

  ksort($cloud);
  $grad=($max==$min)?20:(20/($max-$min));
  //$log->info("min=$min, max=$max, grad=$grad, cloud=".print_r($cloud, true));

  echo "<div class=\"$param\">\n";
  foreach ($cloud as $name => $hit)
  {
    $size=intval(8+($hit-$min)*$grad);
    $search->add_param($param, $name);
    $url=$search->get_url();
    echo "<span style=\"font-size:${size}pt;\"><a href=\"$url\">".$this->escape_html($name)."</a></span>&nbsp;\n";
  }
  echo "</div>\n</p>\n";
  unset($search);
}

/** Prints a cloud of sets
  @param size Cloud size. Default is 50
  @see _print_cloud */
function print_cloud_tag($size=50) 
{
  global $log;
  echo "<h3>"._("Popular tags:")."</h3>\n\n<p>";

  $search=new Search();
  $cloud=$search->get_popular_tags($size);
  if (!count($cloud))
  {
    $this->warning(_("No tags found"));
    return;
  }
  $this->_print_cloud($cloud, 'tags');  
}

/** Prints a cloud of sets
  @param size Cloud size. Default is 50
  @see _print_cloud */
function print_cloud_set($size=50) 
{
  global $log;
  echo "<h3>"._("Popular sets:")."</h3>\n\n<p>";

  $search=new Search();
  $cloud=$search->get_popular_sets($size);
  if (!count($cloud))
  {
    $this->warning(_("No popular sets found"));
    return;
  }
  $this->_print_cloud($cloud, 'sets');  
}

/** Get an randomized subset of images from a specifc order
  @param order Order of the search
  @param num The number of returned ids. Default is 6
  @param limit Consider only the given upper limit. Default is 100
  @return Array of images. The key value is the position
  @see Search::set_orderby */
function _get_ordered_image_ids($order, $num=6, $limit=100)
{
  global $db;
    
  $search=new Search();
  $search->set_orderby($order);
  $search->set_page_size($limit);
  $sql=$search->get_query();

  // fetch all top rated images and remove randomly some
  $ids=$db->query_column($sql);  
  $count=count($ids);
  while (count($ids)>$num)
    unset($ids[rand(0, $count-1)]);

  return $ids;
}

/** Prints a randomized subset of ordered mini images
  @param order Order of the search
  @param num The number of returned ids. Default is 6
  @param limit Consider only the given upper limit. Default is 100
  @see _get_ordered_image_ids
  @todo Remove table and print divs */
function _print_ordered_images($order, $num=6, $limit=100)
{
  $ids=$this->_get_ordered_image_ids($order);
  // Set pagesize to default
  $search=new Search();
  $search->add_param('section', 'image');
  echo "<table width=\"100%\">\n<tr>\n";
  foreach ($ids as $pos => $id)
  {
    echo "  <td>";
    $img=new Image($id);
    if ($img)
    {
      $name=$img->get_name();
      $search->set_pos($pos);
      $search->set_orderby($order);
      $search->add_param('id', $id);
      $url=$search->get_url();

      $iurl=new Url('image.php');
      $iurl->add_param('id', $id);
      $iurl->add_param('type', 'mini');
      echo "<a href=\"$url\"><img src=\"".$iurl->get_url()."\" alt=\"$name\" width=\"75\" height=\"75\"/></a>";
      unset($img);
    }    
    echo "</td>\n";
  }
  echo "</tr>\n</table>\n";
}

/** Prints popular images as small square images 
  @see _print_ordered_images */
function print_images_popular()
{
  echo "<h3>"._("Popular Images:")."</h3>\n\n";

  $this->_print_ordered_images('popularity');  
}

/** Prints new images as small square images 
  @see _print_ordered_images */
function print_images_newest()
{
  echo "<h3>"._("Newest Images:")."</h3>\n\n";

  $this->_print_ordered_images('newest');  
}

/** Prints randomly images as small square images 
  @see _print_ordered_images */
function print_images_random()
{
  echo "<h3>"._("Random Images:")."</h3>\n\n";

  $this->_print_ordered_images('random');  
}

function print_content()
{
  echo "<h2>"._("Home")."</h2>\n";
  $this->print_cloud_tag();
  $this->print_cloud_set();
  $this->print_images_popular();
  $this->print_images_newest();
  $this->print_images_random();
}

}
?>
