<?php

global $phtagr_lib;
include_once("$phtagr_lib/ImageSync.php");
include_once("$phtagr_lib/Constants.php");
include_once("$phtagr_lib/Acl.php");
include_once("$phtagr_lib/SectionAcl.php");

/** This class handles modifications and checks the access rights.
  @class Edit 
  @todo rename from Edit to ImageEdit
  @todo move set method to Image class */
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
        FROM $db->images
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

    $img=new ImageSync($id);
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
          $img->delete();
          unset($img);
          continue;
        }
      }
    }

    /* Accept only votes of unvoted images for this session. The votes needs
     * also a minimum time difference of 2 seconds */
    if (isset($_REQUEST['voting']) && 
      !isset($_SESSION['img_voted'][$id]))
    {
      $now=time();
      if (isset($_SESSION['voting_time']))
        $diff=$now-$_SESSION['voting_time'];
      else
        $diff=60;

      if ($diff>2 && $img->add_voting($_REQUEST['voting'])) {
        $_SESSION['img_voted'][$id]=$_REQUEST['voting'];
        $_SESSION['voting_time']=$now;
      }
    }

    if (!$img->can_edit(&$user))
    {
      unset($img);
      continue;
    }
    
    $img->handle_request();

    if (isset($_REQUEST['js_acl']) ||
      isset($_REQUEST['aacl_read']))
      $this->_handle_request_acl(&$img);
    
    $img->commit();
    unset($img);
  }
  return true;
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
    
  //$this->debug($_REQUEST);
  $acl=new Acl($img->get_gacl(), $img->get_macl(), $img->get_aacl());
 
  // JavaScript formular or a multiple selection?
  $prefix='';
  if (isset($_REQUEST['js_acl']))
    $prefix='js_';
  if (isset($_REQUEST[$prefix.'acl_setgroup']))
  {
    $gid=intval($_REQUEST[$prefix.'acl_setgroup']);
    if ($gid>0)
      $img->set_groupid($gid);
    else if ($gid==-1)
      $img->set_groupid(0);
  }
  $acl->handle_request($prefix);
  
  list($gacl, $macl, $aacl)=$acl->get_values();
  $id=$img->get_id();
  $img->set_gacl($gacl);
  $img->set_macl($macl);
  $img->set_aacl($aacl);
  $img->commit();

  return false;
}

function print_bar()
{
  global $user;

  $search=new Search();
  $search->from_url();
  $size=$search->get_page_size();
  echo "<div class=\"tab\">
<ul>
  <li>"._("Pagesize:")." <select size=\"1\" name=\"pagesize\">\n";
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
  <li>"._("Action:")." <select size=\"1\" name=\"command\">
    <option value=\"none\">"._("Nothing")."</option>
    <option value=\"mark\">"._("Mark")."</option>
    <option value=\"demark\">"._("Demark")."</option>\n";
    if ($user->is_member())
      echo "<option value=\"remove\">"._("Delete")."</option>\n";
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
    <option value=\"0\">"._("Keep")."</option>\n";
    foreach ($groups as $gid => $name)
      echo "    <option value=\"$gid\">$name</option>\n";
    echo "    <option value=\"-1\">"._("Delete group")."</option>\n";
    echo "</select><br/>\n";
  }
  $acl=new SectionAcl();
  $acl->print_table();
  echo "<p>Set the access level to the selected images.</p>
</fieldset>\n";  
}

/** Print the inputs to edit IPTC tags like comment, tags or sets. */
function print_edit_inputs()
{
  global $user;
  echo "\n<div class=\"edit\">

<p><a href=\"javascript:void(0)\" id=\"btnEdit\" class=\"jsbutton\" onclick=\"toggle_visibility('toggleEdit', 'btnEdit')\">-&gt; "._("Edit Image Data")."</a></p>

<fieldset id='toggleEdit' style=\"display:none\"><legend>Edit Image Data <a href=\"javascript:void(0)\" class=\"jsbutton\" onclick=\"toggle_visibility('toggleEdit', 'btnEdit')\">[Hide]</a></legend>
  <input type=\"hidden\" name=\"edit\" value=\"multi\" />
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
