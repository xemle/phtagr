<?php

global $prefix;
global $db;

include_once("$prefix/SectionBody.php");
include_once("$prefix/image.php");
include_once("$prefix/sync.php");
include_once("$prefix/Sql.php");


class SectionImage extends SectionBody
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
    
  //echo "<pre>"; print_r($search);echo "</pre>";
  
  $pos=$search->get_pos();
  $page_size=$search->get_page_size();

  $cur_pos=$pos;
  if ($pos>3)
    $cur_pos-=4;
  else 
    $cur_pos=0;

  $search->set_pos($cur_pos);
  $search->set_page_size($pos-$cur_pos+5);
  $sql=$search->get_query(2);

  $result=$db->query($sql);
  if (!$result)
    return;

  // restore old page style
  $search->set_page_size($page_size);

  echo "\n<div class=\"navigator\">\n<table><tr>\n";
  while ($row=mysql_fetch_row($result))
  {
    // skip current image
    if ($cur_pos==$pos)
    {
      $cur_pos++;
      continue;
    }

    if ($cur_pos==$pos+1)
      echo "<td><div class=\"mini.next\">&gt;</div></td>\n";

    $search->set_pos($cur_pos);
    
    $thumb=get_mini_URL($row[0]);
    $url="index.php?section=image&id=$row[0]";
    $url.=$search->to_URL();
    
    print "  <td><div class=\"mini\"><a href=\"$url\">"
      ."<img src=\"$thumb\" /></a></div></td>\n";
    if ($cur_pos==$pos-1)
      echo "<td><div class=\"mini.prev\">&lt;</div></td>\n";
    $cur_pos++;
  }
  echo "</td></table>\n</div>\n";
}


function print_content()
{
  global $db;
  $search=new Search();
  $search->from_URL();
  
  echo "<h2>Image</h2>\n";
  
  if (!isset($_REQUEST['id']))
    return;
 
  $id=$_REQUEST['id'];
  
  $sql="SELECT id,userid,filename,name,UNIX_TIMESTAMP(synced),UNIX_TIMESTAMP(date),clicks,UNIX_TIMESTAMP(lastview),ranking
        FROM $db->image
        WHERE id=$id";

  
  $result = $db->query($sql);
  if (!$result)
  {
    return;
  }
  

  if (mysql_num_rows($result)==0)
  {
    $this->warning("Could not find image with id $id");
    return;
  }
  
  $row=mysql_fetch_row($result);
  $id=$row[0];
  $userid=$row[1];
  $filename=$row[2];
  $name=$row[3];
  $synced=$row[4];
  $sec=$row[5];
  $clicks=$row[6];
  $lastview=$row[7];
  $ranking=$row[8];
  
  $preview=create_preview($id, $userid, $filename, $synced);
  
  echo "<h3>$name</h3>\n";
  echo "<p><img src=\"$preview\" /></p>\n";
  echo "<table class=\"imginfo\">\n";
  
  echo "  <tr><td class=\"th\">Clicks:</td><td>$clicks (Ranking: $ranking)</td></tr>\n";
  print_row_date($sec);
  print_row_tags($id);
  echo "</table>\n";

  $ranking=0.8*$ranking+500/(1+time()-$lastview);
  $sql="UPDATE $db->image SET ranking=$ranking WHERE id=$id";
  $result = $db->query($sql);
  $sql="UPDATE $db->image SET clicks=clicks+1, lastview=NOW() WHERE id=$id";
  $result = $db->query($sql);
  
  $this->print_navigation($search);
  
}

}

?>
