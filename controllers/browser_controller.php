<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2008 Sebastian Felis, sebastian@phtagr.org
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

  var $components = array('RequestHandler', 'ImageFilter', 'VideoFilter');
  var $uses = array('User', 'Image', 'Tag', 'Category', 'Location', 'Preference');
  var $helpers = array('form', 'formular', 'html');

  /** Array of filesystem root directories. */
  var $_fsRoots = array();

  function beforeFilter() {
    parent::beforeFilter();

    $this->requireRole(ROLE_MEMBER);

    $userDir = $this->User->getRootDir($this->getUser());
    $this->_addFsRoot($userDir);

    $fsroots = $this->Preference->buildTree($this->getUser(), 'path.fsroot', true);
    if (count($fsroots)) {
      foreach ($fsroots['fsroot'] as $id => $root) {
        $this->_addFsRoot($root);
      }
    }
  }

  function beforeRender() {
    $this->_setMenu();
  }

  function _setMenu() {
    $items = array();
    $items[] = array('text' => 'Import Files', 'link' => 'index', 'type' => ($this->action=='index'?'active':false));
    $items[] = array('text' => 'Synchroinze', 'link' => 'sync');
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

    if ($alias == null)
      $alias=basename($root);
    // on root path basename returns an empty string
    if ($alias == '')
      $alias = 'root';

    if (isset($this->_fsRoots[$alias]))
      return false;

    if (!@is_dir($root)) {
      $this->Logger->err("Directory of '$root' does not exists");
      return false;
    }

    // Check alias syntax
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9\-_\.\:]+$/', $alias)) {
      $this->Logger->err("Name '$alias' as alias is invalid");
      return false;
    }

    $this->Logger->trace("Add new FS root '$root' (alias '$alias')");
    $this->_fsRoots[$alias]=$root;
    return true;
  }

  /** Returns the canonicalized path 
    @param path
    @return canonicalized path */
  function _canonicalPath($path)
  {
    $paths=explode('/', $path);
    $canonical=array();
    foreach ($paths as $p) { 
      if ($p ==='' || $p == '.')
        continue;
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
    @return Filesystem path or false, if filesystem root could not be resolved
    */
  function _getFsPath($path) {
    $path = $this->_canonicalPath($path);
    $dirs = explode('/', $path);
    if (count($this->_fsRoots) > 1) {
      // we hav multiple FS root, extract FS root by alias
      if (count($dirs) > 0) {
        $alias = $dirs[0];
        if (isset($this->_fsRoots[$alias])) {
          unset($dirs[0]);
          return $this->_fsRoots[$alias].implode(DS, $dirs);
        }
      } 
    } elseif (count($this->_fsRoots) == 1) {
      // only one FS root
      list($alias) = array_keys($this->_fsRoots);
      return $this->_fsRoots[$alias].implode(DS, $dirs);
    }
    return false;
  }

  function index() {
    if (count($this->params['pass']))
      $path = implode('/', $this->params['pass']);
    else 
      $path = '/';
  
    if (strlen($path) && $path[strlen($path)-1] != '/')
      $path .= '/';
    if ($path[0] != '/')
      $path = '/'.$path;

    $fsPath = $this->_getFsPath($path);

    if ($fsPath) {
      $folder =& new Folder();
      $folder->cd($fsPath);
      list($dirs, $files) = $folder->read();

      // TODO get supported extensions from file filter
      $videos = array('avi', 'mov', 'mpg', 'mpeg');
      $images = array('jpeg', 'jpg');
      $list = array();
      foreach ($files as $file) {
        $ext = strtolower(substr($file, strrpos($file, '.')+1));
        if (in_array($ext, $images))
          $list[$file] = 'image';
        elseif (in_array($ext, $videos))
          $list[$file] = 'video';
        else
          $list[$file] = 'unknown';
      }
      $files = $list;
    } else {
      // filesystem path could not be resolved. Take all aliases of filesystem
      // roots
      $dirs = array_keys($this->_fsRoots);
      $files = array();
    }
    
    $this->set('path', $path);
    $this->set('dirs', $dirs);
    $this->set('files', $files);
  }

  function _importFile($filename) {
    $user =& $this->getUser();
    $path = Folder::slashTerm(dirname($filename));
    $file = basename($filename);
    $image = $this->Image->find(array('path' => $path, 'file' => $file));

    if ($image) {
      if ($image['Image']['flag'] & IMAGE_FLAG_ACTIVE > 0) {
        $this->Logger->debug("File '$filename' is already in database");
        $this->Logger->warn("Import of existing files currently not supported"); 
        return 0;
        // TODO Synchronize data
      }
      $imageId = $image['Image']['id'];      
    } else {
      $this->Logger->debug("File '$filename' is not in the database");
      $imageId = $this->Image->insertFile($filename, $user);
      if (!$imageId) {
        $this->Logger->err("Could not insert '$filename' to the database");
        return -1;
      }
      $image = $this->Image->findById($imageId);
      if (!$image) {
        $this->Logger->err("Could not read image with id $imageId");
        return -1;
      }
    }
    $image['Image']['flag'] |= IMAGE_FLAG_ACTIVE;
    $thumbFilename = false;
    if ($this->Image->isVideo($image)) {
      $this->VideoFilter->readFile(&$image);
      $thumbFilename = $this->VideoFilter->getVideoPreviewFilename(&$image);
    }
    $this->ImageFilter->readFile(&$image, $thumbFilename);
    if ($image !== false && $this->Image->save($image)) {
      $this->Logger->info("Imported file '$filename' with id $imageId");
      return 1;
    } else {
      $this->Logger->err("Could not save imported data of file '$filename'");
      return -1;
    }
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
    foreach ($data['import'] as $file) {
      if (!$file)
        continue;
      $fsPath = $this->_getFsPath($file);
      if (is_dir($fsPath))
        $dirs[] = Folder::slashTerm($fsPath);
      elseif (file_exists($fsPath) && is_readable($fsPath))
        $files[] = $fsPath;
    }
    
    // search for files
    $folder =& new Folder();
    foreach ($dirs as $dir) {
      $cd = $folder->cd($dir);
      // Get extensions from file filter
      $found = $folder->find('.*(jpe?g|avi|mov|mpe?g)');
      foreach ($found as $file) {
        $files[] = $dir.$file;
      }
    }

    // import files
    $numImports = 0;
    $numErrors = 0;
    foreach ($files as $file) {
      $result = $this->_importFile($file);
      if ($result>0)
        $numImports++;
      if ($result<0)
        $numErrors++;
    }
    $this->Session->setFlash("Imported $numImports files ($numErrors errors)");

    // Set data for view
    $this->set('path', $path);
    $this->set('dirs', $dirs);
    $this->set('files', $files);
  }

  function sync() {
    $userId = $this->getUserId();
    $synced = 0;
    $errors = 0;

    @clearstatcache();
    $this->Image->unbindModel(array('hasAndBelongsToMany' => array('Tag', 'Category', 'Location')));
    $result = $this->Image->findAll("Image.user_id=$userId AND Image.flag & ".IMAGE_FLAG_DIRTY." > 0", array("Image.id"));
    if (!$result) {
      $this->Logger->info("No images found for synchronization");
      $this->data['total'] = 0;
    } else {
      $ids = Set::extract($result, '{n}.Image.id');
      $start = getMicrotime();
      foreach ($ids as $id) {
        $image = $this->Image->findById($id);
        if ($this->Image->isVideo($image)) {
          $filename = $this->VideoFilter->getVideoPreviewFilename(&$image);
        } else {
          $filename = $this->Image->getFilename($image);
        }
        if (!$filename || !$this->ImageFilter->writeFile(&$image, $filename)) {
          $this->Logger->err("Count not write file '".$this->Image->getFilename($image)."'");
          $errors++;
        } else {
          $this->Logger->info("Synced file '".$this->Image->getFilename($image)."' ({$image['Image']['id']})");
          $synced++;
        }

        // Ensure only 25 sec maximum of execution
        $time = getMicrotime()-$start;
        if ($time > 25)
          break;
      }
      $this->data['total'] = count($ids);
    }

    $this->data['synced'] = $synced;
    $this->data['errors'] = $errors;
  }

}
?>
