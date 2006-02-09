<?php

global $prefix;
include_once("$prefix/SectionBody.php");
include_once("$prefix/SectionAccount.php");

class SectionSetup extends SectionBody
{

var $stage=0;

function SectionSetup()
{
    global $db;
    $this->name="setup";
    $sql="show tables;";
    $result=$db->query($sql, true);
    if (!$result) 
    {
        $this->stage=0;
        return;
    }
    if (mysql_num_rows($result)==0)
    {
        $this->stage=1;
        return;
    }

    $sql="select id,name from user where name='admin'";
    $result=$db->query($sql, true);
    if (!$result || mysql_num_rows($result)==0)
    {
        $this->stage=2;
        return;
    }
    
    $this->stage=3;
}

function print_stage0()
{
    echo "<h3>Connection to mySQL database</h3>\n";
    $this->print_error("Could not connect to mySQL database.");
    echo "Please check the user/password authentication in phtagr/Sql.php";
    echo "<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"setup\" />
<input type=\"hidden\" name=\"stage\" value=\"0\" />
<table>
  <tr>
    <td>Host</td><td><input type=\"text\" name=\"host\" value=\"localhost\" /></td>
  </tr><tr>
    <td>Database</td><td><input type=\"text\" name=\"database\" value=\"phtagr\" /></td>
  </tr><tr>
    <td>User</td><td><input type=\"text\" name=\"user\" value=\"phtagr\" /></td>
  </tr><tr>
    <td>Password</td><td><input type=\"password\" name=\"password\" /></td>
  </tr><tr>
    <td></td><td><input type=\"submit\" value=\"OK\" /><input type=\"reset\" value=\"Reset\" /></td>
  </tr>
</table>
";

}

function print_stage1()
{
    echo "<h3>Creation of Tables</h3>\n";
    $this->print_warning("Tables are not created");
    echo "<a href=\"setup.php?section=setup&action=create_tables\">Create Tables</a>\n";
     
}

function print_stage2()
{
    echo "<h3>Creation of Admin Account</h3>\n";
    $account=new SectionAccount();
    $account->user='admin';
    $account->print_form_new_account();
}

function print_stage3()
{
    echo "<ul>\n";
    echo "<li><a href=\"setup.php?section=setup&action=delete_tables\">Delete Tables</a></li>\n";
    echo "<li><a href=\"index.php\">Go to phTagr</a></li>\n";
    echo "</ul>\n";
}

function print_content()
{
    global $db;
    global $auth;
    
    echo "<h2>Setup</h2>\n";
    $action=$_REQUEST['action'];
    if ($action=='delete_tables')
    {
        $db->delete_tables();
        $this->print_warning('Tables deleted');
        return;
    }
    else if ($action=='create_tables')
    {
        if ($db->create_tables())
        {
            $this->print_success('Tables created');
        }
        return;
    }
    else if ($action=='create')
    {
        echo "<h2>Create A New Account</h2>\n";
        $name=$_REQUEST['name'];
        $password=$_REQUEST['password'];
        $confirm=$_REQUEST['confirm'];
        if ($password != $confirm)         {
            $this->print_error("Password mismatch");             return;
        }
        $account=new SectionAccount();
        if ($account->create_user($name, $password)==true)
        {
            $this->print_success("User '$name' created");
        }
        return;
    }
    switch ($this->stage) {
    case 0: $this->print_stage0(); break;
    case 1: $this->print_stage1(); break;
    case 2: $this->print_stage2(); break;
    default: $this->print_stage3(); break;
    }
    
}

}
?>
