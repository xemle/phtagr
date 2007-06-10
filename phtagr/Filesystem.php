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

  $this->_init_roots();
}

/** Read the drives of windows
  @return Array of valid windows drives */
function _get_windows_drives()
{
  $drives=array();

  for ($i=ord('C'); $i<ord('Z'); $i++)
  {
    if (is_dir(chr($i).':'))
      array_push($drives, chr($i).':');
  }
  return $drives;
}

/** Removes a directory separator at the end */
function _remove_dir_tail($s)
{
  $len=strlen($s);
  if ($s{$len-1}==DIRECTORY_SEPARATOR)
    return substr($s, 0, $len-1);
  return $s;
}

/** Initiate the root aliases. On Unix like systems it adds the system rooot.
 * On Windows system, it adds the drives as aliases. */
function _init_roots()
{
  $this->reset_roots();
  if ($this->_is_windows)
  {
    $drives=$this->_get_windows_drives();
    foreach ($drives as $drive)
      $this->add_root($drive, $drive);
  } else {
    $this->add_root('/', _("Filesystem"));
  }
}

/** Initialize the roots from user's configuration */
function _init_roots_by_conf()
{
  global $conf;
  $this->reset_roots();
  $roots=$conf->get('path.fsroot[]');
  if (!$roots)
  {
    foreach ($roots as $root)
      $this->add_root($root);
  }
}

/** Add slash at the end of the path if it does not exists
  @param path Current path
  @return path with tailing slash */
function _slashify($path)
{
  if ($path[strlen($path)-1]!='/')
    return $path.'/';
  return $path;
}

/** Removes slashes at the end 
  @param path Path
  @return Path without tailing slash */
function _unslashify($path)
{
  $len=strlen($path);
  if ($path[$len-1]=='/')
    return substr($path, 0, $len-1);
  return $path;
}

/** Merges to paths together
  @param parent Parent part of path
  @param child Child part of path
  @return Merged path */
function _merge_paths($parent, $child)
{
  if ($child[0]=='/')
    return $this->_unslashify($parent).$child;
  else
    return $this->_slashify($parent).$child;
}

/** @return True if you running this under Windows */
function is_windows()
{
  return $this->_is_windows;
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
  $root=$this->_slashify($root);

  if ($alias=='')
    $alias=basename($root);

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

/** Resets all root aliases */
function reset_roots()
{
  $this->_roots=array();
}

function get_num_roots()
{
  return count($this->_roots);
}

/** Return all root aliases in a array
  @return Array of root aliases */
function get_roots()
{
  $roots=array();
  foreach ($this->_roots as $alias => $dir)
    array_push($roots, $alias);
  return $roots;
}

/** Splits the filename to root alias and the filename tail of a given name.
 * The name "image/user/bob" is splitted to alias "image" and filename
 * "user/bob". If only one root is used, the alias can be ommited and the file
 * must start with the directory separator. If "image" is the only root alias,
 * the return of "/user/bob" is also "image" and "user/bob".
  @param path Current path
  @return Array of root alias and filename. Otherwise return array of (false,
  false) */
function _split_alias($path) 
{
  $pos=strpos($path, '/');

  if ($pos>0)
  {
    $alias=substr($path, 0, $pos);
    $tail=substr($path, $pos+1);
  // Note: $pos===0 checks for zero. Otherwise return value false is converted
  // to zero as well.
  } else if ($pos===0 && count($this->_roots)==1) {
    list($alias)=array_keys($this->_roots);
    $tail=substr($path, 1);
  } else {
    $tail='';
    $alias=$path;
  }

  if (isset($this->_roots[$alias]))
    return array($alias, $tail);

  return array(false, false);
}

/** 
  @param path Current path
  @return Returns the real name of a file or directory */
function get_realname($path)
{
  list($alias, $tail)=$this->_split_alias($path);
  if (!$alias)
    return false;

  return $this->_roots[$alias].$tail;
}

function file_exists($file)
{
  if (file_exists($this->get_realname($file)))
    return true;

  return false;
}

/** is_dir function with root prefix
 @param dir Directory without the root directory
 @return true if given dir is a directory. Otherwise false */
function is_dir($dir)
{
  if (is_dir($this->get_realname($dir)))
    return true;

  return false;
}

/** @return Returns the basename of a filename. If is a directory 
  returns the upper directory.
  @note This function will not check if the given file exists */
function basename($file)
{
  // Split alias and tail and operate only on the tail.
  list($alias, $tail)=$this->_split_alias($file);
  $pos=strrpos($tail, '/');
  
  if ($pos===false)
    return $file;
  
  else if ($pos==0)
    return $alias;
  else
    return $alias.'/'.substr($tail, 0, $pos);
}

/** Change the current directory.
  @return True on success, false otherwise
 */
function chdir($dir)
{
  if (!$this->is_dir($dir))
    return false;

  chdir($this->get_realname($dir));
  return true;
}

/** opendir function with root prefix
  @param dir Current directory
  @return False on failure. Handle of the opened directory otherwise */
function opendir($dir)
{
  if (!$this->is_dir($dir))
    return false;
  
  return opendir($this->get_realname($dir));
}

/** is_readable function with root prefix 
 @return true if directory is readable. false otherwise */
function is_readable($file)
{
  $realname=$this->return_realname($file);
  if (!$realname)
    return false;

  return is_readable($realname);
}

/** Returns a list of subdirectories and files of the given directory
  @param dir Directory which should be read
  @return Two dimensioned array with array of subdirectorys and an array of
  files */
function read_dir($dir)
{
  if (!$this->chdir($dir))
    return array(null, null);

  if (!$handle = $this->opendir($dir)) 
    return array(null, null);

  $subdirs=array();
  $files=array();
  while (false != ($file = readdir($handle))) {
    if (!is_readable($file) || $file=='.' || $file=='..') continue;

    if ($this->is_dir($dir.'/'.$file)) {
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
function get_subdirs($dir)
{
  list($subdirs, $files)=$this->read_dir($dir);
  return $subdirs;
}

/** Returns all readable files of given directory. 
  @return List of readable filename. The filename does not include the prefix
  of the given directory. Returns null on error */
function get_files($dir)
{
  list($subdirs, $files)=$this->read_dir($dir);
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

  //$path=$this->_remove_dir_tail($path);
  $matches=array();
  list($subpaths, $files)=$this->read_dir($path);
  if ($subpaths!=null && $_detph>$maxdepth+1)
  {
    foreach ($subpaths as $subpath)
    {
      if ($path!='/')
        $subpath=$this->_merge_paths($path, $subpath);
      else 
        $subpath='/'.$subpath;
      $matches=array_merge($matches, 
        $this->find($subpath, $regex, $maxdepth, $_depth+1));
    }
  }
  if ($files!=null)
  {
    foreach ($files as $file)
    {
      if ($path!='/')
        $file=$path.'/'.$file;
      else 
        $file='/'.$file;
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

/** Creates a directory.
  @param path Directory to create
  @param withparent Create also parent directories if they not exist. 
  Default is false
  @return True on success, false otherwise */
function mkdir($path, $withparent=false)
{
  if ($this->file_exists($path))
  {
    if (!$this->is_dir($path))
      return false;
    else
      return true;
  }
  
  $fspath=$this->get_realname($path);
  return @mkdir($fspath, 0755, $withparent);
}

/** Deletes a file
  @param file Filename
  @return True on success, false otherwise */
function unlink($file)
{
  if ($this->file_exists($file))
    return @unlink($this->get_realname($file));
  return false;
}

/** Removes a directory
  @param dir Directory to be removed
  @param recursive True if directory is deleted recursivly. Default is true
  @return True on success, false otherwise */
function rmdir($path, $recursive=true)
{
  if (!$this->is_dir($path))
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
      if (!@rmdir($this->get_realname($subpath)))
        return false;
    }
  }

  // Remove files
  foreach ($files as $file)
  {
    if (!@unlink($this->get_realname($file)))
      return false;
  }
}

}

?>
