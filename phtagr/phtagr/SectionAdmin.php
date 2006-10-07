<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/SectionAccount.php");
include_once("$phtagr_lib/SectionTab.php");
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Thumbnail.php");
include_once("$phtagr_lib/Debug.php");

define("ADMIN_TAB_GENERAL", "0");
define("ADMIN_TAB_USER", "1");
define("ADMIN_TAB_UPLOAD", "2");
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
  $result=false;

  if (isset ($_REQUEST['user_self_register']))
    $result=$db->set_pref ('allow_user_self_register', '1');
  else
    $result=$db->set_pref ('allow_user_self_register', '0');
  
  if ($result)
    $this->success("Settings saved successfully!");
  else
    $this->error("Settings could not be saved!");
}

function print_general ()
{
  global $db;

  $pref = $db->read_pref();
  $user_self_register = $pref['allow_user_self_register'];

  echo "<h3>General</h3>\n";

  echo "<form action=\"./index.php\" method=\"POST\">\n";

  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_GENERAL);
  $url->add_param('action', 'settings');
  echo $url->to_form();
  if ($user_self_register)
    echo "<input type=\"checkbox\" name=\"user_self_register\" checked/>";
  else
    echo "<input type=\"checkbox\" name=\"user_self_register\"/>";
  echo " Allow users to register themselves.\n";

  echo "<input type=\"submit\" value=\"Save\" />\n";
  echo "</form>\n";
  echo "<p></p>\n";
}

function exec_user()
{
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
    if ($account->user_create($name, $password)==true)
    {
      $this->success(sprintf(_("User '%s' created"), $name));
    }

    return;
  }
  else if ($action=='delete')
  {
    if (isset($_REQUEST['id']))
      $account->user_delete($_REQUEST['id']);
  }
  else if (($action=="edit") && (isset ($_REQUEST['id'])))
  {
    $account=new SectionAccount();
    $info=$account->get_info($_REQUEST['id']);
    echo "<h3>".sprintf(_("Editing User '%s'"), $info['name'])."'</h3>\n";

    // If 'email' is set in the request, we assume that this current request
    // is already an update request with all the values we want to update.
    if (isset($_REQUEST['email']))
    {
      $info['email']=$_REQUEST['email'];
      $info['firstname']=$_REQUEST['firstname'];
      $info['lastname']=$_REQUEST['lastname'];
      if ($account->set_info($info))
        $this->success(_("Update successful!"));
      else
        $this->error(_("Error updating userdata!"));

      return;
    }

    // If we don't have a update request we show all the values we can
    // update.
    echo "<form action=\"./index.php\" method=\"post\">\n";

    $url=new Url();
    $url->add_param('section', 'admin');
    $url->add_param('page', ADMIN_TAB_USER);
    $url->add_param('action', 'edit');
    $url->add_param('id', $_REQUEST['id']);
    echo $url->to_form();
    echo "<table>
  <tr>
    <td>"._("First Name:")."</td>
    <td><input type=\"text\" name=\"firstname\" value=\"".$info['firstname']."\" /><td>
  </tr>
  <tr>
    <td>"._("Last Name:")."</td>
    <td><input type=\"text\" name=\"lastname\" value=\"".$info['lastname']."\" /><td>
  </tr>
  <tr>
    <td>"._("Email:")."</td>
    <td><input type=\"text\" name=\"email\" value=\"".$info['email']."\" /><td>
  </tr>
  <tr>
    <td></td>
    <td><input type=\"submit\" value=\"Save\"/>&nbsp;&nbsp;
      <input type=\"reset\" value=\"Reset\"/></td>
  </tr>
</table>
</form>\n\n";

    return;
  }

}

function print_user ()
{
  global $db;

  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_USER);
  $url->add_param('action', 'create');

  echo "<h3>Create User</h3>\n";
  echo "<form action=\"./index.php\" method=\"post\">\n";
  echo $url->to_form();
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
    <td><input type=\"submit\" value=\"Create\"/>&nbsp;&nbsp;
      <input type=\"reset\" value=\"Reset\"/></td>
  </tr>
</table>
</form>\n\n";

  echo "<h3>"._("Available Users")."</h3>\n";

  $sql="SELECT *
        FROM $db->user";

  $result=$db->query($sql);
  if (!$result)
    return;

  echo "<form action=\"./index.php\" method=\"post\">\n";
  
  echo "<table>
  <tr> 
    <th></td>
    <th>"._("Name")."</th>
    <th>"._("Actions")."</th>
  </tr>\n";
  $delete="index.php?section=admin&page=".ADMIN_TAB_USER."&action=delete&id=";
  $edit="index.php?section=admin&page=".ADMIN_TAB_USER."&action=edit&id=";
  while ($row=mysql_fetch_assoc($result))
  {
    $url->add_param('id', $row['id']);
    if ($row['id'] == 1)
    {
      echo "  <tr>
    <td><input type=\"checkbox\" disabled=\"disabled\"></td>
    <td>${row['name']}</td>
    <td>
    <div class=\"button\">\n";
      $url->add_param('action', 'edit');
      echo "<a href=\"".$url->to_URL()."\" class=\"button\">edit</a>\n";
      echo "    </div>
    </td>
  </tr>\n";
    }
    else
    {
      echo "  <tr>
    <td><input type=\"checkbox\"></td>
    <td>${row['name']}</td>
    <td>
      <div class=\"button\">";
      $url->add_param('action', 'edit');
      echo "<a href=\"".$url->to_URL()."\" class=\"button\">edit</a>";
      $url->add_param('action', 'delete');
      $warning=htmlspecialchars(
        sprintf(_("You are about to delete the user '%s' (ID %d). "
        ."Do you want to proceed?"), $row['name'], $row['id']));
      echo "<a href=\"".$url->to_URL()."\" "
        ."onclick=\"return confirm('$warning');\">delete</a>\n
    </div>
    </td>
  </tr>\n";
    }
  } 
  echo "</table>\n";

  echo "</form>\n";
}

function exec_upload ()
{
  global $db;

  $request_upload_dir=$_REQUEST['set_dir'];
  if ($request_upload_dir != "")
  {
    // Check if upload is already exists
    $sql = "SELECT value 
            FROM $db->pref 
            WHERE name='upload_dir'";
    $result= $db->query($sql);
    if (!$result)
      return false;

    if (mysql_num_rows($result)>0)
      $sql="UPDATE $db->pref 
            SET value='${request_upload_dir}'
            WHERE name='upload_dir'";
    else
      $sql="INSERT INTO $db->pref (name, value) 
            VALUES('upload_dir', '${request_upload_dir}')";
    $result = $db->query ($sql);
    if (!$result)
    {
      $this->warning( "Could not update 'update_dir'!\n");
      return false;
    }
    else
      $this->success( "Update successful!\n");
  }
}

function print_upload ()
{
  global $db;

  $pref = $db->read_pref();
  $upload_dir = $pref['upload_dir'];

  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_UPLOAD);
  $url->add_param('action', upload_dir);

  echo "<h3>Upload Settings</h3>\n";

  echo "<form action=\"./index.php\" method=\"POST\">\n";

  echo "<p>All uploads go below this folder. For each user a subfolder will be ";
  echo "created under which his images will reside. If a file exists, it ";
  echo "will be saved as FILENAME-xyz.EXTENSION.<br>\n";
  echo $url->to_form();
  echo "<input type=\"text\" name=\"set_dir\" value=\"" . $upload_dir . "\" size=\"60\"/>\n";
  echo "<input type=\"submit\" value=\"Save\" class=\"submit\" />\n";
  echo "</form>\n";
  echo "</p>\n";
}

function exec_debug ()
{
  global $db;
  $action="";

  if (!isset($_REQUEST["action"]))
    return;
  else
    $action=$_REQUEST["action"];

  if ($action=="sync")
  {
    echo "<h3>Synchroning image data...</h3>\n";
    $this->info("This operation may take some time");
    sync_files();
  }
  else if ($action=="delete_tables")
  {
    echo "<h3>Deleting Tables...</h3>\n";
    $db->delete_tables();
    $this->warning("Tables deleted!");
  }
  else if ($action=="delete_images")
  {
    echo "<h3>Deleting Images...</h3>\n";
    $db->delete_images();
    $this->warning("All image data is deleted");
  }
  else if ($action=="create_all_previews")
  {
    echo "<h3>Creating preview images...</h3>\n";
    $this->info("This operation may take some time");
    create_all_previews();
  }
  else
  {
   $this->error ("Unknown action: ". $action);
  }
}

function print_debug ()
{
  echo "<h3>Debug</h3>\n";
  $this->warning(_("Please handle these operations carefully!"));
  $url=new Url();
  $url->add_param('section', 'admin');
  $url->add_param('page', ADMIN_TAB_DEBUG);
  echo "<ul>\n";
  echo "<li>";
  $url->add_param('action', 'sync'); $href=$url->to_URL();
  echo "<a href=\"$href\">Synchronize</a> files with the database</li>\n";
  $url->add_param('action', 'delete_tables'); $href=$url->to_URL();
  echo "<li><a href=\"$href\">Delete Tables</a></li>\n";
  $url->add_param('action', 'delete_images'); $href=$url->to_URL();
  echo "<li><a href=\"$href\">Delete all images</a></li>\n";
  $url->add_param('action', 'create_all_previews'); $href=$url->to_URL();
  echo "<li><a href=\"$href\">Create all preview images</a></li>\n";
  $url->rem_param('section');
  $url->rem_param('page');
  $url->rem_param('action');
  $href=$url->to_URL();
  echo "<li><a href=\"$href\">Go to phTagr</a></li>\n";
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
  $tabs2->add_item(ADMIN_TAB_USER, _("User"));
  $tabs2->add_item(ADMIN_TAB_UPLOAD, _("Upload"));
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
    case ADMIN_TAB_USER: 
      $this->exec_user();
      break;
    case ADMIN_TAB_UPLOAD: 
      $this->exec_upload();
      break;
    case ADMIN_TAB_DEBUG: 
      $this->exec_debug();
      break;
    case ADMIN_TAB_GENERAL:
      $this->exec_general();
      break;
    default:
      $this->waring(_("No valid action found"));
      break;
    }

    $url=new Url();
    $url->add_param('section', 'admin');
    $url->add_param('page', $cur);
    $href=$url->to_URL();
    echo "<div class=\"button\">
<a href=\"$href\">Back</a>
</div>\n";
    echo "<p></p>\n";
 
    return; // Uncomment this if you still want to see the contents.
  }

  switch ($cur)
  {
  case ADMIN_TAB_USER: 
    $this->print_user (); 
    break;
  case ADMIN_TAB_UPLOAD: 
    $this->print_upload (); 
    break;
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
