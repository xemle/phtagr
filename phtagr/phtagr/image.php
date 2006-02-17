<?php

include_once("$prefix/Search.php");

/** Create a mini square image with size of 75x75 pixels. 

  @param id image id
  @param userid id of the user
  @param synchornized time of image data in UNIX time 
*/
function create_mini($id,$userid,$filename,$synced,$width,$height) {
  global $pref;
  // Get the mini filename
  $thumb="$userid-$id.mini.jpg";
  $file="${pref['cache']}/$thumb";
 
  if ($height<=0 || $width<=0)
    return '';
  
  if (! file_exists($file) or filectime($file) < $synced) {
    
    if ($width<$height) {
      $w=105;
      $h=intval(95*$height/$width);
      $l=10;
      $t=intval(($h-75)/2);
    } else {
      $w=intval(95*$width/$height);
      $h=105;
      $l=intval(($w-75)/2);
      $t=10;
    }
    $cmd="convert -resize ${w}x$h -crop 75x75+${l}+${t} -quality 80 '$filename' '$file'";
    system ($cmd, $retval);
    if ($retval!=0)
      echo "<div id=\"error\">Could not execute command '$cmd'. Exit with code $retval</div>\n";

    system ("chmod 644 '$file'");
  }
  return './cache/' . $thumb;
}

/** Create a thumbnail image 

  @param id image id
  @param userid id of the user
  @param synchornized time of image data in UNIX time 
*/
function create_thumbnail($id,$userid,$filename,$synced) {
  global $pref;
  // Get the thumbnail filename
  $thumb="$userid-$id.thumb.jpg";
  $file="${pref['cache']}/$thumb";
  
  if (! file_exists($file) or filectime($file) < $synced) {
    system ("convert -resize 220x220 -quality 80 '$filename' '$file'", $retval);
    system ("chmod 644 '$file'");
  }
  return './cache/' . $thumb;
}

function create_preview($id,$userid,$filename,$synced) {
  global $pref;

  // Get the thumbnail filename
  $thumb="$userid-$id.preview.jpg";
  $file="${pref['cache']}/$thumb";
  
  if (! file_exists($file) or filectime($file) < $synced) {
    system ("convert -resize 640x640 -quality 90 '$filename' '$file'", $retval);
    system ("chmod 644 '$file'");
  }
  return './cache/' . $thumb;
}

function print_mini($id) {
  global $db;
  
  $sql="SELECT * 
        FROM $db->image 
        WHERE id=$id";
  $result = $db->query($sql);
  if (!$result)
    return;
  
  $v = mysql_fetch_array($result, MYSQL_ASSOC);
  $thumb=create_mini($v['id'], $v['userid'], $v['filename'], $v['synced'],$v['width'],$v['height']);
  
  echo "<a href=\"index.php?section=image&id=$id\"><img src=\"$thumb\" alt=\"${v['name']}\" align=\"center\"/></a>";

}
function print_preview($id) {
  global $db;
  global $auth;
  global $search;
  
  $sql="SELECT userid,filename,synced,name,UNIX_TIMESTAMP(date),caption
    FROM $db->image
    WHERE id=$id";
  $result = $db->query($sql);
  if (!$result)
    return;
  
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

  echo "<table class=\"imginfo\">\n";
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
  
  
  $sql="SELECT name FROM $db->tag WHERE imageid=$id";
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
