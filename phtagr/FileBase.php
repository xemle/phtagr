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

/** @class FileBase
  Class for adding and setting database relevant IPTC records. */
class FileBase extends Base
{

var $_filename;

/** Constructor 
  @param filename Filename of the image */
function FileBase($filename)
{
  $this->_filename=$filename;
}

/** @return Returns the filename */
function get_filename()
{
  return $this->_filename;
}

/** Sets the filename 
  @param image Image Object
  @note This function should be inherited by sub classes */
function import($image)
{
  $image->set_bytes($this->get_filesize());
  $image->set_modified($this->get_filetime(), true);
}

/** Save the data to the file
  @param image Image object 
  @return True if data was written to the file
  @note This function should be overwritten by inherited classes */
function export($image)
{
  return false;
}

/** @return Returns filesize. If file does not exists, returns -1 */
function get_filesize()
{
  $filename=$this->get_filename();
  if (!file_exists($filename))
    return -1;

  return filesize($filename);
}

/** @return Modification time of the file. If the file does not exists, 
  returns -1 
  @note This function calls filemtime() which is cached. Call clearstatcache() 
  if you operate on the same file. */
function get_filetime()
{
  $filename=$this->get_filename();
  if (!file_exists($filename))
    return -1;

  return filemtime($filename);
}

/** @return Returnstrue if file and directory is writeable */
function is_writeable()
{
  $filename=$this->get_filename();
  if (is_writeable($filename) &&
    is_writeable(dirname($filename)))
    return true;
  return false;
}

/** @return Returns the preview creator. If no preview creator is available, 
  it returns null;
  @note This function should be overwritten by sub classes. */
function get_preview_handler($image)
{
  return null;
}

}
?>
