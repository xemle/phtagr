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

function print_content()
{
    global $db;
    
    echo "<h2>Image</h2>\n";
    
    if (!isset($_REQUEST['id']))
        return;

    $id=$_REQUEST['id'];
    
    $sql="SELECT id,userid,filename,name,UNIX_TIMESTAMP(synced),clicks,UNIX_TIMESTAMP(lastview),ranking
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
    $clicks=$row[5];
    $lastview=$row[6];
    $ranking=$row[7];
    
    $preview=create_preview($id, $userid, $filename, $synced);
    
    echo "<h3>$name</h3>\n";
    echo "<p><img src=\"$preview\" /></p>\n";
    echo "<p>Clicks: $clicks, Ranking: $ranking</p>\n";
  
    $ranking=0.8*$ranking+500/(1+time()-$lastview);
    $sql="UPDATE $db->image SET ranking=$ranking WHERE id=$id";
    $result = $db->query($sql);
    $sql="UPDATE $db->image SET clicks=clicks+1, lastview=NOW() WHERE id=$id";
    $result = $db->query($sql);
  
}

}

?>
