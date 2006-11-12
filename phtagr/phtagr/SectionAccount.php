<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/User.php");
include_once("$phtagr_lib/Url.php");

class SectionAccount extends SectionBase
{

var $message;
var $section;
var $user;

function SectionAccount()
{
  $this->SectionBase("account");
  $this->message='';
  $this->section='';
  $this->user='';
}

/** Creats a new user.
  @param name Name of the new user
  @param password password of the new user
  @return true on success, false otherwise */
function user_create($name, $password)
{
  global $db;
  global $user;
  global $pref;

  if (!($pref['allow_user_self_register']) && !$user->is_admin())
  {
    $this->error(_("You are not allowed to create a new user!"));
    return false;
  }

  $result=$user->create($name, $password);
  if ($result<0)
  {
    $msg='';
    switch ($result) {
    case ERR_USER_NAME_LEN:
      $msg=_("The name is to short or to long. It must have at least 4 characters");
      break;
    case ERR_USER_NAME_INVALID:
      $msg=_("Username contains invalid characters. The name must start with an letter, followed by letters, numbers, or characters of '-', '_', '.', '@'.");
      break;
    case ERR_USER_ALREADY_EXISTS:
      $msg=_("The username is already taken");
      break;
    case ERR_USER_PWD_LEN:
      $msg=_("The password is too short or too long. It must have at least 6 characters and maximum 32 chars.");
      break;
    case ERR_USER_PWD_INVALID:
      $special=$user->get_special_chars();
      $out='';
      for ($i=0; $i<strlen($special); $i++)
      {
        $out.="'".$special{$i}."'";
        if ($i+1<strlen($special))
          $out.=", ";
      }
      $out=htmlentities($out);
      $msg=sprintf(_("The password is invalid. Use at least two lower letters, to upper letters and two special chars of %s"), $out);
      break;
    default:
      break;
    }
    $this->warning(sprintf(_("Sorry, the account could not be created. %s"), $msg));
    return false;
  }

  $new=new User(intval($result));
  $new->set_firstname($_REQUEST['firstname']);
  $new->set_lastname($_REQUEST['surname']);
  $new->commit_changes();
  $new->set_email($_REQUEST['email']);
  unset($new);

  return true;
}

function print_form_new()
{
  $url=new Url();
  $url->from_URL();
  $url->add_param('section', 'account');
  $url->add_param('action', 'create');
  echo "<form method=\"post\">".$url->to_form()."
<table>
  <tr><td>"._("Username:")."</td><td><input type=\"text\" name=\"name\" value=\"$this->user\"/><td></tr>
  <tr><td>"._("Password:")."</td><td><input type=\"password\" name=\"password\"/><td></tr>
  <tr><td>"._("Confirm:")."</td><td><input type=\"password\" name=\"confirm\"/><td></tr>
  <tr><td>"._("Email:")."</td><td><input type=\"text\" name=\"email\"/><td></tr>
  <tr><td></td>
      <td><input type=\"submit\" class=\"submit\" value=\"Create\"/>
      <input type=\"reset\" class=\"reset\" value=\"Reset\"/></td></tr>
</table>

</form>";
}

/** Delete a specific user */
function user_delete($id=-1)
{
  global $user;
  global $db;

  /* We only allow to delete non admin users, which means users
  with an id > 1 */
  if ($id>1)
  {
    /* Only the admin is allowed to delete user. */
    if (!$user->is_admin())
    {
      $this->warning("You are not allowed to delete user.");
      return false;
    }

    if ($this->_delete_user_data($id))
        $this->info("User was deleted successfully");
    return;
  }
  
  $this->warning("You are not allowed to delete this user!");
  return;
}

function print_delete_account()
{
  echo "<h2>Delete Account</h2>\n";
  echo "<form section=\"index.php\" method=\"post\">
<table>
  <tr><td>Username:</td><td><input type=\"text\" name=\"user\"/><td></tr>
  <tr><td>Password:</td><td><input type=\"password\" name=\"password\"/><td></tr>
  <tr><td>Confirm:</td><td><input type=\"password\" name=\"confirm\"/><td></tr>
</table>
<input type=\"hidden\" name=\"section\" value=\"account\" />
<input type=\"hidden\" name=\"action\" value=\"delete\" />
</form>";
}

function print_login()
{
  global $user;
  $url=new Url();
  $url->from_URL();
  $url->add_param('section', 'account');
  $url->add_param('action', 'login');
  if ($this->section!='')
    $url->add_param('goto', $this->section);
    
  echo "<h2>"._("Login")."</h2>\n";
  if ($_REQUEST['user']!='' && $_REQUEST['password']!='')
  {
    $this->warning(_("The username is unkown or the password is incorrect"));
  }

  if ($this->message!='') 
  {
    $this->warning($this->message);
  }
  echo "<form section=\"index.php\" method=\"post\">
".$url->to_form()."
<table>
  <tr><td>"._("Username:")."</td><td><input type=\"text\" name=\"user\"/><td></tr>
  <tr><td>"._("Password:")."</td><td><input type=\"password\" name=\"password\"/><td></tr>
  <tr><td></td><td><input type=\"checkbox\" name=\"remember\" checked=\"checked\" /> "._("Remeber me on the next login")."</td></td>
  <tr><td></td>
      <td><input type=\"submit\" class=\"submit\" value=\""._("Login")."\"/>
      <input type=\"reset\" class=\"reset\"value=\""._("Cancel")."\"/></td></tr>
</table>
</form>";

  //echo "<a href=\"index.php?section=account&action=new\">Create Account</a><br/>\n";
}

function print_user_list()
{
  global $db;
  $sql="SELECT *
        FROM $db->user";

  $result=$db->query($sql);
  if (!$result)
    return;

  echo "<table>
  <tr>
    <th></td>
    <th>Name</th>
    <th>Actions</th>
  </tr>\n";
  $delete="index.php?section=account&amp;action=delete&amp;id=";
  while ($row=mysql_fetch_assoc($result))
  {
    if ($row['id'] == 1)
    {
    echo "  <tr>
    <td><input type=\"checkbox\" disabled></td>
    <td>${row['name']}</td>
    <td><div class=\"button_disabled\"><a href=\"\">delete</a></div></td>
  </tr>\n";
    }
    else
    {
    echo "  <tr>
    <td><input type=\"checkbox\"></td>
    <td>${row['name']}</td>
    <td><div class=\"button\"><a href=\"${delete}${row['id']}\">delete</a></div></td>
  </tr>\n";
    }
  }
  echo "</table>\n";
} 

function print_content()
{
  global $db;
  global $user;
  
  $action=$_REQUEST['action'];
  if ($action=='create')
  {
    echo "<h2>"._("Create A New Account")."</h2>\n";
    $name=$_REQUEST['name'];
    $password=$_REQUEST['password'];
    $confirm=$_REQUEST['confirm'];
    if ($password != $confirm) 
    {
      $this->error(_("Password mismatch"));
      return;
    }
    if ($this->user_create($name, $password)==true)
    {
      $this->success("User '$name' created");
    }
    else
      $this->print_form_new();
    return;
  }
  else if ($action=='new')
  {
    echo "<h2>"._("Create A New Account")."</h2>\n";
    $this->print_form_new();
  } else if ($action=='list')
  {
    if ($user->is_admin())
      $this->print_user_list();
  } else if ($action=='delete')
  {
    if (isset($_REQUEST['id']))
      $this->user_delete($_REQUEST['id']);
    else
      $this->user_delete(-1);
  } else
  {
    $this->print_login();
  }

}

}
?>
