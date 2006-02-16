<?php

global $prefix;
include_once("$prefix/SectionHome.php");
include_once("$prefix/Auth.php");

class SectionAccount extends SectionHome
{

var $message;
var $section;
var $user;

function SectionAccount()
{
    $this->name="account";
    $this->message='';
    $this->section='';
    $this->user='';
}

function create_user($name, $password)
{
    global $db;
    $sql="select id from ".$db->prefix."user where name='$name'";
    $result=$db->query($sql);
    if (!$result)
    {
        return false;
    }
    if (mysql_num_rows($result) > 0)
    {
        $this->error("Username '$name' is already taken");
        return false;
    }
    $sql="insert into ".$db->prefix."user (name, password, email) values ('$name', '$password', 'email')";
    if (!$db->query($sql))
    {
        return false;
    }

    return true;
}

function print_form_new_account()
{
    echo "<form method=\"post\">
<table>
    <tr><td>Username:</td><td><input type=\"text\" name=\"name\" value=\"$this->user\"/><td></tr>
    <tr><td>Password:</td><td><input type=\"password\" name=\"password\"/><td></tr>
    <tr><td>Confirm:</td><td><input type=\"password\" name=\"confirm\"/><td></tr>
    <tr><td>Email:</td><td><input type=\"text\" name=\"email\"/><td></tr>
    <tr><td></td><td><input type=\"submit\" value=\"Account\"/><input type=\"reset\" value=\"Reset\"/>
</table>
<input type=\"hidden\" name=\"section\" value=\"account\" />
<input type=\"hidden\" name=\"action\" value=\"create\" />
</form>";
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
    echo "<h2>Login</h2>\n";
    /*
    if ($_REQUEST['user']!='' && $_REQUEST['password']!='')
    {
        $auth = new Auth();
        if ($auth->check_login($_REQUEST['user'], $_REQUEST['password']))
        {
            echo "Login succeed.</br>\n";
            return;
        }
    }
    */
    if ($this->message!='') 
    {
      $this->div_open('warning');
      echo $this->message;
      $this->div_close();
    }
    echo "<form section=\"index.php\" method=\"post\">
<table>
    <tr><td>Username:</td><td><input type=\"text\" name=\"user\"/><td></tr>
    <tr><td>Password:</td><td><input type=\"password\" name=\"password\"/><td></tr>
    <tr><td></td><td><input type=\"submit\" value=\"Login\"/><input type=\"reset\" value=\"Reset\"/>
</table>
<input type=\"hidden\" name=\"section\" value=\"account\" />
<input type=\"hidden\" name=\"action\" value=\"login\" />\n";
    if ($this->section!='')
    {
      echo "<input type=\"hidden\" name=\"pass-section\" value=\"$this->section\" />\n";
    }
echo "</form>";

    //echo "<a href=\"index.php?section=account&action=docreate\">Create Account</a><br/>\n";
}
function print_content()
{
    global $db;
    
    $action=$_REQUEST['action'];
    /*
    if ($action=='create')
    {
        echo "<h2>Create A New Account</h2>\n";
        $name=$_REQUEST['name'];
        $password=$_REQUEST['password'];
        $confirm=$_REQUEST['confirm'];
        if ($password != $confirm) 
        {
            $this->error("Password mismatch");
            return;
        }
        if ($this->create_user($name, $password)==true)
        {
            $this->success("User '$name' created");
        }
        return;
    }
    else if ($action=='docreate')
    {
        echo "<h2>Create A New Account</h2>\n";
        $this->print_form_new_account();
    }
    */
    if ($action='login')
    {
        $this->print_login();
    }
}

}
?>
