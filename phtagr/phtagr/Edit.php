<?php

global $prefix;
include_once("$prefix/Iptc.php");

class Edit 
{

function Edit()
{
  global $db;
  global $auth;

  if (!isset($_REQUEST['images']))
    return;
 
  foreach ($_REQUEST['images'] as $id)
  {
    $sql="SELECT filename 
          FROM $db->image
          WHERE id=$id";
    $result=$db->query($sql);
    if (!$result)
      continue;

    $row=mysql_fetch_row($result);
    $filename=$row[0];
    $iptc=new Iptc();
    $iptc->load_from_file($filename);
    
    if (isset($_REQUEST['_tags']))
    {
      $tags=split(" ", $_REQUEST['_tags']);
      $iptc->add_iptc_tags("2:025", $tags); 
    }

    //echo "<pre>\n";
    //print_r($iptc);
    //echo "</pre>\n";
    if ($iptc->get_error()!='')
      echo "<div class=\"warning\">IPTC error: ".$iptc->get_error()."<div/>\n";

    if ($iptc->is_changed())
    {
      $iptc->save_to_file();
      update_iptc($id, $auth->userid, $filename);  
    }
  }
}


}
?>
