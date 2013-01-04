<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

require_once "HTTP/WebDAV/Server.php";

/**
 * WebdavServer
 *
 * based on Filesystem.php from Hartmut Holzgraefe <hartmut@php.net>
 *
 */
class WebdavServerComponent extends HTTP_WebDAV_Server
{

  /**
   * Root directory for WebDAV access
   *
   * Defaults to webserver document root (set by ServeRequest)
   *
   * @access private
   * @var  string
   */
  var $_fsRoot="";

  var $_davRoot="";

  var $_putFsPath="";

  var $_fileCache = null;

  var $controller = null;

  var $components = array('FileManager', 'FilterManager');

  public function startup(Controller $controller) {
  }

  public function beforeRender(Controller $controller) {
  }

  public function shutdown(Controller $controller) {
  }

  public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {
  }

  public function WebdavServer() {
    $this->HTTP_WebDAV_Server();
    $this->_fsRoot=$_SERVER['DOCUMENT_ROOT'];
    $this->_davRoot="";
  }

  public function initialize(Controller $controller) {
    $this->controller = $controller;
    // set current controller URL
    $this->setDavRoot($controller->webroot.$controller->name);
    $this->controller->loadComponent(array('FileManager', 'FilterManager'), $this);
    $this->FilterManager->initialize($controller);
  }

  /** Set a new filesystem root directory
    @param root Base directory
    @return True on success, false otherwise
    @note The root directory must be exists, otherwise it returns false */
  public function setFsRoot($root) {
    if (!is_dir($root))
      return false;
    $this->_fsRoot=$this->_unslashify($root);
    return true;
  }

  public function setDavRoot($root) {
    $this->_davRoot=$this->_unslashify($root);
    Logger::trace("setDavRoot to $root");
    return true;
  }

  /** Returns the canonicalized path
    @param path
    @return canonicalized path */
  public function canonicalpath($path) {
    $paths=explode('/', $path);
    $result=array();
    for ($i=0; $i<sizeof($paths); $i++) {
      if ($paths[$i]==='' || $paths[$i]=='.')
        continue;
      if ($paths[$i]=='..') {
        array_pop($result);
        continue;
      }
      array_push($result, $paths[$i]);
    }
    return '/'.implode('/', $result);
  }

  /** Returns the relative WebDAV path to the current WebDAV path
    @param path webdav path
    @return relative WebDAV path */
  public function getRelativeDavpath($path) {
    $lenroot=strlen($this->_davRoot);
    $lenpath=strlen($path);

    $relativePath="/";
    if ($lenpath > $lenroot && $path[$lenroot]=='/') {
      $pos=strpos($path, $this->_davRoot);
      if ($pos===0) {
        $relativePath=$this->canonicalpath(substr($path, $lenroot));
      }
    }
    Logger::trace("get_relativ_davpath($path)==$relativePath");
    return $relativePath;
  }

  /** Returns the filesystem path to the corresponding request path
    @param path Request path
    @return filesystem path */
  public function getFsPath($path) {
    //$relativePath=$this->getRelativeDavpath($path);
    $relativePath=$this->canonicalpath($path);
    if ($relativePath!='/') {
      $fspath=$this->_mergePaths($this->_fsRoot, $relativePath);
    } else {
      $fspath=$this->_fsRoot;
    }
    //Logger::trace("getFsPath($path)=$fspath");
    return $fspath;
  }

  /** Sets a new realm
    @param realm Realm name */
  public function setRealm($realm) {
    $this->_realm=$realm;
  }

  /** Set string for server identification
    @param text Text of server identification */
  public function setPoweredBy($text) {
    $this->dav_powered_by=$text;
  }

  /** Reads all files of a given path and build an file cache
    @param path System path to read */
  public function _buildFileCache($path) {
    if (isset($this->_fileCache[$path]) || !is_dir($path)) {
      return;
    }

    // Initialize cache array
    if (!$this->_fileCache) {
      $this->_fileCache = array();
    }
    $this->_fileCache[$path] = array();

    // Bind only required models
    $this->controller->MyFile->unbindAll();
    $this->controller->MyFile->bindModel(array('hasMany' => array(
      'Property' => array('foreignKey' => 'file_id'),
      'Lock' => array('foreignKey' => 'file_id')
      )));
    $files = $this->controller->MyFile->find('all', array('conditions' => array('path' => $path)));
    //Logger::debug($files);

    // Build cache array
    foreach ($files as $file) {
      $name = $file['File']['file'];
      $this->_fileCache[$path][$name] = $file;
    }
    Logger::trace("Built file cache for path '$path' with ".count($files)." files");
  }

  /** Fetches a model data of an file by using the file cache. If the cache
   * path is not available, it builds the cache first
   @param filename Filename of file
   @return file model data or false if file was not found */
  public function _getFile($filename) {
    if (is_dir($filename)) {
      return false;
    }

    $path = Folder::slashTerm(dirname($filename));
    $file = basename($filename);
    if (!isset($this->_fileCache[$path])) {
      $this->_buildFileCache($path);
    }

    if (isset($this->_fileCache[$path][$file])) {
      return $this->_fileCache[$path][$file];
    } else {
      return false;
    }
  }

  /** Encodes the paths of an url. E.g '/space test/' becomes '/space%20test/'
    @param path Path
    @return Escaped path
    @note Requires PHP 5 (uses references in foreach statement) */
  public function pathRawurlencode($path) {
    $paths=explode('/', $path);
    for ($i = 0; $i < count($paths); $i++) {
      $paths[$i] = rawurlencode($paths[$i]);
    }
    return implode('/', $paths);
  }

  public function ServeRequest($base=false) {
    Logger::info("ServeRequest: {$this->_SERVER["REQUEST_METHOD"]} $base");

    // special treatment for litmus compliance test
    // reply on its identifier header
    // not needed for the test itself but eases debugging
    if (function_exists("apache_request_headers")) {
      foreach (apache_request_headers() as $key => $value) {
        if (stristr($key, "litmus")) {
          Logger::trace("Litmus test $value");
          header("X-Litmus-reply: ".$value);
        }
      }
    }

    if (strpos($base, $this->_davRoot) !== 0) {
      Logger::warn("Request '$base' does not match DAV root '{$this->_davRoot}'. Reset request to '/'");
      $this->_SERVER['PATH_INFO'] = '/';
    } else {
      $this->_SERVER['PATH_INFO'] = substr($base, strlen($this->_davRoot));
    }
    $this->_SERVER['SCRIPT_NAME'] = $this->_davRoot;

    // let the base class do all the work
    parent::ServeRequest();
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
  public function checkAuth($type, $auser, $apass) {
    return true;
  }

  /** Checks if the path could be read by the user
    @param path System directory or filename
    @return True if user is authorized to read directory. False otherwise
    @todo This function must be implemented */
  public function _canRead($fspath) {
    if ($this->controller->getUserRole() >= ROLE_USER) {
      return true;
    }

    $user = $this->controller->getUser();
    $allow = $this->controller->MyFile->canRead($fspath, $user);
    if (!$allow) {
      Logger::trace("Deny user {$user['User']['username']} ({$user['User']['id']}) access to '$fspath'");
    }
    return $allow;
  }

  /**
   * PROPFIND method handler
   *
   * @param  array  general parameter passing array
   * @param  array  return array for file properties
   * @return bool   true on success
   */
  public function PROPFIND(&$options, &$files) {
    // get absolute fs path to requested resource
    $fspath=$this->getFsPath($options["path"]);
    Logger::debug("PROFIND: '$fspath' ({$options["path"]})");

    // sanity check
    if (!file_exists($fspath)) {
      Logger::err("File '$fspath' does not exists");
      return false;
    }

    if (!$this->_canRead($fspath)) {
      Logger::warn("User is not allowed to read '$fspath'");
      return false;
    }

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
      Logger::debug("Read directory '$fspath'");
      if ($handle) {
        $fspath=Folder::slashTerm($fspath);
        // ok, now get all its contents
        while ($filename=readdir($handle)) {
          if ($filename == "." || $filename == "..")
            continue;
          // @todo Improve the read check if user is a guest. Query files from
          // the database instead
          if (!$this->_canRead($fspath.$filename)) {
            continue;
          }
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
  public function fileinfo($path) {
    // map URI path to filesystem path
    $fspath=$this->getFsPath($path);
    Logger::trace("fileinfo: '$fspath' ($path)");

    // create result array
    $info=array();
    // TODO remove slash append code when base clase is able to do it itself
    $info["path"] =is_dir($fspath) ? $this->_slashify($path) : $path;
    $info["path"] =$this->pathRawurlencode($info["path"]);
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
    $file = $this->_getFile($fspath);
    if ($file) {
      foreach($file['Property'] as $property) {
        $info['props'][]=$this->mkprop(
          $property['ns'],
          $property['name'],
          $property['value']);
        Logger::trace("Add property: {$property['ns']}:{$property['name']}={$property['value']}");
      }
    }

    //Logger::trace("fileinfo: info=".print_r($info, true));
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
  public function _canExecute($name, $path=false) {
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
  public function _mimetype($fspath) {
    if (@is_dir($fspath)) {
      // directories are easy
      return "httpd/unix-directory";
    } else if (function_exists("mime_content_type")) {
      // use mime magic extension if available
      $mimeType=mime_content_type($fspath);
    } else if ($this->_canExecute("file")) {
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
          $mimeType=$matches[0];
        }
      }
    }

    if (empty($mimeType)) {
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
        $mimeType="text/html";
        break;
      case ".gif":
        $mimeType="image/gif";
        break;
      case ".jpg":
        $mimeType="image/jpeg";
        break;
      default:
        $mimeType="application/octet-stream";
        break;
      }
    }

    return $mimeType;
  }

  /**
   * GET method handler
   *
   * @param  array  parameter passing array
   * @return bool   true on success
   */
  public function GET(&$options) {
    // get absolute fs path to requested resource
    $fspath=$this->getFsPath($options["path"]);
    Logger::debug("GET: '$fspath' ({$options["path"]})");

    // sanity check
    if (!file_exists($fspath)) {
      Logger::warn("Content '$fspath' does not exists");
      return false;
    }

    if (!$this->_canRead($fspath)) {
      Logger::warn("User is not allowed to view content of '$fspath'");
      return false;
    }

    // is this a collection?
    if (is_dir($fspath)) {
      return $this->GetDir($fspath, $options);
    }

    // Update metadata on dirty file
    $file = $this->controller->MyFile->findByFilename($fspath);
    if ($file && $this->controller->Media->hasFlag($file, MEDIA_FLAG_DIRTY) && $this->controller->getOption('filter.write.onDemand')) {
      $media = $this->controller->Media->findById($file['Media']['id']);
      $this->FilterManager->write($media);
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

    // Clean the open obstack which was open in the controller
    @ob_end_clean();

    // no need to check result here, it is handled by the base class
    $options['stream']=fopen($fspath, "r");

    return true;
  }

  /**
   * GET method handler for directories
   *
   * This is a very simple mod_index lookalike.
   * See RFC 2518, Section 8.4 on GET/HEAD for collections
   *
   * @param  string  directory path
   * @return void  public function has to handle HTTP response itself
   */
  public function GetDir($fspath, &$options) {
    Logger::debug("GetDir: '$fspath' ({$options["path"]})");

    $path=$this->_slashify($options["path"]);

    $baseUri=@$this->_SERVER["HTTPS"]==="on"?"https:":"http:";
    $baseUri.="//".$_SERVER['HTTP_HOST'].$this->_davRoot;

    //Logger::trace("baseUri=".$baseUri);

    if ($path != $options["path"]) {
      header("Location: ".$baseUri.$path);
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
    $link=$this->_unslashify($baseUri.$this->pathRawurlencode($path));
    printf($format,
      number_format(filesize($fspath)),
      strftime("%Y-%m-%d %H:%M:%S", filemtime($fspath)),
      "<a href='$link'>.</a>");

    // Upper directory
    $paths=explode('/', trim($options['path'], '/'));
    if (strlen($paths[0])) {
      $parentFsPath=dirname($fspath);

      unset($paths[count($paths)-1]);
      $parentPath='/'.implode('/', $paths);

      $link=$this->_unslashify($baseUri.$this->pathRawurlencode($parentPath));
      printf($format,
        number_format(filesize($parentFsPath)),
        strftime("%Y-%m-%d %H:%M:%S", filemtime($parentFsPath)),
        "<a href='$link'>..</a>");
    }

    $dirs=array();
    $files=array();
    while ($filename=readdir($handle)) {
      if ($filename == "." || $filename == "..")
        continue;

      if (is_dir($fspath.'/'.$filename))
        array_push($dirs, $filename);
      else
        array_push($files, $filename);
    }
    asort($dirs);
    asort($files);
    $files=array_merge($dirs,$files);
    foreach($files as $filename) {
      $fullpath=$fspath."/".$filename;
      // check access for guest accounts
      if (!$this->_canRead($fullpath)) {
        continue;
      }

      $name  =htmlspecialchars($this->_unslashify($filename));
      $link=$baseUri.$this->pathRawurlencode($path.$name);
      printf($format,
        number_format(filesize($fullpath)),
        strftime("%Y-%m-%d %H:%M:%S", filemtime($fullpath)),
        "<a href='$link'>$name</a>");
    }
    echo "</pre>";

    closedir($handle);

    echo "</html>\n";

    exit;
  }

  public function http_PUT() {
    // http_PUT() calls PUT()
    parent::http_PUT();

    // Update filename on success upload
    $status=substr($this->_http_status, 0, 3);
    if ($status < 200 || $status>=300) {
      Logger::warn("HTTP status is $status. Return false");
      return false;
    }

    $fspath=$this->_putFsPath;
    if (!file_exists($fspath)) {
      Logger::err("Could not determine fspath=$fspath");
      return false;
    }

    $file = $this->controller->MyFile->findByFilename($fspath);
    if ($file) {
      $this->controller->MyFile->update($file);
      if ($this->controller->MyFile->save($file)) {
        Logger::info("Update file '{$file['File']['path']}{$file['File']['file']}' to size {$file['File']['size']} bytes");
      } else {
        Logger::err("Could not update filesize of '{$file['File']['path']}{$file['File']['file']}'");
      }
    } else {
      $id = $this->FileManager->add($fspath, $this->controller->getUser());
      if ($id) {
        Logger::info("Add file '$fspath' to database with id $id");
      } else {
        Logger::err("Could not add file '$fspath' to database");
      }
    }
  }

  /**
   * PUT method handler
   *
   * @param  array  parameter passing array
   * @return bool   true on success
   */
  public function PUT(&$options) {
    if ($this->controller->getUserRole() <= ROLE_GUEST) {
      Logger::warn("PUT denyied for guests");
      return "403 Forbidden";
    }

    $fspath=$this->getFsPath($options["path"]);

    if (!@is_dir(dirname($fspath))) {
      Logger::warn("dir of '$fspath' does not exists");
      return "409 Conflict";
    }

    if (isset($options['ranges'])) {
      // @todo Ranges are not supported yet
      Logger::err("Ranges are not supported yet");
      return "409 Conflict";
    }

    $size=$options['content_length'];
    if ($size<0) {
      Logger::warn("Size is negative");
      return "409 Conflict";
    }

    // Check users quota
    if (!$this->controller->User->canUpload($this->controller->getUser(), $size)) {
      Logger::warn("Quota exceed. Deny upload of $size Bytes");
      return "409 Conflict";
    }

    Logger::debug("PUT: '$fspath' ($size Bytes, {$options["path"]})");
    $options["new"]=!file_exists($fspath);

    // save path
    $this->_putFsPath=$fspath;
    $fp=fopen($fspath, "w");
    if ($fp===false) {
      Logger::err("fopen('$fspath', 'w')===false");
    }

    return $fp;
  }

  /**
   * MKCOL method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  public function MKCOL($options) {
    if ($this->controller->getUserRole() <= ROLE_GUEST) {
      Logger::warn("MKCOL: Denied for guests");
      return "403 Forbidden";
    }
    $fspath=$this->getFsPath($options["path"]);
    Logger::info("MKCOL: '$fspath' ({$options["path"]})");

    $parent=dirname($fspath);
    if (!file_exists($parent)) {
      return "409 Conflict";
    }

    if (!is_dir($parent)) {
      return "403 Forbidden";
    }

    if (file_exists($fspath)) {
      Logger::err("MKCOL: File '$fspath' already exists");
      return "405 Method not allowed";
    }

    if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
      return "415 Unsupported media type";
    }

    if (!@mkdir($fspath, 0775)) {
      Logger::err("Could not create directory '$fspath'");
      return "403 Forbidden";
    }

    return ("201 Created");
  }

  /**
   * DELETE method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  public function DELETE($options) {
    if ($this->controller->getUserRole() <= ROLE_GUEST) {
      Logger::warn("DELETE denyied for guests");
      return "403 Forbidden";
    }

    $fspath = $this->getFsPath($options["path"]);
    if (!file_exists($fspath)) {
      Logger::err("DELETE failed. file '$fspath' does not exists");
      return "404 Not found";
    }

    Logger::info("blub");
    if ($this->FileManager->delete($fspath)) {
      Logger::info("Delete '$fspath' ({$options['path']})");
    } else {
      Logger::err("Could not delete '$fspath' ({$options['path']})");
    }
    return "204 No Content";
  }

  /**
   * MOVE method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  public function MOVE($options) {
    return $this->COPY($options, true);
  }

  /**
   * COPY method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  public function COPY($options, $del=false) {

    if ($this->controller->getUserRole() <= ROLE_GUEST) {
      Logger::warn("COPY/MOVE denied for guests");
      return "403 Forbidden";
    }

    // TODO Property updates still broken (Litmus should detect this?)

    if (!empty($this->_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
      Logger::err("Unsupported media type");
      return "415 Unsupported media type";
    }

    // no copying to different WebDAV Servers yet
    if (isset($options["dest_url"])) {
      Logger::err("Bad gateway");
      return "502 bad gateway";
    }

    $source=$this->getFsPath($options["path"]);
    Logger::debug("COPY '$source' ({$options['path']} -> {$options['dest']}, del=".($del?'true':'false').")");

    if (!file_exists($source)) {
      Logger::warn("Source '$source' not found");
      return "404 Not found";
    }

    $dest=$this->getFsPath($options["dest"]);
    $new=!file_exists($dest);
    $existingCol=false;

    if (!$new) {
      if ($del && is_dir($dest)) {
        if (!$options["overwrite"]) {
          Logger::warn("Precondition failed. Overwrite not set");
          return "412 precondition failed";
        }
        $dest.=basename($source);
        if (file_exists($dest)) {
          $options["dest"].=basename($source);
        } else {
          $new=true;
          $existingCol=true;
        }
      }
    }

    if (!$new) {
      if ($options["overwrite"]) {
        $stat=$this->DELETE(array("path" => $options["dest"]));
        if (($stat{0} != "2") && (substr($stat, 0, 3) != "404")) {
          Logger::err("Could not delete existing file '{$options["dest"]}");
          return $stat;
        }
        Logger::info("Delete existing file '{$options["dest"]}' for overwriting");
        @clearstatcache();
      } else {
        Logger::warn("Precondition failed. Cant overwrite existing destination");
        return "412 precondition failed";
      }
    }

    if (is_dir($source) && ($options["depth"] == 1)) {
      // RFC 2518 Section 9.2, last paragraph
      Logger::err("Bad request: Source is a directory, but depth is ".$options["depth"]);
      return "400 Bad request";
    }

    if ($del) {
      // move file(s)
      if (!is_dir($source)) {
        if (!$this->FileManager->move($source, $dest)) {
          Logger::warn("Could not update file in database from file '$source' to '$dest'");
          return "500 Internal server error";
        }
        Logger::info("Renamed file from '$source' to '$dest'");
      } elseif (is_dir($dest) || !file_exists($dest)) {
        if (!$this->FileManager->move($source, $dest)) {
          Logger::err("Could not update file in database from directory '$source' to directory '$dest'");
          return "500 Internal server error";
        }
        Logger::info("Renamed directory from '$source' to '$dest'");
      } else {
        Logger::err("Move from directory '$source' to file '$dest' not allowed!");
        return "412 precondition failed";
      }
    } else {
      if (!$this->FileManager->copy($source, $dest)) {
        return "500 Internal server error";
      }
      /*
      if (is_dir($source)) {
        $folder = new Folder($source);
        list($dirs, $files) = $folder->tree($source);
        sort($dirs);
        sort($files);
        // TODO check users quota for all files to copy
        // Create required directories
        foreach ($dirs as $dir) {
          $destdir=str_replace($source, $dest, $dir);
          Logger::info($destdir);
          if (!file_exists($destdir) && !@mkdir($destdir)) {
            Logger::err("Could not create directory '$destdir'");
            return "500 Internal server error";
          }
          // COPY properties
        }
      } else {
        $dirs = array();
        // TODO check users quota
        $files = array($source);
      }

      $user = $this->controller->getUser();
      foreach ($files as $file) {
        $destfile=str_replace($source, $dest, $file);

        if (!@copy($file, $destfile)) {
          Logger::err("Could not copy file '$file' to '$destfile'");
          return "409 Conflict";
        }
        $dstFileId = $this->FileManager->add($destfile, $user);
        if (!$dstFileId) {
          Logger::err("Could not insert copied file '$destfile' to database (from '$file')");
          unlink($destfile);
          return "409 Conflict";
        } else {
          // Copy all properties
          $srcFile = $this->controller->MyFile->findByFilename($file);
          if (!$srcFile) {
            Logger::warn("Could not found source '$file' in database");
          } else {
            if (!empty($srcFile['Property'])) {
              $this->controller->Property->copy($srcFile, $dstFileId);
              Logger::debug("Copy properties from '$file' to '$destfile'");
            }
          }
        }
        Logger::info("Insert copied file '$destfile' to database (from '$file')");
      }
        */
    }

    return ($new && !$existingCol) ? "201 Created" : "204 No Content";
  }

  /**
   * PROPPATCH method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  public function PROPPATCH(&$options) {
    Logger::debug("PROPATCH: ".$options["path"]);

    $path=$options["path"];
    $fspath=$this->getFsPath($path);
    $fileId=$this->controller->MyFile->fileExists($fspath);
    if (!$fileId) {
      Logger::err("Filename '$fspath' does not exists");
      return "";
    }

    foreach ($options["props"] as $key => $prop) {
      if ($prop["ns"] == "DAV:") {
        $options["props"][$key]['status']="403 Forbidden";
      } else {
        $property = $this->controller->Property->find('first', array('conditions' => array('Property.file_id' => $fileId, 'Property.name' => $prop['name'], 'Property.ns' => $prop['ns'])));
        if (isset($prop["val"])) {
          if (!$property) {
            $property = array('Property' => array(
              'file_id' => $fileId,
              'name' => $prop['name'],
              'ns' => $prop['ns'],
              'value' => $prop['val']));
            Logger::debug("Create new property for file $fileId: {$prop['ns']}:{$prop['name']}='{$prop['val']}'");
            $property = $this->controller->Property->create($property);
          } else {
            $property['Property']['value'] = $prop['val'];
            Logger::debug("Set new property value for file $fileId: {$prop['ns']}:{$prop['name']}='{$prop['val']}'");
            $this->controller->Property->id = $property['Property']['id'];
          }
          if (!$this->controller->Property->save($property)) {
            Logger::err("Could not save property");
          }
        } elseif ($property) {
          Logger::debug("Delete property of file $fileId: {$prop['ns']}:{$prop['name']}");
          $this->controller->Property->delete($property['Property']['id']);
        }
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
  public function LOCK(&$options) {
    Logger::info("LOCK: ".$options["path"]);

    // get absolute fs path to requested resource
    $fspath=$this->getFsPath($options["path"]);

    // TODO recursive locks on directories not supported yet
    if (is_dir($fspath) && !empty($options["depth"])) {
      Logger::warn("recursive locks on directories not supported yet!");
      return "409 Conflict";
    }

    $options["timeout"]=time()+300; // 5min. hardcoded

    // Delete expired locks
    $this->controller->Lock->deleteAll("Lock.expires < '".date('Y-m-d H:i:s', time())."'");

    $fileId = $this->controller->MyFile->fileExists($fspath);
    if (!$fileId) {
      Logger::warn("Could not find file with path '$fspath'");
      return true;
    }

    if (isset($options["update"])) { // Lock Update
      $lock = $this->controller->Lock->find('first', array('conditions' => array('Lock.file_id' => $fileId, 'Lock.token' => $options['update'])));

      if ($lock) {
        $lock['Lock']['expires'] = date('Y-m-d H:i:s', $options['timeout']);
        $this->controller->Lock->save($lock);
        Logger::info("Updated lock for '$fspath': ".$lock['Lock']['expires']);

        $options['owner']=$lock['Lock']['owner'];
        $options['scope']=$lock['Lock']["exclusivelock"] ? "exclusive" : "shared";
        $options['type']=$lock['Lock']["exclusivelock"] ? "write"   : "read";

        return true;
      } else {
        Logger::warn("No lock found for lock update: '$fspath'");
        return false;
      }
    }

    $lock = array('Lock' => array(
      'token' => $options['locktoken'],
      'file_id' => $fileId,
      'owner' => $options['owner'],
      'expires' => date('Y-m-d H:i:s', $options['timeout']),
      'exclusivelock' => ($options['scope']==="exclusive"?"1":"0")
      ));
    $lock = $this->controller->Lock->create($lock);
    if ($this->controller->Lock->save($lock)) {
      Logger::info("Created lock for '$fspath': ".$lock['Lock']['expires']);
      return "200 OK";
    } else {
      Logger::err("Could not save lock of file $fileId");
      Logger::trace($lock);
      return "409 Conflict";
    }
  }

  /**
   * UNLOCK method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  public function UNLOCK(&$options) {
    Logger::info("UNLOCK: ".$options["path"]);

    $fspath = $this->getFsPath($options["path"]);
    $fileId = $this->controller->MyFile->fileExists($fspath);
    if (!$fileId) {
      Logger::err("Could not find file for path '$fspath'");
      return "409 Conflict";
    }
    $lock = $this->controller->Lock->find('first', array('conditions' => array('Lock.file_id' => $fileId, 'Lock.token' => $options['token'])));
    if (!$lock) {
      Logger::err("Could not find lock token '{$options['token']}' for file $fileId");
      return "409 Conflict";
    }
    $this->controller->Lock->delete($lock['Lock']['id']);
    Logger::info("Deleted lock for '$fspath'");
    return "204 No Content";
  }

  /**
   * checkLock() helper
   *
   * @param  string resource path to check for locks
   * @return bool   true on success
   */
  public function checkLock($path) {
    //Logger::debug("checkLock: ".$path);
    $result=false;

    $res=false;

    $path = urldecode($path);
    $fspath = $this->getFsPath($path);
    $file = $this->_getFile($fspath);
    if (!$file) {
      Logger::debug("Could not find file with path '$fspath'");
      return $result;
    }

    if (isset($file['Lock']) && count($file['Lock'])) {
      $lock = $file['Lock'][0];
      $result=array(
        'type'  => 'write',
        'scope' => $lock['exclusivelock'] ? 'exclusive' : 'shared',
        'depth' => 0,
        'owner' => $lock['owner'],
        'token' => $lock['token'],
        'created' => strtotime($lock['created']),
        'modified' => strtotime($lock['modified']),
        'expires' => strtotime($lock['expires'])
      );
      Logger::debug("File is locked: $fspath");
      //Logger::trace($result);
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
