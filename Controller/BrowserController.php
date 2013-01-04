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

class BrowserController extends AppController
{
  var $name = "Browser";

  var $components = array('FileManager', 'RequestHandler', 'FilterManager', 'Upload', 'Zip', 'Plupload', 'QueryBuilder');
  var $uses = array('User', 'MyFile', 'Media', 'Option');
  var $helpers = array('Form', 'Html', 'Number', 'FileList', 'ImageData', 'Plupload', 'Autocomplete');
  var $subMenu = false;
  /** Array of filesystem root directories. */
  var $_fsRoots = array();

  public function beforeFilter() {
    parent::beforeFilter();
    $this->logUser();
    $this->subMenu = array(
      'import' => __("Import Files"),
      'upload' => __("Upload"),
      'easyacl' => __("Edit Access Rrights"),
      'sync' => __("Meta Data Sync"),
      'view' => __("Overview"),
      );

    $this->requireRole(ROLE_USER, array('redirect' => '/'));

    $userDir = $this->FileManager->getUserDir();
    $this->_addFsRoot($userDir);

    $fsroots = $this->Option->buildTree($this->getUser(), 'path.fsroot', true);
    if (count($fsroots)) {
      foreach ($fsroots['fsroot'] as $id => $root) {
        $this->_addFsRoot($root);
      }
    }

    $this->pageTitle = __('My Files');
    $this->layout = 'backend';
  }

  public function beforeRender() {
    parent::beforeRender();
  }

  /**
   * Add a root to the chroot aliases
   *
   * @param root New root directory. The directory separator will be added to the
   * root, if the root does not end with the directory separator,
   * @param alias Alias name for the root directory. This must start with an
   * character, followed by a character, number, or special characters ('-', '_',
   * '.')
   * @return True on success. False otherwise
   */
  public function _addFsRoot($root, $alias = null) {
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

  /**
   * @return Returns the path of the current request
   */
  public function _getPathFromUrl($strip = 0, $len = false) {
    $strip = max(0, $strip);
    if (count($this->request->params['pass']) - $strip - abs($len) > 0) {
      if ($len) {
        $dirs = array_slice($this->request->params['pass'], $strip, $len);
      } else {
        $dirs = array_slice($this->request->params['pass'], $strip);
      }
      $path = '/'.implode('/', $dirs).'/';
    } else {
      $path = '/';
    }
    return $path;
  }

  /**
   * Returns the canonicalized path
   *
   * @param path
   * @return canonicalized path
   */
  public function _canonicalPath($path) {
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

  /**
   * Returns the filesystem path to the relative path. If multiple filesystem
   * roots are available, the highest directory is handled as alias of the
   * filesystem root.
   *
   * @param path Relative path
   * @return Filesystem path of filename. If filesystem root could not be
   * resolved it returns false
   * @note At lease one filesystem root must be defined
   */
  public function _getFsPath($path) {
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

  /**
   * Read path from database and filesystem and returns array of files
   *
   * @param fsPath Filesystem path
   * @return Array of files or false on error
   */
  public function _readPath($fsPath) {
    if (!$fsPath || !is_dir($fsPath)) {
      Logger::err("Invalid path $fsPath");
      return false;
    }
    $userId = $this->getUserId();
    $fsPath = Folder::slashTerm($fsPath);

    // read database and the filesystem and compare it.
    $dbFiles = $this->MyFile->find('all', array('conditions' => array('path' => $fsPath), 'recursive' => -1));
    $dbFileNames = Set::extract('/File/file', $dbFiles);

    $folder = new Folder();
    $folder->cd($fsPath);
    list($fsDirs, $fsFiles) = $folder->read();

    // add missing files
    $diffFiles = array_diff($fsFiles, $dbFileNames);
    foreach($diffFiles as $file) {
      $dbFiles[] = $this->MyFile->createFromFile($fsPath.$file, $userId);
    }
    // cut 'File' array index
    $files = array();
    foreach($dbFiles as $file) {
      $files[] = $file['File'];
    }

    $dirs = array();
    foreach($fsDirs as $dir) {
      $file = $this->MyFile->createFromFile($fsPath.$dir, $userId);
      $dirs[] = $file['File'];
    }

    return array($dirs, $files);
  }

  public function _readFile($filename) {
    $user = $this->getUser();
    if (!$this->MyFile->canRead($filename, $user)) {
      Logger::info("User cannot read $filename");
      $this->redirect(null, 404);
    }

    // Update metadata on dirty file ??? and if file was also changed? ask owner before writing?
    $file = $this->MyFile->findByFilename($filename);
    if ($file && $this->Media->hasFlag($file, MEDIA_FLAG_DIRTY) && $this->getOption('filter.write.onDemand')) {
      $media = $this->Media->findById($file['Media']['id']);
      $this->FilterManager->write($media);
    }

    $options = $this->MyFile->getMediaViewOptions($filename);
    $options['download'] = true;
    $this->set($options);
    $this->viewClass = 'Media';
  }

  public function index() {
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

  public function import() {

    if ($this->hasRole(ROLE_ADMIN)) {
      ini_set('max_execution_time', 3600);//1 hour
    }

    $path = $this->_getPathFromUrl();
    if (empty($this->request->data)) {
      Logger::warn("Empty post data");
      $this->redirect('index/'.$path);
    }

    $recursive = (bool) $this->request->data['Browser']['options']['recursive'];
    $options = $this->request->data['Browser']['options'];
    if (isset($this->request->data['unlink'])) {
      $this->_unlinkSelected($path, $this->request->data['Browser']['import'], $recursive);
      return;
    }
    // Get dir and imports
    $dirs = array();
    $files = array();
    $toRead = array();
    foreach ($this->request->data['Browser']['import'] as $file) {
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

    $readed = $this->FilterManager->readFiles($toRead, $options);
    $skipped = count($this->FilterManager->skipped);
    $errorCount = count($this->FilterManager->errors);

    $readCount = 0;
    foreach ($readed as $file => $media) {
      if ($media) {
        $readCount++;
      }
    }
    $this->Session->setFlash(__("Processed %d files (imported %d, skipped %d, %d errors)", $readCount, $readCount-$skipped, $skipped, $errorCount));

    $this->FilterManager->ImageFilter->Exiftool->exitExiftool();
    $this->redirect('index/'.$path);
  }

  public function unlink() {
    $path = $this->_getPathFromUrl();
    $fsPath = $this->_getFsPath($path);
    $file = $this->MyFile->findByFilename($fsPath);
    if (!$file) {
      Logger::err("File not found: $fsPath");
      $this->redirect('index/'.$path);
    } elseif ($file['User']['id'] != $this->getUserId()) {
      Logger::warn("Deny access to file: $fsPath");
    } else {
      $this->Session->setFlash(__("Media %d was unlinked successfully", $file['File']['media_id']));
      $this->Media->unlinkFile($file['File']['media_id'], $file['File']['id']);
    }
    $this->redirect('index/'.$this->_getPathFromUrl(0, -1));
  }

  /**
   * Find Media files of path
   *
   * @param type $path Relative path
   * @param type $files Array of filenames or directory names
   * @param type $recursive Find media files recursivly if true
   * @return type Array of File model data
   */
  public function _findMediaFiles($path, $files, $recursive) {
    $fsPath = $this->_getFsPath($path);
    $fsPath = Folder::slashTerm($fsPath);
    if (!is_dir($fsPath)) {
      return array();
    }
    $result = array();
    $filesHere = $this->MyFile->find('all', array('conditions' => array('path' => $fsPath)));
    foreach ($files as $file) {
      if (!$file) {
        continue;
      }
      if ($file==="."){
        $filename = $fsPath;
      } else {
        $filename = $fsPath . $file;
      }
      if (is_dir($filename)) {
        $filename = Folder::slashTerm($filename);
        if ($recursive) {
          $result = am($result, $this->MyFile->find('all', array('conditions' => array('path LIKE' => $filename . '%'))));
        } else {
          $result = am($result, $this->MyFile->find('all', array('conditions' => array('path' => $filename))));
        }
      } else if (file_exists($filename)) {
        // Fetch from current directory
        foreach ($filesHere as $f) {
          if ($f['File']['file'] == $file) {
            $result[] = $f;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Unlink media of selected files
   *
   * @param type $path Relative path of view
   * @param type $files List of selected files or directories
   * @param type $recursive True is unlinking should be act recursive
   */
  public function _unlinkselected($path, $files, $recursive) {
    $unlinkedCount=0;
    $mediaFiles = $this->_findMediaFiles($path, $files, $recursive);
    foreach ($mediaFiles as $file) {
      $this->Media->unlinkFile($file, $file['File']['id']);
      $unlinkedCount ++;
    }

    $this->Session->setFlash(__("Unlinked %d files.", $unlinkedCount));

    $this->redirect('index/'.$path);
  }

  public function delete() {
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
          $this->Session->setFlash(__('Deleted directory successfully'));
        } else {
          $this->Session->setFlash(__('Deleted file successfully'));
        }
      } else {
        $this->Session->setFlash(__('Could not delete file or directory'));
      }
    }

    $this->redirect('index/'.$path);
  }

  /**
   * Synchronize the meta data of the media with its file(s). All media with
   * the MEDIA_FLAG_DIRTY are synced or shortly before the maximum execution
   * time is exceed.
   */
  public function sync($action = false) {
    $userId = $this->getUserId();
    $data = array('action' => $action, 'synced' => array(), 'errors' => array(), 'unsynced' => 0);

    $conditions = array('Media.user_id' => $userId, 'Media.flag & '.MEDIA_FLAG_DIRTY.' > 0');
    $data['unsynced'] = $this->Media->find('count', array('conditions' => $conditions));
    if ($action == 'run') {
      $query = array('conditions' => $conditions, 'limit' => 10, 'order' => 'Media.id ASC');
      $results = $this->Media->find('all', $query);

      // clear file cache
      @clearstatcache();
      $start = $now = microtime(true);
      $executionTime = intval(ini_get('max_execution_time'));
      if ($executionTime !== 0) {
        $executionTime -= 5;
      }

      $this->FilterManager->clearErrors();
      while (count($results)) {
        foreach ($results as $media) {
          if (!$this->FilterManager->write($media)) {
            Logger::err("Could not export media {$media['Media']['name']} ({$media['Media']['id']})");
            $data['errors'][] = $media;
          } else {
            Logger::verbose("Synced meta data of media {$media['Media']['name']} ({$media['Media']['id']})");
            $data['synced'][] = $media;
          }
          $lastId = $media['Media']['id'];

          $now = microtime(true);
          if ($executionTime > 0 && $now - $start > $executionTime) {
            break;
          }
        }
        if ($executionTime > 0 && $now - $start > $executionTime) {
          break;
        }
        // ensure we query not already called media (which might be unsynced due
        // an error)
        $query['conditions']['Media.id >'] = $lastId;
        $results = $this->Media->find('all', $query);
      }
      $data['unsynced'] = $this->Media->find('count', array('conditions' => $conditions));
      $data['errors'] = $this->FilterManager->getErrors();
    }
    $this->request->data = $data;
  }

  public function view() {
    $user = $this->getUser();
    $userId = $this->getUserId();
    $this->request->data = $user;
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

  public function folder() {
    $path = $this->_getPathFromUrl();
    $fsPath = $this->_getFsPath($path);
    // Check for internal path
    if (!$fsPath) {
      Logger::warn("Invalid path to create folder");
      $this->Session->setFlash(__("Invalid path to create folder"));
      $this->redirect("index");
    }
    if ($this->FileManager->isExternal($fsPath)) {
      $this->Session->setFlash(__("Could not create folder here: %s", $path));
      Logger::warn("Could not create folder in external path: $fsPath");
      $this->redirect("index/".$path);
    }

    if (!empty($this->request->data['name'])) {
      $folder = new Folder($fsPath);
      $name = $this->request->data['name'];

      $newFolder = Folder::slashTerm($fsPath).$name;
      if ($folder->create($newFolder)) {
        Logger::verbose("Create folder $newFolder");
        $this->Session->setFlash("Folder $name created");
        $this->redirect("index/".$path.$name);
      } else {
        Logger::err("Could not create folder $name in $fsPath");
        $this->Session->setFlash(__("Could not create folder"));
        $this->redirect('folder/'.$path);
      }
    }

    $this->set('path', $path);
  }

  public function _upload($dst, $redirectOnFailure = false) {
    if ($redirectOnFailure === false) {
      $redirectOnFailure = $this->action;
    }
    $dst = Folder::slashTerm($dst);
    if (!$this->Upload->isUpload()) {
      Logger::info("No upload data available");
      return false;
    } elseif (!$this->FileManager->canWrite($this->Upload->getSize())) {
      $this->Session->setFlash(__("Your upload quota is exceeded"));
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

  public function _extract($dst, $files) {
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

  public function __fromReadableSize($readable) {
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
  public function _getMaxUploadSize() {
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

  /**
   * Set quota information for the view
   */
  public function _setQuotaForView() {
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

  public function upload() {
    $path = $this->_getPathFromUrl();
    $fsPath = $this->_getFsPath($path);
    if (!$fsPath) {
      Logger::warn("Invalid path for upload");
      $this->Session->setFlash(__("Invalid path for upload"));
      $this->redirect("index");
    }
    // Check for internal path
    if ($this->FileManager->isExternal($fsPath)) {
      $this->Session->setFlash(__("Could not upload here: %s", $path));
      Logger::warn("Could not upload in external path: $fsPath");
      $this->redirect("index/".$path);
    }
    if (!empty($this->request->data) && $this->Upload->isUpload()) {
      $files = $this->_upload($fsPath);

      $fileCount = count($files);
      $extractedCount = 0;
      if ($this->request->data['File']['extract']) {
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
        $this->Session->setFlash(__("Uploaded %d and %d extraced file(s)", $fileCount, $extractedCount));
      } else {
        $this->Session->setFlash(__("Uploaded %d file(s)", $fileCount));
      }
    }
    $this->set('path', $path);
    $this->_setQuotaForView();
  }

  public function _getDailyUploadDir() {
    $root = $this->User->getRootDir($this->getUser());
    if (!$root) {
      Logger::err("Invalid user upload directory");
      $this->Session->setFlash(__("Error: Invalid upload directory"));
      return false;
    }

    $dst = $root . date('Y') . DS . date('Y-m-d') . DS;
    $folder = new Folder($dst, true);
    if (!$folder) {
      Logger::err("Daily upload directory not created");
      $this->Session->setFlash(__("Error: Invalid upload directory"));
      return false;
    }
    return $dst;
  }

  public function quickupload() {
    if (!empty($this->request->data)) {
      if (!$this->Upload->isUpload()) {
        Logger::info("No upload data");
        $this->Session->setFlash(__("No files uploaded or upload errors"));
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
        $this->Session->setFlash(__("No files uploaded"));
        $this->redirect($this->action);
      } else {
        $toRead = array();
      }
      $this->FilterManager->clearErrors();
      $readed = $this->FilterManager->readFiles($files, false);
      $errors = $this->FilterManager->errors;
      $mediaIds = array();
      foreach ($readed as $file => $mediaId) {
        if ($mediaId) {
          $mediaIds[] = $mediaId;
        }
      }
      $this->Session->setFlash(__("Uploaded %d files with %d errors.", count($mediaIds), count($errors)));
      $media = $this->Media->find('all', array('conditions' => array('Media.id' => $mediaIds)));
      $this->set('imports', $media);
      $this->set('errors', $errors);
    } else {
      $this->set('imports', array());
      $this->set('errors', array());
    }
    $this->_setQuotaForView();
    $this->layout = 'default';
  }

  public function plupload() {
    $dst = $this->_getDailyUploadDir();
    if (!$dst) {
      $this->redirect($this->action);
    }
    $filename = $this->Plupload->upload($dst);
    $pluploadResponse = $this->Plupload->response;
    if ($filename) {
      $files = array(Folder::addPathElement($dst, $filename));
      $zips = $this->_extract($dst, $files);
      foreach ($zips as $zip => $extracted) {
        $this->FileManager->delete($zip);
        unset($files[array_search($zip, $files)]);
        $files = am($files, $extracted);
      }
      if (!$files) {
        $this->Session->setFlash(__("No files uploaded"));
        $this->redirect($this->action);
      }
      $this->FilterManager->clearErrors();
      $result = $this->FilterManager->readFiles($files);
      $mediaIds = array();
      foreach ($result as $filename => $mediaId) {
        if ($mediaId) {
          $mediaIds[] = $mediaId;
        }
      }
      $pluploadResponse['mediaIds'] = $mediaIds;
    }
    $this->viewClass = 'Json';
    foreach ($pluploadResponse as $key => $value) {
      $this->set($key, $value);
    }
    $this->set('_serialize', array_keys($pluploadResponse));
  }

  /**
   * Extract acl changes from request form
   *
   * @param array $user Current model user
   * @return array ACL changes
   */
  private function _extractAclParameter(&$user) {
    $aclData = array('Media' => array());
    foreach (array('readPreview', 'readOriginal', 'writeTag', 'writeMeta') as $name) {
      if (!empty($this->request->data['Media'][$name]) && $this->request->data['Media'][$name] != ACL_LEVEL_KEEP) {
        $aclData['Media'][$name] = $this->request->data['Media'][$name];
      }
    }
    $editData = $this->Media->prepareMultiEditData($aclData, $user);
    return $editData;
  }

  /**
   * Spilt text into words and remove empty words
   *
   * @param string $text
   * @return array
   */
  private function _splitWords($text) {
    $words = preg_split('/\s*,\s*/', trim($text));
    $result = array();
    foreach ($words as $word) {
      if ($word) {
        $result[] = $word;
      }
    }
    return $result;
  }

  /**
   * Change acl for all media of first selected group or first sellected keyword
   */
  public function easyacl() {
    $mediaIds = array();
    $this->set('mediaIds', $mediaIds);

    if (empty($this->request->data)) {
      return;
    }

    $groupNames = $this->_splitWords($this->request->data['Group']['names']);
    $tagNames = $this->_splitWords($this->request->data['Field']['keyword']);

    if (!$groupNames && !$tagNames) {
      $this->Session->setFlash(__("Group or tag criteria is missing"));
      return;
    }

    $userId = $this->getUserId();
    $user = $this->User->findById($userId);

    $editData = $this->_extractAclParameter($user);
    if (!$editData) {
      $this->Session->setFlash(__("No access right changes given"));
      return;
    }

    $queryData = array('user' => $user['User']['username']);
    if ($groupNames) {
      $queryData['group'] = $groupNames;
    }
    if ($tagNames) {
      $queryData['tag'] = $tagNames;
    }
    $query = $this->QueryBuilder->build($queryData);
    $allMedia = $this->Media->find('all', $query);

    if ($allMedia) {
      $mediaIds = $this->_applyAclChanges($allMedia, $editData, $user);
      $this->set('mediaIds', $mediaIds);
      $this->Session->setFlash(__("Updated access rights of %d media", count($mediaIds)));
    } else {
      $this->Session->setFlash(__("No media found!"));
    }
  }

  private function _applyAclChanges(&$allMedia, &$editData, &$user) {
    $changedMedia = array();
    foreach ($allMedia as $media) {
      $this->Media->setAccessFlags($media, $user);
      // primary access check
      if (!$media['Media']['canWriteTag'] && !$media['Media']['canWriteAcl']) {
        Logger::warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change any metadata of image ".$id);
        continue;
      }
      $tmp = $this->Media->editMulti($media, $editData, $user);
      if ($tmp) {
        $changedMedia[] = $tmp;
      }
    }
    if ($changedMedia) {
      if (!$this->Media->saveAll($changedMedia)) {
        Logger::warn("Could not save media: " . join(", ", Set::extract("/Media/id", $changedMedia)));
      } else {
        Logger::debug("Saved media: " . join(', ', Set::extract("/Media/id", $changedMedia)));
        return Set::extract('/Media/id', $changedMedia);
      }
    }
    return array();
  }
}
