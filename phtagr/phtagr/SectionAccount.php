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

/** Checks the username for validity. 
  The Username must start with an letter, followed by letters, numbers, or
  special characters (-, _, ., @). All letters must be lowered.
  
  @param name Username to check
  @return true if the name is possible, an error string of the error message
  otherwise */
function check_username($name)
{
  if (strlen($name)<4)
    return _("The username is to short. It must have at least 4 characters");
  if (strlen($name)>32)
    return _("The username is to long. Maximum length is 32 characters");
    
  if (!preg_match('/^[a-z][a-z0-9\-_\.\@]+$/', $name))
    return _("Username contains invalid characters. The name must start with an letter, followed by letters, numbers, or characters of '-', '_', '.', '@'.");
  
  global $db;
  $sql="SELECT name 
        FROM $db->user
        WHERE name='$name'";
  $result=$db->query($sql);
  if (mysql_num_rows($result)>0)
    return _("The username is already taken");
  return true;
}

/** Checks the vality of the password. At least 6 and maximum of 32 chars. 2
 * lower, 2 upper and 2 special characters
  @param pwd Password
  @return True on success. Otherwise it returns a reason message */
function check_password($pwd)
{
  if (strlen($pwd)<6)
    return _("The password is to short. It must have at least 6 characters");
  if (strlen($pwd)>32)
    return _("The password is to long. Maximum length is 32 characters");
    
  if (!preg_match('/[A-Z].*[A-Z]/', $pwd))
    return _("The password must contain 2 captialized characters");
  if (!preg_match('/[a-z].*[a-z].*[a-z]/', $pwd))
    return _("The password must contain 3 lower characters");

  if (!preg_match("/[0-9]/", $pwd))
    return _("The password must contain at least one number");

  return true;
}

/** Reads all the saved data we have about an user
  @param id ID of the user
  @return an array of all the saved data */
function get_info($id)
{
  global $db;

  $sql="SELECT *
        FROM $db->user
        WHERE id=$id";

  $result=$db->query($sql);
  $info=array();
  if ($result)
  {
    return mysql_fetch_array($result, MYSQL_ASSOC);
  }

  return $info;
}

/** Updates the data of an user 
  @param info the new values for the info of the user
  @return true on success, false otherwise */
function set_info($info)
{
  global $db;

  $sql="UPDATE $db->user
        SET firstname='".$info['firstname']."',
        lastname='".$info['lastname']."',
        email='".$info['email']."',
        quota='".$info['quota']."',
        quota_interval='".$info['quota_interval']."',
        quota_max='".$info['quota_max']."',
        data='".$info['data']."'
        WHERE id=".$info['id'];
  $result=$db->query($sql);
  if(!$result)
    return false;

  return true;
}
/** Creats a new user.
  @param name Name of the new user
  @param password password of the new user
  @return true on success, false otherwise */
function user_create($name, $password)
{
  global $db;
  global $user;

  $pref=$db->read_pref();
  if (!($pref['allow_user_self_register']) && !$user->is_admin())
  {
    $this->error(_("You are not allowed to create a new user!"));
    return false;
  }

  $result=$this->check_username($name);
  if (!is_bool($result) || $result==false)
  {
    $this->warning(sprintf(_("Sorry, the username '%s' could not be created. %s"), $name, $result));
    return false;
  }
  $result=$this->check_password($password);
  if (!is_bool($result) || $result==false)
  {
    $this->warning(_("Sorry, the password could not be created.")." ".$result);
    return false;
  }
  $sql="INSERT INTO 
        $db->user ( 
          name, password, email
        ) VALUES (
          '$name', '$password', 'email'
        )";
  if (!$db->query($sql))
    return false;
    
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
  <tr><td>Username:</td><td><input type=\"text\" name=\"name\" value=\"$this->user\"/><td></tr>
  <tr><td>Password:</td><td><input type=\"password\" name=\"password\"/><td></tr>
  <tr><td>Confirm:</td><td><input type=\"password\" name=\"confirm\"/><td></tr>
  <tr><td>Email:</td><td><input type=\"text\" name=\"email\"/><td></tr>
  <tr><td></td>
      <td><input type=\"submit\" value=\"Create\"/>&nbsp;&nbsp;
      <input type=\"reset\" value=\"Reset\"/></td></tr>
</table>

</form>";
}

/** Delte all data from a user
  @todo ensure to delete all data from the user */
function _delete_user_data($id)
{
  global $db;

  // delete all tags
  $sql="DELETE $db->tag 
        FROM $db->tag, $db->image
        WHERE $db->image.userid=$id AND $db->image.id=$db->tag.imageid";
  $db->query($sql);

  // Delete cached image data
  $sql="SELECT id 
        FROM $db->image
        WHERE id=$id";
  $result=$db->query($sql);
  if (!$result)
    return;

  while ($row=mysql_fetch_assoc($result))
  {
    // @todo delete all cached data
  }
  
  // Delete all image data
  $sql="DELETE $db->image 
        FROM $db->image
        WHERE id=$id";
  $result=$db->query($sql);

  // Delete all preferences
  $sql="DELETE $db->pref
        FROM $db->pref
        WHERE userid=$id";
  $result=$db->query($sql);
  
  // @todo delete the group of the user
  // @todo delete users upload directory
  
  // Delete the user data
  $sql="DELETE $db->user
        FROM $db->user
        WHERE id=$id";

  $result=$db->query($sql);
  return true;
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
