<?php

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

function print_all_tags() {
  global $db;
  
  echo "<h3>"._("Popular tags:")."</h3>\n\n<p>";
  // best of tags
  $sql="SELECT t.name,COUNT(t.name) AS hits 
        FROM $db->tag AS t, $db->imagetag AS it 
        WHERE t.id=it.tagid
        GROUP BY t.name 
        ORDER BY hits DESC LIMIT 0,50";
  $result = $db->query($sql);
  $tags=array();
  $hits=array();
  $data=array();
  $max=-1;
  $min=0x7fffffff;
  while($row = mysql_fetch_row($result)) {
    $max=max($max,$row[1]);
    $min=min($min,$row[1]);
    array_push($tags, $row[0]);
    array_push($hits, $row[1]);
    $data[$row[0]]=$row[1];
  }
  array_multisort($tags,SORT_ASC,$hits,SORT_ASC,$data);

  /* What is this? Division thru 0! */
  if ($max == $min)
    $grad=20;
  else
    $grad=20/($max-$min);
  foreach ($data as $tag => $hit)
  {
    $size=intval(8+($hit-$min)*$grad);
    echo "<span style=\"font-size:${size}pt;\"><a href=\"?section=explorer&amp;tags=$tag\">$tag</a></span>&nbsp;\n";
  }
  echo "</p>\n";
}

/** Prints randomly images as small square images 
  Only 8 of 50 top rated images are shown */
function print_popular_images()
{
  global $db;
  
  // get total count of images
  $sql="SELECT COUNT(*)
        FROM $db->image";
  $result=$db->query($sql);
  if (!$result)
    return;
  $row=mysql_fetch_row($result);
  $count=intval($row[0]*0.01);
  $count=$count<50?50:$count;
    
  // select top 1% of images
  $search=new Search();
  $search->set_orderby('ranking');
  $search->set_page_size($count);
  $sql=$search->get_query();

  $result=$db->query($sql);
  if (!$result)
    return;

  // fetch all top rated images and remove randomly some
  $ids=array();
  $n=1;
  while ($row=mysql_fetch_row($result))
  {
    array_push($ids, array($row[0], $n));
    $n++;
  }
  while (count($ids)>6)
    array_splice($ids,rand(0,count($ids)-1),1);

  $search->set_page_size(0);

  echo "<h3>"._("Popular Images:")."</h3>\n\n";
  echo "<table width=\"100%\">\n<tr>\n";
  foreach ($ids as $t)
  {
    $id=$t[0];
    $pos=$t[1];
    echo "  <td>";
    $image=new Image($id);
    if ($image)
    {
      $name=$image->get_name();
      $search->set_pos($pos);
      $url="index.php?section=image&amp;id=$id";
      $url.=$search->to_URL();
      echo "<a href=\"$url\"><img src=\"./image.php?id=$id&amp;type=mini\" alt=\"$name\" width=\"75\" height=\"75\"/></a>";
      unset($image);
    }    
    echo "</td>\n";
  }
  echo "</tr>\n</table>\n";
    
}

function print_content()
{
  echo "<h2>"._("Home")."</h2>\n";
  $this->print_all_tags();
  $this->print_popular_images();
}

}
?>
