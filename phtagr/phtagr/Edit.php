<?php

global $prefix;
include_once("$prefix/Iptc.php");
include_once("$prefix/Base.php");

/** This class handles modifications and checks the access rights.
  @class Edit */
class Edit extends Base
{

function Edit()
{
}

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

/** Check the IPTC error. If a fatal error exists, prints the IPTC error
 * message an return true.
  @param iptc Pointer to the IPTC instance
  @return True if a fatal error occursed, false otherwise */
function _check_iptc_error($iptc)
{
  if (!isset($iptc))
    return false;
  if ($iptc->get_errno() < 0)
  {
    $this->error($iptc->get_errmsg());
    return true;
  }
  return false;
}

/** Executes the edits. This function also checks the rights to execute the operation */
function execute()
{
  global $db;
  global $user;

  if (!isset($_REQUEST['images']) && !isset($_REQUEST['image']))
    return;
 
  $signle_image=-1;
  if (isset($_REQUEST['image']))
    $signle_image=$_REQUEST['image'];

  $images=array_merge($_REQUEST['images'], $_REQUEST['image']);
  foreach ($images as $id)
  {
    if ($signle_image>=0 && $signle_image!=$id)
      continue;
      
    $img=new Image($id);
    if ($img->get_id()!=$id)
      continue;
      
    $iptc=new Iptc();
    $iptc->load_from_file($img->get_filename());
    if ($this->_check_iptc_error(&$iptc))
      continue;
    
    //echo "<pre>\n"; print_r($iptc); echo "</pre>\n";
    // Distinguish between javascript values and global values
    if (isset($_REQUEST['js_tags']))
    {
      if (!$user->can_metadata(&$img))
        continue;
        
      $tags=split(" ", $_REQUEST['js_tags']);
      /** @todo optimize set of this operation. Do only delete required tags */
      $iptc->rem_record("2:025");
      if ($this->_check_iptc_error(&$iptc))
        return false;
      // only positive tags
      $add_tags=array();
      foreach ($tags as $tag)
      {
        if ($tag{0}!='-')
          array_push($add_tags, $tag);
      }
      
      $iptc->add_records("2:025", $add_tags); 
      if ($this->_check_iptc_error(&$iptc))
        return false;
    }
    else if (isset($_REQUEST['edit_tags']))
    {
      if (!$user->can_metadata(&$img))
        continue;
        
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
      if ($this->_check_iptc_error(&$iptc))
        return false;
      $iptc->rem_records("2:025", $rem_tags); 
      if ($this->_check_iptc_error(&$iptc))
        return false;
    }

    // Add captions
    if (isset($_REQUEST['js_caption']))
    {
      if (!$user->can_metadata(&$img))
        continue;
        
      $caption=$_REQUEST['js_caption'];
      $iptc->add_record("2:120", $caption);
      if ($this->_check_iptc_error(&$iptc))
        return false;
    }
    else if (isset($_REQUEST['edit_caption']))
    {
      if (!$user->can_metadata(&$img))
        continue;
        
      $caption=$_REQUEST['edit_caption'];
      $iptc->add_record("2:120", $caption);
      if ($this->_check_iptc_error(&$iptc))
        return false;
    }
    
    if ($iptc->is_changed())
    {
      $iptc->save_to_file();
      $image=new Image($id);
      $image->update(true);
      unset($image);
      if ($this->_check_iptc_error(&$iptc))
        return false;
    }

    if (!$user->can_edit(&$img))
      continue;
        
    if (isset($_REQUEST['aacl']))
    {
      switch ($_REQUEST['aacl'])
      {
        case 'add':
          $sql="UPDATE $db->image
                SET aacl=aacl | ".ACL_PREVIEW.",
                    oacl=oacl | ".ACL_PREVIEW.",
                    gacl=gacl | ".ACL_PREVIEW."
                WHERE id=$id";
          $result=$db->query($sql);
          break;
        case 'del':
          $sql="UPDATE $db->image
                SET aacl=aacl & ~".ACL_PREVIEW_MASK." 
                WHERE id=$id";
          $result=$db->query($sql);
          break;
        default:
      }
    }
    if (isset($_REQUEST['oacl']))
    {
      switch ($_REQUEST['oacl'])
      {
        case 'add':
          $sql="UPDATE $db->image
                SET oacl=oacl | ".ACL_PREVIEW.",
                    gacl=gacl | ".ACL_PREVIEW."
                WHERE id=$id";
          $result=$db->query($sql);
          break;
        case 'del':
          $sql="UPDATE $db->image
                SET aacl=aacl & ~".ACL_PREVIEW_MASK.",
                    oacl=oacl & ~".ACL_PREVIEW_MASK." 
                WHERE id=$id";
          $result=$db->query($sql);
          break;
        default:
      }
    }
    if (isset($_REQUEST['gacl']))
    {
      switch ($_REQUEST['gacl'])
      {
        case 'add':
          $sql="UPDATE $db->image
                SET gacl=gacl | ".ACL_PREVIEW."
                WHERE id=$id";
          $result=$db->query($sql);
          break;
        case 'del':
          $sql="UPDATE $db->image
                SET aacl=aacl & ~".ACL_PREVIEW_MASK.",
                    oacl=oacl & ~".ACL_PREVIEW_MASK.", 
                    gacl=gacl & ~".ACL_PREVIEW_MASK." 
                WHERE id=$id";
          $result=$db->query($sql);
          break;
        default:
      }
    }
  }
  return true;
}

/** Print the inputs to edit IPTC tags like comment, tags or sets. */
function print_edit_inputs()
{
  echo "
<fieldset><legend>Edit</legend>
  <table>
    <tr><th>Caption:</th><td><textarea name=\"edit_caption\" cols=\"24\" rows=\"3\" ></textarea></td></tr>
    <tr><th>Tags:</th><td><input type=\"text\" name=\"edit_tags\" size=\"60\"/></td></tr>
    <tr><th>Set:</th><td><input type=\"text\" name=\"edit_sets\" size=\"60\"/></td></tr>
  </table>
</fieldset>
<fieldset><legend>ACL</legend>
  <table>
    <tr><th>Group</th><th>Members</th><th>All</th><tr>
    <tr><td><input type=\"radio\" name=\"gacl\" value=\"add\" />
        <input type=\"radio\" name=\"gacl\" value=\"del\" />
        <input type=\"radio\" name=\"gacl\" value=\"keep\" checked /></td>
      <td><input type=\"radio\" name=\"oacl\" value=\"add\" />
        <input type=\"radio\" name=\"oacl\" value=\"del\" />
        <input type=\"radio\" name=\"oacl\" value=\"keep\" checked /></td>
      <td><input type=\"radio\" name=\"aacl\" value=\"add\" />
        <input type=\"radio\" name=\"aacl\" value=\"del\" />
        <input type=\"radio\" name=\"aacl\" value=\"keep\" checked /></td></tr>
  </table>
</fieldset>

<div><input type=\"hidden\" name=\"action\" value=\"edit\"/></div>
";
}

/** Pirnt the submit and reset buttons */
function print_buttons()
{
  echo "<div><input type=\"submit\" value=\"OK\" />
<input type=\"reset\" value=\"Reset fields\" /></div>
";
}

}
?>
