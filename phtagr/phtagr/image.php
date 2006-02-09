<?php

include_once("$prefix/Search.php");

/** Create a thumbnail image 

  @param id image id
  @param userid id of the user
  @param synchornized time of image data in UNIX time 
*/
function create_thumbnail($id,$userid,$filename,$synced) {
  global $db;

  // Get the thumbnail filename
  $thumb="$userid-$id.thumb.jpg";
  $file="$db->cache/$thumb";
  
  if (! file_exists($file) or filectime($file) < $synced) {
    system ("convert -resize 220x220 -quality 80 '$filename' '$file'", $retval);
    system ("chmod 644 '$file'");
  }
  return './cache/' . $thumb;
}

function create_preview($id,$userid,$filename,$synced) {
  global $db;

  // Get the thumbnail filename
  $thumb="$userid-$id.preview.jpg";
  $file="$db->cache/$thumb";
  
  if (! file_exists($file) or filectime($file) < $synced) {
    system ("convert -resize 640x640 -quality 90 '$filename' '$file'", $retval);
    system ("chmod 644 '$file'");
  }
  return './cache/' . $thumb;
}


function print_preview($id) {
  global $db;
  global $auth;
  global $search;
  
  $sql="SELECT userid,filename,synced,name,UNIX_TIMESTAMP(date),caption
    FROM image
    WHERE id=$id";
  $result = $db->query($sql);
  if (!$result) {
    echo "Could not run query: " . mysql_error();
  }
  $row = mysql_fetch_row($result);          
  $userid=$row[0];
  $filename=$row[1];
  $synced=$row[2];
  $name=$row[3];
  $sec=$row[4];
  $caption=$row[5];
  
  $thumb=create_thumbnail($id, $userid, $filename, $synced);
  
  echo "<div class=\"file\">$name</div>\n";
  echo "<div class=\"thumb\">" .
    "<a href=\"index.php?section=image&id=$id\"><img src=\"$thumb\" alt=\"$name\" align=\"center\"/></a>";
  
  if ($caption != "") {
    echo "<div class=\"Description\">$caption</div>\n";
  }
  echo "</div>\n";  

  echo "<table class=\"info\">\n";
  //echo "  <tr><td class=\"th\">File:</td><td>$filename</td></tr>\n";
  echo "  <tr><td class=\"th\">Date:</td><td>";
  $date=date("Y-m-d H:i:s", $sec);
  $search_date=new Search();
  $search_date->date_start=$sec-(60*30*3);
  $search_date->date_end=$sec+(60*30*3);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "<a href=\"$url\">$date</a>\n";

  // day
  $search_date->date_start=$sec-(60*60*12);
  $search_date->date_end=$sec+(60*60*12);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "[<span class=\"day\"><a href=\"$url\">d</a></span>";
  // week 
  $search_date->date_start=$sec-(60*60*12*7);
  $search_date->date_end=$sec+(60*60*12*7);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "<span class=\"week\"><a href=\"$url\">w</a></span>";
  // month 
  $search_date->date_start=$sec-(60*60*12*30);
  $search_date->date_end=$sec+(60*60*12*30);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "<span class=\"month\"><a href=\"$url\">m</a></span>]";
  echo "</td></tr>\n";
  
  
  $sql="SELECT name FROM tag WHERE imageid=$id";
  $result = $db->query($sql);
  $tags=array();
  while($row = mysql_fetch_row($result)) {
    array_push($tags, $row[0]);
  }
  $num_tags=count($tags);
  
  echo "  <tr><td class=\"th\"";
  if ($auth->is_auth)
  {
    $list='';
    for ($i=0; $i<$num_tags; $i++)
    {
      $list.=$tags[$i];
      if ($i<$num_tags-1)
        $list.=" ";
    }
    echo " onclick=\"add_form_tags('$id','$list')\"";
  }
  echo ">Tags:</td><td id=\"$id-tag\">";  
  
  for ($i=0; $i<$num_tags; $i++)
  {
    echo "<a href=\"?section=explorer&tags=" . $tags[$i] . "\">" . $tags[$i] . "</a>";
    if ($i<$num_tags-1)
        echo ", ";
  }
  echo "</td></tr>\n";
  
  echo "</table>";
} 

?>
