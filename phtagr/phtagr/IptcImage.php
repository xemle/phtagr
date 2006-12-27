<?php

include_once("$phtagr_lib/Iptc.php");

/** @class IptcImage
  Class for adding and setting database relevant IPTC records. */
class IptcImage extends Iptc
{

/** Private EXIF array. It will be initialized on demand */
var $_exif;

/** Constructor 
  @param filename Filename of the image */
function IptcImage($filename='')
{
  $this->Iptc($filename);
  $this->_exif=null;
}

/** Get the caption 
  @return The caption or null, if no caption is available */
function get_caption()
{
  return $this->get_record('2:120');
}

/** Set a new caption 
  @param caption New caption
  @return True if iptc data changes */
function set_caption($caption)
{
  return $this->add_record('2:120', $caption);
}

/** @return Retuns an array of tags or null if no tag exists */
function get_tags()
{
  return $this->get_records('2:025');
}

/** @param tags New tag array which should be added 
  @return True if the iptc data changes */
function add_tags($tags)
{
  return $this->add_records('2:025', $tags);
}

/** Removes tags 
  @param tags Array of tags which should be removed 
  @return True if iptc chagnes */
function del_tags($tags)
{
  return $this->del_records('2:025', $tags);
}

/** @return Retuns an array of sets or null if no set exists */
function get_sets()
{
  return $this->get_records('2:020');
}

/** @param sets New set array which should be added 
  @return True if the iptc data changes */
function add_sets($sets)
{
  return $this->add_records('2:020', $sets);
}

/** Removes sets 
  @param sets Array of sets which should be removed 
  @return True if iptc chagnes */
function del_sets($sets)
{
  return $this->del_records('2:020', $sets);
}

/** Get the location
  @return array of location (city, sublocation, state, and country) */
function get_locations()
{
  return array(LOCATION_CITY => $this->get_record('2:090'),
    LOCATION_SUBLOCATION => $this->get_record('2:092'),
    LOCATION_STATE => $this->get_record('2:095'),
    LOCATION_COUNTRY => $this->get_record('2:101'));
}

/** Sets a single location
  @param value Location name
  @param type Location type */
function set_location($value, $type)
{
  switch ($type) {
  case LOCATION_CITY: $this->add_record('2:090', $value); break;
  case LOCATION_SUBLOCATION: $this->add_record('2:092', $value); break;
  case LOCATION_STATE: $this->add_record('2:095', $value); break;
  case LOCATION_COUNTRY: $this->add_record('2:101', $value); break;
  default: break;
  }
}

/** Set the location
  @param loc Array of location (city, sublocation, state, and country) */
function set_locations($loc)
{
  $this->set_record('2:090', $loc[LOCATION_CITY]);
  $this->set_record('2:092', $loc[LOCATION_SUBLOCATION]);
  $this->set_record('2:095', $loc[LOCATION_STATE]);
  $this->set_record('2:101', $loc[LOCATION_COUNTRY]);
}

/** Deletes a single location
  @param value Currently not used
  @param type Type of location */
function del_location($value, $type)
{
  switch ($type) {
  case LOCATION_CITY: $this->del_record('2:090'); break;
  case LOCATION_SUBLOCATION: $this->del_record('2:092'); break;
  case LOCATION_STATE: $this->del_record('2:095'); break;
  case LOCATION_COUNTRY: $this->del_record('2:101'); break;
  default: 
    $this->warning(sprintf(_("Unsupported type %d"), $type)); break;
  }
}


/** Reads the exif information 
  @return true on success */
function _read_exif()
{
  if ($this->_exif!=null)
    return true;

  if (!function_exists('exif_read_data'))
    return false;

  $this->_exif=@exif_read_data($this->get_filename(), 0, true);
  return true;
}

/** @return Returns the orientation from the exif header if available. Returns
 * 1 if no exif information are available */
function get_orientation()
{
  if (!$this->_read_exif())
    return 1;

  if (isset($this->_exif['IFD0']) && isset($this->_exif['IFD0']['Orientation']))
    return $this->_exif['IFD0']['Orientation'];

  return 1;
}

/** @return Returns the date from the exif. Returns null if no date is provided
 * by the exif block */
function get_date_exif()
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
function get_date()
{
  $date=$this->get_record('2:055');
  
  // No IPTC date found, try exif date
  if ($date==null)
    return $this->get_date_exif();

  // Convert IPTC date/time to sql timestamp "YYYY-MM-DD hh:mm:ss"
  // IPTC date formate is YYYYMMDD
  $date=substr($date, 0, 4)."-".substr($date, 4, 2)."-".substr($date, 6, 2);

  $time=$this->get_record('2:060');
  // IPTC time format is hhmmss[+offset]
  if ($time!=null)
  {
    $time=" ".substr($time, 0, 2).":".substr($time, 2, 2).":".substr($time, 4, 2);
  }
  $date=$date.$time;

  return $date;
}


/** Sets the date. 
  @param s The input can be in UNIX time or the format of 'YYYY-MM-DD
  hh:mm:ss'. Valid prefixes are also allowed like 'YYYY-MM' or 'YYYY-MM-DD
  hh:mm'. If the input starts with an minus sign '-', the date is removed 
  @return True on success, false otherwise */
function set_date($s)
{
  if ($s{0}=='-')
  {
    $this->del_record('2:055');
    $this->del_record('2:060');
    return true;
  }

  // If just a number, check for year or seconds 
  if (!is_numeric($s))
    return false;

  //$date=gmdate("Ymd", intval($s));
  //$time=gmdate("His", intval($s));
  $date=strftime("%G%m%d", intval($s));
  $time=strftime("%H%M%S", intval($s));
  $this->add_record('2:055', $date);
  $this->add_record('2:060', $time);
  
  return true;
}

}
?>
