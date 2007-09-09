<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006,2007 Sebastian Felis, sebastian@phtagr.org
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the 
 * License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

global $phtagr_lib;
include_once("$phtagr_lib/ImageSync.php");
include_once("$phtagr_lib/Constants.php");
include_once("$phtagr_lib/Acl.php");
include_once("$phtagr_lib/SectionAcl.php");

/** This class handles modifications and checks the access rights.
  @class Edit 
  @todo rename from Edit to ImageEdit
  @todo move set method to Image class */
class Edit extends SectionBase
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

/** Executes command for the single image like deleting, cache deleting and
 * synchronization
  @param img Current image object
  @param cmd Command */
function _exec_command($img, $cmd)
{
  global $log;
  if ($img==null)
    return;
  $id=$img->get_id();
  switch ($cmd)
  {
    case 'delete':
      $log->warn("Delete image", $id);
      $img->delete();
      unset($img);
      break;
    case 'delete_cache':
      $handler = $img->get_preview_handler();
      if ($handler != null) {
        $log->debug("Delete image previews", $id);
        $handler->delete_previews();
      }
      break;
    case 'sync':
      $log->debug("Synchronize image", $id);
      $img->synchronize();
      break;
    default:
      $log->info("Unknown command '$cmd'", $id);
      break;
  }
}

/** Executes the edits. This function also checks the rights to execute the operation */
function execute()
{
  global $db, $user, $log;

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
      $log->debug("Could not find image with id $id");
      unset($img);
      continue;
    }
      
    $cmd=$_REQUEST['command'];
    if ($cmd!='' && $img->is_owner(&$user)) 
    {
      $this->_exec_command($img, $cmd);
      if ($cmd=='delete')
        continue;
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

    if (!$img->can_write_tag(&$user) &&
      !$img->can_write_meta(&$user) &&
      !$img->can_write_caption(&$user))
    {
      unset($img);
      continue;
    }
    
    $img->handle_request();

    if (isset($_REQUEST['js_acl']) ||
      isset($_REQUEST['acl_preview']))
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
  $acl=new Acl($img->get_gacl(), $img->get_macl(), $img->get_pacl());
 
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
  
  if ($acl->has_changes())
  {
    list($gacl, $macl, $pacl)=$acl->get_values();
    global $log;
    $log->trace("new acl gacl=$gacl, macl=$macl, pacl=$pacl", $img->get_id());
    $img->set_gacl($gacl);
    $img->set_macl($macl);
    $img->set_pacl($pacl);
  }
  $img->commit();

  return true;
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
  if ($size!=12)
    $this->option($size, $size);
  $this->option(_("Default"), 12);
  foreach (array(12, 24, 60, 120, 240) as $size)
    $this->option($size, $size);
  echo "</select></li>
  <li>"._("Action:")." <select size=\"1\" name=\"command\">\n";
    $this->option(_("Nothing"), "none");
    if ($user->is_member()) {
      $this->option(_("Synchronize"), "sync");
      $this->option(_("Delete Cache"), "delete_cache");
      $this->option(_("Delete"), "delete");
    }
  echo "  </select></li>
  <li><input type=\"checkbox\" id=\"selectall\" onclick=\"checkbox('selectall', 'images[]')\"/>"._("Select all images")."</li>
</ul>
</div>";
}

function print_edit_acl()
{
  global $user;
  if (!$user->is_member())
    return;

  $this->fieldset_collapsable(_("Access Rights"), 'acl', true, true);

  $groups=$user->get_groups();
  if (count($groups)>0)
  {
    echo "<li>";
    $this->label(_("Image belongs to group"));
    echo "<select id=\"acl_grouplist\" name=\"acl_setgroup\" size=\"1\">";
    $this->option(_("Keep"), 0);
    foreach ($groups as $gid => $name)
      $this->option($name, $gid);
    $this->option(_("Delete group"), -1);
    echo "</select>\n";
    echo "</li>";
  }
  $acl=new SectionAcl();
  $acl->print_table();
  echo "</ol>";
  echo "</fieldset>\n";  
}

/** Print the inputs to edit IPTC tags like comment, tags or sets. */
function print_edit_inputs()
{
  global $user;
  echo "\n<div class=\"edit\">\n";

  $this->fieldset_collapsable(_("Tag Images"), 'meta', true, true);

  echo "<ol>";

  echo "<li>";
  $this->label(_("Caption:"));
  $this->textarea("edit_caption", 40, 3);
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Date:"));
  $this->input_text("edit_date", "", 19, 19);
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Tags:"));
  $this->input_text("edit_tags", "", 40, 150);
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Categories:"));
  $this->input_text("edit_categories", "", 40, 150);
  echo "</li>\n";

  echo "<li>";
  $this->label(_("City:"));
  $this->input_text("edit_city", "", 40, 64);
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Sublocation:"));
  $this->input_text("edit_sublocation", "", 40, 64);
  echo "</li>\n";

  echo "<li>";
  $this->label(_("State:"));
  $this->input_text("edit_state", "", 40, 64);
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Country:"));
  $this->input_text("edit_country", "", 40, 64);
  echo "</li>\n";

  echo "</ol>\n";
  echo "</fieldset>\n";

  if ($user->is_member())
    $this->print_edit_acl();

  $this->input_hidden("edit", "multi");
  $this->input_hidden("action", "edit");
  echo "</div>";
}

}
?>
