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

/** Convert the SQL time string to unix time stamp. 
  @param string The time string has the format like "2005-04-06 09:24:56", the
  result is 1112772296 
  @return Unix time in seconds */
function _sqltime2unix($string)
{
  $s=strtr($string, ":", " ");
  $s=strtr($s, "-", " ");
  $a=split(' ', $s);
  $time=mktime($a[3],$a[4],$a[5],$a[1],$a[2],$a[0]);
  return $time;
}

function print_content()
{
  global $db;
  global $user;

  $search=new Search();
  $search->from_URL();
 
  echo "<h2>Image</h2>\n";
  
  if (!isset($_REQUEST['id']))
    return;
 
  $id=$_REQUEST['id'];
  
  $sql="SELECT *         
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
  
  $v=mysql_fetch_array($result, MYSQL_ASSOC);

  $sec=$this->_sqltime2unix($v['synced']); 
  $preview=create_preview($v['id'], $v['userid'], $v['filename'], $sec);
  
  echo "<h3>${v['name']}</h3>\n";
  echo "<p><img src=\"$preview\" /></p>\n";
  if ($user->can_edit($v['id']))
  {
    echo "<form action=\"index.php\" method=\"post\">\n";
    echo "<input type=\"hidden\" name=\"section\" value=\"image\" />\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"edit\" />\n";
    echo $search->to_form();
  } 
  print_caption($v['id'], $v['caption'], false);
  echo "<table class=\"imginfo\">\n";
  
  $ranking=0+strtr($v['ranking'], 'E', 'e');
  echo "  <tr><th>Clicks:</th><td>${v['clicks']}"
    ." (Ranking: $ranking)</td></tr>\n";

  $sec=$this->_sqltime2unix($v['date']); 
  print_row_date($sec);
  print_row_tags($v['id']);
  echo "</table>\n";

  if ($user->can_edit($v['id']))
    echo "</form>\n";


  $ranking=0.8*$ranking+500/(1+time()-$this->_sqltime2unix($v['lastview']));
  $sql="UPDATE $db->image 
        SET ranking=$ranking 
        WHERE id=".$v['id'];
  $result = $db->query($sql);
  $sql="UPDATE $db->image 
        SET clicks=clicks+1, lastview=NOW() 
        WHERE id=$id";
  $result = $db->query($sql);
  
  $this->print_navigation($search);
 
}

}

?>
