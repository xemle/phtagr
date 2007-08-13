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

global $phtagr_prefix;
require_once "HTTP/WebDAV/Server.php";
require_once "System.php";
include_once("$phtagr_lib/Constants.php");
include_once("$phtagr_lib/ImageSync.php");

/**
 * WebdavServer 
 *
 * based on Filesystem.php from Hartmut Holzgraefe <hartmut@php.net>
 *
 */
class WebdavServer extends HTTP_WebDAV_Server
{

/**
 * Root directory for WebDAV access
 *
 * Defaults to webserver document root (set by ServeRequest)
 *
 * @access private
 * @var  string
 */
var $_base="";

var $_realm="phtagr.org";

var $_put_fspath="";

function WebdavServer()
{
  $this->HTTP_WebDAV_Server();
}

/** Set a new base directory
  @param base Base directory
  @return True on success, false otherwise
  @note The base directory must be exists, otherwise it returns false */
function set_base($base)
{
  if (!is_dir($base))
    return false;
  $this->_base=$this->_unslashify($base);
  return true;
}

/** Returns the filesystem path to the corresponding request path
  @param path Request path
  @return filesystem path */
function get_fspath($path)
{
  return $this->_base.$path;
}

/** Sets a new realm
  @param realm Realm name */
function set_realm($realm)
{
  $this->_realm=$realm;
}

/** Set string for server identification 
  @param text Text of server identification */
function set_powered_by($text)
{
  $this->dav_powered_by=$text;
}

/** Encodes the paths of an url. E.g '/space test/' becomes '/space%20test/' 
  @param path Path
  @return Escaped path
  @note Requires PHP 5 (uses references in foreach statement) */
function path_rawurlencode($path)
{
  $paths=explode('/', $path);
  foreach ($paths as &$part)
    $part=rawurlencode($part);
  return implode('/', $paths);
}

function ServeRequest($base=false) 
{
  global $log;
  $log->trace("ServeRequest: base=$base");

  // special treatment for litmus compliance test
  // reply on its identifier header
  // not needed for the test itself but eases debugging
  if (function_exists("apache_request_headers"))
  {
    foreach (apache_request_headers() as $key => $value) 
    {
      if (stristr($key, "litmus")) 
      {
        $log->trace("Litmus test $value");
        header("X-Litmus-reply: ".$value);
      }
    }
  }

  // set root directory, defaults to webserver document root if not set
  if ($base) {
    $this->set_base(realpath($base));
  } else if (!$this->_base) {
    $this->set_base($this->_SERVER['DOCUMENT_ROOT']);
  }

  // let the base class do all the work
  parent::ServeRequest();
}

/** Returns the authorization header. It tryies to fetch the HTTP
 * authorization header from the apache header, from
 * _SERVER[HTTP_AUTHORIZATION] variable or from _SERVER[PHP_AUTH_DIGEST]. If no
 * header information is available, it returns false
  @return HTTP authorization header. False if no header was found */
function get_auth_header()
{
  global $log;
  if (function_exists('apache_request_headers')) {
    $arh=apache_request_headers();
    $hdr=$arh['Authorization'];
  } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $hdr=$_SERVER['HTTP_AUTHORIZATION'];
  } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
    $hdr=$_SERVER['PHP_AUTH_DIGEST'];
  } else {
    $log->debug("Could not find an authentication header");
    $hdr=false;
  }
  return $hdr;
}

/** Parse the http authorization header and checks for all required fields. 
  @param auth_hdr Authoization header
  @return array of authorization parameters. False on missing parameters */
function _http_digest_parse($auth_hdr)
{
  global $log;

  // protect against missing data
  $needed_parts=array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1, 'opaque'=>1);
  $data=array();

  preg_match_all("@(\\w+)=(['\"]?)([a-zA-Z0-9=%./\\_\\\\-]+)\\2@", $auth_hdr, $matches, PREG_SET_ORDER);

  foreach ($matches as $m) {
    $data[$m[1]]=$m[3];
    unset($needed_parts[$m[1]]);
  }

  if ($needed_parts)
    $log->debug("Missing authorization part(s): ".implode(", ", array_keys($needed_parts)));

  return $needed_parts ? false : $data;
}

/** Checks the session to be active and validates the request counter. If the
 * session is not alive or the request counter was already used, an
 * unauthorized response is thrown
  @param sid Session id
  @param nc Request counter */
function _check_session($sid, $nc)
{
  global $log;
  // Take the session ID from opaque value and check alive
  @session_id($sid);
  @session_start();
  if (!isset($_SESSION['nc']))
  {
    $log->trace("Authorization failed: Unknown or died session ($sid)");
    header('HTTP/1.1 401 Unauthorized: Unknown or died session');
    $this->_add_auth_header();
    echo "<h1>Invalid Authorization: Unknwon or died session</h1>";
    session_write_close();
    die();
  }

  if ($_SESSION['nc']==$nc)
    $log->warn("Same request counter $nc is used!");

  // Check request counter
  if ($_SESSION['nc']>=$nc)
  {
    $log->trace("Authorization failed: Reused request counter. Current=".$_SESSION['nc'].", Request counter=$nc");
    header('HTTP/1.1 401 Unauthorized: Reused request counter');
    $this->_add_auth_header();
    echo "<h1>Invalid Authorization: Reused request counter</h1>";
    session_write_close();
    die();
  }
  $_SESSION['nc']=$nc;
}

/** Add authentication header to the response. The session keeps a login
 * counter. If more than 3 logins where done, it denies the access by omitting
 * the authentication header
  @return True if the authentication header was added */
function _add_auth_header()
{
  global $log;

  // Missuse opaque value as session id
  $opaque=session_id();
  $_SESSION['login.counter']=$_SESSION['login.counter']+1;
  if ($_SESSION['login.counter']>3)
  {
    $log->warn('login countes exceeded');
    return false;
  }
  
  header('WWW-Authenticate: Digest realm="'.$this->_realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.$opaque.'",algorithm="MD5"');
  return true;
}

/**
 * No authentication is needed here
 *
 * @access private
 * @param  string  HTTP Authentication type (Basic, Digest, ...)
 * @param  string  Username
 * @param  string  Password
 * @return bool  true on successful authentication
 */
function check_auth($type, $auser, $apass) 
{
  global $user, $log, $conf;
  $log->trace("check_auth: type=$type, auser=$auser, pass=$apass");

  $auth_hdr=$this->get_auth_header();
  if (!strlen($auth_hdr))
  {
    @session_id(md5(uniqid(rand(), true)));
    @session_start();
    $_SESSION['nc']=0;
    // Set cookie for valid session
    $log->trace("Create new Session: ".session_id());
    header('HTTP/1.1 401 Unauthorized');
    $this->_add_auth_header();
    $log->trace("Authorization required");
    session_write_close();
    die('401 Unauthorized');
  }

  // analyze the authorization credentials
  $data=$this->_http_digest_parse($auth_hdr);
  if (empty($data['username'])) {
    @session_id(md5(uniqid(rand(), true)));
    @session_start();
    $_SESSION['nc']=0;
    $log->trace("Authorization failed: Authorization values are missing");
    header('HTTP/1.1 401 Unauthorized');
    $this->_add_auth_header();
    echo "<h1>Invalid Authorization: Authorization is missing</h1>";
    session_write_close();
    die();
  }
 
  $this->_check_session($data['opaque'], intval($data['nc'])); 

  // Windows client syntax "<domain>\<username>"
  if (preg_match("/^(.*)\\\\(.*)$/", $data['username'], $matches))
  {
    $data['domain']=$matches[1];
    $data['winusername']=$matches[2];
    $username=$data['winusername'];
    $log->trace("matches=".print_r($matches, true));
  }
  else
  {
    $username=$data['username'];
  }

  $id=$user->get_id_by_name($username);
  if ($id<0)
  {
    $log->debug("Authorization failed: Invalid username $username");
    header('HTTP/1.1 401 Unauthorized: User or password incorrect');
    $this->_add_auth_header();
    echo "<h1>Invalid Authorization: User or password incorrect</h1>";
    die();
  }
  $user->init_by_id($id);
  $conf->load($id);

  if ($conf->get('webdav.enabled', 0)!=1)
  {
    $log->debug("Authorization failed: Service unavailable for $username");
    header('HTTP/1.1 503 Unauthorized: Service unavailable');
    echo "<h1>Invalid Authorization: Service Unvailable</h1>";
    die();
  }

  // generate the valid response
  $A1=md5($data['username'].':'.$this->_realm.':'.$user->get_password());
  $A2=md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
  $valid_response=md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
  if ($data['response']!=$valid_response) {
    $log->warn("Authorization failed: Invalid response");
    header('HTTP/1.1 401 Unauthorized: User or password incorrect');
    $this->_add_auth_header();
    echo "<h1>Invalid Authorization: User or password incorrect</h1>";
    die();
  }

  $log->debug("Correct authentication for ".$user->get_name(), -1, $user->get_id());

  if ($user->is_guest())
  {
    $creator_id=$user->get_creator();
    if ($creator_id<=0)
    {
      $log->warn("Guest creator not found: Invalid creatorid $creator_id");
      header('HTTP/1.1 404 Not Found');
      echo "<h1>User directory not found</h1>";
      die(); 
    }
    $creator=new User($user->get_creator());
    $path=$creator->get_upload_dir();
    $log->trace("Get userdir from creator $path");
  }
  else
  {
    $path=$user->get_upload_dir();
  }

  if (!is_dir($path) || !is_readable($path))
  {
    $log->err("User directory '$path' does not exists or is not readable");
    header('HTTP/1.1 404 Not Found');
    echo "<h1>User directory not found</h1>";
    die();
  }

  if (!$this->set_base($path))
  {
    $log->err("User directory '$path' could not be set as base directory");
    header('HTTP/1.1 404 Not Found');
    echo "<h1>User directory not found</h1>";
    die();
  }

  return true;
}

/** Returns sql statement for the where clause which checks the acl */
function _add_sql_where_acl()
{
  global $db, $log, $user;

  $acl='';

  if ($user->is_admin())    
    return $acl;

  if ($user->is_guest())
    $acl.=" AND i.user_id=".$user->get_creator();

  // if requested user id is not the own user id
  if ($user->is_member() || $user->is_guest())
  {
    $acl.=" AND ( i.group_id IN (".
          " SELECT group_id".
          " FROM $db->usergroup".
          " WHERE user_id=".$user->get_id().
          " AND i.gacl>=".ACL_READ_ORIGINAL." )";
    if ($user->is_member())
      $acl.=" OR i.macl>=".ACL_READ_ORIGINAL;
    else
      $acl.=" OR i.pacl>=".ACL_READ_ORIGINAL;
    $acl.=" )";
  }
  else {
    $acl.=" AND i.pacl>=".ACL_READ_ORIGINAL;
  }
  $log->trace("_add_sql_where_acl=$acl");

  return $acl;
}

/** Checks if the path could be read by the user
  @param path System directory or filename
  @return True if user is authorized to read directory. False otherwise 
  @todo This function must be implemented */
function _has_read_rights($fspath)
{
  global $db, $user, $log;
  $log->trace("_has_read_rights: $fspath");

  if ($user->is_member())
    return true;

  $spath=mysql_escape_string($this->_slashify(dirname($fspath)));
  $sfile=mysql_escape_string(basename($fspath));

  if (is_dir($path))
    $where="path LIKE '$spath%'";
  else
    $where="path='$spath' AND filename='$sfile'";

  $sql="SELECT id".
       " FROM $db->images AS i".
       " WHERE $where";
  $sql.=$this->_add_sql_where_acl();
  // We need just one 
  $sql.=" LIMIT 0,1";

  $col=$db->query_column($sql);
  if (count($col)>0)
  {
    $log->trace("Guest authorized for $path (col[0]=".$col[0].")");
    return true;
  }

  $log->trace("Guest denyied for $path ($sql)");
  return false;
}

/**
 * PROPFIND method handler
 *
 * @param  array  general parameter passing array
 * @param  array  return array for file properties
 * @return bool   true on success
 */
function PROPFIND(&$options, &$files) 
{
  global $log;
  $log->trace("PROFIND: ".$options["path"]);
  //$log->trace("PROFIND: options=".print_r($options, true));
  //$log->trace("PROFIND: files=".print_r($files, true));

  // get absolute fs path to requested resource
  $fspath=$this->get_fspath($options["path"]);
    
  // sanity check
  if (!file_exists($fspath)) {
    $log->trace("PROFIND: fspath=$fspath does not exists");
    return false;
  }

  if (!$this->_has_read_rights($fspath))
    return false;

  // prepare property array
  $files["files"]=array();

  // store information for the requested path itself
  $files["files"][]=$this->fileinfo($options["path"]);

  // information for contained resources requested?
  if (!empty($options["depth"])) { // TODO check for is_dir() first?
      
    // make sure path ends with '/'
    $options["path"]=$this->_slashify($options["path"]);

    // try to open directory
    $handle=@opendir($fspath);
    $log->info("PROPFIND: opendir($fspath)"); 
    if ($handle) {
      // ok, now get all its contents
      while ($filename=readdir($handle)) {
        if ($filename == "." || $filename == "..") 
          continue;
        // @todo Improve the read check if user is a guest. Query files from
        // the database instead
        if (!$this->_has_read_rights($fspath.$filename))
          continue; 

        $files["files"][]=$this->fileinfo($options["path"].$filename);
      }
      // TODO recursion needed if "Depth: infinite"
    }
  }

  // ok, all done
  return true;
} 
  
/**
 * Get properties for a single file/resource
 *
 * @param  string  resource path
 * @return array   resource properties
 */
function fileinfo($path) 
{
  global $db, $log;
  $log->trace("fileinfo: path=".$path);

  // map URI path to filesystem path
  $fspath=$this->get_fspath($path);
  $log->trace("fileinfo: fspath=".$fspath);

  // create result array
  $info=array();
  // TODO remove slash append code when base clase is able to do it itself
  $info["path"] =is_dir($fspath) ? $this->_slashify($path) : $path; 
  $info["path"] =$this->path_rawurlencode($info["path"]);
  $info["props"]=array();
    
  // no special beautified displayname here ...
  $info["props"][]=$this->mkprop("displayname", strtoupper($path));
    
  // creation and modification time
  $info["props"][]=$this->mkprop("creationdate",  filectime($fspath));
  $info["props"][]=$this->mkprop("getlastmodified", filemtime($fspath));

  // type and size (caller already made sure that path exists)
  if (is_dir($fspath)) {
    // directory (WebDAV collection)
    $info["props"][]=$this->mkprop("resourcetype", "collection");
    $info["props"][]=$this->mkprop("getcontenttype", "httpd/unix-directory");       
  } else {
    // plain file (WebDAV resource)
    $info["props"][]=$this->mkprop("resourcetype", "");
    if (is_readable($fspath)) {
      $info["props"][]=$this->mkprop("getcontenttype", $this->_mimetype($fspath));
    } else {
      $info["props"][]=$this->mkprop("getcontenttype", "application/x-non-readable");
    }         
    $info["props"][]=$this->mkprop("getcontentlength", filesize($fspath));
  }

  // get additional properties from database
  $spath=mysql_escape_string($path);
  $sql="SELECT ns, name, value".
       " FROM $db->properties".
       " WHERE path='$spath'";
  $res=$db->query($sql);
  while ($row=mysql_fetch_assoc($res)) {
    $info["props"][]=$this->mkprop($row["ns"], $row["name"], $row["value"]);
  }
  mysql_free_result($res);

  //$log->trace("fileinfo: info=".print_r($info, true));
  return $info;
}

/**
 * detect if a given program is found in the search PATH
 *
 * helper function used by _mimetype() to detect if the 
 * external 'file' utility is available
 *
 * @param  string  program name
 * @param  string  optional search path, defaults to $PATH
 * @return bool  true if executable program found in path
 */
function _can_execute($name, $path=false) 
{
  // path defaults to PATH from environment if not set
  if ($path === false) {
    $path=getenv("PATH");
  }
    
  // check method depends on operating system
  if (!strncmp(PHP_OS, "WIN", 3)) {
    // on Windows an appropriate COM or EXE file needs to exist
    $exts=array(".exe", ".com");
    $check_fn="file_exists";
  } else {
    // anywhere else we look for an executable file of that name
    $exts=array("");
    $check_fn="is_executable";
  }
    
  // now check the directories in the path for the program
  foreach (explode(PATH_SEPARATOR, $path) as $dir) {
    // skip invalid path entries
    if (!file_exists($dir)) continue;
    if (!is_dir($dir)) continue;

    // and now look for the file
    foreach ($exts as $ext) {
      if ($check_fn("$dir/$name".$ext)) return true;
    }
  }

  return false;
}

  
/**
 * try to detect the mime type of a file
 *
 * @param  string  file path
 * @return string  guessed mime type
 */
function _mimetype($fspath) 
{
  if (@is_dir($fspath)) {
    // directories are easy
    return "httpd/unix-directory"; 
  } else if (function_exists("mime_content_type")) {
    // use mime magic extension if available
    $mime_type=mime_content_type($fspath);
  } else if ($this->_can_execute("file")) {
    // it looks like we have a 'file' command, 
    // lets see it it does have mime support
    $fp =popen("file -i '$fspath' 2>/dev/null", "r");
    $reply=fgets($fp);
    pclose($fp);
      
    // popen will not return an error if the binary was not found
    // and find may not have mime support using "-i"
    // so we test the format of the returned string 
      
    // the reply begins with the requested filename
    if (!strncmp($reply, "$fspath: ", strlen($fspath)+2)) {           
      $reply=substr($reply, strlen($fspath)+2);
      // followed by the mime type (maybe including options)
      if (preg_match('|^[[:alnum:]_-]+/[[:alnum:]_-]+;?.*|', $reply, $matches)) {
        $mime_type=$matches[0];
      }
    }
  } 
    
  if (empty($mime_type)) {
    // Fallback solution: try to guess the type by the file extension
    // TODO: add more ...
    // TODO: it has been suggested to delegate mimetype detection 
    //     to apache but this has at least three issues:
    //     - works only with apache
    //     - needs file to be within the document tree
    //     - requires apache mod_magic 
    // TODO: can we use the registry for this on Windows?
    //     OTOH if the server is Windos the clients are likely to 
    //     be Windows, too, and tend do ignore the Content-Type
    //     anyway (overriding it with information taken from
    //     the registry)
    // TODO: have a seperate PEAR class for mimetype detection?
    switch (strtolower(strrchr(basename($fspath), "."))) {
    case ".html":
      $mime_type="text/html";
      break;
    case ".gif":
      $mime_type="image/gif";
      break;
    case ".jpg":
      $mime_type="image/jpeg";
      break;
    default: 
      $mime_type="application/octet-stream";
      break;
    }
  }
    
  return $mime_type;
}

/**
 * GET method handler
 * 
 * @param  array  parameter passing array
 * @return bool   true on success
 */
function GET(&$options) 
{
  global $log;
  $log->trace("GET: ".$options["path"]);

  // get absolute fs path to requested resource
  $fspath=$this->get_fspath($options["path"]);

  // sanity check
  if (!file_exists($fspath)) return false;
    
  // is this a collection?
  if (is_dir($fspath)) {
    return $this->GetDir($fspath, $options);
  }
    
  // Synchronize image
  $img=new Image();
  $id=$img->get_id_by_filename($fspath);
  if ($id>0)
  {
    $sync=new ImageSync($id);
    $sync->synchronize();
    @clearstatcache();
  }

  // detect resource type
  $options['mimetype']=$this->_mimetype($fspath); 
      
  // detect modification time
  // see rfc2518, section 13.7
  // some clients seem to treat this as a reverse rule
  // requiering a Last-Modified header if the getlastmodified header was set
  $options['mtime']=filemtime($fspath);
    
  // detect resource size
  $options['size']=filesize($fspath);
    
  // no need to check result here, it is handled by the base class
  $options['stream']=fopen($fspath, "r");
  
  $log->trace("GET filesize=".filesize($fspath));  
  return true;
}

/**
 * GET method handler for directories
 *
 * This is a very simple mod_index lookalike.
 * See RFC 2518, Section 8.4 on GET/HEAD for collections
 *
 * @param  string  directory path
 * @return void  function has to handle HTTP response itself
 */
function GetDir($fspath, &$options) 
{
  global $log;
  $log->trace("GetDir: ".$options["path"]);

  $path=$this->_slashify($options["path"]);

  $base_uri=@$this->_SERVER["HTTPS"]==="on"?"https:":"http:";
  $base_uri.="//".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
  
  $log->trace("base_uri=".$base_uri);

  if ($path != $options["path"]) {
    header("Location: ".$base_uri.$path);
    exit;
  }
  // fixed width directory column format
  $format="%15s  %-19s  %-s\n";

  $handle=@opendir($fspath);
  if (!$handle) {
    return false;
  }

  echo "<html><head><title>Index of ".htmlspecialchars($options['path'])."</title></head>\n";
    
  echo "<h1>Index of ".htmlspecialchars($options['path'])."</h1>\n";
    
  echo "<pre>";
  printf($format, "Size", "Last modified", "Filename");
  echo "<hr>";

  // Current directory
  $link=$this->_unslashify($base_uri.$this->path_rawurlencode($path));
  printf($format, 
    number_format(filesize($fspath)),
    strftime("%Y-%m-%d %H:%M:%S", filemtime($fspath)), 
    "<a href='$link'>.</a>");

  // Upper directory
  $paths=explode('/', trim($options['path'], '/'));
  if (strlen($paths[0]))
  {
    $upper_fspath=dirname($fspath);
    $log->trace("fspath=$fspath");
    $log->trace("upper_fspath=$upper_fspath");

    unset($paths[count($paths)-1]);
    $upper_path=implode('/', $paths);
    if ($upper_path!='')
      $upper_path='/'.$upper_path.'/';

    $link=$this->_unslashify($base_uri.$this->path_rawurlencode($upper_path));
    printf($format, 
      number_format(filesize($upper_fspath)),
      strftime("%Y-%m-%d %H:%M:%S", filemtime($upper_fspath)), 
      "<a href='$link'>..</a>");
  }

  while ($filename=readdir($handle)) {
    if ($filename != "." && $filename != "..") {
      $fullpath=$fspath."/".$filename;
      $name  =htmlspecialchars($filename);
      $link=$base_uri.$this->path_rawurlencode($path.$name);
      $log->trace("link=$link");
      printf($format, 
        number_format(filesize($fullpath)),
        strftime("%Y-%m-%d %H:%M:%S", filemtime($fullpath)), 
        "<a href='$link'>$name</a>");
    }
  }

  echo "</pre>";

  closedir($handle);

  echo "</html>\n";

  exit;
}

function http_PUT()
{
  global $db, $user, $log;

  // http_PUT() calls PUT()
  parent::http_PUT();

  // Update filename on success upload
  $status=substr($this->_http_status, 0, 3);
  if ($status < 200 || $status>=300) {
    $log->debug("HTTP status is $status. Return false");
    return false;
  }

  $fspath=$this->_put_fspath;
  if (!file_exists($fspath))
  {
    $log->trace("Could not determine fspath=$fspath");
    return false;
  }
 
  $sync=new ImageSync();
  if (!$sync->add_file($fspath, true))
  {
    $log->warn("Could not add file '$fspath'");
  }
}

/**
 * PUT method handler
 * 
 * @param  array  parameter passing array
 * @return bool   true on success
 */
function PUT(&$options) 
{
  global $log, $user;
  $log->trace("PUT: ".$options["path"]);

  if ($user->is_guest())
  {
    $log->trace("PUT denyied for guests");
    return "409 Conflict";
  }
  $fspath=$this->get_fspath($options["path"]);

  if (!@is_dir(dirname($fspath))) {
    $log->warn("dir of '$fspath' does not exists");
    return "409 Conflict";
  }

  if (isset($options['ranges']))
  {
    // @todo Ranges are not supported yet
    $log->err("Ranges are not supported yet");
    return "409 Conflict";
  }

  if (file_exists($fspath) && filesize($fspath)>0)
  {
    // @todo Overwriting not supported yet
    $log->err("Overwriting not supported yet");
    return "409 Conflict";
  }
  else
  {
    $options["new"]=!file_exists($fspath);
  }

  $size=$options['content_length'];
  if ($size<0) {
    $log->warn("Size is negative");
    return "409 Conflict";
  }

  $log->trace("file size=$size");

  // Check users quota
  if (!$user->can_upload_size($size))
  {
    $log->warn("Quota exceed. Deny upload of $size bytes");
    return "409 Conflict";
  }

  // save path
  $this->_put_fspath=$fspath;
  $fp=fopen($fspath, "w");
  if ($fp===false)
    $log->trace("fopen('$fspath', 'w')===false");

  return $fp;
}


/**
 * MKCOL method handler
 *
 * @param  array  general parameter passing array
 * @return bool   true on success
 */
function MKCOL($options) 
{       
  global $log;
  $log->trace("MKCOL: ".$options["path"]);

  $fspath=$this->get_fspath($options["path"]);
  $parent=dirname($fspath);
  $name=basename($fspath);

  if (!file_exists($parent)) {
    return "409 Conflict";
  }

  if (!is_dir($parent)) {
    return "403 Forbidden";
  }

  if ( file_exists($parent."/".$name) ) {
    $log->trace("MKCOL: 405 $parent/$name");
    return "405 Method not allowed";
  }

  if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
    return "415 Unsupported media type";
  }
    
  $stat=mkdir($parent."/".$name, 0775);
  if (!$stat) {
    return "403 Forbidden";         
  }

  return ("201 Created");
}
  
/** Removes a directory recursivly */
function _rm_rf($path)
{
  global $log;

  if (!is_dir($path))
    return false;

  if (!$handle=@opendir($path))
    return false;
  
  $path=$this->_slashify($path);
  while($file=readdir($handle))
  {
    if ($file=='.' || $file=='..') 
      continue;
    if (is_dir($path.$file))
      $this->_rm_rf($path.$file);
    else
    {
      @unlink($path.$file);
      $log->trace("Deleting file $path$file");
    }
  }  
  closedir($handle);
  $log->trace("Deleting dir $path");
  @rmdir($path);
} 
 
/**
 * DELETE method handler
 *
 * @param  array  general parameter passing array
 * @return bool   true on success
 */
function DELETE($options) 
{
  global $db, $user, $log;
  $log->trace("DELETE path=".$options['path']);

  if ($user->is_guest())
  {
    $log->trace("DELETE denyied for guests");
    return "409 Conflict";
  }

  $fspath=$this->get_fspath($options["path"]);

  if (!file_exists($fspath)) {
    $log->debug("DELETE on non existing file '$fspath'");
    return "404 Not found";
  }

  $sync=new ImageSync();
  $sync->delete_file($fspath);

  $sql="DELETE FROM $db->properties".
       " WHERE path='$sfspath'";
  //$db->query($sql);

  return "204 No Content";
}


/**
 * MOVE method handler
 *
 * @param  array  general parameter passing array
 * @return bool   true on success
 */
function MOVE($options) 
{
  return $this->COPY($options, true);
}

/**
 * COPY method handler
 *
 * @param  array  general parameter passing array
 * @return bool   true on success
 */
function COPY($options, $del=false) 
{
  global $db, $user, $log;
  $log->trace("COPY options=".print_r($options, true)." del=".($del?'true':'false'));

  if ($user->is_guest())
  {
    $log->trace("PUT denyied for guests");
    return "409 Conflict";
  }

  // TODO Property updates still broken (Litmus should detect this?)

  if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
    return "415 Unsupported media type";
  }

  // no copying to different WebDAV Servers yet
  if (isset($options["dest_url"])) {
    return "502 bad gateway";
  }

  $source=$this->_base.$options["path"];

  if (!file_exists($source)) return "404 Not found";

  $dest=$this->_base.$options["dest"];
  $new=!file_exists($dest);
  $existing_col=false;

  if (!$new) {
    if ($del && is_dir($dest)) {
      if (!$options["overwrite"]) {
        return "412 precondition failed";
      }
      $dest.=basename($source);
      if (file_exists($dest)) {
        $options["dest"].=basename($source);
      } else {
        $new=true;
        $existing_col=true;
      }
    }
  }

  if (!$new) {
    if ($options["overwrite"]) {
      $stat=$this->DELETE(array("path" => $options["dest"]));
      if (($stat{0} != "2") && (substr($stat, 0, 3) != "404")) {
        return $stat; 
      }
    } else {
      return "412 precondition failed";
    }
  }

  if (is_dir($source) && ($options["depth"] != "infinity")) {
    // RFC 2518 Section 9.2, last paragraph
    return "400 Bad request";
  }

  // move file(s)
  if ($del) {
    $sync=new ImageSync();
    if (!$sync->move_file($source, $dest))
    {
      $log->err("Could not delete files from '$dest'");
      return "500 Internal server error";
    }

    $query="UPDATE $db->properties".
           " SET path='$sdestpath'".
           " WHERE path='$spath'";
    //mysql_query($query);
  } else {
    $log->err("Copy method unsported yet");
    return "500 Internal server error";
  
    if (is_dir($source)) {
      $files=System::find($source);
      $files=array_reverse($files);
    } else {
      $files=array($source);
    }

    if (!is_array($files) || empty($files)) {
      return "500 Internal server error";
    }
        
    foreach ($files as $file) {
      if (is_dir($file)) {
        $file=$this->_slashify($file);
      }

      $destfile=str_replace($source, $dest, $file);
        
      if (is_dir($file)) {
        if (!is_dir($destfile)) {
          // TODO "mkdir -p" here? (only natively supported by PHP 5) 
          if (!@mkdir($destfile)) {
            return "409 Conflict";
          }
        } 
      } else {
        if (!@copy($file, $destfile)) {
          return "409 Conflict";
        }
      }
    }

    // @todo check sql statement from Filesystem.php of WebDav example
    $sql="INSERT INTO $db->properties".
         " SELECT *".
         " FROM $db->properties".
         " WHERE path='$spath'";
  }

  return ($new && !$existing_col) ? "201 Created" : "204 No Content";     
}

/**
 * PROPPATCH method handler
 *
 * @param  array  general parameter passing array
 * @return bool   true on success
 */
function PROPPATCH(&$options) 
{
  global $db, $log;
  global $prefs, $tab;

  $log->info("PROPATCH: ".$options["path"]);

  $msg="";
  $path=$options["path"];
  $spath=mysql_escape_string($path);
  $dir=dirname($path)."/";
  $base=basename($path);
    
  foreach ($options["props"] as $key => $prop) {
    if ($prop["ns"] == "DAV:") {
      $options["props"][$key]['status']="403 Forbidden";
    } else {
      if (isset($prop["val"])) {
        $sql="REPLACE INTO $db->properties".
             " SET path='$spath',".
             " name='$prop[name]',".
             " ns= '$prop[ns]',".
             " value='$prop[val]'";
      } else {
        $sql="DELETE FROM $db->properties".
             " WHERE path='$spath'". 
             " AND name='$prop[name]'".
             " AND ns='$prop[ns]'";
      }     
      $db->query($sql);
    }
  }
          
  return "";
}


/**
 * LOCK method handler
 *
 * @param  array  general parameter passing array
 * @return bool   true on success
 */
function LOCK(&$options) 
{
  global $db, $log;
  $log->info("LOCK: ".$options["path"]);

  $spath=mysql_escape_string($options['path']);

  // get absolute fs path to requested resource
  $fspath=$this->get_fspath($options["path"]);

  // TODO recursive locks on directories not supported yet
  if (is_dir($fspath) && !empty($options["depth"])) {
    return "409 Conflict";
  }

  $options["timeout"]=time()+300; // 5min. hardcoded

  if (isset($options["update"])) { // Lock Update
    $where="WHERE path='$spath' AND token='$options[update]'";
    $sql="SELECT owner, exclusivelock FROM $db->locks $where";

    $res=$db->query($sql);
    $row=mysql_fetch_assoc($res);
    mysql_free_result($res);

    if (is_array($row)) {
      $sql="UPDATE $db->locks".
           " SET expires='$options[timeout]', ".
           " modified=".time()." ".$where;
      $db->query($sql);

      $options['owner']=$row['owner'];
      $options['scope']=$row["exclusivelock"] ? "exclusive" : "shared";
      $options['type']=$row["exclusivelock"] ? "write"   : "read";

      return true;
    } else {
      return false;
    }
  }
    
  $sql="INSERT INTO $db->locks".
       " SET token='$options[locktoken]',".
       " path='$spath',".
       " created=".time().",".
       " modified=".time().",".
       " owner='$options[owner]',".
       " expires='$options[timeout]',".
       " exclusivelock=".($options['scope']==="exclusive"?"1":"0");
  $db->query($sql);

  return mysql_affected_rows() ? "200 OK" : "409 Conflict";
}

/**
 * UNLOCK method handler
 *
 * @param  array  general parameter passing array
 * @return bool   true on success
 */
function UNLOCK(&$options) 
{
  global $db, $log;
  $log->info("UNLOCK: ".$options["path"]);

  $spath=mysql_escape_string($options['path']);
  $stoken=mysql_escape_string($options['token']);
  $sql="DELETE FROM $db->locks".
       " WHERE path='$spath'".
       " AND token='$stoken'";
  $db->query($sql);

  return mysql_affected_rows() ? "204 No Content" : "409 Conflict";
}

/**
 * checkLock() helper
 *
 * @param  string resource path to check for locks
 * @return bool   true on success
 */
function checkLock($path) 
{
  global $db;
  $result=false;
    
  $spath=mysql_escape_string($path);
  $sql="SELECT owner, token, created, modified, expires, exclusivelock".
       " FROM $db->locks".
       " WHERE path='$spath'";
  $res=$db->query($sql);

  if ($res) {
    $row=mysql_fetch_array($res);
    mysql_free_result($res);

    if ($row) {
      $result=array( "type"  => "write",
               "scope"   => $row["exclusivelock"] ? "exclusive" : "shared",
               "depth"   => 0,
               "owner"   => $row['owner'],
               "token"   => $row['token'],
               "created" => $row['created'],   
               "modified" => $row['modified'],   
               "expires" => $row['expires']
               );
    }
  }

  return $result;
}

}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
?>
