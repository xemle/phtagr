<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/Search.php");
include_once("$phtagr_lib/Edit.php");
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Sql.php");

/** Explore the images.
  @class SectionExplorer */
class SectionExplorer extends SectionBase
{

function SectionExplorer()
{
  $this->name="explorer";
}

/** Print the page navigation bar. It prints the first, the current and the last pagest. Also a preview and a next page link. 
  @param link Base link for pages
  @param current Index of current page 
  @param count Absolut count of pages*/
function print_navigator($search, $current, $count)
{
  if ($count<2) return;

  echo "<div class=\"navigator\">\nPage:&nbsp;";
  
  if ($current>0)
  {
    $i=$current-1;
    $search->set_page_num($i);
    $url=$search->to_URL();
    echo "<a href=\"$url\">&lt;</a>&nbsp;\n";
  }
    
  for ($i=0; $i < $count; $i++)
  {
    if ($i == $current)
    {
      echo "&nbsp;<span class=\"current\">$i</span>&nbsp;\n";
    }
    else if ($i != $current && 
            ($count <= 10) ||
            ($i < 3 || $i > $count-4 || 
            ($i-$current < 4 && $current-$i<4)))
    {
      $search->set_page_num($i);
      $url=$search->to_URL();
      printf("<a href=\"$url\">%d</a>\n",$i);
    }
    else if ($i == $count-4 || $i == 3) 
      echo "&nbsp;...&nbsp;\n";
  }
  if ($current<$count-1)
  {
    $i=$current+1;
    $search->set_page_num($i);
    $url=$search->to_URL();
    echo "&nbsp;<a href=\"$url\">&gt;</a>\n";
  }
  echo "</div>\n\n";
}

function print_tags($tags, $sets, $locations)
{
  echo "<div style=\"clear: both\">";
  $url=new Url();
  $url->add_param('section', 'explorer');
  echo "Tags: ";
  arsort($tags);
  foreach ($tags as $tag => $nums) 
  {
    $url->add_param('tags', $tag);
    $href=$url->to_URL();
    echo "<a href=\"$href\">$tag ($nums)</a>\n";
  }
  $url->rem_param('tags');

  echo "<br/>\nSets: ";
  arsort($sets);
  foreach ($sets as $set => $nums) 
  {
    $url->add_param('sets', $set);
    $href=$url->to_URL();
    echo "<a href=\"$href\">$set ($nums)</a>\n";
  }
  $url->rem_param('sets');

  echo "<br/>\nLocations: ";
  arsort($locations);
  foreach ($locations as $loc => $nums) 
  {
    $url->add_param('location', $loc);
    $href=$url->to_URL();
    echo "<a href=\"$href\">$loc ($nums)</a>\n";
  }

  echo "</div>\n";
}

/** Merge an array and count the entries
  @param counter Pointer to the merged array
  @param add Array to add */
function _array_count_merge(&$counter, $add)
{
  foreach ($add as $value)
    $counter[$value]=$counter[$value]+1;
}

/** Print the current page with an table */
function print_content()
{
  global $db;
  global $search;
  global $user; 

  $sql=$search->get_num_query();
  // for debugging
  // $this->comment($sql);
  $result = $db->query($sql);
  if (!$result)
  {
    return;
  }
  $row=mysql_fetch_row($result);
  $count=$row[0];
  
  if (count($search->tags)>0)
  {
    echo "<h2>Explore Tag: ";
    foreach ($search->tags as $tag)
      echo "$tag ";
    echo "</h2>\n";
  }
  else
    echo "<h2>"._("Explore Tags")."</h2>\n";

  if ($count==0)
  {
    echo "<p>"._("No images found!")."</p>\n";
    return;
  }
  
  $sql=$search->get_query();
  //$this->comment($sql);
  $result = $db->query($sql);
  if (!$result)
  {
    return;
  }

  // Formular for further actions
  echo "<form id=\"formExplorer\" action=\"index.php\" method=\"post\">\n";
  
  $nav_search=clone $search;
  $nav_current=$nav_search->get_page_num();
  $nav_size=$nav_search->get_page_size();
  $nav_search->set_page_num(0);
  $nav_search->set_pos(0);
  $this->print_navigator($nav_search, $nav_current, ceil($count/$nav_size));
 
  $tags=array();
  $sets=array();
  $locs=array();

  echo "<div class=\"images\">\n";
  $cell=0;
  $pos=$search->get_page_size()*$search->get_page_num();
  while($row = mysql_fetch_row($result)) 
  {
    echo "<div class=\"thumbcell\"><a name=\"img-".$row[0]."\"/>\n";
    $search->set_pos($pos);
    $sec_img=new SectionImage($row[0]);
    $sec_img->print_preview(&$search);
    $img=$sec_img->get_img();
    if ($img)
    {
      $this->_array_count_merge(&$tags, $img->get_tags());
      $this->_array_count_merge(&$sets, $img->get_sets());
      $this->_array_count_merge(&$locs, $img->get_locations());
    }
    echo "</div>\n";
    $cell++;
    if ($cell%2==0)
      echo "<div class=\"row2\" ></div>\n";
    if ($cell%3==0)
      echo "<div class=\"row3\" ></div>\n";
    if ($cell%4==0)
      echo "<div class=\"row4\" ></div>\n";
    $pos++;
  }

  echo "</div>\n\n";

  //$this->print_tags($tags, $sets, $locs);

  $this->print_navigator($nav_search, $nav_current, ceil($count/$nav_size));

  echo "<div class=\"edit\">";
  echo $search->to_form();

  $edit=new Edit();
  $edit->print_bar();
  if ($user->is_member())
    $edit->print_edit_inputs();
  $edit->print_buttons();
  echo "</edit>\n";
  echo "</form>\n";
}

}

?>
