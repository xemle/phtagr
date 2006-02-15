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
    
    $sql="SELECT id,userid,filename,name,UNIX_TIMESTAMP(synced),clicks
          FROM image
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
    
    $preview=create_preview($id, $userid, $filename, $synced);
    
    echo "<h3>$name<h3>\n";
    echo "<p><img src=\"$preview\" /></p>\n";
    echo "<p>Clicks: $clicks</p>\n";
  
    $sql="UPDATE image SET clicks=clicks+1 WHERE id=$id";
    $result = $db->query($sql);
  
}

}

?>
