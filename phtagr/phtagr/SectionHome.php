<?php

global $prefix;
include_once("$prefix/SectionBody.php");

class SectionHome extends SectionBody
{


function SectionHome()
{
    $this->name="home";
}

function print_all_tags() {
  global $db;
  
  /*
  // total number of tags
  $sql="select count(*) from tag group by name";
  $result=$db->query($sql);
  if ($result)
  {
    $row=mysql_fetch_row($result);
    $count=$row[0];
  }
  else
  {
    $count=0;
  }
  echo "<div class=\"tags\">Most tags (of $count):";
  */
  
  echo "<div class=\"tags\"><p>Popular tags:</p>\n\n<p>";
  // best of tags
  $sql="select name,COUNT(name) as hits from $db->tag group by name order by hits desc limit 0,50";
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
  $grad=20/($max-$min);
  foreach ($data as $tag => $hit)
  {
    $size=intval(8+($hit-$min)*$grad);
    echo "<span style=\"font-size:${size}pt;\"><a href=\"?section=explorer&tags=$tag\">$tag</a></span>&nbsp;\n";
  }
  echo "<p></div>\n";
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
  $count=$count<20?20:$count;
    
  // select top 1% of images
  $sql="SELECT id
        FROM $db->image AS i
        ORDER BY ranking DESC
        LIMIT 0,$count";
  $result=$db->query($sql);
  if (!$result)
    return;

  // fetch all top rated images and remove randomly some
  $ids=array();
  while ($row=mysql_fetch_row($result))
    array_push($ids,$row[0]);
  while (count($ids)>8)
    array_splice($ids,rand(0,count($ids)-1),1);
  
  echo "<div class=\"mini\"><p>Popular Images:</p>\n\n";
  echo "<table>\n<tr>\n";
  foreach ($ids as $id)
  {
    echo "  <td>";
    print_mini($id);
    echo "</td>\n";
  }
  echo "</tr>\n</table>\n";
    
  echo "</div>\n";
}

function print_content()
{
  echo "<h2>Home</h2>\n";
  $this->print_all_tags();
  $this->print_popular_images();
}

}
?>
