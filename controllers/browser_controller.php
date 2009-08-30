<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2009 Sebastian Felis, sebastian@phtagr.org
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

class BrowserController extends AppController
{
  var $name = "Browser";

  var $components = array('FileManager', 'RequestHandler', 'FilterManager', 'Upload', 'Zip');
  var $uses = array('User', 'MyFile', 'Media', 'Tag', 'Category', 'Location', 'Option');
  var $helpers = array('form', 'formular', 'html', 'number');

  /** Array of filesystem root directories. */
  var $_fsRoots = array();

  function beforeFilter() {
    parent::beforeFilter();

    $this->requireRole(ROLE_USER, array('redirect' => '/'));

    $userDir = $this->FileManager->getUserDir();
    $this->_addFsRoot($userDir);

    $fsroots = $this->Option->buildTree($this->getUser(), 'path.fsroot', true);
    if (count($fsroots)) {
      foreach ($fsroots['fsroot'] as $id => $root) {
        $this->_addFsRoot($root);
      }
    }
  }

  function beforeRender() {
    $this->_setMenu();
    parent::beforeRender();
  }

  function _setMenu() {
    $items = array();
    $items[] = array('text' => 'Import Files', 'link' => 'index', 'type' => ($this->action=='index'?'active':false));
    if (count($this->_fsRoots) > 1) {
      $items[] = array('text' => 'Upload', 'link' => 'upload/files');
    } else {
      $items[] = array('text' => 'Upload', 'link' => 'upload');
    }
    $items[] = array('text' => 'Synchronize', 'link' => 'sync');
    $items[] = array('text' => 'Overview', 'link' => 'view');
    $menu = array('items' => $items);
    $this->set('mainMenu', $menu);
  }

  /** Add a root to the chroot aliases 
  @param root New root directory. The directory separator will be added to the
  root, if the root does not end with the directory separator, 
  @param alias Alias name for the root directory. This must start with an
  character, followed by a character, number, or special characters ('-', '_',
  '.')
  @return True on success. False otherwise */
  function _addFsRoot($root, $alias = null) {
    $root = Folder::slashTerm($root);

    if ($alias == null) {
      $alias=basename($root);
    }
    // on root path basename returns an empty string
    if ($alias == '') {
      $alias = 'root';
    }

    if (isset($this->_fsRoots[$alias])) {
      return false;
    }

    if (!@is_dir($root)) {
      Logger::err("Directory of '$root' does not exists");
      return false;
    }

    // Check alias syntax
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9\-_\.\:]+$/', $alias)) {
      Logger::err("Name '$alias' as alias is invalid");
      return false;
    }

    Logger::trace("Add new FS root '$root' (alias '$alias')");
    $this->_fsRoots[$alias]=$root;
    return true;
  }

  /** @return Returns the path of the current request */
  function getPath() {
    if (count($this->params['pass'])) {
      $path = implode('/', $this->params['pass']);
    } else {
      $path = '/';
    }
  
    if (strlen($path) && $path[strlen($path)-1] != '/') {
      $path .= '/';
    }
    if ($path[0] != '/') {
      $path = '/'.$path;
    }
    return $path;
  }

  /** Returns the canonicalized path 
    @param path
    @return canonicalized path */
  function _canonicalPath($path) {
    $paths=explode('/', $path);
    $canonical=array();
    foreach ($paths as $p) { 
      if ($p ==='' || $p == '.') {
        continue;
      }
      if ($p =='..') { 
        array_pop($canonical);
        continue;
      }
      array_push($canonical, $p);
    }
    return implode('/', $canonical);
  }

  /** Returns the filesystem path to the relative path. If multiple filesystem
   * roots are available, the highest directory is handled as alias of the
   * filesystem root.
    @param path Relative path
    @return Filesystem path of filename. If filesystem root could not be
    resolved it returns false 
    @note At lease one filesystem root must be defined
    */
  function _getFsPath($path) {
    $path = $this->_canonicalPath($path);
    $dirs = explode('/', $path);
    $fspath = false;
    if (count($this->_fsRoots) > 1) {
      // multiple FS roots, extract FS root by alias
      if (count($dirs) < 1 || !isset($this->_fsRoots[$dirs[0]])) {
        return false;
      }
      $alias = $dirs[0];
      unset($dirs[0]);
      $fspath = $this->_fsRoots[$alias].implode(DS, $dirs);
    } elseif (count($this->_fsRoots) == 1) {
      // only one FS root
      list($alias) = array_keys($this->_fsRoots);
      $fspath = $this->_fsRoots[$alias].implode(DS, $dirs);
    }

    if (!file_exists($fspath)) {
      return false;
    }
    return $fspath;
  }

  function index() {
    $path = $this->getPath();
    $fsPath = $this->_getFsPath($path);

    if ($fsPath) {
      $folder =& new Folder();
      $folder->cd($fsPath);
      list($dirs, $files) = $folder->read();

      // TODO get supported extensions from file filter
      $videos = array('avi', 'mov', 'mpg', 'mpeg', 'thm');
      $images = array('jpeg', 'jpg');
      $maps = array('log');
      $list = array();
      foreach ($files as $file) {
        $ext = strtolower(substr($file, strrpos($file, '.')+1));
        if (in_array($ext, $images)) {
          $list[$file] = 'image';
        } elseif (in_array($ext, $videos)) {
          $list[$file] = 'video';
        } elseif (in_array($ext, $maps)) {
          $list[$file] = 'maps';
        } else {
          $list[$file] = 'unknown';
        }
      }
      $files = $list;
    } else {
      if (strlen($path) > 1) {
        Logger::debug("Invalid path: '$path'. Redirect to index");
        $this->redirect('index');
      }
      // filesystem path could not be resolved. Take all aliases of filesystem
      // roots
      $dirs = array_keys($this->_fsRoots);
      $files = array();
    }
    
    $this->set('path', $path);
    $this->set('dirs', $dirs);
    $this->set('files', $files);

    // Check for internal path
    $userRoot = $this->User->getRootDir($this->getUser());
    if ($fsPath && strpos($fsPath, $userRoot) === 0) {
      $isInternal = true;
    } else {
      $isInternal = false;
    }
    $this->set('isInternal', $isInternal);
  }

  function import() {
    $user = $this->getUser();
    // parameter preparation
    $data = am(array('path' => '/', 'import' => array()), 
                $this->data['Browser']);

    $path = $data['path'];
    // Get dir and imports
    $dirs = array();
    $files = array();
    $toRead = array();
    foreach ($data['import'] as $file) {
      if (!$file) {
        continue;
      }
      $fsPath = $this->_getFsPath($file);
      if (is_dir($fsPath)) {
        $dirs[] = Folder::slashTerm($fsPath);
        $toRead[] = $fsPath;
      } elseif (file_exists($fsPath) && is_readable($fsPath)) {
        $files[] = $fsPath;
        $toRead[] = $fsPath;
      }
    }
    
    //Logger::debug($toRead);
    $readed = $this->FilterManager->readFiles($toRead);
    $errors = $this->FilterManager->errors;
    $this->Session->setFlash("Imported $readed files ($errors errors)");

    // Set data for view
    $this->set('path', $path);
    $this->set('dirs', $dirs);
    $this->set('files', $files);
  }

  /** Synchronize the meta data of the media with its file(s). All media with
   * the MEDIA_FLAG_DIRTY are synced or shortly before the maximum execution
   * time is exceed. */
  function sync() {
    $userId = $this->getUserId();
    $data = array('synced' => 0, 'errors' => 0, 'unsynced' => 0, 'total' => 0);

    // clear file cache 
    @clearstatcache();
    $start = $now = getMicrotime();
    $executionTime = ini_get('max_execution_time') - 5;

    $conditions= array('Media.user_id' => $userId, 'Media.flag & '.MEDIA_FLAG_DIRTY.' > 0');
    $data['total'] = $this->Media->find('count', array('conditions' => $conditions));
    $query = array('conditions' => $conditions, 'limit' => 10, 'order' => 'Media.modified ASC');
    $results = $this->Media->find('all', $query);
    while (count($results)) {
      foreach ($results as $media) {
        if (!$this->FilterManager->write($media)) {
          Logger::err("Could not export media {$media['Media']['name']} ({$media['Media']['id']})");
          $data['errors']++;
        } else {
          Logger::verbose("Synced meta data of media {$media['Media']['name']} ({$media['Media']['id']})");
          $data['synced']++;
        }

        $now = getMicrotime();
        if ($now - $start > $executionTime) {
          break;
        }
        $modified = $media['Media']['modified'];
      }
      if ($now - $start > $executionTime) {
        break;
      }
      // ensure we query not already called media (which might be unsynced due
      // an error)
      $query['conditions']['Media.modified >'] = $modified;
      $results = $this->Media->find('all', $query);
    }

    $data['unsynced'] = $this->Media->find('count', array('conditions' => $conditions));
    $this->data = $data;
  }

  function view() {
    $user = $this->getUser();
    $userId = $this->getUserId();
    $this->data = $user;
    $external = (FILE_FLAG_EXTERNAL);

    $files['count'] = $this->MyFile->find('count', array('conditions' => "User.id = $userId"));
    $bytes = $this->MyFile->findAll(array("User.id" => $userId, "File.flag & ".FILE_FLAG_EXTERNAL." = 0"), array('SUM(File.size) AS Bytes'));
    $files['bytes'] = floatval($bytes[0][0]['Bytes']);
    $bytes = $this->MyFile->findAll(array("User.id" => $userId), array('SUM(File.size) AS Bytes'));
    $files['bytesAll'] = $bytes[0][0]['Bytes'];
    $files['quota'] = $user['User']['quota'];
    $files['free'] = $files['quota'] - $files['bytes'];
    $files['active'] = $this->Media->find('count', array('conditions' => "User.id = $userId"));
    $files['dirty'] = $this->Media->find('count', array('conditions' => array('User.id' => $userId, 'Media.flag & '.MEDIA_FLAG_DIRTY.' > 0')));
    $files['video'] = $this->Media->find('count', array('conditions' => "User.id = $userId AND Media.duration > 0"));
    $files['external'] = $this->MyFile->find('count', array('conditions' => "User.id = $userId AND File.flag & $external = $external"));
    $files['public'] = $this->Media->find('count', array('conditions' => "User.id = $userId AND Media.oacl >= ".ACL_READ_PREVIEW));
    $files['user'] = $this->Media->find('count', array('conditions' => "User.id = $userId AND Media.oacl < ".ACL_READ_PREVIEW." AND Media.uacl >= ".ACL_READ_PREVIEW));
    $files['group'] = $this->Media->find('count', array('conditions' => "User.id = $userId AND Media.uacl < ".ACL_READ_PREVIEW." AND Media.gacl >= ".ACL_READ_PREVIEW));
    $files['private'] = $this->Media->find('count', array('conditions' => "User.id = $userId AND Media.gacl < ".ACL_READ_PREVIEW));

    $this->set('files', $files);
  }

  function folder() {
    $path = $this->getPath();
    $fsPath = $this->_getFsPath($path);
    // Check for internal path
    if (!$fsPath) {
      Logger::warn("Invalid path to create folder");
      $this->Session->setFlash("Invalid path to create folder");
      $this->redirect("index");
    }
    if ($this->FileManager->isExternal($fsPath)) {
      $this->Session->setFlash("Could not create folder here: $path");
      Logger::warn("Could not create folder in external path: $fsPath");
      $this->redirect("index/".$path);
    }

    if (!empty($this->data['Folder']['name'])) {
      $folder = new Folder($fsPath);
      $name = $this->data['Folder']['name'];

      $newFolder = Folder::slashTerm($fsPath).$name;
      if ($folder->mkdir($newFolder)) {
        Logger::verbose("Create folder $newFolder");
        $this->Session->setFlash("Folder $name created");
        $this->redirect("index/".$path.$name);
      } else {
        Logger::err("Could not create folder $name in $fsPath");
        $this->Session->setFlash("Could not create folder");
        $this->redirect('folder/'.$path);
      }
    }
    
    $this->set('path', $path);
  }

  function upload() {
    $path = $this->getPath();
    $fsPath = $this->_getFsPath($path);
    if (!$fsPath) {
      Logger::warn("Invalid path for upload");
      $this->Session->setFlash("Invalid path for upload");
      $this->redirect("index");
    }
    // Check for internal path
    if ($this->FileManager->isExternal($fsPath)) {
      $this->Session->setFlash("Could not upload here: $path");
      Logger::warn("Could not upload in external path: $fsPath");
      $this->redirect("index/".$path);
    }
    if ($this->Upload->isUpload()) {
      $filename = $this->Upload->upload(array('root' => $fsPath, 'overwrite' => false));
      if ($filename) {
        Logger::info("File '$filename' uploaded successfully");
        if (substr(strtolower($filename), -4) == '.zip' && $this->data['File']['extract']) {
          $files = $this->Zip->unzip($filename);
          $this->Session->setFlash("File uploaded successfully and ".count($files)." files were extracted");
          if ($this->FileManager->delete($filename)) {
            Logger::info("Delete archive $filename");
          }
        } else {
          $this->Session->setFlash("File uploaded successfully");
        }
      } else {
        $this->Session->setFlash("Could not upload file");
      }
    }

    // Fetch quota and free bytes
    $user = $this->getUser();
    $userId = $this->getUserId();
    $bytes = $this->MyFile->countBytes($userId);
    $quota = $user['User']['quota'];
    $free = $quota - $bytes;
    $this->set('quota', $quota);
    $this->set('free', $free);
    $max = strtoupper(ini_get('upload_max_filesize'));
    if (preg_match('/^([0-9]+)([TGMK])B?$/', $max, $matches)) {
      $max = $matches[1];
      switch ($matches[2]) {
        case 'T': $max *= 1024; 
        case 'G': $max *= 1024; 
        case 'M': $max *= 1024; 
        case 'B': $max *= 1024; 
      }
    }
    $this->set('max', $max);
    $this->set('path', $path);
  } 
}
?>
