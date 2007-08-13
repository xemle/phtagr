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

include_once("$phtagr_lib/FileBase.php");
include_once("$phtagr_lib/PreviewImage.php");
include_once("$phtagr_lib/Iptc.php");

/** @class FileJpg
  Class for adding and setting database relevant IPTC records from a JPEG 
  image. */
class FileJpg extends FileBase
{

var $_iptc;
/** Private EXIF array. It will be initialized on demand */
var $_exif;

function FileJpg($filename)
{
  $this->FileBase($filename);
  $this->_iptc=null;
  $this->_exif=null;
  $this->_image_filename=$filename;
}

function set_image_filename($filename)
{
  if (!file_exists($filename))
    return;

  $this->_image_filename=$filename;
}

function get_image_filename()
{
  return $this->_image_filename;
}

function _import_from_imagefile($image)
{
  global $log;

  $filename=$this->get_image_filename();

  $size=getimagesize($filename);
  if ($size)
  {
    $image->set_width($size[0]);
    $image->set_height($size[1]);
  }
  else
  {
    $log->warn("Image size returned null by getimagesize($filename)");
  }

  $image->set_name(basename($filename));
  $this->_iptc=new Iptc($filename);
  $iptc=$this->_iptc;
  if ($iptc==null)
  {
    $log->debug("No IPTC found in '$filename'");
    return;
  }

  $image->set_caption($iptc->get_record('2:120'));
  $image->add_tags($iptc->get_records('2:025'));
  $image->add_categories($iptc->get_records('2:020'));
  $image->set_location($iptc->get_record('2:090'), LOCATION_CITY);
  $image->set_location($iptc->get_record('2:092'), LOCATION_SUBLOCATION);
  $image->set_location($iptc->get_record('2:095'), LOCATION_STATE);
  $image->set_location($iptc->get_record('2:101'), LOCATION_COUNTRY);

  $date=$this->_get_date_iptc();
  if ($date==null)
    $date=$this->_get_date_exif();
  $image->set_date($date);

  $image->set_orientation($this->_get_orientation_exif());
}

function _export_to_imagefile($image)
{
  global $log;

  $filename=$this->get_image_filename();
  if (!is_writeable($filename) || !is_writeable(dirname($filename)))
  {
    $log->warn("Could not export '$filename'. File or directory is not writeable");
    return false;
  }

  if (!$this->_iptc)
    $this->_iptc=new Iptc($filename);
  $iptc=$this->_iptc;

  $iptc->reset_iptc();

  $iptc->add_record('2:120', $image->get_caption());
  $iptc->add_records('2:025', $image->get_tags());
  $iptc->add_records('2:020', $image->get_categories());
  $iptc->add_record('2:090', $image->get_location(LOCATION_CITY));
  $iptc->add_record('2:092', $image->get_location(LOCATION_SUBLOCATION));
  $iptc->add_record('2:095', $image->get_location(LOCATION_STATE));
  $iptc->add_record('2:101', $image->get_location(LOCATION_COUNTRY));
  
  $date=$image->get_date();
  if ($date==null)
  {
    $iptc->del_record('2:055');
    $iptc->del_record('2:060');
  }
  else
  {
    $this->_set_date_iptc($date);
  }

  $iptc->save_to_file();
  return true;
}

/** Reads the exif information 
  @return true on success */
function _read_exif()
{
  if ($this->_exif!=null)
    return true;

  if (!function_exists('exif_read_data'))
    return false;

  $this->_exif=@exif_read_data($this->get_image_filename(), 0, true);
  return true;
}

/** @return Returns the orientation from the exif header if available. Returns
 * 1 if no exif information are available */
function _get_orientation_exif()
{
  if (!$this->_read_exif())
    return 1;

  if (isset($this->_exif['IFD0']) && isset($this->_exif['IFD0']['Orientation']))
    return $this->_exif['IFD0']['Orientation'];

  return 1;
}

/** @return Returns the date from the exif. Returns null if no date is provided
 * by the exif block */
function _get_date_exif()
{
  if (!$this->_read_exif())
    return null;

  if (isset($this->_exif['EXIF']) && isset($this->_exif['EXIF']['DateTimeOriginal']))
    return $this->_exif['EXIF']['DateTimeOriginal'];
  
  return null;
}

/** @return Return the date in mySQL syntax "YYYY-MM-DD hh:mm:ss". Returns null
 * if no date could be found. 
  @todo Handle time offsets*/
function _get_date_iptc()
{
  $iptc=$this->_iptc;
  if (!$iptc)
    return null;

  $date=$iptc->get_record('2:055');
  
  // No IPTC date found, try exif date
  if ($date==null)
    return null;

  // Convert IPTC date/time to sql timestamp "YYYY-MM-DD hh:mm:ss"
  // IPTC date formate is YYYYMMDD
  $date=substr($date, 0, 4)."-".substr($date, 4, 2)."-".substr($date, 6, 2);

  $time=$iptc->get_record('2:060');
  // IPTC time format is hhmmss[+offset]
  if ($time!=null)
  {
    $time=" ".substr($time, 0, 2).":".substr($time, 2, 2).":".substr($time, 4, 2);
  }
  else
  {
    $time=" 00:00:00";
  }

  $date=$date.$time;
  
  return $date;
}

function _set_date_iptc($date)
{
  if (!is_numeric($date))
    return;

  if (!$this->iptc)
    return;

  // If the date is the same as exif time exit
  $date_exif=$this->_get_date_exif();
  if ($date_exif!=null)
  {
    $date_ref=strftime("%Y:%m:%d %H:%M:%S", intval($s));
    if ($date_exif==$date_ref)
      return true;
  }

  $date=strftime("%Y%m%d", intval($s));
  $time=strftime("%H%M%S", intval($s));

  $iptc=$this->_iptc;
  $iptc->add_record('2:055', $date);
  $iptc->add_record('2:060', $time);
}

function import($image)
{
  global $db;

  parent::import($image);

  $this->_import_from_imagefile($image);
}

function export($image)
{
  return $this->_export_to_imagefile($image);
}

function get_preview_handler($image)
{
  $preview=new PreviewImage($image);
  return $preview;
}


}
?>
