<?php

global $phtagr_lib;
include_once("$phtagr_lib/Iptc.php");
include_once("$phtagr_lib/Base.php");
include_once("$phtagr_lib/Constants.php");

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

  $images=array();

  if (isset($_REQUEST['image']))
    $images=array($_REQUEST['image']);

  if (isset($_REQUEST['images']))
    $images=array_merge($_REQUEST['images'], $images);
 
  foreach ($images as $id)
  {
    if (!is_numeric($id))
      continue;

    $img=new Image($id);
    if ($img->get_id()!=$id)
    {
      unset($img);
      continue;
    }
      
    /* Accept only votes of unvoted images for this session */
    if (isset($_REQUEST['voting']) && 
      !isset($_SESSION['img_voted'][$id]))
    {
      if ($img->new_vote($_REQUEST['voting']))
        $_SESSION['img_voted'][$id]=$_REQUEST['voting'];
    }

    if (!$user->can_edit(&$img))
    {
      unset($img);
      continue;
    }
    
    $iptc=new Iptc();
    $iptc->load_from_file($img->get_filename());
    if ($this->_check_iptc_error(&$iptc))
      continue;
    
    //$this->debug($_REQUEST);

    // Distinguish between javascript values and global values
    if (isset($_REQUEST['js_tags']))
    {
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

    // Distinguish between javascript values and global values
    if (isset($_REQUEST['js_sets']))
    {
      $sets=split(" ", $_REQUEST['js_sets']);
      /** @todo optimize set of this operation. Do only delete required sets */
      $iptc->rem_record("2:020");
      if ($this->_check_iptc_error(&$iptc))
        return false;
      // only positive sets
      $add_sets=array();
      foreach ($sets as $set)
      {
        if ($set{0}!='-')
          array_push($add_sets, $set);
      }
      
      $iptc->add_records("2:020", $add_sets); 
      if ($this->_check_iptc_error(&$iptc))
        return false;
    }
    else if (isset($_REQUEST['edit_sets']))
    {
      $sets=split(" ", $_REQUEST['edit_sets']);
    
      // distinguish between add and remove operation.
      $add_sets=array();
      $rem_sets=array();
      foreach ($sets as $set)
      {
        if ($set{0}=='-')
          array_push($rem_sets, substr($set, 1));
        else
          array_push($add_sets, $set);
      }
      $iptc->add_records("2:020", $add_sets); 
      if ($this->_check_iptc_error(&$iptc))
        return false;
      $iptc->rem_records("2:020", $rem_sets); 
      if ($this->_check_iptc_error(&$iptc))
        return false;
    }

    // Add captions
    if (isset($_REQUEST['js_caption']))
    {
      $caption=$_REQUEST['js_caption'];
      $iptc->add_record("2:120", $caption);
      if ($this->_check_iptc_error(&$iptc))
        return false;
    }
    else if (isset($_REQUEST['edit_caption']))
    {
      $caption=$_REQUEST['edit_caption'];
      $iptc->add_record("2:120", $caption);
      if ($this->_check_iptc_error(&$iptc))
        return false;
    }
   
    $this->handle_iptc_location(&$iptc);
    
    if ($iptc->is_changed())
    {
      $iptc->save_to_file();
      $img->update(true);
      if ($this->_check_iptc_error(&$iptc))
        return false;
    }

    if (isset($_REQUEST['js_acl']) ||
      isset($_REQUEST['aacl_edit']))
      $this->_handle_request_acl(&$img);
    
    unset($img);
  }
  return true;
}

/** Change the location information of the image. Location types are state,
 * sublocation, state and country. If a location type starts with an minus sign
 * in the multi-select mode, the location type will be removed. 
 @param iptc Pointer to the IPTC object of the image */
function handle_iptc_location(&$iptc)
{
  if (isset($_REQUEST['js_location']))
  {
    if ($_REQUEST['js_city']!='')
      $iptc->add_record("2:090", $_REQUEST['js_city']);
    else
      $iptc->rem_record("2:090");
    
    if ($_REQUEST['js_sublocation']!='')
      $iptc->add_record("2:092", $_REQUEST['js_sublocation']);
    else
      $iptc->rem_record("2:092");
    
    if ($_REQUEST['js_state']!='')
      $iptc->add_record("2:095", $_REQUEST['js_state']);
    else
      $iptc->rem_record("2:095");
    
    if ($_REQUEST['js_country']!='')
      $iptc->add_record("2:101", $_REQUEST['js_country']);
    else
      $iptc->rem_record("2:101");
  }
  else 
  {
    if ($_REQUEST['edit_city']!='')
    {
      if ($_REQUEST['edit_city'][0]=='-')
        $iptc->rem_record("2:090", substr($_REQUEST['edit_city'],1));
      else
        $iptc->add_record("2:090", $_REQUEST['edit_city']);
    }
    
    if ($_REQUEST['edit_sublocation']!='')
    {
      if ($_REQUEST['edit_sublocation'][0]=='-')
        $iptc->rem_record("2:092", substr($_REQUEST['edit_sublocation'],1));
      else
        $iptc->add_record("2:092", $_REQUEST['edit_sublocation']);
    }
    
    if ($_REQUEST['edit_state']!='')
    {
      if ($_REQUEST['edit_state'][0]=='-')
        $iptc->rem_record("2:095", substr($_REQUEST['edit_state'],1));
      else
        $iptc->add_record("2:095", $_REQUEST['edit_state']);
    }
    
    if ($_REQUEST['edit_country']!='')
    {
      if ($_REQUEST['edit_country'][0]=='-')
        $iptc->rem_record("2:101", substr($_REQUEST['edit_country'],1));
      else
        $iptc->add_record("2:101", $_REQUEST['edit_country']);
    }
  }
}

/* Permit a new flag. The ACL flag influence lower levels. E.g. if a member is
 * allowed to access some data, the group member is also allowed.
  @param acl Pointer to acl array
  @param level Level of flag. 0 for group, 1 for member, 2 for all
  @param flag ACL flag */
function _add_acl(&$acl, $level, $flag)
{
  switch ($level) {
  case ACL_GROUP:
    $acl[ACL_GROUP]|=$flag;
    break;
  case ACL_OTHER:
    $acl[ACL_OTHER]|=$flag;
    $acl[ACL_GROUP]|=$flag;
    break;
  case ACL_ALL:
    $acl[ACL_ALL]|=$flag;
    $acl[ACL_OTHER]|=$flag;
    $acl[ACL_GROUP]|=$flag;
    break;
  default:
  }
}

/* Deny a resource. The ACL flag influence higher levels. E.g. if a member is
 * denied to access some data, a non-member is also denied.
  @param acl Pointer to acl array
  @param level Level of flag. 0 for group, 1 for member, 2 for all
  @param mask Mask to deny higher data. */
function _del_acl(&$acl, $level, $mask)
{
  switch ($level) {
  case ACL_GROUP:
    $acl[ACL_GROUP]&=~$mask;
    $acl[ACL_OTHER]&=~$mask;
    $acl[ACL_ALL]&=~$mask;
    break;
  case ACL_OTHER:
    $acl[ACL_OTHER]&=~$mask;
    $acl[ACL_ALL]&=~$mask;
    break;
  case ACL_ALL:
    $acl[ACL_ALL]&=~$mask;
    break;
  default:
  }
}

/** 
  @param acl ACL array of current image
  @param op Operant. Possible values are strings of 'add', 'del', 'keep' or
  null. If op is null, the operant is handled as 'del' and will remove the ACL.
  The operand 'keep' changes nothing.
  @param flag Permit bit of the current ACL
  @param mask Deny mask of current ACL */
function _handle_acl(&$acl, $op, $level, $flag, $mask)
{
  if ($op=='add')
    $this->_add_acl(&$acl, $level, $flag);
  else if ($op=='del' || $op==null)
    $this->_del_acl(&$acl, $level, $mask);
}

/** Handle the ACL requests of an specific image. Only the image owner can
 * modify the ACL levels.
  @param img Pointer to the image object
  @return True on success, false otherwise 
  @todo Update image only if acl changes */
function _handle_request_acl(&$img)
{
  global $user;
  global $db;
  
  if (!$img)
    return false;
  if (!$user->is_owner(&$img))
    return false;
    
  $acl=array();
  $acl[ACL_GROUP]=$img->get_gacl();
  $acl[ACL_OTHER]=$img->get_oacl();
  $acl[ACL_ALL]=$img->get_aacl();
  
  // JavaScript formular or set selection?
  if (isset($_REQUEST['js_acl']))
  {
    $this->_handle_acl(&$acl, $_REQUEST['js_aacl_edit'], ACL_ALL, ACL_EDIT, ACL_EDIT_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['js_oacl_edit'], ACL_OTHER, ACL_EDIT, ACL_EDIT_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['js_gacl_edit'], ACL_GROUP, ACL_EDIT, ACL_EDIT_MASK);
      
    $this->_handle_acl(&$acl, $_REQUEST['js_aacl_preview'], ACL_ALL, ACL_PREVIEW, ACL_PREVIEW_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['js_oacl_preview'], ACL_OTHER, ACL_PREVIEW, ACL_PREVIEW_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['js_gacl_preview'], ACL_GROUP, ACL_PREVIEW, ACL_PREVIEW_MASK);
  }
  else 
  {
    $this->_handle_acl(&$acl, $_REQUEST['aacl_edit'], ACL_ALL, ACL_EDIT, ACL_EDIT_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['oacl_edit'], ACL_OTHER, ACL_EDIT, ACL_EDIT_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['gacl_edit'], ACL_GROUP, ACL_EDIT, ACL_EDIT_MASK);

    $this->_handle_acl(&$acl, $_REQUEST['aacl_preview'], ACL_ALL, ACL_PREVIEW, ACL_PREVIEW_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['oacl_preview'], ACL_OTHER, ACL_PREVIEW, ACL_PREVIEW_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['gacl_preview'], ACL_GROUP, ACL_PREVIEW, ACL_PREVIEW_MASK);
  }
  
  $id=$img->get_id();
  $sql="UPDATE $db->image
        SET gacl=".$acl[ACL_GROUP].
        ",oacl=".$acl[ACL_OTHER].
        ",aacl=".$acl[ACL_ALL]."
        WHERE id=$id";
  if ($db->query($sql))
    return true;

  return false;
}


/** Print the inputs to edit IPTC tags like comment, tags or sets. */
function print_edit_inputs()
{
  echo "
<div class=\"edit\">
<p><input type=\"checkbox\" id=\"selectall\" onclick=\"checkbox('selectall', 'images[]')\"/>"._("Select all")."</p>

<p><a href=\"javascript:void(0)\" id=\"btnEdit\" class=\"jsbutton\" onclick=\"toggle_visibility('toggleEdit', 'btnEdit')\">-&gt; "._("Edit Image Data")."</a></p>

<fieldset id='toggleEdit' style=\"display:none\"><legend>Edit Image Data <a href=\"javascript:void(0)\" class=\"jsbutton\" onclick=\"toggle_visibility('toggleEdit', 'btnEdit')\">[Hide]</a></legend>
  <table>
    <tr>
      <th>Caption:</th>
      <td><textarea name=\"edit_caption\" cols=\"24\" rows=\"3\" ></textarea></td>
    </tr>
    <tr>
      <th>Tags:</th>
      <td><input type=\"text\" name=\"edit_tags\" size=\"60\"/></td>
    </tr>
    <tr>
      <th>Set:</th>
      <td><input type=\"text\" name=\"edit_sets\" size=\"60\"/></td>
    </tr>
    <tr>
      <th>Location:</th>
      <td>City: <input type=\"text\" name=\"edit_city\" size=\"60\"/><br/>
        Sublocation: <input type=\"text\" name=\"edit_sublocation\" size=\"60\"/><br/> 
        State: <input type=\"text\" name=\"edit_province\" size=\"60\"/><br/>
        Country: <input type=\"text\" name=\"edit_country\" size=\"60\"/></td>
    </tr>
  </table>
</fieldset>

<p><a href=\"javascript:void(0)\" id=\"btnAcl\" class=\"jsbutton\" onclick=\"toggle_visibility('toggleAcl', 'btnAcl')\">-&gt; "._("Edit Access Control Lists (ACL)")."</a></p>

<fieldset id='toggleAcl' style=\"display:none\"><legend>Access Control List <a href=\"javascript:void(0)\" class=\"jsbutton\" onclick=\"toggle_visibility('toggleAcl', 'btnAcl')\">[Hide]</a></legend>
  <table>
    <tr>
      <th></th>
      <th>Friends</th>
      <th>Members</th>
      <th>All</th>
    </tr>
    <tr>
      <td>Edit</td>
      <td>
        <select size=\"1\" name=\"gacl_edit\">
          <option selected=\"selected\" value=\"keep\">Keep</option>
          <option value=\"del\">Deny</option>
          <option value=\"add\">Permit</option>
        </select>
      </td>
      <td>
        <select size=\"1\" name=\"oacl_edit\">
          <option selected=\"selected\" value=\"keep\">Keep</option>
          <option value=\"del\">Deny</option>
          <option value=\"add\">Permit</option>
        </select>
      </td>
      <td>
        <select size=\"1\" name=\"aacl_edit\">
          <option selected=\"selected\" value=\"keep\">Keep</option>
          <option value=\"del\">Deny</option>
          <option value=\"add\">Permit</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>Preview</td>
      <td>
        <select size=\"1\" name=\"gacl_preview\">
          <option selected=\"selected\" value=\"keep\">Keep</option>
          <option value=\"del\">Deny</option>
          <option value=\"add\">Permit</option>
        </select>
      </td>
      <td>
        <select size=\"1\" name=\"oacl_preview\">
          <option selected=\"selected\" value=\"keep\">Keep</option>
          <option value=\"del\">Deny</option>
          <option value=\"add\">Permit</option>
        </select>
      </td>
      <td>
        <select size=\"1\" name=\"aacl_preview\">
          <option selected=\"selected\" value=\"keep\">Keep</option>
          <option value=\"del\">Deny</option>
          <option value=\"add\">Permit</option>
        </select>
      </td>
    </tr>
  </table>
  <p>Set the access level to the selected images.</p>
</fieldset>

<input type=\"hidden\" name=\"action\" value=\"edit\"/></div>
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
