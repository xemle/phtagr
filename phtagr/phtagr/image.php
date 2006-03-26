<?php

include_once("$prefix/Search.php");

/** Create a mini square image with size of 75x75 pixels. 

  @param id image id
  @param userid id of the user
  @param synchornized time of image data in UNIX time 
  @return URL of the square image. false on an error.
*/
function create_mini($id,$userid,$filename,$synced,$width,$height) {
  global $pref;
  // Get the mini filename
  $thumb="$userid-$id.mini.jpg";
  $file="${pref['cache']}/$thumb";
 
  if ($height<=0 || $width<=0)
    return false;
  
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
    {
      echo "<div id=\"error\">Could not execute command '$cmd'. Exit with code $retval</div>\n";
      return false;
    }

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

/** Gets the URL of the mini preview 
  @param id ID of the image
  @return URL string. On error an false */
function get_mini_URL($id)
{
  global $db;
  
  $sql="SELECT id,userid,filename,UNIX_TIMESTAMP(synced),name,UNIX_TIMESTAMP(date),caption,width,height
        FROM $db->image 
        WHERE id=$id";
  $result = $db->query($sql);
  if (!$result)
    return false;
  
  $v = mysql_fetch_array($result, MYSQL_ASSOC);
  $thumb=create_mini($v['id'], $v['userid'], $v['filename'], $v['synced'],$v['width'],$v['height']);
  
  return $thumb;
}

/** print a link to the image */
function print_mini($id) 
{
  $src=get_mini_URL($id);
  if (!$src)
    return;
  echo "<a href=\"index.php?section=image&id=$id\"><img src=\"$src\" alt=\"${v['name']}\" align=\"center\"/></a>";

}

/** Cut the caption by words. If the length of the caption is longer than 20
 * characters, the caption will be cutted into words and reconcartenated to the
 * length of 20.  */
function _cut_caption($id, $caption)
{
  if (strlen($caption)< 60) 
    return $caption;

  $words=split(" ", $caption);
  $result="<span id=\"$id-caption-text\">";
  foreach ($words as $word)
  {
    if (strlen($result) > 40)
      break;

    $result.=" $word";
  }
  $b64=base64_encode($caption);
  $result.=" <span class=\"js-button\" onclick=\"print_caption('$id', '$b64')\">[...]</span>";
  $result.="</span>";
  return $result;
}

/** Print the caption of an image. 
  @param id ID of current image
  @param caption String of the caption
  @param docut True if a long caption will be shorted. False if the whole
  caption will be printed. Default true */
function print_caption($id, $caption, $docut=true)
{
  global $user;
  $can_edit=$user->can_edit($id);
  
  echo "<div class=\"caption\" id=\"$id-caption\">";
  // the user can not edit the image
  if (!$can_edit)
  {
    if ($caption!="")
      echo _cut_caption($id, &$caption);

    echo "</div>\n";
    return;
  }
  
  // The user can edit the image
  if ($caption != "") 
  {
    if ($docut=true)
      $text=_cut_caption($id, &$caption);
    else
      $text=&$caption;
    echo "$text <span class=\"js-button\" onclick=\"add_form_caption('$id', '$caption') \">[edit]</span>";
  }
  else
  {
    echo " <span onclick=\"add_form_caption('$id', '')\">Click here to add a caption</span>";
  }
  
  echo "</div>\n";
}

function print_row_date($sec)
{
  echo "  <tr><th>Date:</th><td>";
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
}

function print_row_tags($id)
{
  global $db;
  global $user;

  $sql="SELECT name FROM $db->tag WHERE imageid=$id";
  $result = $db->query($sql);
  $tags=array();
  while($row = mysql_fetch_row($result)) {
    array_push($tags, $row[0]);
  }
  sort($tags);
  $num_tags=count($tags);
  
  echo "  <tr><th>Tags:</th><td id=\"$id-tag\">";  

  for ($i=0; $i<$num_tags; $i++)
  {
    echo "<a href=\"?section=explorer&tags=" . $tags[$i] . "\">" . $tags[$i] . "</a>";
    if ($i<$num_tags-1)
        echo ", ";
  }
  if ($user->can_edit($id))
  {
    $list='';
    for ($i=0; $i<$num_tags; $i++)
    {
      $list.=$tags[$i];
      if ($i<$num_tags-1)
        $list.=" ";
    }
    echo " <span class=\"js-button\" onclick=\"add_form_tags('$id','$list')\">[edit]</span>";
  }
  echo "</td></tr>\n";
}

function print_preview($id, $search=null) {
  global $db;
  global $user;
  
  $sql="SELECT userid,filename,UNIX_TIMESTAMP(synced),name,UNIX_TIMESTAMP(date),caption
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
  echo "<div class=\"thumb\">&nbsp;";
  
  $link="index.php?section=image&id=$id";
  if ($search!=null)
    $link.=$search->to_URL();
  echo "<a href=\"$link\"><img src=\"$thumb\" alt=\"$name\" align=\"center\"/></a>";
  
  print_caption($id, $caption);
  
  echo "</div>\n";  

  echo "<table class=\"imginfo\">\n";
  //echo "  <tr><th>File:</th><td>$filename</td></tr>\n";
  print_row_date($sec);
  
  print_row_tags($id);
  if ($user->can_select($id))
  {
    echo "<tr><th>Select:</th><td><input type=\"checkbox\" name=\"images[]\" value=\"$id\" /></td></tr>\n";
  }
  
  echo "</table>";
} 

?>
