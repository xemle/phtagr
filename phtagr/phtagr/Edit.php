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

  if (!isset($_REQUEST['images']) && !isset($_REQUEST['image']))
    return;
 
  $signle_image=-1;
  if (isset($_REQUEST['image']))
    $signle_image=$_REQUEST['image'];
  
  //echo "<pre>"; print_r($_REQUEST); echo "</pre>";
  $images=array_merge($_REQUEST['images'], $_REQUEST['image']);
  foreach ($images as $id)
  {
    if ($signle_image>=0 && $signle_image!=$id)
      continue;
      
    $filename=$this->_get_filename($id);
    if ($filename=='')
      continue;
      
    $iptc=new Iptc();
    if (!$iptc->load_from_file($filename))
      continue;
    $iptc->reset_error();
    
    //echo "<pre>\n"; print_r($iptc); echo "</pre>\n";
    // Distinguish between javascript values and global values
    if (isset($_REQUEST['js_tags']))
    {
      $tags=split(" ", $_REQUEST['js_tags']);
      /** @todo optimize set of this operation. Do only delete required tags */
      $iptc->rem_record("2:025");
      
      // only positive tags
      $add_tags=array();
      foreach ($tags as $tag)
      {
        if ($tag{0}!='-')
          array_push($add_tags, $tag);
      }
      $iptc->add_records("2:025", $add_tags); 
    }
    else if (isset($_REQUEST['edit_tags']))
    {
      $tags=split(" ", $_REQUEST['edit_tags']);
    
      // distinguish between add and remove operation.
      $add_tags=array();
      $rem_tags=array();
      foreach ($tags as $tag)
      {
        if ($tag{0}=='-')
          array_push($rem_tags, substr($tag, 1));
        else
          array_push($add_tags, $tag);
      }
      $iptc->add_records("2:025", $add_tags); 
      $iptc->rem_records("2:025", $rem_tags); 
    }
    //echo "<pre>\n"; print_r($iptc); echo "</pre>\n";
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
