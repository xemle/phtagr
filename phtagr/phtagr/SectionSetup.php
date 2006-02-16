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

    $sql="select id,name from ".$db->prefix."user where name='admin'";
    $result=$db->query($sql, true);
    if (!$result || mysql_num_rows($result)==0)
    {
        $this->stage=2;
        return;
    }
    
    $this->stage=3;
}

function exec_stage0()
{
    // check sql parameters
    global $db;
    $result=$db->test_database($_REQUEST['host'], 
                   $_REQUEST['user'], 
                   $_REQUEST['password'], 
                   $_REQUEST['database']);
    if ($result!=true)
    {
      $this->error($result);
      return false;
    }
    
    $configdir=getcwd()."/phtagr";
    if (!is_writeable($configdir))
    {
      $this->error("Could not write to config directory $configdir");
      return false;
    }
    
    // check for writing the minimalistic configure file
    $config="$configdir/vars.inc";
    
    // write minimalistic configuration file
    $f=fopen($config, "w");
    if (!$f) 
    {
      $this->error("Could not write to config file $config");
      return false;
    }

    fwrite($f, "# Configuration file\n");
    fwrite($f, "db_host=".$_REQUEST['host']."\n");
    fwrite($f, "db_user=".$_REQUEST['user']."\n");
    fwrite($f, "db_password=".$_REQUEST['password']."\n");
    fwrite($f, "db_database=".$_REQUEST['database']."\n");
    fwrite($f, "# Prefix of phTagr tables.\n");
    fwrite($f, "db_prefix=".$_REQUEST['prefix']."\n");
    fclose($f);

    $this->p("The configure file was created successfully");
    $this->warning("Please change the write permission of directory $configdir to only readable");
    $db = new Sql();
    if (!$db->connect() || !$db->create_tables())
    {
      $this->warning("The tables could not be created successfully");
      return false;
    }
    $this->success("Tables where successfully created");
    $sql="INSERT $db->pref (name, value) VALUES('cache', '".getcwd()."/cache')";
    $db->query($sql);
    
    return true;
}

function exec_stage1()
{
    // check cache directory
    if (!is_dir($_REQUEST['cache']))
    {
      $this->error("Cache directory does not exists");
      return false;
    }
    if (!is_writeable($_REQUEST['cache']))
    {
      $this->error("Could not write to cache directory");
      return false;
    }
}

function print_stage0()
{
    echo "<h3>Setup of mySQL database connection</h3>\n";
    
    $this->p("Please insert the connection data for the mysql connection data");
    
    echo "<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"setup\" />
<input type=\"hidden\" name=\"stage\" value=\"0\" />
<input type=\"hidden\" name=\"action\" value=\"init\" />

<fieldset><legend><b>SQL Table</b></legend>
<table>
  <tr>
    <td>Host</td><td><input type=\"text\" name=\"host\" value=\"localhost\" /></td>
  </tr><tr>
    <td>User</td><td><input type=\"text\" name=\"user\" value=\"phtagr\" /></td>
  </tr><tr>
    <td>Password</td><td><input type=\"password\" name=\"password\" /></td>
  </tr><tr>
    <td>Database</td><td><input type=\"text\" name=\"database\" value=\"phtagr\" /></td>
  </tr><tr>
    <td>Table prefix</td><td><input type=\"text\" name=\"prefix\" value=\"\" /></td>
  </tr>
</table>
</fieldset>

<input type=\"submit\" value=\"OK\" /><input type=\"reset\" value=\"Reset\" />

";
  $this->info("The data will be stored in the phtagr directory. For this reason,
  the directory should be writeable by the webserver. After this setup step,
  the permission should be set to read-only.");
  
  $this->info("To run multiple phTagr instances within one database, please use
  the table prefix");
}

function print_stage1()
{
    echo "<h3>Creation of Tables</h3>\n";
    $this->warning("Tables are not created");
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
    if ($action=='init')
    {
        $this->exec_stage0();
        $this->stage++;
    }
    else if ($action=='delete_tables')
    {
        $db->delete_tables();
        $this->warning('Tables deleted');
        return;
    }
    else if ($action=='create_tables')
    {
        if ($db->create_tables())
        {
            $this->success('Tables created');
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
            $this->error("Password mismatch");             return;
        }
        $account=new SectionAccount();
        if ($account->create_user($name, $password)==true)
        {
            $this->success("User '$name' created");
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
