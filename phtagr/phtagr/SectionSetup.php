<?php

global $prefix;

include_once("$phtagr_prefix/SectionBase.php");
include_once("$phtagr_prefix/SectionAccount.php");
include_once("$phtagr_prefix/Image.php");
include_once("$phtagr_prefix/Thumbnail.php");

class SectionSetup extends SectionBase
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

  $sql="select id,name from $db->user where name='admin'";
  $result=$db->query($sql, true);
  if (!$result || mysql_num_rows($result)==0)
  {
    $this->stage=1;
    return;
  }
  
  $this->stage=2;
}

function exec_stage_db()
{
  // check sql parameters
  global $db;
  
  if (!file_exists($_REQUEST['dir']))
  {
    $this->error("Directory ".$_REQUEST['dir']." does not exists. Create it first.");
    return false;
  }
  $configdir=realpath($_REQUEST['dir']);

  if (!is_writeable($configdir))
  {
    $this->error("Could not write to data directory $configdir");
    return false;
  }
  
  $result=$db->test_database($_REQUEST['host'], 
                 $_REQUEST['user'], 
                 $_REQUEST['password'], 
                 $_REQUEST['database']);
  if ($result!=true)
  {
    $this->error($result);
    return false;
  }
  
  // check for writing the minimalistic configure file
  $config=$configdir.DIRECTORY_SEPARATOR."vars.inc";
  
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
  
  if (!$db->connect($config))
  {
    $this->error("Could not read the configuration file $config");
    // remove the configuration file
    unlink($config);
    return false;
  }
  
  if (!$db->create_tables())
  {
    $this->error("The tables could not be created successfully");
    // remove the configuration file
    unlink($config);
    return false;
  }
  
  $this->success("Configuration file and tables created successfully");
  $this->warning("Please move the file '$config' to the directory '".getcwd().DIRECTORY_SEPARATOR."phtagr'");
  
  if (!$this->init_tables($configdir))
  {
    $this->warning("Could not init the tables correctly");
    return false;
  }

  return true;
}

function _create_dir($dir)
{
  echo $dir;
  if (!file_exists($dir))
  {
    if (!@mkdir($dir, true))
    {
      $this->error("Could not create directory $dir.");
      return false;
    }
  }

  if (!@chmod($dir, 0755))
  {
    $this->error("Could not change the permission correctly of directory $dir.");
    return false;
  }

  return true;
}

/** Insert default values to the table
  @return true on success. false on failure */
function init_tables($configdir)
{
  global $db;
  
  // image cache
  $cache=$configdir.DIRECTORY_SEPARATOR."cache";
  if (!$this->_create_dir($cache))
    return false;
  
  $cache=str_replace('\\','\\\\',$cache);
  $sql="INSERT $db->pref (userid, name, value) VALUES(0, 'cache', '$cache')";
  $result=$db->query($sql);
  if (!$result) return false;

  // upload dir
  $upload=$configdir.DIRECTORY_SEPARATOR."upload";
  if (!$this->_create_dir($upload))
    return false;

  $upload=str_replace('\\','\\\\',$upload);
  $sql="INSERT $db->pref (userid, name, value) VALUES(0, 'upload_dir', '$upload')";
  $result=$db->query($sql);
  if (!$result) return false;
  
  return true;
}

function print_stage_db()
{
  
  echo "<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"setup\" />
<input type=\"hidden\" name=\"stage\" value=\"0\" />
<input type=\"hidden\" name=\"action\" value=\"init\" />

<h3>Data Directory</h3>

<p>phTagr will store all its data in this directory including cached images, uploaded images, etc.</p>

<p>This directory must be writeable by PHP</p>

<table>
  <tr>
    <td>Data Directory:</td>
    <td><input type=\"text\" name=\"dir\" value=\"\" /></td>
</table>
";

  $this->info("For security reasons, the data directory should be writeable by the PHP but not readable from the browser.");

  echo "<h3>mySQL Connection</h3>
  
<p>Please insert the connection data for the mysql connection data</p>

<table>
  <tr>
    <td>Host:</td>
    <td><input type=\"text\" name=\"host\" value=\"localhost\" /></td>
  </tr><tr>
    <td>User:</td>
    <td><input type=\"text\" name=\"user\" value=\"\" /></td>
  </tr><tr>
    <td>Password:</td>
    <td><input type=\"password\" name=\"password\" /></td>
  </tr><tr>
    <td>Database:</td>
    <td><input type=\"text\" name=\"database\" value=\"\" /></td>
  </tr><tr>
    <td>Table Prefix:</td>
    <td><input type=\"text\" name=\"prefix\" value=\"\" /></td>
  </tr>
</table>

";
  $this->info("To run multiple phTagr instances within one database, please use
  the table prefix. Usually this option is not used.");

  echo "
<input type=\"submit\" value=\"OK\" />&nbsp;&nbsp;<input type=\"reset\" value=\"Reset\" />
";
}

function print_stage_admin()
{
  echo "<h3>Creation of Admin Account</h3>\n";
  $account=new SectionAccount();
  $account->user='admin';
  $account->print_form_new();
}

function print_actions()
{
  $this->warning("Please handle these operations carefully!");
  echo "<ul>\n";
  echo "<li><a href=\"index.php?section=setup&action=sync\">Synchronize</a> files with the database</li>\n";
  echo "<li><a href=\"index.php?section=setup&action=init\">Create a phTagr Instance</a></li>\n";
  echo "<li><a href=\"index.php?section=setup&action=delete_tables\">Delete Tables</a></li>\n";
  echo "<li><a href=\"index.php?section=setup&action=delete_images\">Delete all images</a></li>\n";
  echo "<li><a href=\"index.php?section=setup&action=upload_dir\">Set the upload directory</a></li>\n";
  echo "<li><a href=\"index.php?section=setup&action=create_all_previews\">Create all preview images</a></li>\n";
  echo "<li><a href=\"index.php\">Go to phTagr</a></li>\n";
  echo "</ul>\n";
}

function setup_upload()
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

  $pref = $db->read_pref();
  $upload_dir = $pref['upload_dir'];

  echo "<h3>Uploads</h3>\n";
  echo "<form action=\"./index.php\" method=\"POST\">\n";
  echo "All uploads go below this folder. For each user a subfolder will be ";
  echo "created under which his images will reside. If a file exists, it ";
  echo "will be saved as FILENAME-xyz.EXTENSION.<br>\n";
  echo "<input type=\"hidden\" name=\"section\" value=\"setup\" />\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"upload_dir\" />\n";
  echo "<input type=\"text\" name=\"set_dir\" value=\"" . $upload_dir . "\" size=\"60\"/>\n";
  echo "<input type=\"submit\" value=\"Save\" class=\"submit\" />\n";
  echo "</form>\n";
}

/** Synchronize files between the database and the filesystem. If a file not
 * exists delete its data. If a file is newer since the last update, update its
 * data. */
function sync_files()
{
  global $db;

  echo "<h3>Synchronize image data...</h3>\n";

  $this->info("This operation may take some time");
  
  $sql="SELECT id,filename
        FROM $db->image";
  $result=$db->query($sql);
  if (!$result)
    return;
    
  $count=0;
  $updated=0;
  $deleted=0;
  while ($row=mysql_fetch_row($result))
  {
    $id=$row[0];
    $filename=$row[1];
    $count++;
    
    if (!file_exists($filename))
    {
      $this->delete_image_data($id,$filename);
      $deleted++;
    }
    else 
    {
      $image=new Image($id);
      if ($image->update())
        $updated++;
      unset($image);
    }
  }
  echo "All $count images are now synchronized. $deleted images are deleted. $updated images are updated.<br/>\n";
}

/** Create all preview images */
function create_all_previews()
{
  global $db;

  echo "<h3>Create preview images ...</h3>\n";

  $this->info("This operation may take some time");
  
  $sql="SELECT id
        FROM $db->image";
  $result=$db->query($sql);
  if (!$result)
    return;
    
  $count=0;
  $updated=0;
  $deleted=0;
  while ($row=mysql_fetch_row($result))
  {
    $id=$row[0];
    $count++;
    
    $img=new Thumbnail($id);
    $img->create_all_previews();
  }
  echo "All preview images of $count images are now created.<br/>\n";
}

/** Deletes a file from the database */
function delete_image_data($id, $file)
{
  global $db;
  echo "<div class='warning'>File '$file' does not exists. Deleting its data form database</div>\n";
  $sql="DELETE FROM $db->imagetag 
        WHERE imageid=$id";
  $result = $db->query($sql);

  $sql="DELETE FROM $db->image 
        WHERE id=$id";
  $result = $db->query($sql);
}


function print_content()
{
  global $db;
  global $user;
  
  echo "<h2>Setup</h2>\n";
  $action=$_REQUEST['action'];
  if ($action=='install')
  {
    $this->stage=0;
  }
  else if ($action=='init')
  {
    if (!$this->exec_stage_db())
      $this->stage=0;
    else
      $this->stage=1;
  }
  else if ($action=='sync')
  {
    $this->sync_files();   
  }
  else if ($action=='delete_images')
  {
    $db->delete_images();
    $this->warning('All image data are deleted');
    return;
  }
  else if ($action=='delete_tables')
  {
    $db->delete_tables();
    $this->warning('Tables deleted');
    return;
  }
  else if ($action=='upload_dir')
  {
    $this->setup_upload(); 
    return;
  }
  else if ($action=='create_all_previews')
  {
    $this->create_all_previews(); 
    return;
  }
  
  else if ($action=='create')
  {
    echo "<h2>Create A New Account</h2>\n";
    $name=$_REQUEST['name'];
    $password=$_REQUEST['password'];
    $confirm=$_REQUEST['confirm'];
    if ($password != $confirm) {
      $this->error("Password mismatch");             
      return;
    }
    $account=new SectionAccount();
    if ($account->create_user($name, $password)==true) {
      $this->success("User '$name' created");
    }
    return;
  }
  switch ($this->stage) {
  case 0: $this->print_stage_db(); break;
  case 1: $this->print_stage_admin(); break;
  default: $this->print_actions(); break;
  }
}

}
?>
