<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/SectionAccount.php");
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Thumbnail.php");
include_once("$phtagr_lib/Upgrade.php");

define("ADMIN_TAB_GENERAL", "0");
define("ADMIN_TAB_USERS", "1");
define("ADMIN_TAB_CREATE_USER", "2");
define("ADMIN_TAB_DEBUG", "3");

class SectionAdmin extends SectionBase
{

function SectionAdmin()
{
  $this->SectionBase("administration");
}

function exec_general ()
{
  global $db;
  global $conf;
  $result=false;

  if (isset ($_REQUEST['user_self_register']))
    $result=$conf->set_default('allow_user_self_register', '1');
  else
    $result=$conf->set_default('allow_user_self_register', '0');
  
  if ($result)
    $this->success("Settings saved successfully!");
  else
    $this->error("Settings could not be saved!");
}

function print_general ()
{
  global $db;
  global $conf;
  $user_self_register = $conf->get('allow_user_self_register', '0');

  echo "<h3>"._("General")."</h3>\n";

  echo "<form action=\"./index.php\" method=\"POST\">\n";

  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_GENERAL);
  $url->add_param('action', 'settings');
  echo $url->get_form();
  if ($user_self_register)
    echo "<input type=\"checkbox\" name=\"user_self_register\" checked/>";
  else
    echo "<input type=\"checkbox\" name=\"user_self_register\"/>";
  echo " Allow users to register themselves.\n";

  echo "<input type=\"submit\" class=\"submit\" value=\"Save\" />\n";
  echo "</form>\n";
  echo "<p></p>\n";
}

/** Prints the details of an user
  @param u User object */
function print_user_details($u=null) 
{
  if ($u==null)
    return;
  $c=new Config($u->get_id());

  echo "<h3>".sprintf(_("Editing User '%s'"), $u->get_name())."</h3>\n";

  // If we don't have a update request we show all the values we can
  // update.
  echo "<form action=\"./index.php\" method=\"post\">\n";

  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_USERS);
  $url->add_param('action', 'edit');
  $url->add_param('id', $u->get_id());
  echo $url->get_form();
  echo "<table>
  <tr>
    <td>"._("First Name:")."</td>
    <td><input type=\"text\" name=\"firstname\" value=\"".$u->get_firstname()."\" /><td>
  </tr>
  <tr>
    <td>"._("Last Name:")."</td>
    <td><input type=\"text\" name=\"lastname\" value=\"".$u->get_lastname()."\" /><td>
  </tr>
  <tr>
    <td>"._("Email:")."</td>
    <td><input type=\"text\" name=\"email\" value=\"".$u->get_email()."\" /><td>
  </tr>
</table>

<h3>"._("Quota")."</h3>\n";

echo "<p>"._("You can set the quoata limit for uploads of the user. Quota is the
absolut limit. Quota Slice is the size which can be uploaded by the user within
the time of Quota Interval")."</p>\n";

echo "<table>
  <tr>
    <td>"._("Quota (MB)")."</td>
    <td><input type=\"text\" name=\"quota\" value=\"".
      sprintf("%.2f", $u->get_quota()/1048576)."\" /><td>
  </tr>
  <tr>
    <td>"._("Quota Slice (MB)")."</td>
    <td><input type=\"text\" name=\"qslice\" value=\"".
      sprintf("%.2f", $u->get_qslice()/1048576)."\" /><td>
  </tr>
  <tr>
    <td>"._("Quota Interval (Days)")."</td>
    <td><input type=\"text\" name=\"qinterval\" value=\"".
      sprintf("%.2f", $u->get_qinterval()/86400)."\" /><td>
  </tr>
</table>
<h3>"._("Filesystem")."</h3>\n";

echo "<p>"._("Here you can specify the browsable paths for a user. The user can import files from these direcories.")."</p>\n";

echo "<table>\n";
  $roots=$c->get('path.fsroot[]', null);
  if ($roots!=null)
  {
    foreach($roots as $root)
    {
      echo "<tr><td></td><td>".htmlentities($root)." ";
      $url->add_param('remove_root', $root);
      echo "<a href=\"".$url->get_url()."\" class=\"jsbutton\">"._("Remove")."</td></tr>\n";
    }
    $url->del_param('remove_root');
  }
  echo "<tr><td>"._("Add root")."</td>\n";
  echo "<td><input type=\"text\" name=\"add_root\" /></td></tr>\n";

  echo "</table>
<input type=\"submit\" class=\"submit\"value=\"Save\"/>
<input type=\"reset\" class=\"reset\" value=\"Reset\"/>
</form>\n\n";
  return;
}

function exec_users()
{
  global $user;

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
    if ($id==1)
    {
      $this->error(_("The deletion of the admin is not allowed"));
      return;
    }
    if ($user->get_id()!=$id)
      $u=new User($id);
    else 
      $u=$user;
    $name=$u->get_name();
    $err=$u->delete();
    if ($err==0)
      $this->success(sprintf(_("The user '%s' was successfully deleted"), $name));
    else
      $this->error(sprintf(_("The user '%s' could not deleted. An error occured (%d)"), $name, $err));
  }
  else if ($action=="edit")
  {
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
      $c->remove('path.fsroot[]', $_REQUEST['remove_root']);

    $u->commit();

    $this->print_user_details($u);
    unset($u);
    unset($c);
  }

}

function print_users()
{
  global $db;

  echo "<h3>"._("Available Users")."</h3>\n";

  $sql="SELECT id
        FROM $db->user
        WHERE type!='".USER_GUEST."'";
  $result=$db->query($sql);
  if (!$result)
    return;

  echo "<form action=\"./index.php\" method=\"post\">\n";
  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_USERS);
  
  echo "<table>
  <tr> 
    <th>"._("Name")."</th>
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

    echo "<td>".sprintf("%.1f MB (%d %% used)", 
      $u->get_quota()/(1024*1024),
      $u->get_quota_used()*100)."</td>\n";

    if ($id!=1)
    {
      $url->add_param('action', 'delete');
      echo "<td><a href=\"".$url->get_url()."\" class=\"jsbutton\">"._("Remove")."</td>\n";
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
    if ($err<0)
    {
      $this->error(sprintf(_("User '%s' could not be created. Error %d occur"), $name, $err));
    } else {
      $this->success(sprintf(_("User '%s' created"), $name));
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

  echo "<h3>"._("Create User")."</h3>\n";
  echo "<form action=\"./index.php\" method=\"post\">\n";
  echo $url->get_form();
  echo "<table>
  <tr>
    <td>"._("Username:")."</td>
    <td><input type=\"text\" name=\"name\" value=\"$this->user\"/><td>
  </tr>
  <tr>
    <td>"._("Password:")."</td>
    <td><input type=\"password\" name=\"password\"/><td>
  </tr>   
  <tr>
    <td>"._("Confirm:")."</td>
    <td><input type=\"password\" name=\"confirm\"/><td>
  </tr>
  <tr>
    <td>"._("Email:")."</td>
    <td><input type=\"text\" name=\"email\"/><td>
  </tr>
  <tr>
    <td></td>
    <td><input type=\"submit\" class=\"submit\" value=\"Create\"/>&nbsp;&nbsp;
      <input type=\"reset\" class=\"reset\" value=\"Reset\"/></td>
  </tr>
</table>
</form>\n\n";
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

/** Static callback function for image deletion 
  @param id Image ID to delete */
static function cb_delete($id)
{
  $thumb=new Thumbnail($id);
  $thumb->delete();
  unset($thumb);
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
    $thumb=new Thumbnail();
    list($count, $updated, $deleted)=$thumb->sync_files(array("SectionAdmin", "cb_delete"));
    if ($count>0) {
      $this->success(sprintf(_("All %d files where synchronized. %d were updated, %d were deleted"), $count, $updated, $deleted));
    } else {
      $this->error(sprintf(_("Synchronization of files failed. Error %d returned"), $count));
    }
    unset($thumb);
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
    $thumb=new Thumbnail();
    $thumb->create_all_previews();
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
  global $search;
  
  echo "<h2>"._("Administration")."</h2>\n";
  $tabs2=new SectionMenu('tab', _("Actions:"));
  $tabs2->add_param('section', 'admin');
  $tabs2->set_item_param('page');

  $tabs2->add_item(ADMIN_TAB_GENERAL, _("General"), ADMIN_TAB_GENERAL==$curid );
  $tabs2->add_item(ADMIN_TAB_USERS, _("Users"));
  $tabs2->add_item(ADMIN_TAB_CREATE_USER, _("Create User"));
  //$tabs2->add_item(ADMIN_TAB_UPLOAD, _("Upload"));
  $tabs2->add_item(ADMIN_TAB_DEBUG, _("Debug"));
  $tabs2->print_sections();
  $cur=$tabs2->get_current();
  
  echo "\n";

  if (isset ($_REQUEST["action"]))
  {
    // @todo: Get rid of the <br> but keep the success boxes in correct
    //        positions.
    echo "<br/>\n";

    switch ($cur)
    {
    case ADMIN_TAB_USERS: 
      $this->exec_users();
      break;
    case ADMIN_TAB_CREATE_USER: 
      $this->exec_create_user();
      break;
    /*
    case ADMIN_TAB_UPLOAD: 
      $this->exec_upload();
      break;
    */
    case ADMIN_TAB_DEBUG: 
      $this->exec_debug();
      break;
    case ADMIN_TAB_GENERAL:
      $this->exec_general();
      break;
    default:
      break;
    }

    $url=new Url();
    $url->add_param('section', 'admin');
    $url->add_param('page', $cur);
    $href=$url->get_url();
    echo "<div class=\"button\">
<a href=\"$href\">Back</a>
</div>\n";
    echo "<p></p>\n";
 
    return; // Uncomment this if you still want to see the contents.
  }

  switch ($cur)
  {
  case ADMIN_TAB_USERS: 
    $this->print_users(); 
    break;
  case ADMIN_TAB_CREATE_USER: 
    $this->print_create_user(); 
    break;
  /*
  case ADMIN_TAB_UPLOAD: 
    $this->print_upload (); 
    break;
  */
  case ADMIN_TAB_DEBUG: 
    $this->print_debug (); 
    break;
  default:
    $this->print_general (); 
    break;
  }
}

}
?>
