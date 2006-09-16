<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Sql.php");

class SectionImage extends SectionBase
{

function SectionImage()
{
  $this->name="image";
}

/** print image preview table */
function print_navigation($search)
{
  global $db;
  
  if ($search==null)
    return;
  
  $pos=$search->get_pos();
  $page_size=$search->get_page_size();

  $cur_pos=$pos;
  if ($pos>1)
    $cur_pos-=1;
  else 
    $cur_pos=0;

  $search->set_pos($cur_pos);
  $search->set_page_size($pos-$cur_pos+2);
  $search->set_imageid(null);
  $sql=$search->get_query(2);

  $result=$db->query($sql);
  // we need at least 2 lines.
  if (!$result || mysql_num_rows($result)<2)
    return;

  // restore old page style
  $search->set_page_size($page_size);

  echo "\n<div class=\"navigator\">\n";
  while ($row=mysql_fetch_row($result))
  {
    $search->set_pos($cur_pos);
    // skip current image
    if ($cur_pos==$pos)
    {
      $url="index.php?section=explorer";
      $url.=$search->to_URL();
      echo "<a href=\"$url\">"._("up")."</a>&nbsp;";
      $cur_pos++;
      continue;
    }

    $id=$row[0];
    $search->set_pos($cur_pos);
    
    $url="index.php?section=image&id=$id";
    $url.=$search->to_URL();
    
    if ($cur_pos<$pos)
      echo "<a href=\"$url\">"._("prev")."</a>&nbsp;";
    else
      echo "<a href=\"$url\">"._("next")."</a>";
    
    $cur_pos++;
  }
  echo "</div>\n";
}

function print_content()
{
  global $db;
  global $user;
  global $search;
 
  echo "<h2>"._("Image")."</h2>\n";
  
  if (!isset($_REQUEST['id']))
    return;
 
  $id=$_REQUEST['id'];
  $image=new Image($id);
  
  $name=$image->get_name();
  
  $search_nav=new Search();
  $search_nav->from_URL();
  $this->print_navigation($search_nav);

  echo "<div class=\"name\">$name</div>\n";

  echo "<div align=\"middle\">\n";

  $size=$image->get_size(600);
  echo "<p><img src=\"./image.php?id=$id&amp;type=preview\" alt=\"$name\" ".$size[2]."/></p>\n";

  echo "<form name=\"formImage\" id=\"formImage\" action=\"index.php\" method=\"post\">\n";
  echo "<input type=\"hidden\" name=\"section\" value=\"image\" />\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"edit\" />\n";
  echo "<input type=\"hidden\" name=\"image\" value=\"$id\" />\n";

  echo $search->to_form();

  $image->print_caption(false);
  echo "<p><table class=\"imginfo\">\n";
  
  if ($user->is_owner(&$image)) {
    $image->print_row_filename();
    $image->print_row_acl();
  }

  $image->print_row_date();
  $image->print_row_tags();
  $image->print_row_location();
  $image->print_row_clicks();
  $image->print_row_voting();
  echo "</table></p>\n";

  echo "</form>\n";

  echo "</div>\n";

  if (!isset($_SESSION['img_viewed'][$id]))
    $image->update_ranking();

  $_SESSION['img_viewed'][$id]++;

  $this->print_navigation($search_nav);
}

}

?>
