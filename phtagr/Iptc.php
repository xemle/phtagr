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
/*
 Thanks to Christian Tratz, who has written a nice IPTC howto on
 http://www.codeproject.com/bitmap/iptc.asp
*/
include_once("$phtagr_lib/Base.php");
include_once dirname(__FILE__).DIRECTORY_SEPARATOR."ExifConstants.php";

// Size of different headers 
define("HDR_SIZE_JPG", 0x04);
define("HDR_SIZE_PS3", 0x0e);
define("HDR_SIZE_8BIM", 0x0c);
define("HDR_SIZE_IPTC", 0x05);

define("HDR_PS3", "Photoshop 3.0\0");
define("HDR_8BIM_IPTC", chr(4).chr(4));

/** @class Iptc
  Reads and write IPTC tags from a given JPEG file. */
class Iptc extends Base 
{

/** Error number. Fatal errors are less than zero. If this number is greater
 * than zero, the error could be accepted */
var $_errno;
/** General error string. This field must be resettet after each operation */
var $_errmsg;
/** array of jpg segents. This array contains the JPEG segments */
var $_jpg;
/** Struct of JPEG comment as XML DOM object */
var $_xml;
/** IPTC data, array of array. The IPTC tags are stored in as "r:ttt", where
 * 'r' is the record number and 'ttt' is the type of the IPTC tag
 */
var $iptc;
/** This flag is true if the IPTC data changes. */
var $_changed_iptc; 
/** This flag is true if the JPEG comment segment changes */
var $_changed_com;

var $_has_iptc_bug;

/** True on little endian encoding of exif. False on big endian */
var $_lendian;

function Iptc($filename='')
{
  $this->_errno=0;
  $this->_errmsg='';
  $this->_jpg=null;
  $this->_xml=null;
  $this->_iptc=null;
  $this->_changed_com=false;
  $this->_changed_iptc=false;
  $this->_has_iptc_bug=false;
  $this->_lendian=false;
  if ($filename!='')
    $this->load_from_file($filename);
}

/** Return true if IPTC records changed */
function is_changed()
{
  return $this->_changed_iptc || $this->_changed_com;
}

/** Resets the error number and message for internal purpose */
function _reset_error()
{
  $this->_errno=0;
  $this->_errmsg='';
}

/** Returns the current error no
  @return A fatal error has an error number less than zero. Error which are
  greater than zero are informational. 
  @see get_errmsg() */
function get_errno()
{
  return $this->_errno;
}

/** Returns the current error message */
function get_errmsg()
{
  return $this->_errmsg;
}

/** Reads the JPEG segments, the photoshop segments, and finally the IPTC
 segments. 
 @return true on success. False otherwise. */
function load_from_file($filename)
{
  $this->_reset_error();
  if (!is_readable($filename))
  {
    $this->_errmsg="$filename could not be read";
    $this->_errno=-1;
    return -1;
  }
  $size=filesize($filename);
  if ($size<30)
  {
    $this->_errmsg="Filesize of $size is to small";
    $this->_errno=-1;
    return -1;
  }
  
  $this->_jpg=array();
  $jpg=&$this->_jpg;
  $jpg['filename']=$filename;
  $jpg['size']=$size;
  
  $fp=fopen($filename, "rb");
  if ($fp===false) 
  {
    $this->_errmsg="Could not open file for reading";
    $this->_errno=-1;
    $this->_jpg=null;
    return -1;
  }
  $jpg['_fp']=$fp;

  $data=fread($fp, 2);
  if (ord($data[0])!=0xff || ord($data[1])!=0xd8)
  {
    $this->_errmsg="JPEG header mismatch";
    $this->_errno=-1;
    $this->_jpg=null;
    fclose($fp);
    return -1;
  }
  
  if (!$this->_read_seg_jpg())
  {
    $this->_jpg=null;
    fclose($fp);
    return -1;
  }

  if (isset($this->_jpg['_app1']))
  {
    //$this->_read_seg_app1();
  }

  if (isset($this->_jpg['_app13']))
  {
    $this->_read_seg_ps3();
    if (isset($this->_jpg['_iptc']))
      $this->_read_seg_iptc();
  } 

  if (isset($this->_jpg['_com']))
    $this->_read_seg_com();

  fclose($fp);
  
  return $this->_errno;
}

/** Save changes to the file
  @param do_rename If true, the temporary file overwrites the origin
  file. Otherwise, the temoprary file remains. This option is only used for
  debugging. Default is true. */
function save_to_file($do_rename=true)
{
  if ($this->_changed_iptc==false && $this->_changed_com==false)
    return;

  //$this->warning("Saving file");
  if (!isset($this->_jpg))
    return false;
  $jpg=&$this->_jpg;

  // Open image for reading
  $fin=fopen($jpg['filename'], 'rb');
  if (!$fin)
  {
    $this->_errno=-1;
    $this->_errmsg="Could not read the file ".$jpg['filename'];
    return false;
  }

  // Check writeable directory
  if (!is_writeable(dirname($jpg['filename'])))
  {
    $this->_errno=-1;
    $this->_errmsg=sprintf(_("Could not write to file directory '%s'"), dirname($jpg['filename']));
    return false;
  }

  // Create unique temporary filename
  $tmp=$jpg['filename'].'.tmp';
  $i=1;
  while (file_exists($tmp)) {
    $tmp=$jpg['filename'].".$i.tmp";
    $i++;
  }
  $fout=@fopen($tmp, 'wb');
  if ($fout==false) 
  {
    $this->_errno=-1;
    $this->_errmsg="Could not write to file $tmp";
    return false;
  }

  // Copy and modifiy file 
  $buf=fread($fin, 2);
  fwrite($fout, $buf);
  $offset=2;
  foreach ($jpg['_segs'] as $seg) {
    switch ($seg['type']) {
    case 'ffed':
      // IPTC segment
      if (!$this->_changed_iptc) {
        $offset+=$this->_copy_jpeg_seg($fin, $fout, &$seg);
        break;
      }
      $this->_replace_seg_ps3($fin, $fout);
      $this->_changed_iptc=false;
      break;
    case 'fffe':
      // Comment segment
      if (!$this->_changed_com) {
        $offset+=$this->_copy_jpeg_seg($fin, $fout, &$seg);
        break;
      }
      $this->_replace_seg_com($fout);
      $this->_changed_com=false;
      break;
    case 'ffda':
      if ($this->_changed_iptc)
        $this->_replace_seg_ps3($fin, $fout);
      if ($this->_changed_com)
        $this->_replace_seg_com($fout);

      // write rest of file in blocks to save memory and to avoid memory
      // exhausting
      fseek($fin, $seg['pos'], SEEK_SET);
      $size=$jpg['size']-$seg['pos'];
      $done=0;
      $blocksize=1024;
      while ($done<$size)
      {
        if ($done+$blocksize>$size)
          $blocksize=$size-$done;

        $buf=fread($fin, $blocksize);
        fwrite($fout, $buf);
        $done+=$blocksize;
      }
      unset($buf);
      break;
    default:
      // copy unchanged segment
      $offset+=$this->_copy_jpeg_seg($fin, $fout, &$seg);
    }
  }

  fclose($fin);
  fclose($fout);

  if ($this->_errno==0 && $do_rename)
    rename($tmp, $jpg['filename']);
}

/** @return Returns the filename */
function get_filename()
{
  if (!isset($this->_jpg))
    return '';

  return $this->_jpg['filename'];
}

/** @return Returns an array of JPEG segments */
function get_jpeg_segments()
{
  if (!empty($this->_jpg['_segs']))
    return $this->_jpg['_segs'];
  return null;
}

/** @return Returns an array of photoshop segments */
function get_ps3_segments()
{
  if (!empty($this->_jpg['_app13']))
    return $this->_jpg['_app13']['_segs'];
  return null;
}

function get_iptc_segment()
{
  if (!empty($this->_jpg['_iptc']))
    return $this->_jpg['_iptc'];
  return null;
}

/** @return True if iptc contains the iptc bug from an early imlementation.
 * This bug was fixted in svn 219 */
function has_iptc_bug()
{
  return $this->_has_iptc_bug;
}

/** Read all segments of an JPEG image until the data segment. Each JPEG
 * segment starts with a marker of an 0xff byte followed by the segemnt type
 * (also one byte). The segment type 0xda indicates the data segment, the real
 * image data. If this segment is found the algorithm stops. The segment type
 * 0xed indicates the photoshop meta data segment.  The segment size of 2 bytes
 * is folled. The size does not include the segment marker and the segment
 * byte.

  @return Return true if all segments have a correct size. The function will
  return false on a segment start mismatch. */
function _read_seg_jpg()
{
  if (empty($this->_jpg) && !isset($this->_jpg['_fp']))
    return false;

  $jpg=&$this->_jpg;
  $fp=$jpg['_fp'];
  $jpg['_segs']=array();
  
  $i=0;
  while (true)
  {
    $pos=ftell($fp);
    $hdr=fread($fp, HDR_SIZE_JPG);
    // get segment marker
    $marker=substr($hdr, 0, 2);
    if (ord($marker[0])!=0xff)
    {
      $this->_errno=-1;
      $this->_errmsg="Invalid jpeg segment start: ".$this->_buf2hex($marker)." at $pos";
      return false;
    }
    // size is excl. marker 
    // size of jpes section starting from pos: size+2
    $size=$this->_ato16u(substr($hdr, 2, 2));
    if ($pos+$size+2>$jpg['size'] || $size<2)
    {
      $this->_errno=-1;
      $this->_errmsg="Invalid segment size of $size at $pos (0x".dechex($pos).")";
      return false;
    }
    $seg=array();
    $seg['pos']=$pos;
    $seg['size']=$size;
    $seg['type']=$this->_buf2hex($marker);
    array_push($jpg['_segs'], $seg);

    // save exif segment
    if ($marker[1]==chr(0xe1)) {
      $seg['index']=$i;
      $jpg['_app1']=$seg;
    }

    // save photoshop segment
    if ($marker[1]==chr(0xed)) {
      $seg['index']=$i;
      $jpg['_app13']=$seg;
    }

    // save com segment
    if ($marker[1]==chr(0xfe)) {
      $seg['index']=$i;
      $jpg['_com']=$seg;
    }
    
    // end on SOS (start of scan) segment (0xff 0xda)
    if (ord($marker[1])==0xda)
      break;
    // jump to the next segment
    fseek($fp, $size-2, SEEK_CUR);
    $i++;
  }
  return true;
}

function _read_seg_app1()
{
  global $log; 
  if ($this->_jpg==null || !isset($this->_jpg['_app1']))
  {
    $this->_errmsg="No app1 header found";
    return -1;
  }

  $jpg=&$this->_jpg;
  $app1=$this->_jpg['_app1'];
  $fp=$this->_jpg['_fp'];
  fseek($fp, $app1['pos']+4, SEEK_SET);
  $log->debug("Reading APP1 at ".$app1['pos']);

  $exif_hdr=fread($fp, 6);
  if (substr($exif_hdr, 0, 4)!='Exif')
  {
    $this->_errno=-1;
    $this->_errmsg="Wrong Exif marker: ".substr($buf, 0, 4);
    return;
  }

  // read whole exif information into the buffer
  // 8 = 2 byte for jpeg semenent size, 6 byte exif header
  $buf=fread($fp, $app1['size']-8); 

  // Read TIFF header
  $endian=substr($buf, 0, 2);
  if ($endian=='II')
  {
    $app1['lendian']=true;
    $this->_lendian=true;
  }
  elseif ($endian=='MM')
  {
    $app1['lendian']=false;
    $this->_lendian=false;
  }
  else
  { 
    $this->_errno=-1;
    $this->_errmsg="Wrong Exif encoding: ".$endian;
    return;
  }

  $i42=$this->_get16u(substr($buf, 2, 2));
  if ($i42!=0x2a)
  {
    $this->_errno=-1;
    $this->_errmsg="Wrong 42 header: $i42 (should be 42)";
    return;
  }

  $offset=$this->_get32u(substr($buf, 4, 4));
  if ($offset!=0x08)  
  {
    $this->_errno=-1;
    $this->_errmsg="Unsupported 0th IFD offset of $offset";
    return;
  }

  $pos=8;
  $ifd=$this->_read_seg_app1_ifd($buf, $pos);
  $ifd_nice=$this->_ifd2array($ifd);
  if (isset($ifd['exif']))
  {
    $ifd_nice['Exif']=$this->_ifd2array($ifd['exif']);
    if (isset($ifd['exif']['interop']))
      $ifd_nice['Exif']['Interop']=$this->_ifd2array($ifd['exif']['interop']);
  }
  $log->trace("ifd_nice=".print_r($ifd_nice, true));
  //$log->trace("ifd=".print_r($ifd, true));

  if ($ifd['next_ifd']>0)
  {
    $ifd1=$this->_read_seg_app1_ifd($buf, $ifd['next_ifd']);
    if (!empty($ifd1))
    {
      $ifd1_nice=$this->_ifd2array($ifd1);
      $log->trace("ifd1_nice=".print_r($ifd1_nice, true));
    }
  }
  
}

/** Reads an APP1 IFD segment. 
  @param buf Buffer of EXIF
  @param pos Current position of the IFD.
  @param tableName Table of attribut name
  @return Array of the IFD. False on error. Null if IFD is not available */
function _read_seg_app1_ifd($buf, $pos, $tableName=false)
{
  global $log;
  $ifd=array();
  $len=strlen($buf);

  if ($pos==0)
  {
    // IFD pointer is 0, no IFD follows
    return null;
  }

  if ($pos<0 || $pos>=$len)
  {
    $this->_errno=-1;
    $this->_errmsg="Invalid position of interoperability entries (max $len): ".$pos;
    return false;
  }

  /*
  - 2 bytes=number: Number of interoperability attributes
  - 12 bytes*number: Per each interoperability attribute 
  - 4 bytes: Pointer to next IFD segment
  - Data block of the IFD
  
  On interoperability attribute has:
    2 bytes: Interoperability ID of attribute
    2 bytes: Data type of attribute
    4 bytes: Counts of datatypes (array size)
    4 bytes: Tag value (or offset if value requires more than 4 bytes)

  Data block: If an interoperability attribute data requires more than 4
    bytes, use the data block after all interoperability attributes. The 4
    bytes tag value is an (absolute) offset to the data position
  */

  // get number of interoperability tags
  $number=$this->_get16u(substr($buf, $pos, 2));
  if ($pos+$number*12>=$len)
  {
    $this->_errno=-1;
    $this->_errmsg="Invalid number of interoperability tags: ".$number." at ".$pos;
    return false;
  }
  $pos+=2;

  $log->trace("IFD has $number interoperability tags");
  $i=0;
  while ($i++<$number)
  {
    $attr=array();
    $attr['pos']=$pos; // position offset at TIFF header
    $attr['index']=$i;
    $attr['id']=$this->_get16u(substr($buf, $pos, 2), true);
    $attr['name']=$this->_getExifAttributeName($attr['id'], $tableName);
    $attr['type']=$this->_get16u(substr($buf, $pos+2, 2));
    $attr['count']=$this->_get32u(substr($buf, $pos+4, 4));
    $this->_getExifAttributeValue($buf, &$attr);
    //$log->trace("Attr ".$attr['name']." (".$attr['id'].": ".print_r($attr, true));
    // Check for error
    if ($this->_errno<0)
    {
      $log->err($this->_errmsg);
      return null;
    }

    // EXIF IFD
    if ($attr['id']==34665)
    {
      // JPEG segment header has 4 bytes, app1 header has 6 bytes = 10 bytes 
      $exif_pos=$app1['pos']+10+$attr['value'];
      $log->trace("Exif Header at ".$attr['value']." ($exif_pos 0x".dechex($exif_pos).")");
      $exif=$this->_read_seg_app1_ifd($buf, $attr['value'], 'Exif');
      if ($exif==null) 
        return null;
      $ifd['exif']=$exif;
    }
    else if ($attr['id']==40965)
    {
      // JPEG segment header has 4 bytes, app1 header has 6 bytes = 10 bytes 
      $interop_pos=$app1['pos']+10+$attr['value'];
      $log->trace("Interoperability IFD Header at ".$attr['value']." ($interop_pos 0x".dechex($interop_pos).")");
      $interop=$this->_read_seg_app1_ifd($buf, $attr['value'], 'Interoperability');
      if ($interop!=null) 
        $ifd['interop']=$interop;
    } 
    else if ($attr['name']=='MakerNote')
    {
      $log->trace("Maker note at ".$attr['offset']." (0x".dechex($attr['offset']).")");
      $maker=$this->_read_seg_app1_ifd($buf, $attr['offset']);
      if ($maker!=null) 
      {
        $ifd['MakerNote']=$maker;
        $log->trace("makernote: ".print_r($maker, true));
      }
    } 
       
    $ifd[$attr['id']]=$attr;
    $pos+=12;
  }

  // Set next ifd offset
  $ifd['next_ifd']=$this->_get32u(substr($buf, $pos, 4));
  $pos+=4;
  $ifd['size']=$pos-$ifd['pos'];

  return $ifd;
}

/** Returns the string prepesention of the EXIF attribute
  @param id Id of the EXIF attribute
  @return String represention if available, otherwise the id */
function _getExifAttributeName($id, $tableName=false)
{
  global $ExifAttributeTable, $InteropAttributeTable;
  switch ($tableName)
  {
    case 'Interoperability':
      $table=$InteropAttributeTable;
      break;
    default: 
      $table=$ExifAttributeTable;
  }
  if (isset($table[$id]['name']))
    return $table[$id]['name'];
  return $id;
}

/** Returns the id of EXIF attribute name
  @param name Name of the attribute. If name is numeric, this number is
  returned if it exists as ID in the attribute table
  @return Id of the attribute or -1 if no attribute was found */
function _getExifAttributeId($name)
{
  global $ExifAttributeTable;
  if (is_numeric($name))
  {
    if (isset($ExifAttributeTable[$name]))
      return $name;
    return -1;
  }

  $name=strtolower($name);
  foreach($ExifAttributeTable as $id => $attr)
  {
    if (isset($attr['name']) && strtolower($attr['name'])==$name)
      return $id;
  }
  return -1;
}

/** Returns the attribute array of a given id or field name 
  @param idOrName Attribute id or attribute name
  @return Attribute array or false on error */
function _getExifAttribute($idOrName)
{
  global $ExifAttributeTable;
  $id=$this->_getExifAttributeId($idOrName);
  if ($id<0)
    return false;
  return $ExifAttributeTable['id'];  
}

/** Checks the EXIF attribute type and returns true if the given attribute type is correct 
  @param idOrName Attribute id or attribute name
  @param type Attribute type
  @return True if the given attribute has the correct type, false otherwise */
function _checkExifAttributeType($idOrName, $type)
{
  $attr=$this->_getExifAttribute($idOrName);
  if ($attr===false)
    return false;

  if ($type==$attr['type'] || 
    (isset($attr['type2']) && $type==$attr['type2']))
    return true;

  return false;
}

/** Returns the attribute type of a given EXIF attribute 
  @param idOrName Attribute id or attribute name
  @param alternative if true returns the alternative if available, else the
  primary
  @return Primary attribute type. false on error*/
function _getExifAttributeType($idOrName, $alternative=false)
{
  $attr=$this->_getExifAttribute($idOrName);
  if ($attr===false || !isset($attr['type']))
    return false;
  
  if ($alternative && isset($attr['type2']))
    return $attr['type2'];

  return $attr['type'];
}

/** Converts the ifd internal format to a nicer array with field names as array
 * keys and field values as array value 
  @param ifd IFD array (internal structure)
  @return Pretty print IFD array */
function _ifd2array($ifd)
{
  if (empty($ifd))
    return array();

  $result=array();
  foreach($ifd as $id => $attr)
  { 
    if (is_numeric($id) &&
      isset($attr['name']) && !is_numeric($attr['name']) && 
      isset($attr['value']))
      $result[$attr['name']]=$attr['value'];
  }
  return $result;
}

function _get16u($val)
{
  if (strlen($val)<2)
    return -1;
  if ($this->_lendian)
    return (ord($val[1])<<8 | ord($val[0]));
  else
    return (ord($val[0])<<8 | ord($val[1]));
}

function _get32u($val)
{
  if (strlen($val)<4)
    return -1;

  if ($this->_lendian)
    return (ord($val[3])<<24 | ord($val[2])<<16 | ord($val[1])<<8 | ord($val[0]));
  else
    return (ord($val[0])<<24 | ord($val[1])<<16 | ord($val[2])<<8 | ord($val[3]));
}

/** Returns the 32 bit signed in 2's compliments notation */
function _get32s($val)
{
  if (strlen($val)<4)
    return -1;

  if ($this->_lendian)
    $result=(ord($val[3])<<24 | ord($val[2])<<16 | ord($val[1])<<8 | ord($val[0]));
  else
    $result=(ord($val[0])<<24 | ord($val[1])<<16 | ord($val[2])<<8 | ord($val[3]));
  // negative
  if ($result & 0x80000000)
    $result=0x80000000-$result;
  return $result;
}


function _getExifAttributeSize($type, $count)
{
  switch ($type)
  {
    case EXIF_BYTE: 
    case EXIF_ASCII: 
    case EXIF_UNDEFINED:
      return $count;
    case EXIF_SHORT:
      return $count<<1;
    case EXIF_LONG:
    case EXIF_SLONG:
      return $count<<2;
    case EXIF_RATIONAL:
    case EXIF_SRATIONAL:
      return $count<<3;
    default:
      $this->_errno=-1;
      $this->_errmsg="Undefinded Exif Tag Type: $type";
      return -1;
  }
}

function _getExifAttributeValue($buf, &$attr)
{
  global $log;
  $type=$attr['type'];
  $count=$attr['count'];
  $pos=$attr['pos'];
  $size=$this->_getExifAttributeSize($type, $count);
  $attr['size']=$size;
  if ($size<=4)
  {
    // offset as value
    switch($type)
    {
      case EXIF_BYTE:
        // An 8-bit unsigned integer.
        $attr['value']=ord($buf[$pos+8]);
        break; 
      case EXIF_ASCII:
        $attr['value']=substr($buf, $pos+8, $size);
        break;
      case EXIF_UNDEFINED:
        $attr['value']=$this->_buf2hex(substr($buf, $pos+8, $size));
        break;
      case EXIF_SHORT:
        $attr['value']=$this->_get16u(substr($buf, $pos+8, 2));
        break; 
      case EXIF_LONG:
        $attr['value']=$this->_get32u(substr($buf, $pos+8, 4));
        break; 
      case EXIF_SLONG:
        $attr['value']=$this->_get32s(substr($buf, $pos+8, 4));
        break;
      default:
        $attr['value']="err";
        $this->_errno=-1;
        $this->_errmsg="Undefinded Exif tag type $type at offset $pos with size $size";
        break;
    }
  }
  else
  {
    // value buf at offset
    $offset=$this->_get32u(substr($buf, $pos+8, 4));
    $attr['offset']=$offset;
    switch ($type)
    {
      case EXIF_ASCII:
        // An 8-bit byte containing one 7-bit ASCII code. The final byte is
        // terminated with NULL
        $attr['value']=substr($buf, $offset, $size-1);
        break;
      case EXIF_UNDEFINED:
        $attr['value']=$this->_buf2hex(substr($buf, $offset, $size));
        break;
      case EXIF_SHORT:
        $attr['value']=$this->_get16u(substr($buf, $offset, 2));
        break; 
      case EXIF_LONG:
        $attr['value']=$this->_get32u(substr($buf, $offset, 4));
        break;
      case EXIF_SLONG:
        $attr['value']=$this->_get32s(substr($buf, $offset, 4));
        break;
      case EXIF_RATIONAL:
        // Two LONGs. The first LONG is the numerator and the second LONG
        // expresses the denominator.,
        $attr['num']=$this->_get32u(substr($buf, $offset, 4));
        $attr['den']=$this->_get32u(substr($buf, $offset+4, 4));
        if ($attr['den']>0)
          $attr['value']=$attr['num']."/".$attr['den'];
        else
          $attr['value']=0;
        break;
      case EXIF_SRATIONAL:
        // Two SLONGs. The first SLONG is the numerator and the second SLONG is
        // the denominator.
        $attr['num']=$this->_get32s(substr($buf, $offset, 4));
        $attr['den']=$this->_get32s(substr($buf, $offset+4, 4));
        if ($attr['den']>0)
          $attr['value']=$attr['num']."/".$attr['den'];
        else
          $attr['value']=0;
        break;
      default:
        $attr['value']="err";
        $this->_errno=-1;
        $this->_errmsg="Undefinded Exif Tag Type: $type";
        break;
    }
  }
}

/* Read the photoshop meta headers.
 * @return false on failure with an error string */
function _read_seg_ps3()
{
  global $log; 
  if ($this->_jpg==null || !isset($this->_jpg['_app13']))
  {
    $this->_errmsg="No PS3 header found";
    return -1;
  }

  $app13=&$this->_jpg['_app13'];
  $fp=$this->_jpg['_fp'];
  fseek($fp, $app13['pos']+4, SEEK_SET);
  
  $marker=fread($fp, 14);
  if ($marker!=HDR_PS3)
  {
    unset($this->_jpg['_app13']);
    $this->_errno=-1;
    $this->_errmsg="Wrong photoshop marker $marker at ".$app13['pos']." (0x".dechex($app13['pos']).")";
    return -1;
  }
  //size of segment starting from pos: size+2
  $app13['marker']=$marker;
  $app13['_segs']=array();

  $ps3_pos_end=$app13['pos']+2+$app13['size'];

  /* Read 8BIM segments
    Section header size: 12 bytes
    marker: 4 bytes='8BIM'
    type: 2 bytes
    name: PString (1 byte string length, string data), padded to even size. Null name is 0x0000
    size: 4 bytes, excl. marker, type, name, size
    data: length of size. Padded to even size
    
    padding: 1 byes=0x00 if odd size. Not included in size! 
    See Photoshop File Formats.pdf, Section 'Image resource blocks', page 8, Table 2-1
  */
  $i=0;
  while (true)
  {
    $seg=array();
    $seg['pos']=ftell($fp);
    $hdr=fread($fp, HDR_SIZE_8BIM);
    if (strlen($hdr)!=HDR_SIZE_8BIM)
    {
      $this->_errno=-1;
      $this->_errmsg="Could not read PS record header";
      return -1;
    }

    // padding fix for aligned data
    if (substr($hdr, 0, 4)!="8BIM")
    {
      // IPTC bug detection
      if (substr($hdr, 0, 5)=="\08BIM")
      {
        // positive padding, shift by 1 byte
        $this->_has_iptc_bug=true;
        $log->warn("Shift 8BIM header due IPCT bug!");
        $hdr=substr($hdr, 1).fread($fp, 1);
        $seg['pos']++;
      }
      elseif (substr($hdr, 0, 3)=="BIM")
      {
        // negative padding, rewind by 1 byte
        fseek($fp, $seg['pos']-1, SEEK_SET);
        $this->_has_iptc_bug=true;
        continue;
      }
      else
      {
        $this->_errmsg="Wrong PS3 marker at ".$seg['pos']." with ".substr($hdr, 1, 4);
        $this->_errno=-1;
        break;
      }
    }

    // size of section starting from pos: size+12
    $seg['type']=$this->_buf2hex(substr($hdr, 4, 2));

    // Check for path segment
    $type=$this->_ato16u(substr($hdr, 4, 2));

    // Path block: 8BIM + PATH_TYPE + LEN[1] + String[len] + [optional odd padding '0'] + Size[4] + data
    $name_len=ord($hdr[6])+1; // add byte of LEN
    $name_len+=($name_len&0x01); // padding to even size

    $hdr_len=10+$name_len; // 4 Bytes '8BIM', 2 Bytes Type, name size, 4 Bytes size
    if ($hdr_len>HDR_SIZE_8BIM)
      $hdr.=fread($fp, $hdr_len-HDR_SIZE_8BIM);
    else if ($len<HDR_SIZE_8BIM)
    {
      $hdr=substr($hdr,0,$hdr_len);
      fseek($fp, $seg['pos']+$hdr_len, SEEK_SET);
    }
    // Maximum of JPEG size are 2 bytes at all! 
    $seg['size']=$this->_ato32u(substr($hdr, $hdr_len-4, 4));
    
    $name_len=ord($hdr[6]);
    if ($name_len>0)
      $seg['name']=substr($hdr, 7, $name_len);
    //printf("New Header Length: %d\n", $hdr_len);
    //printf("New Segement Size: %d\n", $seg['size']);

    $seg_size_aligned=$seg['size']+($seg['size']&1);
    // 8BIM block must be padded with '0's to even size. Allow incorrect
    $seg_pos_end=$seg['pos']+$hdr_len+$seg_size_aligned;
    if ($seg_pos_end>$ps3_pos_end)
    {
      if ($seg_pos_end-$ps3_pos_end==1 && $seg['size']&1==1 && $seg['type']=='0404')
      {
        $log->warn("PS3 record (last IPTC) is not padded to aligned even size!");
        $this->_has_iptc_bug=true;
      }
      else 
      {
        $this->_errno=-1;
        $this->_errmsg=sprintf("PS3 record $i:%s size overflow: (pos:%d, hdrlen:%d, size: %d)=%d, max:%d", 
          $seg['type'], $seg['pos'], $hdr_len, $seg_size_aligned, $seg_pos_end, $ps3_pos_end);
        return -1;
      }
    }
  
    if ($seg['type']=='0404') {
      $seg['index']=$i;
      $this->_jpg['_iptc']=$seg;
    }

    array_push($app13['_segs'], $seg);

    $log->trace(sprintf("seg[$i]: type: %s, pos: %d, hdrlen: %d, size: %d", $seg['type'], $seg['pos'], $hdr_len, $seg['size']));
    $log->trace("PS3 end: $ps3_pos_end. Seg end: $seg_pos_end, Size aligned: $seg_size_aligned");
    // positive shift
    if ($ps3_pos_end-$seg_pos_end==1 && $seg['type']=='0404')
    {
      $log->warn("Found IPTC bug. Adjust size");
      $this->_has_iptc_bug=true;
      $seg_size_aligned++;
      $seg_pos_end++;
    }
    //negative shift
    if ($seg_pos_end-$ps3_pos_end==1 && $seg['type']=='0404')
    {
      $log->warn("Found IPTC bug. Adjust size");
      $this->_has_iptc_bug=true;
      $seg_size_aligned--;
      $seg_pos_end--;
    }
    fseek($fp, $seg_size_aligned, SEEK_CUR);

    $seg_free=$ps3_pos_end-$seg_pos_end;
    if ($seg_free==0)
    {
      $log->trace("Reaching PS3 segment end. Normal break.");
      break;
    }
    if ($seg_free<HDR_SIZE_8BIM)
    {
      $log->warn("PS3 unnormal break. Size to small for next record: ".$seg_free);
      break;
    }

    $i++;
  }

  return 0;
}

/* Read the IPTC data from textual 8BIM segment (type 0x0404) 
  @return false on failure with an error message */
function _read_seg_iptc()
{
  if ($this->_jpg==null || !isset($this->_jpg['_iptc']))
  {
    $this->_errmsg="No IPTC data found";
    $this->_errno=-1;
    return -1;
  }

  $iptc=&$this->_jpg['_iptc'];
  $fp=$this->_jpg['_fp'];
  fseek($fp, $iptc['pos']+HDR_SIZE_8BIM, SEEK_SET);
  
  $this->_iptc=array();
  $iptc_keys=&$this->_iptc;
  $iptc['_segs']=array();

  /* textual segment is divided in iptc segements:
    Section header size: 5 byte
    marker: 1 byte = 0x1c
    record type: 1 byte
    segment type: 1 byte
    size: 2 bytes, excl. marker, record, type, size
    data: length of size
  */
  while (true)
  {
    $seg=array();
    $seg['pos']=ftell($fp);
    $hdr=fread($fp, HDR_SIZE_IPTC);

    // Header checks
    if (ord($hdr[0])==0x00||ord($hdr[0])==0xff||$hdr[0]=='8')
      break;
    if (ord($hdr[0])!=0x1c)
    {
      $this->_errno=-1;
      $this->_errmsg="Wrong 8BIM segment start at ".$seg['pos'];
      break;
    }
    if (strlen($hdr)!= HDR_SIZE_IPTC)
    {
      $this->_errno=-1;
      $this->_errmsg="Could not read IPTC header at ".$seg['pos'];
      return -1;
    }
    
    // size of segment starting from pos: size+5
    $seg['size']=$this->_ato16u(substr($hdr, 3, 2));
    if ($seg['pos']+HDR_SIZE_IPTC+$seg['size']>$iptc['pos']+HDR_SIZE_8BIM+$iptc['size'])
    {
      $this->_errno=-1;
      $this->_errmsg="IPTC segment size overflow: ".$seg['size']." at ".$seg['pos'];
      return -1;
    }
    $seg['marker']=substr($hdr, 0, 1);
    $seg['rec']=substr($hdr, 1, 1);
    $seg['type']=substr($hdr, 2, 1);
    $seg['data']=fread($fp, $seg['size']);

    array_push($iptc['_segs'], $seg);

    // Add named tags to array
    $name=sprintf("%d:%03d",ord($seg['rec']),ord($seg['type']));
    if (!isset($iptc_keys[$name]))
      $iptc_keys[$name]=array();
    array_push($iptc_keys[$name], $seg['data']);
   
    if ($seg['pos']+HDR_SIZE_IPTC+$seg['size']>=$iptc['pos']+HDR_SIZE_8BIM+$iptc['size']-1)
      break;
  }
  return 0;
}

/** Reads the JPEG comment segment.
  @return True on success, false otherwise */
function _read_seg_com()
{
  global $user, $log;
  if ($this->_jpg==null || !isset($this->_jpg['_com']))
  {
    $this->_errmsg="No comment section found";
    $this->_errno=-1;
    return -1;
  }

  $fp=$this->_jpg['_fp'];
  $com=&$this->_jpg['_com'];
  fseek($fp, $com['pos']+HDR_SIZE_JPG, SEEK_SET);
  if ($com['size']<2)
  {
    $log->err("JPEG comment segment size is to low: ".$com['size']." in ".$this->_jpg['filename']);
    return false;
  }
  else if ($com['size']==2)
  {
    $this->_jpg['denycom']=true;
    return 0;
  }

  $buf=fread($fp, $com['size']-2);
  if (substr($buf, 0, 5)!="<?xml") {
    $this->_xml=null;
    $this->_jpg['denycom']=true;
    return 0;
  }
  $this->_xml=new DOMDocument();
  if (!$this->_xml->loadXML($buf)) {
    $this->_xml=null;
    $this->_jpg['denycom']=true;
    return 0;
  }
  return 0;
}

/** Copies a JPEG segment from on file to the other
  @param fin File handle of the input file
  @param fout Handle of the output file
  @param seg Array of the JPEG segment 
  @return False on error. Otherwise the written bytes. */
function _copy_jpeg_seg($fin, $fout, $seg)
{
  if ($fin==0 || $fout==0 || $seg==null)
  fseek($fin, $seg['pos'], SEEK_SET);
  $size=$seg['size']+2;

  // copy segment in blocks to save memory and to avoid memory exhausting
  $done=0;
  $blocksize=1024;
  while ($done<$size)
  {
    if ($done+$blocksize>$size)
      $blocksize=$size-$done;

    $buf=fread($fin, $blocksize);
    if ($blocksize!=fwrite($fout, $buf))
    {
      $this->_errno=-1;
      $this->_errmsg='Could not write all buffered data';
      return $size;
    }
    $done+=$blocksize;
  }
  unset($buf);
  return $size;
}

/** convert iptc values to 8BIM segment in bytes 
  @return Byte string of IPTC block. On failure it returns false */
function _iptc2bytes()
{
  if (!isset($this->_iptc))
    return false;
  
  $buf='';
  foreach ($this->_iptc as $key => $values)
  {  
    list($rec, $type) = split (':', $key, 2);
    foreach ($values as $value)
    {
      $buf.=chr(0x1c);
      $buf.=chr(intval($rec));
      $buf.=chr(intval($type));
      $buf.=$this->_16utoa(strlen($value));
      $buf.=$value;
    }
  }
  return $this->_create_8bim_record(HDR_8BIM_IPTC, $buf);
}

function _create_8bim_record($type, $buf)
{
  global $log;
  $hdr='8BIM'.$type;                          // PS header and type
  $hdr.=chr(0).chr(0).chr(0).chr(0);          // padding
  $len=strlen($buf);
  $hdr.=$this->_16utoa($len);  // size
  // padding of '0' to even size (fixed iptc bug)
  if (($len&1)==1)
  {
    $log->trace("Record length of $len is odd. Add 8BIM padding");
    $buf.=chr(0);
  }
  return $hdr.$buf;
}

/** Replaces the photoshop segment with new IPTC data block and writes it to
 * the destination file.
  @param fin Handle of input file
  @param fout Handle of the output file 
  @return Count of written bytes */
function _replace_seg_ps3($fin, $fout)
{
  if (!$this->_jpg)
    return 0;
  if (!$this->_changed_iptc)
    return 0;

  $new_iptc=$this->_iptc2bytes();
  if ($new_iptc==false)
    return 0;
    
  $new_iptc_len=strlen($new_iptc);

  $jpg=&$this->_jpg;
  if (!isset($jpg['_app13']))
  {
    // Write new photoshop segment before the last jpg segment
    // position points to the last segment
    $hdr_app13=chr(0xff).chr(0xed);
    $new_size=2+HDR_SIZE_PS3+$new_iptc_len; // jpg segment size
    $hdr_app13.=$this->_16utoa($new_size);
    $hdr_app13.=HDR_PS3;

    // segment inclusive Photoshop termination
    $new_buf=$hdr_app13.$new_iptc;

    return fwrite($fout, $new_buf);
  } else {
    $app13=&$jpg['_app13'];
    $ps_seg=&$jpg['_segs'][$app13['index']];

    fseek($fin, $ps_seg['pos'], SEEK_SET);
    $ps_seg_size=$ps_seg['size']+2;
    $buf=fread($fin, $ps_seg_size);

    $bim_seg_first=&$app13['_segs'][0];
    if (!empty($jpg['_iptc']))
    {
      $iptc=&$jpg['_iptc'];
      $iptc_index=$iptc['index'];

      $bim_seg_iptc=&$app13['_segs'][$iptc_index];
      if (!empty($app13['_segs'][$iptc_index+1]))
        $bim_seg_after=&$app13['_segs'][$iptc_index+1];
    }

    // Create new photoshop block, copy 8BIM records before old iptc, insert
    // new iptc, and write 8BIM records after old IPTC

    // header of photoshop
    $new_ps_buf=HDR_PS3;

    // Copy data until old IPTC

    $offset=$bim_seg_first['pos']-$ps_seg['pos'];
    if (!empty($bim_seg_iptc))
      $size_head=$bim_seg_iptc['pos']-$bim_seg_first['pos'];
    else
      $size_head=$ps_seg_size-$bim_seg_first['pos'];
    $new_ps_buf.=substr($buf, $offset, $size_head);
     
    // Intert new IPTC data
    $new_ps_buf.=$new_iptc;
  
    // Copy data after old IPTC
    if (!empty($bim_seg_after))
    {
      $offset=$bim_seg_after['pos']-$ps_seg['pos'];
      $size_tail=$ps_seg_size-$offset;
      $new_ps_buf.=substr($buf, $offset, $size_tail);
    }

    // Create JPEG segment header with adjusted size
    $hdr_app13=chr(0xff).chr(0xed);
    $hdr_app13.=$this->_16utoa(2+strlen($new_ps_buf));

    return fwrite($fout, $hdr_app13.$new_ps_buf);
  }
  return 0;
}

/** Writes the new JPEG comment segment to the output file
  @param fout Handle of output file 
  @return Count of written bytes. Returns -1 on errors. */
function _replace_seg_com($fout)
{
  if (!$this->_jpg)
    return -1;

  if (!$this->_xml)
    return 0;
  $new_comment=$this->_xml->saveXML();
  if ($new_comment==false)
    return false;
    
  $new_comment_len=strlen($new_comment);
 
  $jpg=&$this->_jpg;
  // Write new comment segment
  $hdr_com=chr(0xff).chr(0xfe);
  $new_size=2+$new_comment_len; // jpg segment size
  $hdr_app13.=$this->_16utoa($new_size);

  // segment inclusive Photoshop termination
  $new_buf=$hdr_com+$new_comment;

  return fwrite($fout, $new_buf);
}

/** Converts a short int value (16 bit) a byte sting in big endian */
function _16utoa($i)
{
  $i=abs($i);
  return chr(($i>>8)&0xff) . chr(($i)&0xff);
}

/** Convert a short byte string (2 bytes) to an integer in big endian */
function _ato16u($val)
{
  if (strlen($val)<2)
    return -1;
  return ord($val[0])<<8 | ord($val[1]);
}

/** Converts a string to 32 unsigned integer in big endian 
  @param val Byte string (at least 4 bytes) 
  @return unsigned int */
function _ato32u($val)
{
  if (strlen($val)<4)
    return -1;
  return ord($val[0])<<24 | ord($val[1])<<16 | ord($val[2])<<8 | ord($val[3]);
}

/** Convert a buffer to a hex string. This is only for debugging purpose */
function _buf2hex($string, $reverse=false) {
  $hex= '';
  $len=strlen($string);
  
  for ($i = 0; $i < $len; $i++) {
    if (!$reverse)
      $hex.=str_pad(dechex(ord($string[$i])), 2, 0, STR_PAD_LEFT);
    else
      $hex=str_pad(dechex(ord($string[$i])), 2, 0, STR_PAD_LEFT).$hex;
  }
  return $hex;   
}

/** Return the iptc array of array
  @return null if no iptc tag was found */
function get_iptc()
{
  return $this->_iptc;
}

/** Resets the iptc data */
function reset_iptc()
{
  if (!isset($this->_iptc))
    return;

  unset($this->_iptc);
  $this->_iptc=array();
}

/** Return, if the record occurs only one time */
function _is_single_record($name)
{
  
  if ($name=='2:025' || // keywords
    $name=='2:020')     // categories
    return false;
  return true;
}

/** Add iptc value 
  @param name Name of IPTC record
  @param value Value of IPTC record. If iptc tag is a single record, the
  value will be replaced. For multiple records like keywords or sets, the value
  will be added if it does not exist.
  @return true if the iptc record changes */
function add_record($name, $value)
{
  if ($value=='')
    return false;

  if (!isset($this->_iptc))
  {
    $this->_iptc=array();
    $this->_changed_iptc=true;
  }
  $iptc=&$this->_iptc;
  if (!isset($iptc[$name]))
  {
    $this->_changed_iptc=true;
    $iptc[$name][0]=$value;
    return true;
  }
  
  // Single record. Remove all other records and set the new value
  if ($this->_is_single_record($name))
  {
    if ($iptc[$name][0]==$value)
      return false;

    while(count($iptc[$name]))
      array_shift($iptc[$name]);
    array_push($iptc[$name], $value);
    $this->_changed_iptc=true;
    return true;
  }

  // List tags
  $key=array_search($value, $iptc[$name]);
  if (is_int($key) && $key>=0)
    return false;

  array_push($iptc[$name], $value);
  $this->_changed_iptc=true;
  return true;
}

/** Add a multiple IPTC record
  @param name Name of the IPTC record. 
  @param values Array of values. The array must be set, otherwise it returns
  false.
  @return true if iptc changes */
function add_records($name, $values)
{
  if (count($values)==0)
    return false;

  $changed=false;
  foreach ($values as $value)
  {
    if ($this->add_record($name, $value))
      $changed=true;
  }
  return $changed;
}

/** Return an single value of a given record name
  @return Returns null if no record is available. */
function get_record($name)
{
  if (!$this->_is_single_record($name))
    return null;
    
  if (isset($this->_iptc) && isset($this->_iptc[$name]))
    return $this->_iptc[$name][0];
  return null;
}

/** Return an array of a given record name
  @return Returns empty array if no record is available. */
function get_records($name)
{
  if ($this->_is_single_record($name))
    return array();
    
  if (isset($this->_iptc) && isset($this->_iptc[$name]))
    return $this->_iptc[$name];
  return array();
}

/** Remove an record with an optional value for multi records
  @param name Name of IPTC record
  @param value Value of IPTC record. If null, the record will be removed,
  especially whole multiple records.
  @return true if the iptc changes */
function del_record($name, $value=null)
{
  if (!isset($this->_iptc))
  {
    return false;
  }
  $iptc=&$this->_iptc;
  if (!isset($iptc[$name]))
  {
    return false;
  }
  
  // Single tags
  if ($this->_is_single_record($name) || $value===null)
  {
    unset($iptc[$name]);
    $this->_changed_iptc=true;
    return true;
  }

  // Multiple records
  $key=array_search($value, $iptc[$name]);
  if (is_int($key) && $key>=0)
  {
    unset($iptc[$name][$key]);
    if (count($iptc[$name])==0)
      unset($iptc[$name]);
    $this->_changed_iptc=true;
    return true;
  }
  
  return false;
}

/** Remove iptc records. This function is used for multiple records like
  keywords or sets.
  @param name IPTC name of the record. 
  @param values array of values. This must be set, otherwise it returns false.
  @return true if iptc changes */
function del_records($name, $values)
{
  if (count($values)==0)
    return false;

  $changed=false;
  foreach ($values as $value)
  {
    if ($this->del_record($name, $value))
      $changed=true;
  }
  return $changed;
}

}?>
