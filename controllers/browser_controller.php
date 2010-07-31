<?php
/*
 * phtagr.
 * 
 * social photo gallery for your community.
 * 
 * Copyright (C) 2006-2010 Sebastian Felis, sebastian@phtagr.org
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
  var $helpers = array('Form', 'Html', 'Number', 'FileList', 'ImageData');

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

    $this->pageTitle = __('My Files', true);
  }

  function beforeRender() {
    $this->_setMenu();
    parent::beforeRender();
  }

  function _setMenu() {
    if ($this->action == 'quickupload') {
      return;
    }
    $items = array();
    $items[] = array('text' => __('Import Files', true), 'link' => 'index', 'type' => ($this->action=='index'?'active':false));
    if (count($this->_fsRoots) > 1) {
      $items[] = array('text' => __('Upload', true), 'link' => 'upload/files');
    } else {
      $items[] = array('text' => __('Upload', true), 'link' => 'upload');
    }
    $items[] = array('text' => __('Synchronize', true), 'link' => 'sync');
    $items[] = array('text' => __('Overview', true), 'link' => 'view');
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
    if (!$root) {
      Logger::warn("Invalid directory. Input is empty");
      return false;
    } elseif (!@is_dir($root)) {
      Logger::err("Directory of '$root' does not exists");
      return false;
    }

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

    // Check alias syntax
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9\-_\.\: ]+$/', $alias)) {
      Logger::err("Name '$alias' as alias is invalid");
      return false;
    }

    Logger::trace("Add new FS root '$root' (alias '$alias')");
    $this->_fsRoots[$alias]=$root;
    return true;
  }

  /** @return Returns the path of the current request */
  function _getPathFromUrl($strip = 0, $len = false) {
    $strip = max(0, $strip);
    if (count($this->params['pass']) - $strip - abs($len) > 0) {
      if ($len) {
        $dirs = array_slice($this->params['pass'], $strip, $len);
      } else {
        $dirs = array_slice($this->params['pass'], $strip);
      }
      $path = '/'.implode('/', $dirs).'/';
    } else {
      $path = '/';
    }
    return $path;
  }

  /** Returns the canonicalized path 
    @param path
    @return canonicalized path */
  function _canonicalPath($path) {
    $paths = explode('/', $path);
    $canonical = array();
    foreach ($paths as $p) { 
      if ($p === '' || $p == '.') {
        continue;
      }
      if ($p == '..') { 
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

  /** Read path from database and filesystem and returns array of files
    @param fsPath Filesystem path
    @return Array of files or false on error */
  function _readPath($fsPath) {
    if (!$fsPath || !is_dir($fsPath)) {
      Logger::err("Invalid path $fsPath");
      return false;
    }
    $userId = $this->getUserId();
    $fsPath = Folder::slashTerm($fsPath);

    // read database and the filesystem and compare it.
    $dbFiles = $this->MyFile->find('all', array('conditions' => array('path' => $fsPath), 'recursive' => -1));
    $dbFileNames = Set::extract('/File/file', $dbFiles);

    $folder =& new Folder();
    $folder->cd($fsPath);
    list($fsDirs, $fsFiles) = $folder->read();

    // add missing files
    $diffFiles = array_diff($fsFiles, $dbFileNames);
    foreach($diffFiles as $file) {
      $dbFiles[] = $this->MyFile->create($fsPath.$file, $userId);
    }
    // cut 'File' array index 
    $files = array();
    foreach($dbFiles as $file) {
      $files[] = $file['File'];
    }
    
    $dirs = array();
    foreach($fsDirs as $dir) {
      $file = $this->MyFile->create($fsPath.$dir, $userId);
      $dirs[] = $file['File'];
    }

    return array($dirs, $files);
  }

  function _readFile($filename) {
    $user = $this->getUser();
    if (!$this->MyFile->canRead($filename, $user)) {
      Logger::info("User cannot read $filename");
      $this->redirect(null, 404);
    }
    
    // Update metadata on dirty file
    $file = $this->MyFile->findByFilename($filename);
    if ($file && $this->Media->hasFlag($file, MEDIA_FLAG_DIRTY)) {
      $media = $this->Media->findById($file['Media']['id']);
      $this->FilterManager->write($media);
    }

    $options = $this->MyFile->getMediaViewOptions($filename);
    $options['download'] = true; 
    $this->set($options);
    $this->view = 'Media';
  }

  function index() {
    $path = $this->_getPathFromUrl();
    $fsPath = $this->_getFsPath($path);

    if ($fsPath) {
      if (is_file($fsPath)) {
        return $this->_readFile($fsPath);
      } else {
        list($dirs, $files) = $this->_readPath($fsPath);
        $isExternal = $this->FileManager->isExternal($fsPath);
      }
    } else {
      if (strlen($path) > 1) {
        Logger::debug("Invalid path: '$path'. Redirect to index");
        $this->redirect('index');
      }
      // filesystem path could not be resolved. Take all aliases of filesystem
      // roots
      $dirs = array();
      foreach(array_keys($this->_fsRoots) as $dir) {
        $dirs[] = array(
          'file' => $dir,
          'path' => $dir
          );
      }
      $files = array();
      $isExternal = true;
    }
    
    $this->set('path', $path);
    $this->set('dirs', $dirs);
    $this->set('files', $files);
    $this->set('isInternal', !$isExternal);
  }

  function import() {
    $path = $this->_getPathFromUrl();
    if (empty($this->data)) {
      Logger::warn("Empty post data");
      $this->redirect('index/'.$path);
    }

    // Get dir and imports
    $dirs = array();
    $files = array();
    $toRead = array();
    foreach ($this->data['Browser']['import'] as $file) {
      if (!$file) {
        continue;
      }
      $fsPath = $this->_getFsPath($path.$file);
      if (is_dir($fsPath)) {
        $dirs[] = Folder::slashTerm($fsPath);
        $toRead[] = $fsPath;
      } elseif (file_exists($fsPath) && is_readable($fsPath)) {
        $files[] = $fsPath;
        $toRead[] = $fsPath;
      }
    }
    
    $this->FilterManager->clearErrors();
    $readed = $this->FilterManager->readFiles($toRead);
    $errorCount = count($this->FilterManager->errors);

    $readCount = 0;
    foreach ($readed as $file => $media) {
      if ($media) {
        $readCount++;
      }
    }
    $this->Session->setFlash("Imported $readCount files ($errorCount) errors)");

    $this->redirect('index/'.$path);
  }

  function unlink() {
    $path = $this->_getPathFromUrl();
    $fsPath = $this->_getFsPath($path);
    $file = $this->MyFile->findByFilename($fsPath);
    if (!$file) {
      Logger::err("File not found: $fsPath");
      $this->redirect('index/'.$path);
    } elseif ($file['User']['id'] != $this->getUserId()) {
      Logger::warn("Deny access to file: $fsPath");
    } else {
      $this->Session->setFlash("Media {$file['File']['media_id']} was unlinked successfully");
      $this->Media->unlinkFile($file['File']['media_id'], $file['File']['id']);
    }
    $this->redirect('index/'.$this->_getPathFromUrl(0, -1));
  }

  function delete() {
    $path = $this->_getPathFromUrl();
    $fsPath = $this->_getFsPath($path);
    if (file_exists($fsPath)) {
      $path = $this->_getPathFromUrl(0, -1);
      $isDir = false;
      if (is_dir($fsPath)) {
        $isDir = true;
      }
      if ($this->FileManager->delete($fsPath)) {
        if ($isDir) {
          $this->Session->setFlash('Deleted directory successfully');
        } else {
          $this->Session->setFlash('Deleted file successfully');
        }
      } else {
        $this->Session->setFlash('Could not delete file or directory');
      }
    }
    $this->redirect('index/'.$path);
  }

  /** Synchronize the meta data of the media with its file(s). All media with
   * the MEDIA_FLAG_DIRTY are synced or shortly before the maximum execution
   * time is exceed. */
  function sync($action = false) {
    $userId = $this->getUserId();
    $data = array('action' => $action, 'synced' => array(), 'errors' => array(), 'unsynced' => 0);

    $conditions = array('Media.user_id' => $userId, 'Media.flag & '.MEDIA_FLAG_DIRTY.' > 0');
    $data['unsynced'] = $this->Media->find('count', array('conditions' => $conditions));
    if ($action == 'run') {
      $query = array('conditions' => $conditions, 'limit' => 10, 'order' => 'Media.modified ASC');
      $results = $this->Media->find('all', $query);

      // clear file cache 
      @clearstatcache();
      $start = $now = getMicrotime();
      $executionTime = ini_get('max_execution_time') - 5;

      while (count($results)) {
        foreach ($results as $media) {
          if (!$this->FilterManager->write($media)) {
            Logger::err("Could not export media {$media['Media']['name']} ({$media['Media']['id']})");
            $data['errors'][] = $media;
          } else {
            Logger::verbose("Synced meta data of media {$media['Media']['name']} ({$media['Media']['id']})");
            $data['synced'][] = $media;
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
    }
    $this->data = $data;
  }

  function view() {
    $user = $this->getUser();
    $userId = $this->getUserId();
    $this->data = $user;
    $external = (FILE_FLAG_EXTERNAL);

    $files['count'] = $this->MyFile->find('count', array('conditions' => "User.id = $userId"));
    $bytes = $this->MyFile->find('all', array('conditions' => array("User.id" => $userId, "File.flag & ".FILE_FLAG_EXTERNAL." = 0"), 'fields' => 'SUM(File.size) AS Bytes'));
    $files['bytes'] = floatval($bytes[0][0]['Bytes']);
    $bytes = $this->MyFile->find('all', array('conditions' => array("User.id" => $userId), 'fields' => 'SUM(File.size) AS Bytes'));
    $files['bytesAll'] = $bytes[0][0]['Bytes'];
    $files['quota'] = $user['User']['quota'];
    $files['free'] = max(0, $files['quota'] - $files['bytes']);
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
    $path = $this->_getPathFromUrl();
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
      if ($folder->create($newFolder)) {
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

  function _upload($dst, $redirectOnFailure = false) {
    if ($redirectOnFailure === false) {
      $redirectOnFailure = $this->action;
    }
    $dst = Folder::slashTerm($dst);
    if (!$this->Upload->isUpload()) {
      Logger::info("No upload data available");
      return false;
    } elseif (!$this->FileManager->canWrite($this->Upload->getSize())) {
      $this->Session->setFlash(__("Your upload quota is exceeded", true));
      Logger::warn("User upload quota exceeded. Upload denied.");
      return false;
    }
    $files = $this->Upload->upload($dst, array('overwrite' => false));
    $result = array();
    foreach($files as $file) {
      $result[] = $dst . $file;
    }
    return $result;
  }

  function _extract($dst, $files) {
    $result = array();
    foreach ($files as $file) {
      if (strtolower(substr($file, -4)) == '.zip') {
        if (!$this->FileManager->canWrite($this->Zip->getExtractedSize($file))) {
          Logger::warn("User upload quota exceeded. Unzip failed: " . $file);
          continue;
        }
        $zipFiles = $this->Zip->unzip($file);
        if ($zipFiles !== false) {
          $result[$file] = $zipFiles;
        } else {
          Logger::warn("Could not extract $file");
        }
      }
    }
    
    return $result;    
  }

  function __fromReadableSize($readable) {
    if (is_float($readable) || is_numeric($readable)) {
      return $readable;
    } elseif (preg_match_all('/^\s*(0|[1-9][0-9]*)(\.[0-9]+)?\s*([KkMmGg][Bb]?)?\s*$/', $readable, $matches, PREG_SET_ORDER)) {
      $matches = $matches[0];
      $size = (float)$matches[1];
      if (is_numeric($matches[2])) {
        $size += $matches[2];
      }
      if (is_string($matches[3])) {
        switch ($matches[3][0]) {
          case 'k':
          case 'K':
            $size = $size * 1024;
            break;
          case 'm':
          case 'M':
            $size = $size * 1024 * 1024;
            break;
          case 'g':
          case 'G':
            $size = $size * 1024 * 1024 * 1024;
            break;
          default:
            Logger::err("Unknown unit {$matches[3]}");
        }
      }
      if ($size < 0) {
        Logger::err("Size is negtive: $size");
        return 0;
      }
      return $size;
    } else {
      return 0;
    }
  }

  /** Evaluates the maximum upload size by php configuration of post_max_size,
 * upload_max_filesize, and memory_limit */
  function _getMaxUploadSize() {
    $max = 1024 * 1024 * 1024;
    if (!function_exists('ini_get')) {
      return 16 * 1024 * 1024;
    }

    if (ini_get('upload_max_filesize')) {
      $max = min($max, $this->__fromReadableSize(ini_get('upload_max_filesize')));
    }
    if (ini_get('post_max_size')) {
      $max = min($max, $this->__fromReadableSize(ini_get('post_max_size')));
    }
    if (ini_get('memory_limit')) {
      $max = min($max, $this->__fromReadableSize(ini_get('memory_limit')));
    }
    return $max;
  }

  /** Set quota information for the view */
  function _setQuotaForView() {
    // Fetch quota and free bytes
    $user = $this->getUser();
    $userId = $this->getUserId();
    $bytes = $this->MyFile->countBytes($userId);
    $quota = $user['User']['quota'];
    $free = max(0, $quota - $bytes);
    $this->set('quota', $quota);
    $this->set('free', $free);
    $this->set('max', $this->_getMaxUploadSize());
  }

  function upload() {
    $path = $this->_getPathFromUrl();
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
    if (!empty($this->data) && $this->Upload->isUpload()) {
      $files = $this->_upload($fsPath);

      $fileCount = count($files);
      $extractedCount = 0;
      if ($this->data['File']['extract']) {
        $zips = $this->_extract($fsPath, $files);
        if ($zips) {
          foreach ($zips as $zip => $extracted) {
            $extractedCount += count($extracted);
            $this->FileManager->delete($zip);
            unset($files[array_search($zip, $files)]);
            $files = am($files, $extracted);
          }
        }
      }  
      if ($extractedCount) {
        $this->Session->setFlash(sprintf(__("Uploaded %d and %d extraced file(s)", true), $fileCount, $extractedCount));
      } else {
        $this->Session->setFlash(sprintf(__("Uploaded %d file(s)", true), $fileCount));
      }
    }
    $this->set('path', $path);
    $this->_setQuotaForView();
  } 

  function _getDailyUploadDir() {
    $root = $this->User->getRootDir($this->getUser());
    if (!$root) {
      Logger::err("Invalid user upload directory");
      $this->Session->setFlash(__("Error: Invalid upload directory", true));
      return false;
    }

    $dst = $root . date('Y') . DS . date('Y-m-d') . DS;
    $folder = new Folder($dst, true);
    if (!$folder) {
      Logger::err("Daily upload directory not created");
      $this->Session->setFlash(__("Error: Invalid upload directory", true));
      return false;
    }
    return $dst;
  }

  function quickupload() {
    if (!empty($this->data)) {
      if (!$this->Upload->isUpload()) {
        Logger::info("No upload data");
        $this->Session->setFlash(__("No files uploaded or upload errors", true));
        $this->redirect($this->action);
      }

      $dst = $this->_getDailyUploadDir();     
      if (!$dst) {
        $this->redirect($this->action);
      }

      $files = $this->_upload($dst);
      $zips = $this->_extract($dst, $files);
      foreach ($zips as $zip => $extracted) {
        $this->FileManager->delete($zip);
        unset($files[array_search($zip, $files)]);
        $files = am($files, $extracted);
      }
      if (!$files) {
        $this->Session->setFlash(__("No files uploaded", true));
        $this->redirect($this->action);
      } else { 
        $toRead = array();
      }
      $this->FilterManager->clearErrors();
      $readed = $this->FilterManager->readFiles($files);
      $errors = $this->FilterManager->errors;
      $this->Session->setFlash(sprintf(__("Uploaded %d files with %d errors.", true), count($readed), count($errors)));
      $this->set('imports', $readed);
      $this->set('errors', $errors);
    } else {
      $this->set('imports', array());
      $this->set('errors', array());
    }
    $this->_setQuotaForView();
  }
}
?>
