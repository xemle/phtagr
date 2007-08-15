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

include_once("$phtagr_lib/Base.php");

/** This class abstracts the filesystem with chroot aliases. 
  @class Filesystem Implements a chrooted filesystem with multiple 
  aliases
  @todo Add flags to allow symlinks, allow create, allow delete
  @note The Filesystem does not check the users authorization! */
class Filesystem extends Base
{

/** Relative root directory to emulate chroot() at userspace */
var $_roots;
var $_is_windows;

function Filesystem()
{
  $this->_roots=array();
  $this->_is_windows=false;

  if (strtolower(substr(php_uname(),0,3))=='win')
  {
    $this->_is_windows=true;
  }
}

/** Returns the count of the filesystem paths */
function get_num_roots()
{
  return count($this->_roots);
}

/** Return all root aliases in a array
  @return Array of root aliases */
function get_roots()
{
  return array_keys($this->_roots);
}

/** Add a root to the chroot aliases 
  @param root New root directory. The directory separator will be added to the
  root, if the root does not end with the directory separator, 
  @param alias Alias name for the root directory. This must start with an
  character, followed by a character, number, or special characters ('-', '_',
  '.')
  @return True on success. False otherwise */
function add_root($root, $alias)
{
  $root=$this->slashify($root);

  if ($alias == '') 
    $alias=basename($root);
  // on root path basename returns an empty string
  if ($alias == '')
    $alias = 'root';

  if (isset($this->_roots[$alias]))
    return false;

  if (!@is_dir($root))
    return false;

  // Check alias syntax
  if (!preg_match('/^[A-Za-z][A-Za-z0-9\-_\.\:]+$/', $alias))
    return false;

  $this->_roots[$alias]=$root;
  return true;
}

/** Removes an root alias
  @param alias Name of the alias
  @return True on success, false otherwise */
function remove_root($alias)
{
  if (isset($this->_roots[$alias]))
  {
    unset($this->_roots[$alias]);
    return true;
  }
  return false;
}

/** Delte all root aliases */
function clear_roots()
{
  $this->_roots=array();
}

/** Add the root aliases for the filesystem. On Unix like systems it adds the
 * system rooot.  On Windows system, it adds the drives as aliases. */
function add_system_roots()
{
  $this->clear_roots();
  if ($this->_is_windows)
  {
    $drives=$this->_get_windows_drives();
    foreach ($drives as $drive)
      $this->add_root($drive, $drive);
  } else {
    $this->add_root('/', _("Filesystem"));
  }
}

/** @return True if you running this under Windows */
function is_windows()
{
  return $this->_is_windows;
}

/** Read the drives of windows
  @return Array of valid windows drives */
function _get_windows_drives()
{
  $drives=array();

  for ($i=ord('C'); $i<ord('Z'); $i++)
  {
    if (@is_dir(chr($i).':'))
      array_push($drives, chr($i).':');
  }
  return $drives;
}

static function path_to_unix($win_path)
{
  return implode('/', explode('\\', $win_path));
}

static function path_to_windows($unix_path)
{
  return implode('\\', explode('/', $unix_path));
}

static function slashify($path)
{
  $len = strlen($path) - 1;
  if ($len < 0 || $path[$len] != '/')
    return $path.'/';
  return $path;
}

/** Removes a directory separator at the end */
static function unslashify($path)
{
  $len = strlen($path) - 0;
  while ($len > 0 && $path[$len] == '/')
    $len--;
  if ($len == 0)
    return '';
  return substr($path, 0, $len);
}

static function merge_paths($path, $child)
{
  if ($child[0] == '/')
    return Filesystem::unslashify($path).$child;
  else
    return Filesystem::slashify($path).$child;
}

/** Returns the filesystem path to an relative path 
  @param path Relative path
  @return Corresponding filesystem path or false on error */
function get_fspath($path)
{
  while ($path[0] == '/')
    $path=substr($path, 1);

  $paths = explode('/', trim($path));
  if (count($paths) == 0 || count($this->_roots) == 0)
  {
    return false;
  }

  foreach ($this->_roots as $alias => $fsroot) {
    if ($alias == $paths[0]) 
    {
      unset($paths[0]);
      return $this->merge_paths($fsroot, implode('/', $paths));
    }
  }
  return false;
}

/** Returns a list of subdirectories and files of the given directory
  @param dir Directory which should be read
  @return Two dimensioned array with array of subdirectorys and an array of
  files */
function read_dir($path)
{
  $fspath = $this->get_fspath($path);
  if ($fspath == false)
    return array(null, null);

  $fspath = $this->slashify($fspath);
  if (!@chdir($fspath))
    return array(null, null);

  if (!$handle = @opendir($fspath)) 
    return array(null, null);

  $subdirs=array();
  $files=array();
  while (false != ($file = readdir($handle))) {
    if (!is_readable($file) || $file=='.' || $file=='..') continue;

    if (@is_dir($fspath.$file)) {
      array_push($subdirs, $file);
    } else {
      array_push($files, $file);
    }
  }
  closedir($handle);
  sort($subdirs);
  sort($files);
  return array($subdirs, $files);
}

/** Returns all readable subdirs of given directory 
  @return List of readable subdirectories. The subdirectories does not include
  the prefix of the given directoy. Null on error */
function get_subdirs($path)
{
  list($subdirs, $files)=$this->read_dir($path);
  return $subdirs;
}

/** Returns all readable files of given directory. 
  @return List of readable filename. The filename does not include the prefix
  of the given directory. Returns null on error */
function get_files($path)
{
  list($subdirs, $files)=$this->read_dir($path);
  return $files;
}

/** Find files by regular expression. It searches the subdirectories first,
 * before inspecting the current directory.
  @param path Given directory to search
  @param regex Regular expression, which must match the file
  @param maxdepth Maximum depth of search. Default is 255. Use 0 if you want to
  search in the current directory only
  @param _depth Current Depth. Default is 0. This is used for internal
  recursion.
  @result List of matched files */
function find($path, $regex, $maxdepth=255, $_depth=0)
{
  if ($_depth>$maxdepth)
    return array();

  $matches=array();
  list($subpaths, $files)=$this->read_dir($path);
  if ($subpaths!=null && $_detph<$maxdepth+1)
  {
    foreach ($subpaths as $subpath)
    {
      $subpath=$this->merge_paths($path, $subpath);
      $matches=array_merge($matches, 
        $this->find($subpath, $regex, $maxdepth, $_depth+1));
    }
  }
  if ($files!=null)
  {
    foreach ($files as $file)
    {
      $file = $this->merge_paths($path, $file);
      if (preg_match($regex, $file))
        array_push($matches, $file);
    }
  }

  return $matches;
}

/** Search images recursively of a directory. 
 The function search only for jpg or JPG files. 
 @param path Search images in directories
 @param recursive If true, search recursive, otherwise just in the given
 directory. Default is true.
 @return Array of images files */
function find_images($path, $recursive=true)
{
  if ($recursive)
    $maxdepth=255;
  else
    $maxdetph=0;
  $files=$this->find($path, "/\.(jpe?g|avi|mov|mpe?g)$/i", $maxdepth);
  return $files;
}

/** Removes a directory
  @param dir Directory to be removed
  @param recursive True if directory is deleted recursivly. Default is true
  @return True on success, false otherwise */
function rm($path, $recursive=true)
{
  $fspath = $this->get_fspath($path);

  if (!@is_dir($fspath))
    return false;

  list($subpaths, $files)=$this->read_dir($path); 

  // Subdirs found
  if (count($subpaths)>0)
  {
    if (!$recursive)
      return false;

    // recursive remove of all subdirs
    foreach ($subpaths as $subpath)
    {
      $result=$this->rmdir($subpath, $recursive);
      if (!$result)
        return false;
      if (!@rmdir($this->merge_paths($fspath, $subpath)))
        return false;
    }
  }

  // Remove files
  foreach ($files as $file)
  {
    if (!@unlink($this->merge_paths($fspath, $file)))
      return false;
  }
}

}

?>
