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
include_once("$phtagr_lib/ImageSync.php");
include_once("$phtagr_lib/Upgrade.php");

define("ADMIN_TAB_USERS", "1");
define("ADMIN_TAB_CREATE_USER", "2");
define("ADMIN_TAB_LOG", "3");
define("ADMIN_TAB_DEBUG", "4");

class SectionAdmin extends SectionBase
{

function SectionAdmin()
{
  $this->SectionBase("administration");
}

/** Prints the details of an user
  @param u User object */
function print_user_details($u=null) 
{
  global $log;
  if ($u==null)
    return;
  $c=new Config($u->get_id());
  
  $this->h3(sprintf(_("Editing User: %s"), $u->get_name()));

  // If we don't have a update request we show all the values we can
  // update.
  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_USERS);
  $url->add_param('action', 'edit');
  $url->add_param('id', $u->get_id());
  echo "<form action=\"".$url->get_url()."\" method=\"post\">\n";

  echo "<fieldset><ol>";

  echo "<li>";
  $this->label(_("First Name:"));
  $this->input_text('firstname', $u->get_firstname());
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Last Name:"));
  $this->input_text('lastname', $u->get_lastname());
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Email:"));
  $this->input_text('email', $u->get_email());
  echo "</li>\n";

  echo "</ol></fieldset>\n";

  $this->h3(_("Quota"));

  $this->p(_("You can set the quoata limit for uploads of the user. Quota is
the absolut limit. Quota Slice is the size which can be uploaded by the user
within the time of Quota Interval"));

  echo "<fieldset><ol>";

  echo "<li>";
  $this->label(_("Quota (MB):"));
  $this->input_text('quota', sprintf("%.2f", $u->get_quota()/1048576));
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Quota Slice (MB):"));
  $this->input_text('qslice', sprintf("%.2f", $u->get_qslice()/1048576));
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Quota Interval (Days):"));
  $this->input_text('qinterval', sprintf("%.2f", $u->get_qinterval()/86400));
  echo "</li>\n";

  echo "</ol></fieldset>\n";

  
  $this->h3(_("Webdav"));
  $this->p(_("Allow the webdav access to the user"));
  echo "<fieldset><ol>";

  echo "<li>";
  $this->label(_("Enable Webdav:"));
  $log->info($c->get('webdav.enabled'));
  $this->input_checkbox('enable_webdav', 1, ($c->get('webdav.enabled', 0)==1));
  echo "</li>\n";

  echo "</ol></fieldset>\n";

  $this->h3(_("Filesystem"));

  $this->p(_("Here you can specify system directories for a user which are
browsable for this user. The user can import files from these system
direcories."));

  echo "<table>\n";
  $roots=$c->get('path.fsroot[]', null);
  if ($roots!=null)
  {
    foreach($roots as $root)
    {
      echo "<tr><td></td><td>".$this->escape_html($root)." ";
      $url->add_param('remove_root', $root);
      echo "<a href=\"".$url->get_url()."\" class=\"jsbutton\">"._("Remove")."</a></td></tr>\n";
    }
    $url->del_param('remove_root');
  }
  echo "<tr><td>"._("Add root")."</td>\n";
  echo "<td><input type=\"text\" name=\"add_root\" /></td></tr>\n";

  echo "</table>\n";

  echo "<div class=\"buttons\">";
  $this->input_submit(_("Save"));
  $this->input_reset(_("Reset"));
  echo "</div>\n";

  echo "</form>\n\n";

  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_USERS);
  echo "<div style=\"clear: both\"><a href=\"".$url->get_url()."\" class=\"jsbutton\">"._("Back")."</a></div>\n";
}

function exec_users()
{
  global $user, $log;

  if (!isset($_REQUEST['action']) ||!isset($_REQUEST['id']))
    return false;

  $id=intval($_REQUEST['id']);
  if ($id<0)
    return false;

  $account=new SectionAccount();
  $action=$_REQUEST['action'];
  if ($action=='delete')
  {
    if (!$user->exists($id))
    {
      $this->error(_("User does not exists"));
      return;
    }
    if ($user->get_num_users(false)==1)
    {
      $this->error(_("The deletion of the last user is not allowed"));
      return;
    }
    if ($user->get_id()!=$id)
      $u=new User($id);
    else 
      $u=$user;
    $name=$u->get_name();
    $err=$u->delete();
    switch ($err)
    {
      case 0:
        $this->success(sprintf(_("The user '%s' was successfully deleted"), $name));
        break;
      case ERR_NOT_PERMITTED:
        $this->warning("You are not allowed to delete an user!");
        break;
      default:
        $this->error(sprintf(_("The user '%s' could not deleted. An error occured (%d)"), $name, $err));
    }
  }
  else if ($action=="edit")
  {
    $log->info("Edit user $id");
    $u=new User($id);
    $c=new Config($id);

    // If 'email' is set in the request, we assume that this current request
    // is already an update request with all the values we want to update.
    if (isset($_REQUEST['email']))
      $u->set_email($_REQUEST['email']);
    if (isset($_REQUEST['firstname']))
      $u->set_firstname($_REQUEST['firstname']);
    if (isset($_REQUEST['lastname']))
      $u->set_lastname($_REQUEST['lastname']);

    // Quota
    if (isset($_REQUEST['quota']))
      $u->set_quota($_REQUEST['quota']*1048576);
    if (isset($_REQUEST['qslice']))
      $u->set_qslice($_REQUEST['qslice']*1048576);
    if (isset($_REQUEST['qinterval']))
      $u->set_qinterval($_REQUEST['qinterval']*86400);

    // Webdav
    if (isset($_REQUEST['enable_webdav']))
    {
      if ($_REQUEST['enable_webdav']==1)
        $c->set('webdav.enabled', 1);
      else
        $c->set('webdav.enabled', 0);
    }
    // Filesystem roots
    if (isset($_REQUEST['add_root'])&& $_REQUEST['add_root']!='')
    {
      $root=$_REQUEST['add_root'];
      if (!is_dir($root) || !is_readable($root))
        $this->error(_("Path could not be added, because given root is not a dirctory or readable."));
      else
        $c->set('path.fsroot[]', $root);
    }
    
    if (isset($_REQUEST['remove_root']) &&
      strlen($_REQUEST['remove_root'])>0)
      $c->del('path.fsroot[]', $_REQUEST['remove_root']);

    $u->commit();

    $this->print_user_details($u);
    unset($u);
    unset($c);
  }

}

function print_users()
{
  global $db;

  if (isset($_REQUEST['action']))
    return;

  echo "<h3>"._("Available Users")."</h3>\n";

  $sql="SELECT id
        FROM $db->users
        WHERE type!='".USER_GUEST."'";
  $result=$db->query($sql);
  if (!$result)
    return;

  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_USERS);
  echo "<form action=\"".$url->get_url()."\" method=\"post\">\n";
  
  echo "<table>
  <tr> 
    <th>"._("Name")."</th>
    <th>"._("Guests")."</th>
    <th>"._("Quota")."</th>
    <th>"._("Action")."</th>
  </tr>\n";
  while ($row=mysql_fetch_assoc($result))
  {
    $id=$row['id'];
    $u=new User($id);
    echo "<tr>";

    $url->add_param('id', $id);
    $url->add_param('action', 'edit');
    echo "<td><a href=\"".$url->get_url()."\">".$u->get_name()."</a></td>\n";

    echo "<td>".$u->get_num_guests()."</td>\n";
    echo "<td>".sprintf("%.1f MB (%d %% used)", 
      $u->get_quota()/(1024*1024),
      $u->get_quota_used()*100)."</td>\n";

    if ($id!=1)
    {
      $url->add_param('action', 'delete');
      echo "<td><a href=\"".$url->get_url()."\" class=\"jsbutton\">"._("Remove")."</a></td>\n";
    } else {
      echo "<td></td>\n";
    }
    echo "</tr>\n";
  } 
  echo "</table>\n";

  echo "</form>\n";
}

function exec_create_user()
{
  global $user;

  if (!isset ($_REQUEST['action']))
    return false;

  $account=new SectionAccount();

  $action=$_REQUEST['action'];
  if ($action=='create')
  {
    echo "<h3>"._("Creating account")."</h3>\n";
    $name=$_REQUEST['name'];
    $password=$_REQUEST['password'];
    $confirm=$_REQUEST['confirm'];
    if ($password != $confirm)
    {
      $this->error(_("Password mismatch"));
      return;
    }
    $err=$user->create($name, $password, USER_MEMBER);
    if ($err>0)
    {
      $this->success(sprintf(_("User '%s' created"), $name));
    }
    else
    {
      switch($err)
      {
        case ERR_NOT_PERMITTED:
          $this->warning(_("You are not permitted to create an user"));
          break;
        case ERR_USER_NAME_LEN:
          $this->error(_("The username must have at least 4 character and maximum of 32 character"));
          break;
        case ERR_USER_NAME_INVALID:
          $this->error(_("The username has invalid character"));
          break;
        case ERR_USER_ALREADY_EXISTS:
          $this->error(_("The username already exists"));
          break;
        case ERR_USER_PWD_LEN:
          $this->error(_("The password is to short or to long"));
          break;
        case ERR_USER_PWD_INVALID:
          $this->error(_("The password is invalid"));
          break;
        default:
          $this->error(sprintf(_("User '%s' could not be created. Error %d occur"), $name, $err));
      }
    }
  
    return;
  } 
}

function print_create_user()
{
  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_CREATE_USER);
  $url->add_param('action', 'create');

  $this->h3(_("Create User"));

  echo "<form action=\"".$url->get_url()."\" method=\"post\">\n";
  echo "<fieldset><ol>\n";

  echo "<li>";
  $this->label(_("Username:"));
  $this->input_text('name');
  echo "</li>";

  echo "<li>";
  $this->label(_("Password:"));
  $this->input_password('password');
  echo "</li>";

  echo "<li>";
  $this->label(_("Confirm:"));
  $this->input_password('confirm');
  echo "</li>";

  echo "<li>";
  $this->label(_("Email:"));
  $this->input_text('email');
  echo "</li>";

  echo "</ol></fieldset>\n";

  echo "<div class=\"buttons\">";
  $this->input_submit(_("Create"));
  $this->input_reset(_("Reset"));
  echo "</div>\n";

  echo "</form>\n\n";
}


function exec_upload ()
{
  global $db;
  global $conf;
  $request_upload_dir=$_REQUEST['set_dir'];
  if ($request_upload_dir != "")
  {
    $this->warning("NIY");
  }
}

function print_upload ()
{
  global $db;
  global $conf;
  $upload_dir = $conf->get('upload_dir', '');

  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_UPLOAD);
  $url->add_param('action', upload_dir);

  echo "<h3>"._("Upload Settings")."</h3>\n";

  echo "<form action=\"./index.php\" method=\"POST\">\n";

  echo "<p>"._("All uploads go below this folder. For each user a subfolder will be created under which his images will reside. If a file exists, it will be saved as FILENAME-xyz.EXTENSION.");
  echo $url->get_form();
  echo "<input type=\"text\" name=\"set_dir\" value=\"" . $upload_dir . "\" size=\"60\"/>\n";
  echo "<input type=\"submit\" value=\"Save\" class=\"submit\" />\n";
  echo "</form>\n";
  echo "</p>\n";
}

function exec_log()
{
  global $user, $log, $conf;
  if (!$user->is_admin())
    return;
 
  $type=$_REQUEST['backend'];
  $filename=$_REQUEST['filename'];
  $level=$_REQUEST['level'];

  // Parameter checks
  switch ($type)
  {
    case LOG_FILE:
      if (!strlen($filename))
      {
        $this->warning(_("Log file is not set"));
        return false;
      }
    case LOG_DB:
    case LOG_SESSION:
      break;
    default:
      $this->warning(_("Unknown log backend"));
      $log->warn('Unknown log backend: '.$type, -1, $user->get_id());
      return false;
  }

  switch ($level)
  {
    case LOG_INFO:
    case LOG_WARN:
    case LOG_DEBUG:
    case LOG_TRACE:
      break;
    default:
      $log->warn('Unknown log level: '.$level, -1, $user->get_userid());
      $this->warning(_("Unknown log level"));
      return false;
  }

  // Parameter Execution
  $conf->set_default('log.enable',$_REQUEST['enable']==1?1:0);
  $conf->set_default('log.type', $type);
  $conf->set_default('log.filename', $filename);
  $conf->set_default('log.level', $level);

  $this->success(_("Settings saved"));
}

function print_log()
{
  global $db, $conf;

  $this->h3(_("Log"));
  
  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_LOG);
  $url->add_param('action', 1);

  echo "<form action=\"".$url->get_url()."\" method=\"post\">\n";

  echo "<fieldset>
  <ol>\n";

  echo "<li>";
  $this->label(_("Enable Logging:"));
  $enabled=$conf->get('log.enable', 0);
  $this->input_checkbox('enable', 1, $enabled!=0);
  echo "</li>";

  echo "<li>";
  $this->label(_("Backend:"));
  echo "<select name=\"backend\" size=\"1\">\n";
  $type=$conf->get('log.type', -1);
  $this->option(_("File"), LOG_FILE, $type==LOG_FILE);
  $this->option(_("Database"), LOG_DB, $type==LOG_DB);
  $this->option(_("Session"), LOG_SESSION, $type==LOG_SESSION);
  echo "</select></li>";

  echo "<li>";
  $this->label(_("Filename:"));
  $this->input_text("filename", $conf->get('log.filename', ''));
  echo "</li>";

  echo "<li>";
  $this->label(_("Log Level:"));
  echo "<select name=\"level\" size=\"1\">\n";
  $level=$conf->get('log.level', LOG_INFO);
  $this->option(_("Info"), LOG_INFO, $level==LOG_INFO);
  $this->option(_("Warn"), LOG_WARN, $level==LOG_WARN);
  $this->option(_("Debug"), LOG_DEBUG, $level==LOG_DEBUG);
  $this->option(_("Trace"), LOG_TRACE, $level==LOG_TRACE);
  echo "</select></li>";

  echo "</ol>\n";
  echo "</fieldset>"; 

  echo "<div class=\"buttons\">";
  $this->input_submit(_("Apply"));
  $this->input_reset(_("Reset"));
  echo "</div>\n";
  echo "</form>\n";
}

function exec_debug()
{
  global $db;
  $action="";

  if (!isset($_REQUEST["action"]))
    return;
  else
    $action=$_REQUEST["action"];

  if ($action=="sync")
  {
    echo "<h3>"._("Synchroning image data...")."</h3>\n";
    $this->info(_("This operation may take some time"));
    $sync=new ImageSync();
    list($count, $updated, $deleted)=$sync->sync_files();
    if ($count>=0) {
      $this->success(sprintf(_("All %d files where synchronized. %d were updated, %d were deleted"), $count, $updated, $deleted));
    } else {
      $this->error(sprintf(_("Synchronization of files failed. Error %d returned"), $count));
    }
    unset($sync);
  }
  else if ($action=="delete_unassigned_data")
  {
    echo "<h3>"._("Deleting unassigned meta data...")."</h3>\n";
    $affected=$db->delete_unassigned_data();
    $this->warning(sprintf(_("Deleted %d unassigned data."), $affected));
  }
  else if ($action=="delete_tables")
  {
    echo "<h3>"._("Deleting Tables...")."</h3>\n";
    $db->delete_tables();
    $this->warning(_("Tables deleted!"));
  }
  else if ($action=="delete_images")
  {
    echo "<h3>Deleting Images...</h3>\n";
    $db->delete_images();
    $this->warning(_("All image data are deleted"));
  }
  else if ($action=="create_all_previews")
  {
    echo "<h3>"._("Creating preview images...")."</h3>\n";
    $this->info(_("This operation may take some time"));
    $previewer=new PreviewBase();
    $previewer->create_all_previews();
    $this->success(_("All previews are now created"));
  }
  else if ($action=="upgrade")
  {
    echo "<h3>"._("Upgrade")."</h3>\n";
    $upgrade=new Upgrade();
    if ($upgrade->is_upgradable())
    {
      $upgrade->do_upgrade();
      $old=$upgrade->get_old_version();
      $cur=$upgrade->get_cur_version();
      $this->success(sprintf(_("Your phtagr instance was upgraded from version %d to version %d."), $old, $cur));
    }
    else
    {
      $this->info(_("Your phtagr instance is up-to-date"));
    }
  }
  else
  {
   $this->error(_("Unknown action: "). $action);
  }
}

function print_debug ()
{
  echo "<h3>"._("Debug")."</h3>\n";
  $this->warning(_("Please handle these operations carefully!"));
  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_DEBUG);
  echo "<ul>\n";
  echo "<li>";
  $url->add_param('action', 'sync'); $href=$url->get_url();
  echo "<a href=\"$href\">"._("Synchronize files with the database")."</a></li>\n";
  $url->add_param('action', 'delete_unassigned_data'); $href=$url->get_url();
  echo "<li><a href=\"$href\">"._("Delete unassigned meta data")."</a></li>\n";
  $url->add_param('action', 'delete_tables'); $href=$url->get_url();
  echo "<li><a href=\"$href\">"._("Delete Tables")."</a></li>\n";
  $url->add_param('action', 'delete_images'); $href=$url->get_url();
  echo "<li><a href=\"$href\">"._("Delete all images")."</a></li>\n";
  $url->add_param('action', 'create_all_previews'); $href=$url->get_url();
  echo "<li><a href=\"$href\">"._("Create all preview images")."</a></li>\n";
  $url->add_param('action', 'upgrade'); $href=$url->get_url();
  echo "<li><a href=\"$href\">"._("Upgrade this instance")."</a></li>\n";
  $url->del_param('section');
  $url->del_param('page');
  $url->del_param('action');
  $href=$url->get_url();
  echo "<li><a href=\"$href\">"._("Go to phTagr")."</a></li>\n";
  echo "</ul>\n";
}

function print_content()
{
  global $db;
  global $user;
  
  echo "<h2>"._("Administration")."</h2>\n";

  $tabs=new SectionMenu('tab', _("Actions:"));
  $tabs->add_param('section', 'admin');
  $tabs->set_item_param('page');

  $tabs->add_item(ADMIN_TAB_USERS, _("Users"));
  $tabs->add_item(ADMIN_TAB_CREATE_USER, _("Create User"));
  $tabs->add_item(ADMIN_TAB_LOG, _("Logging"));
  $tabs->add_item(ADMIN_TAB_DEBUG, _("Debug"));

  $tabs->print_sections();
  $page=$tabs->get_current();

  // Execute Actions
  if (isset($_REQUEST["action"]))
  {
    switch ($page)
    {
    case ADMIN_TAB_USERS: 
      $this->exec_users();
      break;
    case ADMIN_TAB_CREATE_USER: 
      $this->exec_create_user();
      break;
    case ADMIN_TAB_DEBUG: 
      $this->exec_debug();
      break;
    case ADMIN_TAB_LOG: 
      $this->exec_log();
      break;
    default:
      break;
    }
  }

  // Print tab
  switch ($page)
  {
    case ADMIN_TAB_USERS:
      $this->print_users(); 
      break;
    case ADMIN_TAB_CREATE_USER: 
      $this->print_create_user(); 
      break;
    case ADMIN_TAB_LOG: 
      $this->print_log(); 
      break;
    case ADMIN_TAB_DEBUG: 
      $this->print_debug(); 
      break;
    default:
      $this->print_users(); 
      break;
  }
}

}
?>
