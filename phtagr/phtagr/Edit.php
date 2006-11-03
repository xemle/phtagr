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

    $img=new Thumbnail($id);
    if ($img->get_id()!=$id)
    {
      unset($img);
      continue;
    }
      
    if (isset($_REQUEST['command']))
    {
      $cmd=$_REQUEST['command'];
      if ($cmd=='remove')
      {
        if ($img->is_owner(&$user))
        {
          $img->delete_previews();
          if ($img->is_upload())
            @unlink($img->get_filename());
          $img->remove_from_db();
          unset($img);
          continue;
        }
      }
    }

    /* Accept only votes of unvoted images for this session */
    if (isset($_REQUEST['voting']) && 
      !isset($_SESSION['img_voted'][$id]))
    {
      if ($img->new_vote($_REQUEST['voting']))
        $_SESSION['img_voted'][$id]=$_REQUEST['voting'];
    }

    if (!$img->can_edit(&$user))
    {
      unset($img);
      continue;
    }
    
    $iptc=new Iptc();
    $iptc->load_from_file($img->get_filename());
    $this->_handle_iptc_caption(&$iptc);
    $this->_handle_iptc_date(&$iptc);
    $this->_handle_iptc_tags(&$iptc);
    $this->_handle_iptc_sets(&$iptc);
    $this->_handle_iptc_location(&$iptc);
    
    if (!$this->_check_iptc_error(&$iptc) &&
      $iptc->is_changed())
    {
      $iptc->save_to_file();
      $img->touch_previews();
      $img->update(true);
    }
    unset($iptc);

    if (isset($_REQUEST['js_acl']) ||
      isset($_REQUEST['aacl_edit']))
      $this->_handle_request_acl(&$img);
    
    unset($img);
  }
  return true;
}

/** Add captions
  @param iptc Pointer to the IPTC object */
function _handle_iptc_caption(&$iptc)
{
  if ($this->_check_iptc_error(&$iptc))
    return false;

  if (isset($_REQUEST['js_caption']))
  {
    $caption=$_REQUEST['js_caption'];
    $iptc->add_record("2:120", $caption);
  }
  else if (isset($_REQUEST['edit_caption']))
  {
    $caption=$_REQUEST['edit_caption'];
    $iptc->add_record("2:120", $caption);
  }
}

/** Set the date
  @param iptc Pointer to the IPTC object */
function _handle_iptc_date(&$iptc)
{
  if ($this->_check_iptc_error(&$iptc))
    return false;

  $date=null;

  if (isset($_REQUEST['js_date']))
    $date=$_REQUEST['js_date'];
  else if (isset($_REQUEST['edit_caption']))
    $date=$_REQUEST['edit_date'];

  if ($date!=null)
    $iptc->set_date($date);
}

function _handle_iptc_tags(&$iptc)
{
  if ($this->_check_iptc_error(&$iptc))
    return false;

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

}
function _handle_iptc_sets(&$iptc)
{
  if ($this->_check_iptc_error(&$iptc))
    return false;

  // Distinguish between javascript values and global values
  if (isset($_REQUEST['js_sets']))
  {
    $sets=split(" ", $_REQUEST['js_sets']);
    /** @todo optimize set of this operation. Do only delete required sets */
    $iptc->rem_record("2:020");
    // only positive sets
    $add_sets=array();
    foreach ($sets as $set)
    {
      if ($set{0}!='-')
        array_push($add_sets, $set);
    }
    
    $iptc->add_records("2:020", $add_sets); 
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
    $iptc->rem_records("2:020", $rem_sets); 
  }
}

/** Change the location information of the image. Location types are state,
 * sublocation, state and country. If a location type starts with an minus sign
 * in the multi-select mode, the location type will be removed. 
 @param iptc Pointer to the IPTC object of the image */
function _handle_iptc_location(&$iptc)
{
  if ($this->_check_iptc_error(&$iptc))
    return false;

  if (isset($_REQUEST['js_city']))
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
  if (!$img->is_owner(&$user))
    return false;
    
  $acl=array();
  $acl[ACL_GROUP]=$img->get_gacl();
  $acl[ACL_OTHER]=$img->get_oacl();
  $acl[ACL_ALL]=$img->get_aacl();
  
  // JavaScript formular or set selection?
  if (isset($_REQUEST['js_acl']))
  {
    if (isset($_REQUEST['js_acl_setgroup']))
    {
      $gid=intval($_REQUEST['js_acl_setgroup']);
      if ($gid>=0)
        $img->set_groupid($gid);
    }
    $this->_handle_acl(&$acl, $_REQUEST['js_aacl_edit'], ACL_ALL, ACL_EDIT, ACL_EDIT_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['js_oacl_edit'], ACL_OTHER, ACL_EDIT, ACL_EDIT_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['js_gacl_edit'], ACL_GROUP, ACL_EDIT, ACL_EDIT_MASK);
      
    $this->_handle_acl(&$acl, $_REQUEST['js_aacl_preview'], ACL_ALL, ACL_PREVIEW, ACL_PREVIEW_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['js_oacl_preview'], ACL_OTHER, ACL_PREVIEW, ACL_PREVIEW_MASK);
    $this->_handle_acl(&$acl, $_REQUEST['js_gacl_preview'], ACL_GROUP, ACL_PREVIEW, ACL_PREVIEW_MASK);
  }
  else 
  {
    if (isset($_REQUEST['acl_setgroup']))
    {
      $gid=intval($_REQUEST['acl_setgroup']);
      if ($gid>=0)
        $img->set_groupid($gid);
    }
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

function print_bar()
{
  global $user;
  global $search;
  $size=$search->get_page_size();
  echo "<div class=\"tab\">
<h2>"._("Actions:")."</h2>
<ul>
  <li>"._("Images per page:")." <select size=\"1\" name=\"pagesize\">\n";
  if ($size!=10)
    echo "    <option value=\"$size\">$size</option>\n";
  echo "    <option value=\"10\">"._("Default")."</option>
  <option value=\"5\">5</option>
  <option value=\"10\">10</option>
  <option value=\"25\">25</option>
  <option value=\"50\">50</option>
  <option value=\"100\">100</option>
  <option value=\"150\">150</option>
  <option value=\"200\">200</option>
</select></li>
  <li>"._("Images:")." <select size=\"1\" name=\"command\">
    <option value=\"none\">"._("Nothing")."</option>
    <option value=\"mark\">"._("Mark")."</option>
    <option value=\"demark\">"._("Demark")."</option>\n";
    if ($user->is_member())
      echo "<option value=\"remove\">"._("Remove from DB")."</option>\n";
  echo "  </select></li>
  <li><input type=\"checkbox\" id=\"selectall\" onclick=\"checkbox('selectall', 'images[]')\"/>"._("Select all images")."</li>
</ul>
</div>";
}

function _print_edit_acl_row($param, $text)
{
  echo "  <tr>
    <td>$text</td>\n";
  $levels=array('g', 'o','a');
  foreach ($levels as $level)
  {
    echo "    <td>
      <select size=\"1\" name=\"".$level."acl_$param\">
        <option selected=\"selected\" value=\"keep\">"._("Keep")."</option>
        <option value=\"del\">"._("Deny")."</option>
        <option value=\"add\">"._("Permit")."</option>
      </select>
    </td>\n";
  }
  echo "  </tr>\n";
}

function print_edit_acl()
{
  global $user;
  if (!$user->is_member())
    return;

  echo "<p><a href=\"javascript:void(0)\" id=\"btnAcl\" class=\"jsbutton\" onclick=\"toggle_visibility('toggleAcl', 'btnAcl')\">-&gt; "._("Edit Access Control Lists (ACL)")."</a></p>

<fieldset id='toggleAcl' style=\"display:none\"><legend>Access Control List <a href=\"javascript:void(0)\" class=\"jsbutton\" onclick=\"toggle_visibility('toggleAcl', 'btnAcl')\">[Hide]</a></legend>\n";
  $groups=$user->get_groups();
  if (count($groups)>0)
  {
    echo "Group <select id=\"acl_grouplist\" name=\"acl_setgroup\" size=\"1\">
    <option value=\"-1\">"._("Keep")."</option>\n";
    foreach ($groups as $gid => $name)
      echo "    <option value=\"$gid\">$name</option>\n";
    echo "</select><br/>\n";
  }
  echo "<table>
  <tr>
    <th></th>
    <th>Friends</th>
    <th>Members</th>
    <th>All</th>
  </tr>\n";
  $this->_print_edit_acl_row("edit", _("Edit"));
  $this->_print_edit_acl_row("preview", _("Preview"));
  echo "</table>
  <p>Set the access level to the selected images.</p>
</fieldset>\n";  
}

/** Print the inputs to edit IPTC tags like comment, tags or sets. */
function print_edit_inputs()
{
  global $user;
  echo "\n<div class=\"edit\">

<p><a href=\"javascript:void(0)\" id=\"btnEdit\" class=\"jsbutton\" onclick=\"toggle_visibility('toggleEdit', 'btnEdit')\">-&gt; "._("Edit Image Data")."</a></p>

<fieldset id='toggleEdit' style=\"display:none\"><legend>Edit Image Data <a href=\"javascript:void(0)\" class=\"jsbutton\" onclick=\"toggle_visibility('toggleEdit', 'btnEdit')\">[Hide]</a></legend>
  <table>
    <tr>
      <th>"._("Caption:")."</th>
      <td><textarea name=\"edit_caption\" cols=\"24\" rows=\"3\" ></textarea></td>
    </tr>
    <tr>
      <th>"._("Date:")."</th>
      <td><input type=\"text\" name=\"edit_date\" size=\"19\"/></td>
    </tr>
    <tr>
      <th>"._("Tags:")."</th>
      <td><input type=\"text\" name=\"edit_tags\" size=\"60\"/></td>
    </tr>
    <tr>
      <th>"._("Set:")."</th>
      <td><input type=\"text\" name=\"edit_sets\" size=\"60\"/></td>
    </tr>
    <tr>
      <th>"._("Location:")."</th>
      <td>"._("City:")." <input type=\"text\" name=\"edit_city\" size=\"60\"/><br/>
        "._("Sublocation:")." <input type=\"text\" name=\"edit_sublocation\" size=\"60\"/><br/> 
        "._("State:")." <input type=\"text\" name=\"edit_province\" size=\"60\"/><br/>
        "._("Country:")." <input type=\"text\" name=\"edit_country\" size=\"60\"/></td>
    </tr>
  </table>
</fieldset>\n";

  if ($user->is_member())
    $this->print_edit_acl();

  echo "<input type=\"hidden\" name=\"action\" value=\"edit\"/></div>
";
}

/** Pirnt the submit and reset buttons */
function print_buttons()
{
  echo "<div><input type=\"submit\" class=\"submit\" value=\""._("Update")."\" />
<input type=\"reset\" class=\"reset\" value=\""._("Reset")."\" /></div>
";
}

}
?>
