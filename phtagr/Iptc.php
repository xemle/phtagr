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

// Size of different headers 
define("HDR_SIZE_JPG", 0x04);
define("HDR_SIZE_PS", 0x0e);
define("HDR_SIZE_8BIM", 0x0c);
define("HDR_SIZE_IPTC", 0x05);

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

function Iptc($filename='')
{
  $this->_errno=0;
  $this->_errmsg='';
  $this->_jpg=null;
  $this->_xml=null;
  $this->_iptc=null;
  $this->_changed_com=false;
  $this->_changed_iptc=false;
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
    return -1;
  }
  $size=filesize($filename);
  if ($size<30)
  {
    $this->_errmsg="Filesize of $size is to small";
    return -1;
  }
  
  $this->_jpg=array();
  $jpg=&$this->_jpg;
  $jpg['filename']=$filename;
  $jpg['size']=$size;
  
  $fp=fopen($filename, "rb");
  if ($fp==false) 
  {
    $this->_errmsg="Could not open file for reading";
    $this->_jpg=null;
    return -1;
  }
  $jpg['_fp']=$fp;

  $data=fread($fp, 2);
  if (ord($data{0})!=0xff || ord($data{1})!=0xd8)
  {
    $this->_errmsg="JPEG header mismatch";
    $this->_jpg=null;
    fclose($fp);
    return -1;
  }
  
  if (!$this->_read_seg_jpg(&$jpg))
  {
    $this->_jpg=null;
    fclose($fp);
    return -1;
  }

  if (isset($this->_jpg['_app13']))
  {
    $this->_read_seg_ps(&$jpg);
    if (isset($this->_jpg['_iptc']))
      $this->_read_seg_iptc(&$jpg);
  } 

  if (isset($this->_jpg['_com']))
    $this->_read_seg_com(&$jpg);

  fclose($fp);
  
  return 0;
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
      $this->_replace_seg_ps($fin, $fout);
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
        $this->_replace_seg_ps($fin, $fout);
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

  if ($do_rename)
    rename($tmp, $jpg['filename']);
}

/** @return Returns the filename */
function get_filename()
{
  if (!isset($this->_jpg))
    return '';

  return $this->_jpg['filename'];
}

/** Read all segments of an JPEG image until the data segment. Each JPEG
 * segment starts with a marker of an 0xff byte followed by the segemnt type
 * (also one byte). The segment type 0xda indicates the data segment, the real
 * image data. If this segment is found the algorithm stops. The segment type
 * 0xed indicates the photoshop meta data segment.  The segment size of 2 bytes
 * is folled. The size does not include the segment marker and the segment
 * byte.

  @param jpg Pointer to the JPEG array
  @return Return true if all segments have a correct size. The function will
  return false on a segment start mismatch. */
function _read_seg_jpg($jpg)
{
  $fp=$jpg['_fp'];
  $jpg['_segs']=array();
  
  $i=0;
  while (true)
  {
    $pos=ftell($fp);
    $hdr=fread($fp, HDR_SIZE_JPG);
    // get segment marker
    $marker=substr($hdr, 0, 2);
    if (ord($marker{0})!=0xff)
    {
      $this->_errno=-1;
      $this->_errmsg="Invalid jpeg segment start: ".$this->_str2hex($marker)." at $pos";
      return false;
    }
    // size is excl. marker 
    // size of jpes section starting from pos: size+2
    $size=$this->_byte2short(substr($hdr, 2, 2));
    if ($pos+$size+2>$jpg['size'])
    {
      $this->_errmsg="Invalid segment size of $size";
      return false;
    }
    $seg=array();
    $seg['pos']=$pos;
    $seg['size']=$size;
    $seg['type']=$this->_str2hex($marker);
    array_push($jpg['_segs'], $seg);

    // save photoshop segment
    if ($marker[1]==chr(0xed)) {
      $seg['index']=$i;
      $jpg['_app13']=$seg;
    }

    // save com segment
    if ($marker{1}==chr(0xfe)) {
      $seg['index']=$i;
      $jpg['_com']=$seg;
    }
    
    // end on SOS (start of scan) segment (0xff 0xda)
    if (ord($marker{1})==0xda)
      break;
    // jump to the next segment
    fseek($fp, $size-2, SEEK_CUR);
    $i++;
  }
  return true;
}

/* Read the photoshop meta headers.
 * @param jpg Pointer to the JPEG array
 * @return false on failure with an error string */
function _read_seg_ps($jpg)
{
  if ($jpg==null || !isset($jpg['_app13']))
    return false;

  $app13=&$jpg['_app13'];
  $fp=$jpg['_fp'];
  fseek($fp, $app13['pos']+4, SEEK_SET);
  
  $marker=fread($fp, 14);
  if ($marker!="Photoshop 3.0\0")
  {
    unset($jpg['_app13']);
    $this->_errno=-1;
    $this->_errmsg="Wrong photoshop marker $marker";
    return false;
  }
  //size of segment starting from pos: size+2
  $app13['marker']=$marker;
  $app13['_segs']=array();

  /* Read 8BIM segments
    Section header size: 12 bytes
    marker: 4 bytes='8BIM'
    type: 2 bytes
    padding: 4 bytes=0x00000000
    size: 2 bytes, excl. marker, type, padding, size
    data: length of size
    
    padding: 1 byes=0x00 (only last 8BIM segment). However, not all program
    uses this padding.
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
      $this->_errmsg="Could not read PS segment header";
      return false;
    }

    // try to fix padding of last 8BIM segment and shift header with 1 byte
    if ($hdr{0}!='8')
    {
      if ($hdr{1}=='8')
      {
        $hdr=substr($hdr, 1).fread($fp, 1);
        $seg['pos']++;
      }
      else
        break;
    }

    // size of section starting from pos: size+12
    $seg['size']=$this->_byte2short(substr($hdr, 10, 2));
    $seg['marker']=substr($hdr, 0, 4);
    if ($seg['marker']!='8BIM')
    {
      $this->_errno=-1;
      $this->_errmsg="Wrong 8BIM marker: ".$seg['marker'];
      return false;
    }

    $seg['type']=$this->_str2hex(substr($hdr, 4, 2));

    // Check for path segment
    $type=$this->_byte2short(substr($hdr, 4, 2));
    if ($type>=2000 && $type<3000)
    {
      // Path block: 8BIM + PATH_TYPE + LEN[1] + String[len] + '0' + Size[4] + data
      $hdr_len=ord($hdr{6});
      //printf("String Length: %d\n", $hdr_len);
      $hdr_len+=12; // 4 Bytes '8BIM', 2 Bytes Type, 1 Byte len, 1 Byte leading '0', 4 Bytes size
      if ($hdr_len>HDR_SIZE_8BIM)
        $hdr.=fread($fp, $hdr_len-HDR_SIZE_8BIM);
      else if ($len<HDR_SIZE_8BIM)
      {
        $hdr=substr($hdr,0,$hdr_len);
        fseek($fp, $seg['pos']+$hdr_len, SEEK_SET);
      }
      $seg['size']=$this->_byte2short(substr($hdr, $hdr_len-2, 2));
      //printf("New Header Length: %d\n", $hdr_len);
      //printf("New Segement Size: %d\n", $seg['size']);
    } else {
      $hdr_len=HDR_SIZE_8BIM;
      $seg['padding']=$this->_str2hex(substr($hdr, 6, 4));
    }

    if ($seg['pos']+$hdr_len+$seg['size']>$app13['pos']+2+$app13['size'])
    {
      $this->_errno=-1;
      $this->_errmsg="PS segment size overflow: ".$seg['size']." at ".$seg['pos'];
      return false;
    }
  
    array_push($app13['_segs'], $seg);
    
    if ($seg['type']=='0404') {
      $seg['index']=$i;
      $jpg['_iptc']=$seg;
    }

    // Some programs have a padding '0' at the end of the PS segment 
    if ($seg['pos']+HDR_SIZE_8BIM+$seg['size']>=$app13['pos']+$app13['size']+1)
      break;
    fseek($fp, $seg['size'], SEEK_CUR);
    $i++;
  }

  return true;
}

/* Read the IPTC data from textual 8BIM segment (type 0x0404) 
  @param jpg Pointer to the JPEG array
  @return false on failure with an error message */
function _read_seg_iptc($jpg)
{
  if ($jpg==null || !isset($jpg['_iptc']))
    return false;

  $iptc=&$jpg['_iptc'];
  $fp=$jpg['_fp'];
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
    if (ord($hdr{0})==0x00||ord($hdr{0})==0xff||$hdr{0}=='8')
      break;
    if (ord($hdr{0})!=0x1c)
    {
      $this->_errno=-1;
      $this->_errmsg="Wrong 8BIM segment start at ".$seg['pos'];
      break;
    }
    if (strlen($hdr)!= HDR_SIZE_IPTC)
    {
      $this->_errno=-1;
      $this->_errmsg="Could not read IPTC header at ".$seg['pos'];
      return false;
    }
    
    // size of segment starting from pos: size+5
    $seg['size']=$this->_byte2short(substr($hdr, 3, 2));
    if ($seg['pos']+HDR_SIZE_IPTC+$seg['size']>$iptc['pos']+HDR_SIZE_8BIM+$iptc['size'])
    {
      $this->_errno=-1;
      $this->_errmsg="IPTC segment size overflow: ".$seg['size']." at ".$seg['pos'];
      return false;
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
  return true;
}

/** Reads the JPEG comment segment.
  @param jpg JPEG data array
  @return True on success, false otherwise */
function _read_seg_com($jpg)
{
  global $user, $log;
  if ($jpg==null || !isset($jpg['_com']))
    return false;

  $fp=$jpg['_fp'];
  $com=&$jpg['_com'];
  fseek($fp, $com['pos']+HDR_SIZE_JPG, SEEK_SET);
  if ($com['size']<2)
  {
    $log->error("JPEG comment segment size is negative: ".$jpg['filename'], -1, $user->get_id());
    return false;
  }
  $buf=fread($fp, $com['size']-2);
  if (substr($buf, 0, 5)!="<?xml") {
    $this->_xml=null;
    $this->_jpg['denycom']=true;
    return false;
  }
  $this->_xml=new DOMDocument();
  if (!$this->_xml->loadXML($buf)) {
    $this->_xml=null;
    $this->_jpg['denycom']=true;
    return false;
  }
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
      $this->_errno=1;
      $this->_errmsg='Could not write all buffered data';
      return false;
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
      $buf.=$this->_short2byte(strlen($value));
      $buf.=$value;
    }
  }
  $hdr='8BIM'.chr(0x04).chr(0x04);            // PS header and type
  $hdr.=chr(0).chr(0).chr(0).chr(0);          // padding
  $hdr.=$this->_short2byte(strlen($buf));  // size
  return $hdr.$buf;
}

/** Replaces the photoshop segment with new IPTC data block and writes it to
 * the destination file.
  @param fin Handle of input file
  @param fout Handle of the output file 
  @return Count of written bytes */
function _replace_seg_ps($fin, $fout)
{
  if (!$this->_jpg)
    return;
  if (!$this->_changed_iptc)
    return;

  $new_iptc=$this->_iptc2bytes();
  if ($new_iptc==false)
    return false;
    
  $new_iptc_len=strlen($new_iptc);
 
  $jpg=&$this->_jpg;
  if (!isset($jpg['_app13']))
  {
    // Write new photoshop segment before the last jpg segment
    // position points to the last segment
    $hdr_app13=chr(0xff).chr(0xed);
    $new_size=2+HDR_SIZE_PS+$new_iptc_len+1; // jpg segment size
    $hdr_app13.=$this->_short2byte($new_size);
    $hdr_app13.='Photoshop 3.0'.chr(0);

    // segment inclusive Photoshop termination
    $new_buf=$hdr_app13.$new_iptc.chr(0);

    return fwrite($fout, $new_buf);
  } else {
    $app13=&$jpg['_app13'];
    $seg=&$jpg['_segs'][$app13['index']];

    fseek($fin, $seg['pos'], SEEK_SET);
    $size_ps=$seg['size']+2;
    $buf=fread($fin, $size_ps);

    if (!isset($jpg['_iptc']))
    {
      // IPTC data is not in the photoshop segment
      // write new photoshop header with corrected size
      // position points to the first 8BIM photoshop segment
      $iptc_diff=$new_iptc_len;
      $new_size=$app13['size']+$iptc_diff;

      $offset=0;
      // Copy Photoshop header
      $new_buf=substr($buf, $offset, 2);
      $new_buf.=$this->_short2byte($new_size);
      $offset+=HDR_SIZE_JPG;

      $new_buf.=substr($buf, $offset, HDR_SIZE_PS);
      $offset+=HDR_SIZE_PS;

      // Insert new IPTC data
      $new_buf.=$new_iptc;

      // Copy data after new IPTC segment
      $new_buf.=substr($buf, $offset, $size_ps-$offset);

      return fwrite($fout, $new_buf);
    } else {
      // Correct photoshop size and write data until iptc data
      // position points to the first byte after original iptc data
      $iptc=&$jpg['_iptc'];
    
      $iptc_diff=$new_iptc_len-HDR_SIZE_8BIM - $iptc['size'];
      $new_size=$app13['size']+$iptc_diff;

      $offset=0;

      // Create JPEG Segment header
      $new_buf=substr($buf, $offset, 2);
      $new_buf.=$this->_short2byte($new_size);
      $offset+=HDR_SIZE_JPG;

      // Copy Photoshop header
      $new_buf.=substr($buf, $offset, HDR_SIZE_PS);
      $offset+=HDR_SIZE_PS;

      // Copy data until old IPTC
      $ps_seg_first=&$app13['_segs'][0];
      $ps_seg_iptc=&$app13['_segs'][$iptc['index']];
      $size_head=$ps_seg_iptc['pos']-$ps_seg_first['pos'];
      $new_buf.=substr($buf, $offset, $size_head);
      $offset+=$size_head;
      
      // Intert new IPTC data
      $new_buf.=$new_iptc;
      $offset+=$ps_seg_iptc['size']+HDR_SIZE_8BIM;
       
      // Copy data after old IPTC
      $new_buf.=substr($buf, $offset, $size_ps-$offset);

      return fwrite($fout, $new_buf);
    }      
  }
}

/** Writes the new JPEG comment segment to the output file
  @param fout Handle of output file 
  @return Count of written bytes. Returns -1 on errors. */
function _replace_seg_com($fout)
{
  if (!$this->_jpg)
    return -1;
  if (!$this->_changed_com)
    return 0;

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
  $hdr_app13.=$this->_short2byte($new_size);

  // segment inclusive Photoshop termination
  $new_buf=$hdr_com+$new_comment;

  return fwrite($fout, $new_buf);
}

/** Converts a shor int value (16 bit) a byte sting */
function _short2byte($i)
{
  return chr(($i>>8)&0xff) . chr(($i)&0xff);
}

/** Convert a short byte string (2 bytes) to an integer */
function _byte2short($buf)
{
  return ord($buf{0})<<8 | ord($buf{1});
}

/** Convert a sting to a hex string. This is only for debugging purpose */
function _str2hex($string) {
  $hex = '';
  $len = strlen($string);
  
  for ($i = 0; $i < $len; $i++) {
      $hex .= str_pad(dechex(ord($string[$i])), 2, 0, STR_PAD_LEFT);
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
    $name=='2:020')     // sets
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
