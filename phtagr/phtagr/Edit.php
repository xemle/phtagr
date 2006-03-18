<?php

global $prefix;
include_once("$prefix/Iptc.php");

class Edit 
{

/** Returns the filename of an id 
  @return on error return an empty string */
function _get_filename($id)
{
  global $db;
  $sql="SELECT filename 
        FROM $db->image
        WHERE id=$id";
  $result=$db->query($sql);
  if (!$result)
    return '';
  
  $row=mysql_fetch_row($result);
  return $row[0];
}

function Edit()
{
  global $db;
  global $auth;

  if (!isset($_REQUEST['images']))
    return;
 
  //echo "<pre>"; print_r($_REQUEST); echo "</pre>";
  foreach ($_REQUEST['images'] as $id)
  {
    $filename=$this->_get_filename($id);
    if ($filename=='')
      continue;
      
    $iptc=new Iptc();
    if (!$iptc->load_from_file($filename))
      continue;
    $iptc->reset_error();
    
    // Distinguish between java script values and global values
    if (isset($_REQUEST['js_tags']))
    {
      $tags=split(" ", $_REQUEST['js_tags']);
      $iptc->add_iptc_tags("2:025", $tags); 
    }
    else if (isset($_REQUEST['edit_tags']))
    {
      $tags=split(" ", $_REQUEST['edit_tags']);
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
