<?php

global $prefix;

include_once("$phtagr_prefix/SectionBase.php");
include_once("$phtagr_prefix/SectionAccount.php");
include_once("$phtagr_prefix/Image.php");

class SectionInstall extends SectionBase
{

/* $stage = 0 : welcome
   $stage = 1 : directory
   $stage = 2 : database
   $stage = 3 : tables
   $stage = 4 : admin
   $stage = 5 : cleanup
   */
var $stage=0;

function _create_dir($dir)
{
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
function init_tables()
{
  global $db;
  $directory="";
  if (isset($_REQUEST["directory"]))
    $directory=$_REQUEST["directory"];
  else
  {
    $this->error("No directory specified!");
    return false;
  }

  if (strrpos($directory,DIRECTORY_SEPARATOR) < strlen($directory) - 1)
    $directory=$directory.DIRECTORY_SEPARATOR;
  
  // image cache
  $cache=$directory."data/cache";
  if (!$this->_create_dir($cache))
    return false;
  
  $cache=str_replace('\\','\\\\',$cache);
  $sql="INSERT $db->pref (userid, name, value) VALUES(0, 'cache', '$cache')";
  $result=$db->query($sql);
  if (!$result) return false;

  // upload dir
  $upload=$directory."data/upload";
  if (!$this->_create_dir($upload))
    return false;

  $upload=str_replace('\\','\\\\',$upload);
  $sql="INSERT $db->pref (userid, name, value) VALUES(0, 'upload_dir', '$upload')";
  $result=$db->query($sql);
  if (!$result) return false;
  
  return true;
}

function SectionInstall()
{
  global $db;
  $this->name="install";
  $this->stage=0;
}

function exec_stage_authenticate()
{
  $auth_file=getcwd().DIRECTORY_SEPARATOR."login.txt";

  if (file_exists($auth_file))
  {
    $f=fopen($auth_file,"r");
    $id=fgets($f,256); // This should be long enough for the md5 sum
    $id=trim($id);
    fclose($f);
    $install_id="";
    if (isset($_REQUEST['id']))
      $install_id=$_REQUEST['id'];
    else
      return false;

    if ($id==$install_id)
      return true;
  }

  return false;
}

/** Checks whether a file in a directory is writable or whether
  it is possible to create it.
  @param directory The directory in which the file should be in.
  @param file The name of the file that should be checked.
  @return true if the file can be created or if it is writable,
          false otherwise.
*/
function check_file_permissions($directory, $file)
{
  if (file_exists($directory.$file)
    && is_writable($directory.$file))
    return true;

  if (is_writable($directory)
    && !file_exists($directory.$file))
    return true;

  return false;
}

function exec_stage_directory()
{
  $directory="";
  if (isset($_REQUEST['directory']))
    $directory=$_REQUEST['directory'];
  else
    return false;
 
  if (strrpos($directory,DIRECTORY_SEPARATOR) < strlen($directory) - 1)
    $directory=$directory.DIRECTORY_SEPARATOR;

  $dir_writable=is_writable($directory);

  if (file_exists($directory))
  {
    // At first we need to check whether it is the current directory. If it
    // is, we don't need to write index.php.

    if ($directory!=getcwd().DIRECTORY_SEPARATOR)
    {
      // In this case we only need to check, whether we can write
      // the config file and into the data dir.
      if (!$this->check_file_permissions($directory,"index.php"))
      {
        $this->error("index.php is not writable!");
        return false;
      }
      else
      {
	// We need to copy the file there
	if (!copy (getcwd().DIRECTORY_SEPARATOR."index.php", $directory."index.php"))
	{
	  $this->error("Could not copy index.php to $directory!");
	  return false;
	}
      }
      if(!$this->check_file_permissions($directory,"image.php"))
      {
        $this->error("image.php is not writable!");
        return false;
      }
      else
      {
	// We need to copy the file there
	if (!copy (getcwd().DIRECTORY_SEPARATOR."image.php", $directory."image.php"))
	{
	  $this->error("Could not copy image.php to $directory!");
	  return false;
	}
      }
    }
    if (!$this->check_file_permissions($directory,"config.php"))
    {
      $this->error($directory."config.php is not writable!");
      return false;
    }

    if(!$this->check_file_permissions($directory,"data/"))
    {
      $this->error("Data directory is not writable!");
      return false;
    }

    if(!file_exists($directory."data".DIRECTORY_SEPARATOR))
      mkdir($directory."data");

    return true;
  }
  else
  {
    $this->error("$directory does not exist!");
  }

  return false;
}

function exec_stage_database()
{
  // check sql parameters
  global $db;
  $directory="";
  if (isset($_REQUEST["directory"]))
    $directory=$_REQUEST["directory"];
  else
  {
    $this->error("No directory specified!");
    return false;
  }

  if (strrpos($directory,DIRECTORY_SEPARATOR) < strlen($directory) - 1)
    $directory=$directory.DIRECTORY_SEPARATOR;

  $result=$db->test_database($_REQUEST['host'], 
                 $_REQUEST['user'], 
                 $_REQUEST['password'], 
                 $_REQUEST['database']);
  if ($result!=true)
  {
    $this->error($result);
    return false;
  }

  $this->success("Connection to the database successful!");
  
  // check for writing the minimalistic configure file
  $config=$directory."config.php";
  
  // write minimalistic configuration file
  $f=fopen($config, "w+");
  if (!$f) 
  {
    $this->error("Could not write to config file $config");
    return false;
  }

  // The url_prefix is needed to have valid links to the themes in the
  // main repository of phTagr.
  $url_prefix=substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],DIRECTORY_SEPARATOR));

  fwrite($f, '<?php
// Configuration file for phTagr
   
$db_host=\''.$_REQUEST['host'].'\';
$db_user=\''.$_REQUEST['user'].'\';
$db_password=\''.$_REQUEST['password'].'\';
$db_database=\''.$_REQUEST['database'].'\';
// Prefix of phTagr tables.
$db_prefix=\''.$_REQUEST['prefix'].'\';
$phtagr_prefix=\''.getcwd().DIRECTORY_SEPARATOR."phtagr".DIRECTORY_SEPARATOR.'\';
$phtagr_url_prefix=\''.$url_prefix.'\';

?>');

  fclose($f);
  
  if (!$db->connect($config))
  {
    $this->error("Could not read the configuration file $config");
    // remove the configuration file
    return false;
  }
 
  $this->success("Configuration file created successfully");
  
  return true;
}

function exec_stage_tables()
{
  // check sql parameters
  global $db;

  $directory='';
  if(isset($_REQUEST['directory']))
    $directory=$_REQUEST['directory'];
  else
  {
    $this->error("No directory specified!");
    return false;
  }

  if (strrpos($directory,DIRECTORY_SEPARATOR) < strlen($directory) - 1)
    $directory=$directory.DIRECTORY_SEPARATOR;

  $config=$directory."config.php";
  $resolve=$_REQUEST['resolve'];

  if ($resolve=="delete")
  {
    $db->connect($config);
    if ($db->delete_tables())
      $this->success("Deletion of tables successful!");
    else
    {
      $this->error("Error while deleting tables!");
      return false;
    }
  }

  // We only need to create the tables if we don't want to use
  // the old ones.
  if ($resolve!="use")
  {
    if ($db->create_tables())
    {
      $this->success("Tables created successfully!");
    }
    else
    {
      $this->error("The tables could not be created successfully");
      // remove the configuration file
      return false;
    }

    if ($this->init_tables($directory."data"))
    {
      $this->success("Tables initialized successfully!");
    }
    else
    {
      $this->warning("Could not init the tables correctly");
      return false;
    }
    $this->success("Initialization of the tables successful!");
  }

  return true;
}

function exec_stage_admin()
{
  global $db;

  $install_id="";
  $directory="";
  $password="";
  $confirm="";

  if (isset($_REQUEST['id']))
    $install_id=$_REQUEST['id'];
  if (isset($_REQUEST['directory']))
    $directory=$_REQUEST['directory'];
  if (isset($_REQUEST['password']))
    $password=$_REQUEST['password'];
  if (isset($_REQUEST['confirm']))
    $confirm=$_REQUEST['confirm'];
 
  if (strrpos($directory,DIRECTORY_SEPARATOR) < strlen($directory) - 1)
    $directory=$directory.DIRECTORY_SEPARATOR;

  if (!$db->connect($directory."config.php"))
  {
    $this->error("Could not add admin account: No connection to the database!");
    return false;
  }
 
  if ($password=="")
  {
    $this->error("No password specified!");
    return false;
  }

  if ($confirm==$password)
  {
    $account = new SectionAccount();
    $account->user_create("admin",$password);
    $this->success("Admin account successfully created!");
    return true;
  }
  else
  {
    $this->error("Passwords do not match!");
  }

  return false;
}

function print_stage_welcome()
{
  $install_id="";
  if (isset($_REQUEST['id']))
    $install_id=$_REQUEST['id'];
  else 
    $install_id=md5(time()*rand());

  if (isset($_REQUEST['id']))
  {
    $this->error("Could not open $curr_path"."login.txt or it contained wrong installation id!");
  }

//  $install_id="ca0f54141d795ebb3c32223f246ba453"; //DEBUG
  $curr_path=getcwd().DIRECTORY_SEPARATOR;

  echo "<h3>Welcome to the installation of phTagr</h3>

<p>To ensure that nobody unwanted performs an installation, please create
a file <code><b>login.txt</b></code> in the directory
<code><b>$curr_path</b></code> with the following content:</p>

<p><code><b>$install_id</b></code></p>

<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"install\" />
<input type=\"hidden\" name=\"action\" value=\"authenticate\" />
<input type=\"hidden\" name=\"id\" value=\"$install_id\" />

<input type=\"submit\" value=\"Next\" />
</form>
";

}

function print_stage_directory()
{
  $install_id="";
  $directory=getcwd().DIRECTORY_SEPARATOR;

  if (isset($_REQUEST['id']))
    $install_id=$_REQUEST['id'];
  else
  {
    $this->error("No installation id specified!");
    return;
  }
  if (isset($_REQUEST['directory']))
    $directory=$_REQUEST['directory'];

//  $directory="/home/martin/public_html/phtagr_test/"; //DEBUG

  echo "<h3>Choosing the installation directory</h3>

<p>phTagr allows you to make multiple instances of phTagr which all use
the same codebase but have their own directory for upload, cache etc.
and their own configurations. In this step you need to choose where you
want to have this new instance installed to.</p>

<p>The directory in which you want to install this instance must exist and
bei either fully writable by the web server or at least the following
directories and files in it need write access by the web server:</p>

<p>
<table frame=\"box\">
<tr><td><code>index.php</code></td></tr>
<tr><td><code>image.php</code></td></tr>
<tr><td><code>config.php</code></td></tr>
<tr><td><code>data".DIRECTORY_SEPARATOR."</code></td></tr>
</table>
</p>

<p>
<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"install\" />
<input type=\"hidden\" name=\"action\" value=\"directory\" />
<input type=\"hidden\" name=\"id\" value=\"$install_id\" />

<table>
<tr>
<td>Directory:</td>
<td><input type=\"text\" name=\"directory\" value=\"$directory\" size=\"50\" /></td>
</tr>
</table>

<input type=\"submit\" value=\"Next\" />&nbsp;&nbsp;<input type=\"Reset\" value=\"Reset\" />
</p>
</form>
";
}

function print_stage_database()
{
  $install_id="";
  $directory=getcwd().DIRECTORY_SEPARATOR;

  if (isset($_REQUEST['id']))
    $install_id=$_REQUEST['id'];
  else
  {
    $this->error("No installation id specified!");
    return;
  }
  if (isset($_REQUEST['directory']))
    $directory=$_REQUEST['directory'];
  else
  {
    $this->error("No directory specified!");
    return;
  }

  echo "<h3>mySQL connection</h3>

<p>In this step we create the connection to the mySQL database. Please fill
in the parameters for your database in the following form:</p>

<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"install\" />
<input type=\"hidden\" name=\"action\" value=\"database\" />
<input type=\"hidden\" name=\"id\" value=\"$install_id\" />
<input type=\"hidden\" name=\"directory\" value=\"$directory\" />

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
  the table prefix. Otherwise you can ignore it.");

  echo "
<input type=\"submit\" value=\"Next\" />&nbsp;&nbsp;<input type=\"reset\" value=\"Reset\" />
</form>
";
}

function print_stage_tables()
{
  $install_id="";
  $directory=getcwd().DIRECTORY_SEPARATOR;

  if (isset($_REQUEST['id']))
    $install_id=$_REQUEST['id'];
  else
  {
    $this->error("No installation id specified!");
    return;
  }
  if (isset($_REQUEST['directory']))
    $directory=$_REQUEST['directory'];
  else
  {
    $this->error("No directory specified!");
    return;
  }

  echo "<h3>Conflict while initializing the tables</h3>

<p>It seems as if there is already a phTagr installation in that database with
the given prefix. To solve this conflict you can either use the existing
database, delete the existing database or return to
<a href=\"index.php?section=install&action=database&id=$install_id&directory=$directory\">this</a> step and use another prefix or a different database
this installation.</p>

<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"install\" />
<input type=\"hidden\" name=\"action\" value=\"tables\" />
<input type=\"hidden\" name=\"id\" value=\"$install_id\" />
<input type=\"hidden\" name=\"directory\" value=\"$directory\" />

<p>
<input type=\"radio\" name=\"resolve\" value=\"use\">Use the existing database.<br>
<input type=\"radio\" name=\"resolve\" value=\"delete\">Delete the existing database.<br>
</p>

<input type=\"submit\" value=\"OK\" />&nbsp;&nbsp;<input type=\"reset\" value=\"Reset\" />
</form>
";
}

function print_stage_admin()
{
  $install_id="";
  $directory="";

  if (isset($_REQUEST['id']))
    $install_id=$_REQUEST['id'];
  if (isset($_REQUEST['directory']))
    $directory=$_REQUEST['directory'];

  echo "<h3>Creation of the admin account</h3>

<p>In this final step you need to enter the details for the admin
account of this phTagr instance.</p>

<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"install\" />
<input type=\"hidden\" name=\"action\" value=\"admin\" />
<input type=\"hidden\" name=\"id\" value=\"$install_id\" />
<input type=\"hidden\" name=\"directory\" value=\"$directory\" />

<table>
  <tr><td>Username:</td><td><input type=\"text\" name=\"name\" value=\"admin\" disabled/><td></tr>
  <tr><td>Password:</td><td><input type=\"password\" name=\"password\"/><td></tr>
  <tr><td>Confirm:</td><td><input type=\"password\" name=\"confirm\"/><td></tr>
  <tr><td>Email:</td><td><input type=\"text\" name=\"email\"/><td></tr>
  <tr><td></td>
      <td><input type=\"submit\" value=\"Create\"/>&nbsp;&nbsp;
      <input type=\"reset\" value=\"Reset\"/></td></tr>
</table>

</form>";
}

function print_stage_cleanup()
{
  echo "<h3>Almost done!</h3>

<p>Please delete delete now the file
<code><b>".getcwd().DIRECTORY_SEPARATOR."login.txt</b></code> to ensure
that no one will create instances of phTagr that you do not want.</p>

<p>Have fun!</p>
";
}

function print_content()
{
  global $db;
  global $user;
  
  echo "<h2>Installation</h2>\n";
  $action=$_REQUEST['action'];

  if (!$this->exec_stage_authenticate())
  {
    $action=""; 
    $this->stage=0;
  }

  if ($action=='authenticate')
  {
    if ($this->exec_stage_authenticate())
      $this->stage=1;
    else
      $this->stage=0;
  }
  else if ($action=='directory')
  {
    if ($this->exec_stage_directory())
      $this->stage=2;
    else
      $this->stage=1;
  }
  else if ($action=='database')
  {
    if ($this->exec_stage_database())
    {
      if ($db->tables_exist())
        $this->stage=3;
      else
      {
        $this->exec_stage_tables();
        if ($this->init_tables())
        $this->stage=4;
      }
    }
    else
      $this->stage=2;
  }
  else if ($action=='tables')
  {
    if ($this->exec_stage_tables())
    {
      $this->stage=4;
      if (isset($_REQUEST['resolve']) && $_REQUEST['resolve']=="use")
        $this->stage=5;
    }
    else
      $this->stage=3;
  }
   else if ($action=='admin')
  {
    if ($this->exec_stage_admin())
      $this->stage=5;
    else
      $this->stage=4;
  }
   else if ($action=='cleanup')
  {
    if ($this->exec_stage_cleanup())
      $this->stage=6;
    else
      $this->stage=5;
  }
  
  switch ($this->stage) {
  case 1: $this->print_stage_directory(); break;
  case 2: $this->print_stage_database(); break;
  case 3: $this->print_stage_tables(); break;
  case 4: $this->print_stage_admin(); break;
  case 5: $this->print_stage_cleanup(); break;
  default: $this->print_stage_welcome(); break;
  }
}

}
?>
