<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/SectionAccount.php");
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Filesystem.php");

define("INSTALLER_STAGE_WELCOME", "0");
define("INSTALLER_STAGE_DIRECTORY", "1");
define("INSTALLER_STAGE_DATABASE", "2");
define("INSTALLER_STAGE_TABLES", "3");
define("INSTALLER_STAGE_ADMIN", "4");
define("INSTALLER_STAGE_CLEANUP", "5");
define("INSTALLER_STAGE_DONE", "6");

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

/** Removes the doubled backslashes on Windows systems and removes the 
  tailing directory separator */
function _escape_dir($s)
{
  if (DIRECTORY_SEPARATOR=='\\')
    $s=str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $s);
  $len=strlen($s);
  if ($s{$len-1}==DIRECTORY_SEPARATOR)
    $s=substr($s, 0, $len-1);
    
  return $s;
}


function clear_session()
{
  if (isset($_SESSION['directory']))
    unset ($_SESSION['directory']);
  if (isset($_SESSION['install_id']))
    unset ($_SESSION['install_id']);
  if (isset($_SESSION['data_directory']))
    unset ($_SESSION['data_directory']);
}

/** Insert default values to the table
  @return true on success. false on failure */
function init_tables()
{
  global $db;
  global $conf;
  $fs=new Filesystem();
  $directory="";
  $data_directory="";

  if (isset($_SESSION["directory"]))
    $directory=$_SESSION["directory"];
  else
  {
    $this->error(_("Invalid installation session!"));
    return false;
  }

  $data_directory=$_SESSION["data_directory"];

  // image cache
  $cache=$data_directory.DIRECTORY_SEPARATOR."cache";
  if (!$fs->mkdir($cache, true))
  {
    $this->warning(sprintf(_("Could not create directory '%s'"), $cache)); 
    return false;
  }
  
  // upload dir
  $upload=$data_directory.DIRECTORY_SEPARATOR."users";
  if (!$fs->mkdir($upload, true))
  {
    $this->warning(sprintf(_("Could not create directory '%s'"), $upload)); 
    return false;
  }

  return true;
}

function SectionInstall()
{
  global $db;
  $this->name="install";
  $this->stage=0;
}

function check_install_id ($id='')
{
  $auth_file=getcwd().DIRECTORY_SEPARATOR."login.txt";

  if (file_exists($auth_file))
  {
    $f=fopen($auth_file,"r");
    $install_id=fgets($f,256); // This should be long enough for the md5 sum
    $install_id=trim($install_id);
    fclose($f);
    if ($id==$install_id)
      return true;
  }
  return false;
}

function exec_stage_authenticate()
{
  if (isset($_REQUEST['install_id']))
  {
    $_SESSION['install_id']=$_REQUEST['install_id'];
  }
  if (isset($_SESSION['install_id']))
  {
    if ($this->check_install_id($_SESSION['install_id']))
    {
      return true;
    }
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
  $len=strlen($directory);
  if ($directory{$len-1}!=DIRECTORY_SEPARATOR)
    $directory.=DIRECTORY_SEPARATOR;
    
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
  $fs=new Filesystem();
  
  $directory="";
  $data_directory="";
  $ext_data_directory=true;

  if (isset($_REQUEST['directory']))
    $directory=$_REQUEST['directory'];
  else
  {
    $this->error(_("No directory specified!"));
    return false;
  }
  $directory=$this->_escape_dir($directory);

  if (isset($_REQUEST['data_directory']))
    $data_directory=$_REQUEST['data_directory'];

  if ($data_directory=="")
  {
    $data_directory=$directory.DIRECTORY_SEPARATOR."data";
    $ext_data_directory=false;
  }
  $data_directory=$this->_escape_dir($data_directory);
 
  $dir_writable=is_writable($directory);

  if ($ext_data_directory && !is_writable($data_directory))
  {
    $this->error (_("Your specified data directory is not writable!"));
    return false;
  }

  if (file_exists($directory))
  {
    // At first we need to check whether it is the current directory. If it
    // is, we don't need to write index.php.

    if ($directory!=getcwd())
    {
      // In this case we only need to check, whether we can write
      // the config file and into the data dir.
      if (!$this->check_file_permissions($directory,"index.php"))
      {
        $this->error(sprintf(_("File '%s' is not writable!"), "index.php"));
        return false;
      }
      else
      {
        // We need to copy the file there
        if (!copy (getcwd().DIRECTORY_SEPARATOR."index.php", $directory.DIRECTORY_SEPARATOR."index.php"))
        {
          $this->error(sprintf(_("Could not copy file '%s' to directory '%s'!"), "index.php", $directory));
          return false;
        }
      }
      if(!$this->check_file_permissions($directory,"image.php"))
      {
        $this->error(sprintf(_("File '%s' is not writable!"), "image.php"));
        return false;
      }
      else
      {
        // We need to copy the file there
        if (!copy (getcwd().DIRECTORY_SEPARATOR."image.php", $directory.DIRECTORY_SEPARATOR."image.php"))
        {
          $this->error(sprintf(_("Could not copy image.php to %s!"), $directory));
          return false;
        }
      }
    }

    if (!$ext_data_directory)
    {
      if (!$this->check_file_permissions($directory,"data"))
      {
        $this->error(_("Data directory is not writable!"));
        return false;
      }

      if(!file_exists($directory.DIRECTORY_SEPARATOR."data"))
        $fs->mkdir($directory."data");
    }

    $_SESSION['directory']=$directory;
    $_SESSION['data_directory']=$data_directory;

    return true;
  }
  else
  {
    $this->error(sprintf(_("%s does not exist!"), $directory));
  }

  return false;
}

/** @todo Check database values from REQUEST */
function exec_stage_database()
{
  // check sql parameters
  global $db;
  $directory="";
  if (isset($_SESSION["directory"]))
    $directory=$_SESSION["directory"];
  else
  {
    $this->error(_("Invalid installation session!"));
    return false;
  }
  if (isset($_SESSION["data_directory"]))
    $data_directory=$_SESSION["data_directory"];

  if (strrpos($directory,DIRECTORY_SEPARATOR) < strlen($directory) - 1)
    $directory=$directory.DIRECTORY_SEPARATOR;

  $error=$db->test_database($_REQUEST['host'], 
                 $_REQUEST['user'], 
                 $_REQUEST['password'], 
                 $_REQUEST['database']);
  if ($error)
  {
    $this->error($error);
    return false;
  }

  $this->success(_("Connection to the database successful!"));
  
  // check for writing the minimalistic configure file
  if (!$this->check_file_permissions ($directory, "config.php"))
    $config=$data_directory.DIRECTORY_SEPARATOR."config.php";
  else
    $config=$directory.DIRECTORY_SEPARATOR."config.php";
  
  // write minimalistic configuration file
  $f=fopen($config, "w+");
  if (!$f) 
  {
    $this->error(sprintf(_("Could not write to config file %s"), $config));
    return false;
  }

  // The url_prefix is needed to have valid links to the themes in the
  // main repository of phTagr.
  $htdocs=$this->_escape_dir(dirname($_SERVER['PHP_SELF']));

  fwrite($f, '<?php
// Configuration file for phTagr
// 
// Please only modify if you know what you are doing!

// Database settings
$db_host=\''.$_REQUEST['host'].'\';
$db_user=\''.$_REQUEST['user'].'\';
$db_password=\''.$_REQUEST['password'].'\';
$db_database=\''.$_REQUEST['database'].'\';
// Prefix of phTagr tables.
$db_prefix=\''.$_REQUEST['prefix'].'\';

// The path to the actual phtagr sources
$phtagr_prefix=\''.getcwd().'\';

// The url to the phtagr base (needed for including the
// css and javascript files)
$phtagr_htdocs=\''.$htdocs.'\';

// The directory for the uploaded images, cache, etc.
$phtagr_data=\''.$data_directory.'\';

?>');

  fclose($f);
  
  if (!$db->connect($config))
  {
    $this->error(sprintf(_("Could not read the configuration file %s"), $config));
    // remove the configuration file
    return false;
  }

  $this->success(_("Configuration file created successfully"));
  
  return true;
}

function exec_stage_tables()
{
  // check sql parameters
  global $db;

  $directory='';
  if(isset($_SESSION['directory']))
    $directory=$_SESSION['directory'];
  else
  {
    $this->error(_("Invalid installation session!"));
    return false;
  }

  $data_directory=$_SESSION['data_directory'];

  if (strrpos($directory,DIRECTORY_SEPARATOR) < strlen($directory) - 1)
    $directory=$directory.DIRECTORY_SEPARATOR;

  // Per default we use the config file in the $data_directory. So the
  // config file in the $directory does not need to be writable.
  $config=$directory."config.php";
  if (file_exists($data_directory."config.php"))
    $config=$data_directory."config.php";

  // @TODO: Tables are not created properly. Missing prefix!!!

  $resolve=$_REQUEST['resolve'];

  if ($resolve=="delete")
  {
    $db->connect($config);
    if ($db->delete_tables())
      $this->success(_("Deletion of tables successful!"));
    else
    {
      $this->error(_("Error while deleting tables!"));
      return false;
    }
  }

  // We only need to create the tables if we don't want to use
  // the old ones.
  if ($resolve!="use")
  {
    if ($db->create_tables())
    {
      $this->success(_("Tables created successfully!"));
    }
    else
    {
      $this->error(_("The tables could not be created successfully"));
      // remove the configuration file
      return false;
    }

    if ($this->init_tables())
    {
      $this->success(_("Tables initialized successfully!"));
    }
    else
    {
      $this->warning(_("Could not init the tables correctly"));
      return false;
    }
  
  }

  return true;
}

function exec_stage_admin()
{
  global $db;
  global $user;

  $install_id="";
  $directory="";
  $data_directory="";
  $password="";
  $confirm="";
  $config="";

  if (isset($_SESSION['directory']))
    $directory=$_SESSION['directory'];
  if (isset($_REQUEST['password']))
    $password=$_REQUEST['password'];
  if (isset($_REQUEST['confirm']))
    $confirm=$_REQUEST['confirm'];
  if (isset($_SESSION['directory']))
    $directory=$_SESSION['directory'];
  else
  {
    $this->error(_("Invalid installation session!"));
    return false;
  }
  $data_directory=$_SESSION['data_directory'];
 
  if (strrpos($directory,DIRECTORY_SEPARATOR) < strlen($directory) - 1)
    $directory=$directory.DIRECTORY_SEPARATOR;

  // Per default we use the config file in the $data_directory. So the
  // config file in the $directory does not need to be writable.
  $config=$directory."config.php";
  if (file_exists($data_directory."config.php"))
    $config=$data_directory."config.php";

  if (!$db->connect($config))
  {
    $this->error(_("Could not add admin account: No connection to the database!"));
    return false;
  }
 
  if ($password=="")
  {
    $this->error(_("No password specified!"));
    return false;
  }

  if ($confirm==$password)
  {
    global $user;

    $result=$user->create("admin",$password,USER_ADMIN);
    if ($result<0)
    {
      $this->error(sprintf(_("The admin account could not be created! Error %d"), $result));
      return false;
    }
    $this->success(_("Admin account successfully created!"));
    return true;
  }
  else
  {
    $this->error(_("Passwords do not match!"));
  }

  return false;
}

function print_stage_welcome()
{
  $install_id="";
  
  $install_id=md5(time()*rand());

  if (isset($_SESSION['install_id']))
  {
    $this->error(sprintf(_("Could not open %slogin.txt or it contained wrong installation id!"),$curr_path));
    unset($_SESSION['install_id']);
  }

  $this->clear_session();

  $curr_path=getcwd().DIRECTORY_SEPARATOR;

  echo "<h3>"._("Welcome to the installation of phTagr")."</h3>

<p>".sprintf(_("To ensure that nobody unwanted performs an installation, please
create a file '%s' in the directory '%s' with the following content:"),
"<code><b>login.txt</b></code>", "<code><b>$curr_path</b></code>")."</p>

<p><code><b>$install_id</b></code></p>

<p>"._("If you run phTagr in an Linux environment, execute")."</p>
<pre>$> echo $install_id > $curr_path"."login.txt</pre>

<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"install\" />
<input type=\"hidden\" name=\"action\" value=\"authenticate\" />
<input type=\"hidden\" name=\"install_id\" value=\"$install_id\" />


<input type=\"submit\" value=\""._("Next")."\" />
</form>
";

}

function print_stage_directory()
{
  $install_id="";
  $directory=getcwd().DIRECTORY_SEPARATOR;

  if (isset($_SESSION['directory']))
    $directory=$_SESSION['directory'];

  echo "<h3>"._("Choosing the installation directory")."</h3>

<p>"._("phTagr allows you to make multiple instances of phTagr which all use
the same codebase but have their own directory for upload, cache etc.
and their own configurations. In this step you need to choose where you
want to have this new instance installed to.")."</p>

<p>"._("The directory in which you want to install this instance must exist and
bei either fully writable by the web server or at least the following
directories and files in it need write access by the web server:")."</p>

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

<table>
<tr>
<td>"._("Directory:")."</td>
<td><input type=\"text\" name=\"directory\" value=\"$directory\" size=\"50\" /></td>
</tr>
</table>
</p>

";
$this->info(_("You can optionally specify a datadirectory that is not directly
accessible by the web browser. By doing so you can use a more secure accessing
mode in which the web server includes the images in his reply and the images
are not accessible through direct links."));

echo "<p>
<table>
<tr>
<td>";
echo _("Data directory:");
echo "</td>
<td><input type=\"text\" name=\"data_directory\" value=\"\" size=\"50\" /></td>
</tr>
</table>
</p>

<input type=\"submit\" value=\""._("Next")."\" />&nbsp;&nbsp;<input type=\"reset\" value=\""._("Reset")."\" />
</form>
</p>
";
}

function print_stage_database()
{
  $install_id="";
  $directory=getcwd().DIRECTORY_SEPARATOR;

  if (isset($_SESSION['directory']))
    $directory=$_SESSION['directory'];
  else
  {
    $this->error(_("Invalid installation session!"));
    return;
  }

  echo "<h3>"._("mySQL connection")."</h3>

<p>"._("In this step we create the connection to the mySQL database. Please
fill in the parameters for your database in the following form:")."</p>

<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"install\" />
<input type=\"hidden\" name=\"action\" value=\"database\" />

<table>
  <tr>
    <td>"._("Host:")."</td>
    <td><input type=\"text\" name=\"host\" value=\"localhost\" /></td>
  </tr><tr>
    <td>"._("User:")."</td>
    <td><input type=\"text\" name=\"user\" value=\"\" /></td>
  </tr><tr>
    <td>"._("Password:")."</td>
    <td><input type=\"password\" name=\"password\" /></td>
  </tr><tr>
    <td>"._("Database:")."</td>
    <td><input type=\"text\" name=\"database\" value=\"\" /></td>
  </tr><tr>
    <td>"._("Table Prefix:")."</td>
    <td><input type=\"text\" name=\"prefix\" value=\"\" /></td>
  </tr>
</table>

";
  $this->info(_("To run multiple phTagr instances within one database, please
  use the table prefix. Otherwise you can ignore it."));

  echo "
<input type=\"submit\" value=\""._("Next")."\" />&nbsp;&nbsp;<input type=\"reset\" value=\""._("Reset")."\" />
</form>
";
}

function print_stage_tables()
{
  $install_id="";
  $directory=getcwd().DIRECTORY_SEPARATOR;

  if (isset($_SESSION['directory']))
    $directory=$_SESSION['directory'];
  else
  {
    $this->error(_("Invalid installation session!"));
    return;
  }

  echo "<h3>"._("Conflict while initializing the tables")."</h3>

";

$link=sprintf("<a href=\"index.php?section=install&action=database\">%s</a>",_("this"));

$text=sprintf(_("It seems as if there is already a phTagr installation in that
database with the given prefix. To solve this conflict you can either use the
existing database, delete the existing database or return to
%s step and use another prefix or a different database this installation."),
$link);

echo "<p>$text</p>

<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"install\" />
<input type=\"hidden\" name=\"action\" value=\"tables\" />

<p>
<input type=\"radio\" name=\"resolve\" value=\"use\">"._("Use the existing database.")."<br>
<input type=\"radio\" name=\"resolve\" value=\"delete\">"._("Delete the existing database.")."<br>
</p>

<input type=\"submit\" value=\"OK\" />&nbsp;&nbsp;<input type=\"reset\" value=\""._("Reset")."\" />
</form>
";
}

function print_stage_admin()
{
  $install_id="";
  $directory="";

  if (isset($_SESSION['directory']))
    $directory=$_SESSION['directory'];

  echo "<h3>"._("Creation of the admin account")."</h3>

<p>"._("In this final step you need to enter the details for the admin
account of this phTagr instance.")."</p>

<form method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"install\" />
<input type=\"hidden\" name=\"action\" value=\"admin\" />

<table>
  <tr><td>"._("Username:")."</td><td><input type=\"text\" name=\"name\" value=\"admin\" disabled/><td></tr>
  <tr><td>"._("Password:")."</td><td><input type=\"password\" name=\"password\"/><td></tr>
  <tr><td>"._("Confirm:")."</td><td><input type=\"password\" name=\"confirm\"/><td></tr>
  <tr><td>"._("Email:")."</td><td><input type=\"text\" name=\"email\"/><td></tr>
  <tr><td></td>
      <td><input type=\"submit\" value=\""._("Create")."\"/>&nbsp;&nbsp;
      <input type=\"reset\" value=\""._("Reset")."\"/></td></tr>
</table>

</form>";
}

function print_stage_cleanup()
{
  echo "<h3>"._("Almost done!")."</h3>

<p>".sprintf(_("Please delete delete now the file '%s' to ensure
that no one will create instances of phTagr that you do not want."),"<code><b>".getcwd().DIRECTORY_SEPARATOR."login.txt</b></code>")."</p>
";
  if (file_exists ($_SESSION['data_directory']."config.php"))
  {
    $this->info(sprintf(_("As a final step please move '%s' to '%s' and remove the write permissions!"),"<code><b>".$_SESSION['data_directory']."config.php</b></code>", "<code><b>".$_SESSION['directory']."</code></b>")); 
  }

echo "
<p><a href=\"".$_SERVER['PHP_SELF']."\">"._("Have fun!")."</a></p>
";
  $this->clear_session();
}

function print_content()
{
  global $db;
  global $user;
  
  echo "<h2>"._("Installation")."</h2>\n";
  $action=$_REQUEST['action'];
  
  if (!$this->exec_stage_authenticate())
  {
    $action=""; 
    $this->stage=INSTALLER_STAGE_WELCOME;
  }

  if ($action=='authenticate')
  {
    if($this->exec_stage_authenticate())
      $this->stage=INSTALLER_STAGE_DIRECTORY;
  }
  if ($action=='directory')
  {
    if ($this->exec_stage_directory())
      $this->stage=INSTALLER_STAGE_DATABASE;
    else
      $this->stage=INSTALLER_STAGE_DIRECTORY;
  }
  else if ($action=='database')
  {
    if ($this->exec_stage_database())
    {
      $table_status=$db->tables_exist();

      if ($table_status==1)
        $this->stage=INSTALLER_STAGE_TABLES;
      else if ($table_status==0)
      {
        $this->exec_stage_tables();
        if ($this->init_tables())
        $this->stage=INSTALLER_STAGE_ADMIN;
      }
      else
      {
        $this->error(_("Some of the required tables do exist already. Please use another prefix or delete them manually!"));
        $this->stage=INSTALLER_STAGE_DATABASE;
      }
    }
    else
      $this->stage=INSTALLER_STAGE_DATABASE;
  }
  else if ($action=='tables')
  {
    if ($this->exec_stage_tables())
    {
      $this->stage=INSTALLER_STAGE_ADMIN;
      if (isset($_REQUEST['resolve']) && $_REQUEST['resolve']=="use")
        $this->stage=INSTALLER_STAGE_CLEANUP;
    }
    else
      $this->stage=INSTALLER_STAGE_TABLES;
  }
  else if ($action=='admin')
  {
    if ($this->exec_stage_admin())
      $this->stage=INSTALLER_STAGE_CLEANUP;
    else
      $this->stage=INSTALLER_STAGE_ADMIN;
  }
  else if ($action=='cleanup')
  {
    if ($this->exec_stage_cleanup())
      $this->stage=INSTALLER_STAGE_DONE;
    else
      $this->stage=INSTALLER_STAGE_CLEANUP;
  }
 
  switch ($this->stage) {
  case INSTALLER_STAGE_DIRECTORY: $this->print_stage_directory(); break;
  case INSTALLER_STAGE_DATABASE: $this->print_stage_database(); break;
  case INSTALLER_STAGE_TABLES: $this->print_stage_tables(); break;
  case INSTALLER_STAGE_ADMIN: $this->print_stage_admin(); break;
  case INSTALLER_STAGE_CLEANUP: $this->print_stage_cleanup(); break;
  default: $this->print_stage_welcome(); break;
  }
}

}
?>
