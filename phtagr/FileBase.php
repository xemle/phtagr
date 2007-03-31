<?php

include_once("$phtagr_lib/Iptc.php");

/** @class IptcImage
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
  @note This function should be overwritten by inherited classes */
function export($image)
{
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
function get_preview_handler()
{
  return null;
}

}
?>
