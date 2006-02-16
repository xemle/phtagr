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
  echo "<p></div>";
}

function print_content()
{
    echo "<h2>Home</h2>\n";
    $this->print_all_tags();
}

}
?>
