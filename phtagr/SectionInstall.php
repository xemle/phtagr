<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006,2007 Sebastian Felis, sebastian@phtagr.org
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the 
 * License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/SectionAccount.php");
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Filesystem.php");

define("INSTALLER_STAGE_WELCOME", "0");
define("INSTALLER_STAGE_PATH", "1");
define("INSTALLER_STAGE_DATABASE", "2");
define("INSTALLER_STAGE_TABLES", "3");
define("INSTALLER_STAGE_ADMIN", "4");
define("INSTALLER_STAGE_CLEANUP", "5");
define("INSTALLER_STAGE_DONE", "6");

class SectionInstall extends SectionBase
{

/* $stage = 0 : welcome
   $stage = 1 : path
   $stage = 2 : database
   $stage = 3 : tables
   $stage = 4 : admin
   $stage = 5 : cleanup
   */
var $stage=0;

function _slashify($path)
{
  $last=$path[strlen($path)-1];
  if ($last!="/" && $last!="\\")
    $path.=DIRECTORY_SEPARATOR;
  return $path;
}

function _unslashify($path)
{
  $last=$path[strlen($path)-1];
  while ($last=="/" || $last=="\\")
  {
    $path=substr($path, 0, strlen($path)-1);
    $last=$path[strlen($path)-1];
  }
  return $path;
}

/** Removes the doubled backslashes on Windows systems*/
function _unescape_dir($path)
{
  $DS=DIRECTORY_SEPARATOR;
  $path=str_replace($DS.$DS, $DS, $path);
  
  return $this->_slashify($path);
}

function _escape_dir($path) 
{
  $DS=DIRECTORY_SEPARATOR;
  $path=str_replace($DS, $DS.$DS, $path);

  return $path;
}

function clear_session()
{
  global $log;
  $log->info("Clear the session");
  foreach($_SESSION as $key => $value)
    unset($_SESSION[$key]);
}

/** Insert default values to the table
  @return true on success. false on failure */
function init_tables()
{
  global $db, $log, $conf;
  $path="";
  $data_path="";

  $path=$_SESSION['path'];
  $data_path=$_SESSION['data_path'];

  // image cache
  $cache=$data_path."cache";
  if (!is_dir($cache) && !@mkdir($cache, 0775, true))
  {
    $log->err("Could not create directory '$cache'");
    $this->warning(sprintf(_("Could not create directory '%s'"), $cache)); 
    return false;
  }
  
  // upload dir
  $users=$data_path."users";
  if (!is_dir($users) && !@mkdir($users, 0775, true))
  {
    $log->err("Could not create directory '$users'");
    $this->warning(sprintf(_("Could not create directory '%s'"), $users)); 
    return false;
  }

  $db->init_tables();
  return true;
}

function SectionInstall()
{
  global $db;
  $this->name="install";
  $this->stage=0;
}

/** Reads the login file and checks it against the install id of the session 
  @return True is the login file contains the correct installation id */
function exec_stage_authenticate()
{
  global $log;
  $auth_file=getcwd().DIRECTORY_SEPARATOR."login.txt";

  if (file_exists($auth_file))
  {
    $f=fopen($auth_file,"r");
    $install_id=fgets($f,256); // This should be long enough for the md5 sum
    $install_id=trim($install_id);
    fclose($f);
    if ($install_id==$_SESSION['install_id'])
      return true;
    $log->err("Authentication of file '$auth_file' is incorrect");
    return false;
  }
  $log->err("Authentication file '$auth_file' is missing");
  return false;
}

/** Checks whether a file in a directory is writable or whether
  it is possible to create it.
  @param directory The directory in which the file should be in.
  @param file The name of the file that should be checked.
  @return true if the file can be created or if it is writable,
          false otherwise.
*/
function check_file_permissions($path, $file)
{
  $this->_slashify($path);

  if (file_exists($path.$file) && is_writable($path.$file))
    return true;

  if (is_writable($path) && !file_exists($path.$file))
    return true;

  return false;
}

function exec_stage_path()
{
  global $log;
  
  $log->info("Execute stage path");
  
  $cwd=$this->_unescape_dir(getcwd());
  $DS=DIRECTORY_SEPARATOR;
  $path="";
  $data_path="";
  $ext_data_path=true;

  // @todo clean path to real path
  $path=$this->_unescape_dir($_REQUEST['path']);

  if (strlen($_REQUEST['data_path']>0))
  {
    $data_path=$_REQUEST['data_path'];
    $ext_data_path=true;
    $log->info("data_path=".$data_path);
  }
  else
  {
    $data_path=$this->_unslashify($path).$DS."data";
    $ext_data_path=false;
  }
  $data_path=$this->_unescape_dir($data_path);

  if ($ext_data_path)
  {
    if (!is_dir($data_path))
    {
      $log->err("Data directory '$data_path' does not exists");
      $this->error (sprintf(_("The data directory '%s' does not exists!"), $data_path));
      return false;
    }
    if (!is_writable($data_path))
    {
      $log->err("Data directory '$data_path' is not writeable");
      $this->error (sprintf(_("The data directory '%s' is not writeable!"), $data_path));
      return false;
    }
  }

  // At first we need to check whether it is the current directory. If it
  // is, we don't need to write index.php.
  if ($path!=$cwd)
  {
    if (!is_dir($path))
    {
      $log->err("Directory '$path' does not exists");
      $this->error(sprintf(_("%s does not exist!"), $path));
      return false;
    }
    if (!is_writeable($path))
    {
      $log->err("Directory '$path' is not writeable");
      $this->error(sprintf(_("%s is not writeable!"), $path));
      return false;
    }
    
    $src_path=$cwd;
    $files=array("index.php", "image.php", "webdav.php");
    foreach($files as $file)
    {
      if (!copy ($src_path.$DS.$file, $path.$DS.$file))
      {
        $this->error(sprintf(_("Could not copy file '%s' to directory '%s'!"), $file, $path));
        return false;
      }
    }
  }

  $_SESSION['path']=$path;
  $_SESSION['data_path']=$data_path;
  $_SESSION['ext_data_path']=$ext_data_path;

  $log->debug("Set path=$path");
  $log->debug("Set data_path=$data_path");
  $log->debug("Set ext_data_path=".($ext_data_path?'true':'false'));
  return true;
}

/** @todo Check database values from REQUEST */
function exec_stage_database()
{
  // check sql parameters
  global $db, $log;
  
  $cwd=$this->_unescape_dir(getcwd());
  $path=$_SESSION['path'];
  $data_path=$_SESSION['data_path'];

  $error=$db->test_database($_REQUEST['db_host'], 
                 $_REQUEST['db_user'], 
                 $_REQUEST['db_password'], 
                 $_REQUEST['db_database']);
  if ($error)
  {
    $log->err("Could not test the database");
    $this->error($error);
    return false;
  }

  $this->success(_("Connection to the database successful!"));
  
  // check for writing the minimalistic configure file
  if (!$this->check_file_permissions($path, "config.php"))
    $config=$data_path."config.php";
  else
    $config=$path."config.php";
  
  // write minimalistic configuration file
  $f=fopen($config, "w+");
  if (!$f) 
  {
    $this->error(sprintf(_("Could not write to config file %s"), $config));
    return false;
  }

  // The url_prefix is needed to have valid links to the themes in the
  // main repository of phTagr.
  $htdocs=$this->_unescape_dir(dirname($_SERVER['PHP_SELF']));

  fwrite($f, '<?php
// Configuration file for phTagr
// 
// Please only modify if you know what you are doing!

// Database settings
$db_host=\''.$_REQUEST['db_host'].'\';
$db_user=\''.$_REQUEST['db_user'].'\';
$db_password=\''.$_REQUEST['db_password'].'\';
$db_database=\''.$_REQUEST['db_database'].'\';
// Prefix of phTagr tables.
$db_prefix=\''.$_REQUEST['db_prefix'].'\';

// The path to the actual phtagr sources
$phtagr_prefix=\''.$this->_unslashify($cwd).'\';

// The url to the phtagr base (needed for including the
// css and javascript files)
$phtagr_htdocs=\''.$this->_unslashify($htdocs).'\';

// The directory for the uploaded images, cache, etc.
$phtagr_data=\''.$this->_unslashify($data_path).'\';

?>');

  fclose($f);
  
  if (!$db->connect($config))
  {
    $this->error(sprintf(_("Could not read the configuration file %s"), $config));
    // remove the configuration file
    unlink($config);
    return false;
  }

  $this->success(_("Configuration file created successfully"));
  
  $params=array('db_host', 'db_user', 'db_database', 'db_prefix');
  foreach ($params as $param) 
  {
    $_SESSION[$param] = $_REQUEST[$param];
    $log->debug("set $param=".$_REQUEST[$param]);
  }
  return true;
}

function exec_stage_tables()
{
  // check sql parameters
  global $db, $log;

  $path=$_SESSION['path'];
  $data_path=$_SESSION['data_path'];

  // Per default we use the config file in the $data_path. So the
  // config file in the $path does not need to be writable.
  $config=$path."config.php";
  if (file_exists($data_path."config.php"))
    $config=$data_path."config.php";

  // @TODO: Tables are not created properly. Missing prefix!!!

  $resolve=$_REQUEST['db_resolve'];

  if ($resolve=="use") {
    $log->warn("Usinging existing tables.");
    return true;
  }
   
  if (!$db->connect($config)) {
    $log->err("Could not connect to the database");
    return false;
  }
  
  if ($resolve=='delete') {
    if ($db->delete_tables()) {
      $log->info("Delete all tables!");
      $this->warning(_("Old tables were deleted!"));
    }
    else
    {
      $log->err("Could not delete tables");
      $this->error(_("Error while deleting tables!"));
      return false;
    }
  }
  
  if ($db->create_tables())
  {
    $log->info("Tables created");
    $this->success(_("Tables created successfully!"));
  }
  else
  {
    $log->err("Could not create tables");
    $this->error(_("The tables could not be created successfully"));
    // remove the configuration file
    return false;
  }

  if ($this->init_tables())
  {
    $log->info("Tables initialized");
    $this->success(_("Tables initialized successfully!"));
  }
  else
  {
    $log->err("Could not initialize tables");
    $this->warning(_("Could not init the tables correctly"));
    return false;
  }

  return true;
}

function exec_stage_admin()
{
  global $db, $user, $log;

  $path=$_SESSION['path'];

  $username=$_REQUEST['username'];
  $password=$_REQUEST['password'];
  $confirm=$_REQUEST['confirm'];

  $data_path=$_SESSION['data_path'];

  
  if (strlen($username)==0)
  {
    $log->err("Username not given");
    $this->error(_("Username not given"));
    return false;
  }
  elseif (strlen($username)<4)
  {
    $log->err("Username is to short");
    $this->error(_("Username is to short"));
    return false;
  }
 
  if (strlen($password)==0)
  {
    $log->err("No password given");
    $this->error(_("No password given"));
    return false;
  }
  elseif (strlen($password)<4)
  {
    $log->err("Password is to short");
    $this->error(_("Password is to short!"));
    return false;
  }

  if ($confirm!=$password)
  {
    $log->err("Password and confirmation does not match");
    $this->error(_("Passwords do not match!"));
    return false;
  }

  // Per default we use the config file in the $data_path. So the
  // config file in the $path does not need to be writable.
  $config=$path."config.php";
  if (file_exists($data_path."config.php"))
    $config=$data_path."config.php";

  if (!$db->connect($config))
  {
    $log->err("Could not connect to the database. Config file: ".$config);
    $this->error(_("Could not add admin account: No connection to the database!"));
    return false;
  }

  $result=$user->create($username,$password,USER_ADMIN);
  if ($result<0)
  {
    $this->error(sprintf(_("The admin account could not be created! Error %d"), $result));
    return false;
  }
  $this->success(_("Admin account successfully created!"));
  return true;
}

function print_stage_welcome()
{
  global $log;
  $log->info("Enter stage welcome");
  
  if (!isset($_SESSION['install_id']))
  {
    $this->clear_session();
    $install_id=md5(time()*rand());
    $_SESSION['install_id']=$install_id;
    $log->info("Create new installation id: ".$install_id);
  }
  else
  {
    $install_id=$_SESSION['install_id'];
  }
  
  $path=$this->_slashify(getcwd());

  $this->h3(_("Welcome to the installation of phTagr"));

  echo "<p>".sprintf(_("To ensure that nobody unwanted performs an installation, please
create a file '%s' in the directory '%s' with the following content:"),
"<code><b>login.txt</b></code>", "<code><b>$path</b></code>")."</p>

<p><code><b>$install_id</b></code></p>\n";

  $this->p(_("Execute"));
  $this->p("<pre>echo $install_id > \"${path}login.txt\"</pre>");

  $url=new Url();
  echo "<form method=\"post\" action=\"".$url->get_url()."\">";
  $this->input_hidden("section", "install");
  $this->input_hidden("action", "authenticate");

  $this->input_submit(_("Next"));

  echo "</form>";
}

function print_stage_path()
{
  global $log;
  
  $log->info("Enter stage path");
  
  $path=$this->_slashify(getcwd());

  if (isset($_SESSION['path']))
    $path=$_SESSION['path'];

  $this->h3(_("Choosing the installation directory"));

  $this->p(_("phTagr allows you to make multiple instances of phTagr which all use
the same codebase but have their own directory for upload, cache etc.
and their own configurations. In this step you need to choose where you
want to have this new instance installed to."));

  $this->p(_("The directory in which you want to install this instance must exist and
bei either fully writable by the web server or at least the following
directories and files in it need write access by the web server:"));

  $this->p("<ul>
  <li><code>index.php</code></li>
  <li><code>image.php</code></li>
  <li><code>webdav.php</code></li>
  <li><code>config.php</code></li>
  <li><code>data".DIRECTORY_SEPARATOR."</code></li>
</ul>");

  $url=new Url();
  echo "<p><form method=\"post\" action=\"".$url->get_url()."\">";
  $this->input_hidden("section", "install");
  $this->input_hidden("action", "path");

  echo "<fieldset><ol><li>";
  $this->label(_("phTagr directory"));
  $this->input_text("path", $path);
  echo "</li></ol></fieldset>";

  $this->info(_("You can optionally specify a data directory that is not directly
accessible by the web browser. By doing so you can use a more secure accessing
mode in which the web server includes the images in his reply and the images
are not accessible through direct links."));

  echo "<fieldset><ol><li>";
  $this->label(_("Data directory"));
  $this->input_text("data_path", "");
  echo "</li></ol></fieldset>";

  $this->input_submit(_("Next"));
  $this->input_reset(_("Reset"));

  echo "</form></p>";
}

function print_stage_database()
{
  global $log;
  
  $log->info("Enter stage database");

  $this->h3(_("mySQL connection"));

  echo "<p>"._("In this step we create the connection to the mySQL database. Please
fill in the parameters for your database in the following form:")."</p>";

  $url=new Url();
  echo "<p><form method=\"post\" action=\"".$url->get_url()."\">";
  $this->input_hidden("section", "install");
  $this->input_hidden("action", "database");

  echo "<fieldset><ol>";
  
  echo "<li>";
  $this->label(_("Host:"));
  $this->input_text("db_host", (isset($_REQUEST['db_host'])?$_REQUEST['db_host']:"localhost"));
  echo "</li>\n";
  
  echo "<li>";
  $this->label(_("User:"));
  $this->input_text("db_user", (isset($_REQUEST['db_user'])?$_REQUEST['db_user']:"phtagr"));
  echo "</li>\n";
  
  echo "<li>";
  $this->label(_("Password:"));
  $this->input_password("db_password", "");
  echo "</li>\n";
  
  echo "<li>";
  $this->label(_("Database:"));
  $this->input_text("db_database", (isset($_REQUEST['db_database'])?$_REQUEST['db_database']:"phtagr"));
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Table prefix:"));
  $this->input_text("db_prefix", (isset($_REQUEST['db_prefix'])?$_REQUEST['db_prefix']:""));
  echo "</li>\n";
  
  echo "</ol></fieldset>";

  $this->info(_("To run multiple phTagr instances within one database, please
  use the table prefix. Otherwise you can ignore it."));

  $this->input_submit(_("Next"));
  $this->input_reset(_("Reset"));

  echo "</form></p>";
}

function print_stage_tables()
{
  global $log;
  
  $log->info("Enter stage table creation");

  $this->h3(_("Conflict while initializing the tables"));

  $url=new Url();
  $url->add_param("section", "install");
  $url->add_param("action", "database");
  $link=sprintf("<a href=\"".$url->get_url()."\">%s</a>",_("this"));

  $text=sprintf(_("It seems as if there is already a phTagr installation in that
database with the given prefix. To solve this conflict you can either use the
existing database, delete the existing database or return to
%s step and use another prefix or a different database this installation."),
$link);

  echo "<p>$text</p>";

  $url=new Url();
  echo "<p><form method=\"post\" action=\"".$url->get_url()."\">";
  $this->input_hidden("section", "install");
  $this->input_hidden("action", "tables");

  echo "<p>
<input type=\"radio\" name=\"db_resolve\" value=\"use\">"._("Use the existing database.")."<br>
<input type=\"radio\" name=\"db_resolve\" value=\"delete\">"._("Delete the existing database.")."<br>
</p>";

  $this->input_submit(_("Apply"));

  echo "</form></p>";
}

function print_stage_admin()
{
  global $log;
  
  $log->info("Enter stage admin");


  echo "<h3>"._("Creation of the admin account")."</h3>

<p>"._("In this final step you need to enter the details for the admin
account of this phTagr instance.")."</p>";

  $url=new Url();
  echo "<p><form method=\"post\" action=\"".$url->get_url()."\">";
  $this->input_hidden("section", "install");
  $this->input_hidden("action", "admin");

  echo "<fieldset><ol>";
  
  echo "<li>";
  $this->label(_("Username:"));
  $this->input_text("username", "admin");
  echo "</li>\n";
  
  echo "<li>";
  $this->label(_("Password:"));
  $this->input_password("password", "");
  echo "</li>\n";
  
  echo "<li>";
  $this->label(_("Confirm:"));
  $this->input_password("confirm", "");
  echo "</li>\n";
  
  echo "<li>";
  $this->label(_("Email:"));
  $this->input_text("email", "");
  echo "</li>\n";
  
  echo "</ol></fieldset>";

  $this->input_submit(_("Next"));
  $this->input_reset(_("Reset"));

  echo "</form></p>";
}

function print_stage_cleanup()
{
  global $log;
  
  $log->info("Enter stage final");

  $this->h3(_("Almost done!"));

  $login=getcwd().DIRECTORY_SEPARATOR."login.txt";

  echo "<p>".sprintf(_("Please delete delete now the file '%s' to ensure
that no one will create instances of phTagr that you do not want."),"<code><b>$login</b></code>")."</p>
";
  $path=$this->_slashify($_SESSION['path']);
  $data_path=$this->_slashify($_SESSION['data_path']);
  $config=$data_path."config.php";
  if (file_exists($config))
  {
    $this->info(sprintf(_("As a final step please move '%s' to '%s' and remove the write permissions!"),"<code><b>".$config."</b></code>", "<code><b>".$path."</code></b>")); 
  }

  $this->p("<a href=\"".$this->escape_html($_SERVER['PHP_SELF'])."\">"._("Have fun!")."</a>");

  $this->clear_session();
}

function print_content()
{
  global $db;
  global $user;
  global $log;
  
  $this->h2(_("Installation"));
  
  $action=$_REQUEST['action'];
  
  if (!$this->exec_stage_authenticate())
  {
    $action=""; 
    $this->stage=INSTALLER_STAGE_WELCOME;
  }

  $log->debug("action=$action");
  switch($action)
  {
    case 'authenticate':
      if($this->exec_stage_authenticate())
        $this->stage=INSTALLER_STAGE_PATH;
      break;
    case 'path':
      if ($this->exec_stage_path())
        $this->stage=INSTALLER_STAGE_DATABASE;
      else
        $this->stage=INSTALLER_STAGE_PATH;
      break;
    case 'database';
      if ($this->exec_stage_database())
      {
        $table_status=$db->tables_exist();

        switch($table_status) 
        {
          case 1:
            // All tables exists
            $this->stage=INSTALLER_STAGE_TABLES;
            break;
          case 0:
            // No tables exists
            $this->exec_stage_tables();
            if ($this->init_tables())
              $this->stage=INSTALLER_STAGE_ADMIN;
            break;
          case -1:
            $this->error(_("Some of the required tables do exist already. Please use another prefix or delete them manually!"));
            $this->stage=INSTALLER_STAGE_DATABASE;
            break;
          default:
            $log->err("Unknown return status");
            $this->error(_("Unknown return status"));
        }
      }
      else
        $this->stage=INSTALLER_STAGE_DATABASE;
      break;
    case 'tables':
      if ($this->exec_stage_tables())
      {
        $this->stage=INSTALLER_STAGE_ADMIN;
        if ($_REQUEST['db_resolve']=="use")
          $this->stage=INSTALLER_STAGE_CLEANUP;
      }
      else
        $this->stage=INSTALLER_STAGE_TABLES;
      break;
    case 'admin':
      if ($this->exec_stage_admin())
        $this->stage=INSTALLER_STAGE_CLEANUP;
      else
        $this->stage=INSTALLER_STAGE_ADMIN;
      break;
    case 'cleanup':
      if ($this->exec_stage_cleanup())
        $this->stage=INSTALLER_STAGE_DONE;
      else
        $this->stage=INSTALLER_STAGE_CLEANUP;
      break;
    default:
      $this->stage=INSTALLER_STAGE_WELCOME;
  }
  
  $log->debug("stage=".$this->stage);
  switch ($this->stage) {
  case INSTALLER_STAGE_PATH: 
    $this->print_stage_path(); 
    break;
  case INSTALLER_STAGE_DATABASE: 
    $this->print_stage_database(); 
    break;
  case INSTALLER_STAGE_TABLES: 
    $this->print_stage_tables(); 
    break;
  case INSTALLER_STAGE_ADMIN: 
    $this->print_stage_admin(); 
    break;
  case INSTALLER_STAGE_CLEANUP: 
    $this->print_stage_cleanup(); 
    break;
  default: 
    $this->print_stage_welcome(); 
    break;
  }
}

}
?>
