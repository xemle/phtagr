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

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/SectionAccount.php");
include_once("$phtagr_lib/SectionUpload.php");
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Group.php");
include_once("$phtagr_lib/SectionAcl.php");

define("MYACCOUNT_TAB_UPLOAD", "1");
define("MYACCOUNT_TAB_GENERAL", "2");
define("MYACCOUNT_TAB_DETAILS", "3");
define("MYACCOUNT_TAB_GROUPS", "4");
define("MYACCOUNT_TAB_GUESTS", "5");

class SectionMyAccount extends SectionBase
{

function SectionMyAccount()
{
  $this->SectionBase("myaccount");
}

function print_general ()
{
  global $user;
  global $conf;
  $account=new SectionAccount();

  $url=new Url();
  $url->add_param('section', 'myaccount');
  $url->add_param('tab', MYACCOUNT_TAB_GENERAL);
  $url->add_param('action', 'edit');

  echo "<h3>"._("General")."</h3>\n";
  echo "<form action=\"./index.php\" method=\"post\">\n";
  echo $url->get_form();
  echo "<fieldset><legend>"._("User Information")."</legend>\n";
  echo "<ol>\n";

  echo "<li>";
  $this->label(_("First Name:"));
  $this->input_text("firstname", $user->get_firstname());
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Last Name:"));
  $this->input_text("lastname", $user->get_lastname());
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Email:"));
  $this->input_text("email", $user->get_email());
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Current Password:"));
  $this->input_password("oldpasswd");
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Password:"));
  $this->input_password("passwd");
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Confirm:"));
  $this->input_password("confirm");
  echo "</li>\n";

  echo "</ol>";
  echo "</fieldset>\n";

  echo "<fieldset><legend>"._("Default Access Rights")."</legend>\n";
  echo "<ol>\n";
  $gacl=$conf->get('image.gacl', (ACL_EDIT | ACL_PREVIEW));
  $macl=$conf->get('image.macl', ACL_PREVIEW);
  $aacl=$conf->get('image.aacl', ACL_PREVIEW);

  $acl=new SectionAcl($gacl, $macl, $aacl);
  $acl->print_table(false);
  echo "</li>\n";
  echo "</ol>"; 
  echo "</fieldset>\n"; 

  echo "<fieldset><legend>"._("Other")."</legend>\n";
  echo "<ol>";
  echo "<li>";
  $this->label(_("Metadata Seperator:"));
  echo "<select size=\"1\" name=\"meta_separator\">\n";
  $sep=array( " " => _("Space"), 
              "," => _("Comma"),
              "." => _("Dot"),
              ";" => _("Semicolon"));
  $cur_sep=$conf->get('meta.separator', ';');
  foreach ($sep as $k => $v)
  {
    $this->option($v, $k, ($k==$cur_sep));
  }
  echo "</select>\n";
  echo "</li>\n";
  
  echo "<li>";
  $this->label(_("Lazy Sync:"));
  echo "<p>";
  $this->input_checkbox("lazysync", 1, ($conf->get('image.lazysync', '0')==1));
  $url->add_param("action", "imagesync");
  echo "<a href=\"".$url->get_url()."\">"._("Syncronize images now")."</a>\n";
  echo "</p>";
  echo "</li>\n";
  echo "</ol>";
  echo "</fieldset>\n";

  echo "<div class=\"buttons\">";
  $this->input_submit(_("Apply"));
  $this->input_reset(_("Reset"));
  echo "</div>\n";

  echo "</form>\n\n";
}

function exec_general ()
{
  global $user;
  global $conf;

  $action="";
  if (isset($_REQUEST['action']))
    $action=$_REQUEST['action'];

  if($action=='edit')
  {
    if (isset($_REQUEST['email']))
      $user->set_email($_REQUEST['email']);
    if (isset($_REQUEST['firstname']))
      $user->set_firstname($_REQUEST['firstname']);
    if (isset($_REQUEST['lastname']))
      $user->set_lastname($_REQUEST['lastname']);

    if ($_REQUEST['passwd']!='' &
      $_REQUEST['oldpasswd']!='' &&
      $_REQUEST['confirm']!='')
    {
      $oldpasswd=$_REQUEST['oldpasswd'];
      $passwd=$_REQUEST['passwd'];
      $confirm=$_REQUEST['confirm'];
      if ($passwd!==$confirm)
        $this->error(_("Passwords do not match together"));
      else {
        $result=$user->passwd($oldpasswd, $passwd);
        if ($result<0)
          $this->error(sprintf(_("Could not set the password. Error %d"), $result));
        else
          $this->success(_("Password was changed successfully"));
      }
    }

    $user->commit();

    $acl=new Acl(
      $conf->get('image.gacl', (ACL_EDIT|ACL_PREVIEW)),
      $conf->get('image.macl', (ACL_PREVIEW)),
      $conf->get('image.aacl', (ACL_PREVIEW)));
    $acl->handle_request();
    $conf->set('image.gacl', $acl->get_gacl());
    $conf->set('image.macl', $acl->get_macl());
    $conf->set('image.aacl', $acl->get_aacl());

    $sep=$_REQUEST['meta_separator'];
    if ($sep==' ' || $sep=='.' || $sep==';' || $sep==',')
      $conf->set('meta.separator', $sep);
    
    $lazysync=$_REQUEST['lazysync'];
    if ($lazysync==1)
      $conf->set('image.lazysync', 1);
    else
      $conf->set('image.lazysync', 0);
  
    return;
  }
  elseif ($action=="imagesync")
  {
    if ($user->get_id()<=0 || !$user->is_member())
      return;

    $sync=new ImageSync();
    list($count, $updated, $deleted)=$sync->sync_files($user->get_id());
    $this->success(sprintf(_("Files are now synchronized (%d in total, %d updated, %d deleted)"), $count, $updated, $deleted));
  }
}

function print_upload ()
{
  global $user;

  echo "<h3>"._("Upload")."</h3>\n";
  $url=new Url();
  $url->add_param('section', 'myaccount');
  $url->add_param('tab', MYACCOUNT_TAB_UPLOAD);
  $url->add_param('action', 'upload');

  $qslice=$user->get_qslice();
  $qinterval=$user->get_qinterval();
  $quota=$user->get_quota();
  $used=$user->get_image_bytes(true);
  $upload_max=$user->get_upload_max();
  printf(_("You have %.3f MB already uploaded. Your total limit is %.3f MB. Currently you are allowed to upload %.3f MB."), $used/(1024*1024), $quota/(1024*1024), $upload_max/(1024*1024));

  echo "<form action=\"./index.php\" method=\"post\" enctype=\"multipart/form-data\">\n";
  echo $url->get_form();
  echo "<div class=\"upload_files\" \>\n";
  echo "<table id=\"upload_files\"><tbody>
  <tr id=\"upload-1\">
    <td>"._("Upload image:")."</td>
    <td>
      <input name=\"images[]\" type=\"file\" size=\"40\" />
      <a href=\"javascript:void(0)\" class=\"jsbutton\" onclick=\"add_file_input(1,'"._("Remove file")."')\">"._("Add another file")."</a>
    </td>
  </tr>
</tbody></table>\n";
  echo "</div>\n";

  echo "<div class=\"buttons\">";
  $this->input_submit(_("Upload"));
  echo "</div>\n";

  echo "</form>\n";
}

function exec_upload()
{
  $upload=new SectionUpload();
  $upload->upload_process();
}

function print_details()
{
  global $user;
  echo "<h3>"._("Details")."</h3>\n";
  
  echo "<table>
  <tr>
    <td></td>
    <td>"._("Count")."</td>
    <td>"._("Size")."</td>
  </tr>
  <tr>
    <td>"._("Total Images:")."</td>
    <td>".$user->get_image_count()."</td>
    <td>".sprintf(_("%.2f MB"), $user->get_image_bytes()/(1024*1024))."</td>
  </tr>
  <tr>
    <td>"._("Uploded Images:")."</td>
    <td>".$user->get_image_count(true)."</td>
    <td>".sprintf(_("%.2f MB"), $user->get_image_bytes(true)/(1024*1024))."</td>
  </tr>
</table>\n";
}

function exec_groups()
{
  global $user;

  if ($_REQUEST['action']=='add' &&
    isset($_REQUEST['name']))
  {
    $name=$_REQUEST['name'];
    if ($name=='')
      return;
    $groups=$user->get_groups();
    if (count($groups)>GROUP_MAX)
      return;
    $group=new Group();
    $result=$group->create($_REQUEST['name']);
    if ($result<0)
    {
      $this->error(_("The group could not created"));
      return;
    }
    $this->success(_("The group could be created"));
  }
  elseif ($_REQUEST['action']=='remove' &&
    isset($_REQUEST['gid']))
  {
    $gid=$_REQUEST['gid'];
    $group=new Group($gid);
    if ($group->get_id()!=$gid)
      return;
    $name=$group->get_name();
    $group->delete();
    unset($group);
    $this->success(sprintf(_("The group '%s' was successfully deleted"), $name));
    // remove group id from request to jumb to the group list overview
    unset($_REQUEST['gid']);
  }
  elseif ($_REQUEST['action']=='none' &&
    isset($_REQUEST['add_member']) &&
    isset($_REQUEST['gid']))
  {
    $id=$_REQUEST['gid'];
    $name=$_REQUEST['add_member'];
    $group=new Group($id);
    if ($group->get_id()!=$id)
      return;
    if ($group->get_num_members()>GROUP_MEMBER_MAX)
    {
      $this->warning(_("Your group member list full."));
      return;
    }
    if ($group->add_member($name))
      $this->success(sprintf(_("Member '%s' was successfully added to your group '%s'"), $name, $group->get_name()));
    else
      $this->warning(sprintf(_("Member '%s' could not added to your group '%s'"), $name, $group->get_name()));
  }
  elseif ($_REQUEST['action']=='remove_member' &&
    isset($_REQUEST['gid']) && 
    isset($_REQUEST['name']))
  {
    $id=$_REQUEST['gid'];
    $name=$_REQUEST['name'];
    $group=new Group($id);
    if ($group->get_id()!=$id)
      return;
    if ($group->remove_member($name))
      $this->success(sprintf(_("Member '%s' was deleted from group '%s'"), $name, $group->get_name()));
  }
  elseif ($_REQUEST['action']=='remove_members' &&
    isset($_REQUEST['gid']) && 
    isset($_REQUEST['members']))
  {
    $id=$_REQUEST['gid'];
    $members=$_REQUEST['members'];
    $group=new Group($id);
    if ($group->get_id()!=$id)
      return;
    foreach ($members as $name)
      if ($group->remove_member($name))
        $this->success(sprintf(_("Member '%s' was deleted from group"), $name));
  }
  elseif ($_REQUEST['action']=='copy_members' &&
    isset($_REQUEST['gid']) && 
    isset($_REQUEST['dst_gid']) && 
    isset($_REQUEST['members']))
  {
    $id=$_REQUEST['gid'];
    $dst_id=$_REQUEST['dst_gid'];
    $members=$_REQUEST['members'];
    $group=new Group($id);
    if ($group->get_id()!=$id)
      return;
    $dst_group=new Group($dst_id);
    if ($dst_group->get_id()!=$dst_id)
      return;
    $success=0;
    $failed=0;
    foreach ($members as $name)
    {
      if (!$group->has_member($name))
        continue;
      if ($dst_group->get_num_members()>GROUP_MEMBER_MAX)
        continue;
      if ($dst_group->add_member($name))
        $success++;
      else 
        $failed++;
    }
    if ($success==1)
      $this->success(sprintf(_("Member '%s' was copied from group '%s' to group '%s'"), $name, $group->get_name(), $dst_group->get_name()));
    elseif ($success>1)
      $this->success(sprintf(_("%d members were copied from group '%s' to group '%s'"), $success, $group->get_name(), $dst_group->get_name()));
  }
  elseif ($_REQUEST['action']=='move_members' &&
    isset($_REQUEST['gid']) && 
    isset($_REQUEST['dst_gid']) && 
    isset($_REQUEST['members']))
  {
    $id=$_REQUEST['gid'];
    $dst_id=$_REQUEST['dst_gid'];
    $members=$_REQUEST['members'];
    $group=new Group($id);
    if ($group->get_id()!=$id)
      return;
    $dst_group=new Group($dst_id);
    if ($dst_group->get_id()!=$dst_id)
      return;
    $success=0;
    $failed=0;
    foreach ($members as $name)
    {
      if (!$group->has_member($name))
        continue;
      if ($dst_group->get_num_members()>GROUP_MEMBER_MAX)
        continue;
      if ($dst_group->add_member($name))
      {
        $group->remove_member($name);
        $success++;
      }
      else 
        $failed++;
    }
    if ($success==1)
      $this->success(sprintf(_("Member '%s' was moved from group '%s' to group '%s'"), $name, $group->get_name(), $dst_group->get_name()));
    elseif ($success>1)
      $this->success(sprintf(_("%d members were moved from group '%s' to group '%s'"), $success, $group->get_name(), $dst_group->get_name()));
  }
}

function print_group_list()
{
  global $user;

  echo "<h3>"._("Groups")."</h3>\n";

  $url=new Url();
  $url->add_param('section', 'myaccount');
  $url->add_param('tab', MYACCOUNT_TAB_GROUPS);

  $groups=$user->get_groups();
  // Group Tables
  if (count($groups)>0)
  {
    $url->add_param('action', 'edit');
    echo "<table>
    <tr>
      <th>"._("Name")."</th>
      <th>"._("Members")."</th>
      <th></th>
    </tr>\n";
    foreach ($groups as $gid => $name)
    {
      $group=new Group($gid);
      if ($group->get_id()!=$gid)
        continue;
      $url->add_param('gid', $gid);
      echo "  <tr>
      <td><a href=\"".$url->get_url()."\">".$group->get_name()."</a></td>
      <td>".$group->get_num_members()."</td>\n";
      $url->add_param("action", "remove");
      echo "<td><a href=\"".$url->get_url()."\" class=\"jsbutton\">"._("Remove")."</a></td>
    </tr>\n";
      $url->del_param("action");
      unset($group);
    }
    echo "</table>\n";
    $url->del_param('gid');
  }
  else
  {
    echo "<p>"._("Currently you have now groups defined.")."</p>";
  }
  echo "<p>"._("Groups are used for the access right managment. Images can be
  assigned to groups which have different access rights. You can manage the
  groups here and can add or remove other phtagr members or guests to your
  groups.")."</p>";

  if (count($group)<11) 
  {
    echo "<h3>"._("Add new group")."</h3>";
    echo "<p>".sprintf(_("Add a new group here. You are allowed to create %d different
    groups."), GROUP_MAX)."</p>";
    $url->add_param('action', 'add');
    echo "<form action=\"./index.php\" method=\"post\">\n";
    echo $url->get_form();
    $this->input_text('name');

    echo "<div class=\"buttons\">";
    $this->input_submit(_("Add"));
    echo "</div>\n";

    echo "</form>\n";
  }
}

function print_group($gid)
{
  global $user;

  $group=new Group($gid);
  if ($group->get_id()!=$gid)
  {
    $this->warning(sprintf(_("Could not load group with ID %d"),$gid));
    return;
  }
  $url=new Url();
  $url->add_param('section', 'myaccount');
  $url->add_param('tab', MYACCOUNT_TAB_GROUPS);
  $url->add_param('gid', $gid);

  echo "<h3>"._("Group").": ".$group->get_name()."</h3>\n";
  echo "<form action=\"./index.php\" method=\"post\">\n";
  echo $url->get_form();

  // Group Tables
  $members=$group->get_members();
  if (count($members)>0)
  {

    $url->add_param('action', 'remove_member');
    echo "<table>
    <tr>
      <th></th>
      <th>"._("Members")."</th>
      <th></th>
    </tr>\n";
    foreach ($members as $uid => $name)
    {
      $url->add_param('name', $name);
      echo "  <tr>
      <td><input type=\"checkbox\" name=\"members[]\" value=\"".$name."\"/></td>
      <td>".$name."</td>
      <td><a href=\"".$url->get_url()."\" class=\"jsbutton\">"._("Delete")."</a></td>
    </tr>\n";
    }
    echo "</table>\n";
    $url->del_param('action');
    $url->del_param('name');

    echo "<select size=\"1\" name=\"action\">\n";
    $this->option(_("Select action"), 'none');
    $this->option(_("Delete"), 'remove_members');
    $this->option(_("Copy"), 'copy_members');
    $this->option(_("Move"), 'move_members');
    echo "</select>\n";
    $groups=$user->get_groups();
    echo " to <select size=\"1\" name=\"dst_gid\">\n";
    $this->option(_("Select Group"), 'none');
    // List all other groups without itself
    if (count($groups)>1)
    {
      foreach ($groups as $dst => $name)
      {
        if ($dst==$gid) continue;
        $this->option($name, $value);
      }
    }
    echo "</select>\n";
    echo "<div class=\"buttons\">";
    $this->input_submit(_("Execute"));
    echo "</div>\n";
  }
  else
  {
    echo _("Currently you have now members in the group");
    echo "<br />\n";
    $this->input_hidden('action', 'none');
  }

  if ($group->get_num_members()<=GROUP_MEMBER_MAX)
  {
    echo "<h3>"._("Add new group member")."</h3>\n";
    echo "<input type=\"text\" name=\"add_member\" />
    <input type=\"submit\" class=\"submit\" value=\""._("Add member")."\" />\n";
    echo "</form>\n";

    $url=new Url();
    $url->add_param('section', 'myaccount');
    $url->add_param('tab', MYACCOUNT_TAB_GROUPS);
  }

  echo "<a href=\"".$url->get_url()."\" class=\"jsbutton\">"._("Show all groups")."</a>\n";
}

function print_groups()
{
  global $user;
  global $db;
  global $conf;

  if (isset($_REQUEST['gid']))
    $this->print_group($_REQUEST['gid']);
  else
    $this->print_group_list();
}

function exec_guests()
{
  global $user;

  if ($_REQUEST['action']=='add' &&
    isset($_REQUEST['name']) &&
    isset($_REQUEST['passwd']) &&
    isset($_REQUEST['confirm']))
  {
    $name=$_REQUEST['name'];
    if ($name=='')
      return;
    $pwd=$_REQUEST['passwd'];
    $confirm=$_REQUEST['confirm'];
    if ($pwd!=$confirm)
      return;

    $guests=$user->get_guests();
    if (count($guests)>GUEST_MAX)
      return;

    $result=$user->create_guest($name, $pwd);
    if ($result<0)
    {
      $this->error(sprintf(_("The guest '%s' could not created. Error %d"), $name, $result));
      return;
    }
    $this->success(_("The guest could be created"));
  }
  elseif ($_REQUEST['action']=='remove' &&
    isset($_REQUEST['guestid']))
  {
    $gid=$_REQUEST['guestid'];
    $guest=new User($gid);
    if ($guest->get_id()!=$gid)
      return;
    $name=$guest->get_name();
    $guest->delete();
    unset($guest);
    $this->success(sprintf(_("The guest '%s' was successfully deleted"), $name));
    // remove group id from request to jumb to the group list overview
    unset($_REQUEST['guestid']);
  }
  elseif ($_REQUEST['action']=='update' &&
    isset($_REQUEST['guestid']))
  {
    $gid=$_REQUEST['guestid'];
    $guest=new User($gid);
    if ($guest->get_id()!=$gid)
      return;

    if (isset($_REQUEST['oldpasswd']) &&
      isset($_REQUEST['passwd']) &&
      isset($_REQUEST['confirm']) &&
      strlen($_REQUEST['oldpasswd'])>0 &&
      strlen($_REQUEST['passwd'])>0)
    {
      $oldpasswd=$_REQUEST['oldpasswd'];
      $passwd=$_REQUEST['passwd'];
      $confirm=$_REQUEST['confirm'];
      
      if ($passwd!=$confirm)
      {
        $this->error(_("Passwords do not match!"));
        return;
      } else {
        $name=$guest->get_name();
        $result=$guest->passwd($oldpasswd, $passwd);
        if ($result==0)
          $this->success(sprintf(_("The password of guest '%s' was successfully changed"), $name));
        elseif ($result==ERR_PASSWD_MISMATCH)
          $this->error(_("The password does not match."));
        elseif ($result==ERR_USER_PWD_INVALID)
          $this->error(_("The new password is invalid."));
        elseif ($result==ERR_NOT_PERMITTED)
          $this->error(_("You are not permitted to change the password."));
        else
          $this->error(sprintf(_("An unknown error occured (Error number %d)"), $result));
      }
    }

    if (isset($_REQUEST['expire']) &&
      strlen($_REQUEST['expire']>=4))
    {
      $expire=$_REQUEST['expire'];
      if ($guest->set_expire($expire))
      {
        $guest->commit();
        $this->success(_("Expire date successfully changed"));
      }
    }
    unset($guest);
  }
}

function print_guest_list()
{
  global $user;

  echo "<h3>"._("Guests")."</h3>\n";

  $url=new Url();
  $url->add_param('section', 'myaccount');
  $url->add_param('tab', MYACCOUNT_TAB_GUESTS);

  $guests=$user->get_guests();
  // Guests Tables
  if (count($guests)>0)
  {
    $url->add_param('action', 'edit');
    echo "<table>
    <tr>
      <th>"._("Name")."</th>
      <th></th>
    </tr>\n";
    foreach ($guests as $guestid => $name)
    {
      $url->add_param('guestid', $guestid);
      echo "  <tr>
      <td><a href=\"".$url->get_url()."\">".$name."</a></td>\n";
      $url->add_param("action", "remove");
      echo "<td><a href=\"".$url->get_url()."\" class=\"jsbutton\">"._("Remove")."</a></td>
    </tr>\n";
      $url->del_param("action");
      unset($group);
    }
    echo "</table>\n";
    $url->del_param('guestid');
  }
  else
  {
    echo _("Currently your guest list is empty");
  }
  echo "<p>"._("A guest can view all images, which is assigned to the guest's
  group.")."</p>";

  if (count($guests)<11) 
  {
    echo "<h3>"._("New guest account")."</h3>\n";
    echo "<p>".sprintf(_("Add a new guest account here. You are allowed to create %d guest accounts."), GUEST_MAX)."</p>";
    $url->add_param('action', 'add');
    echo "<form action=\"./index.php\" method=\"post\">\n";
    echo $url->get_form();

    echo "<table>
  <tr>
    <td>"._("Name:")."</td>
    <td><input type=\"text\" name=\"name\" /></td>
  </tr>
  <tr>
    <td>"._("Password:")."</td>
    <td><input type=\"password\" name=\"passwd\" /></td>
  </tr>
  <tr>
    <td>"._("Confirm:")."</td>
    <td><input type=\"password\" name=\"confirm\" /></td>
  </tr>
  <tr>
    <td></td>
    <td><input type=\"submit\" value=\""._("New guest")."\" /></td>
  </tr>
</table>\n";
    echo "</form>\n";
  }
}

function print_guest($guestid)
{
  global $user;

  $guest=new User($guestid);
  if ($guest->get_id()!=$guestid)
  {
    $this->warning(sprintf(_("Could not load guest with ID %d"),$guestid));
    return;
  }
  $url=new Url();
  $url->add_param('section', 'myaccount');
  $url->add_param('tab', MYACCOUNT_TAB_GUESTS);
  $url->add_param('action', 'update');
  $url->add_param('guestid', $guestid);

  echo "<h3>"._("Guest").": ".$guest->get_name()."</h3>\n";
  echo "<form action=\"./index.php\" method=\"post\">\n";
  echo $url->get_form();

  echo "<table>
  <tr>
    <td>"._("Expires:")."</td>
    <td><input type='text' name='expire' value='".$guest->get_expire()."'/></td>
  </tr>
  <tr>
    <td>"._("Old Password:")."</td>
    <td><input type=\"password\" name=\"oldpasswd\" /></td>
  </tr>
  <tr>
    <td>"._("New Password:")."</td>
    <td><input type='password' name='passwd' /></td>
  </tr>
  <tr>
    <td>"._("Confirm:")."</td>
    <td><input type='password' name='confirm' /></td>
  </tr>
  <tr>
    <td></td>
    <td>
      <input type=\"submit\" value=\""._("Save changes")."\" />
    </td>
</table>\n";

  echo "</form>\n";

  $url=new Url();
  $url->add_param('section', 'myaccount');
  $url->add_param('tab', MYACCOUNT_TAB_GUESTS);
  echo "<a href=\"".$url->get_url()."\" class=\"jsbutton\">"._("Show all guests")."</a>\n";
}

function print_guests()
{
  global $user;
  global $db;
  global $conf;

  if (isset($_REQUEST['guestid']))
    $this->print_guest($_REQUEST['guestid']);
  else
    $this->print_guest_list();
}

function print_content()
{
  global $db;
  global $user;
  
  echo "<h2>"._("My Account")."</h2>\n";
  $tabs2=new SectionMenu('tab', _("Actions:"));
  $tabs2->add_param('section', 'myaccount');
  $tabs2->set_item_param('tab');

  $tabs2->add_item(MYACCOUNT_TAB_UPLOAD, _("Upload"));
  $tabs2->add_item(MYACCOUNT_TAB_GROUPS, _("Groups"));
  $tabs2->add_item(MYACCOUNT_TAB_GUESTS, _("Guests"));
  $tabs2->add_item(MYACCOUNT_TAB_GENERAL, _("General"));
  $tabs2->add_item(MYACCOUNT_TAB_DETAILS, _("Details"));
  if (isset($_REQUEST['tab']))
    $cur=intval($_REQUEST['tab']);
  else
    $cur=MYACCOUNT_TAB_UPLOAD;
  $tabs2->set_current($cur);
  $tabs2->print_sections();
  $cur=$tabs2->get_current();

  echo "\n";

  if (isset($_REQUEST["action"]))
  {
    switch ($cur)
    {
    case MYACCOUNT_TAB_UPLOAD: 
      $this->exec_upload();
      break;
    case MYACCOUNT_TAB_GENERAL:
      $this->exec_general();
      break;
    case MYACCOUNT_TAB_GROUPS:
      $this->exec_groups();
      break;
    case MYACCOUNT_TAB_GUESTS:
      $this->exec_guests();
      break;
    default:
      $this->warning(_("No action found for this tab"));
      break;
    }
  }

  switch ($cur)
  {
  case MYACCOUNT_TAB_UPLOAD: 
    $this->print_upload(); 
    break;
  case MYACCOUNT_TAB_DETAILS: 
    $this->print_details(); 
    break;
  case MYACCOUNT_TAB_GROUPS: 
    $this->print_groups(); 
    break;
  case MYACCOUNT_TAB_GUESTS: 
    $this->print_guests(); 
    break;
  default:
    $this->print_general(); 
    break;
  }
}

}
?>
