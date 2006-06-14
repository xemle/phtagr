<?php
/*
 Thanks to Christian Tratz, who has written a nice IPTC howto on
 http://www.codeproject.com/bitmap/iptc.asp
*/
include_once("$phtagr_prefix/Base.php");

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
/** IPTC data, array of array. The IPTC tags are stored in as "r:ttt", where
 * 'r' is the record number and 'ttt' is the type of the IPTC tag
 */
var $iptc;
/** This flag is true if the IPTC data changes. */
var $_changed; 

function Iptc()
{
  $this->_jpg=NULL;
  $this->iptc=NULL;
  $this->_errno=0;
  $this->_errmsg='';
  $this->_changed=false;
}

/** Return true if IPTC records changed */
function is_changed()
{
  return $this->_changed;
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
    return -1;
  }
  $jpg['_fp']=$fp;

  $data=fread($fp, 2);
  if (ord($data{0})!=0xff || ord($data{1})!=0xd8)
  {
    $this->_errmsg="JPEG header mismatch";
    fclose($fp);
    return -1;
  }
  
  if (!$this->_read_jpg_segs(&$jpg))
  {
    fclose($fp);
    return -1;
  }

  if ($this->_read_ps_segs(&$jpg))
  {
    $this->_read_iptc_segs(&$jpg);
  } 
  fclose($fp);
  
  return 0;
}

function save_to_file()
{
  if ($this->_changed==true)
  {
    $this->_replace_iptc(true);
  }
}

/** Return the iptc array of array
  @return null if no iptc tag was found */
function get_iptc()
{
  return $this->iptc;
}

/** Return, if the record occurs only one time */
function _is_single_record($name)
{
  
  if ($name=='2:025') // keyword
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

  // echo "Add tag $name=$value<br/>\n";
  if (!isset($this->iptc))
  {
    $this->iptc=array();
    $this->_changed=true;
  }
  $iptc=&$this->iptc;
  if (!isset($iptc[$name]))
  {
    $this->_changed=true;
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
    $this->_changed=true;
    return true;
  }

  // List tags
  $key=array_search($value, $iptc[$name]);
  
  if (is_int($key) && $key>=0)
    return false;

  array_push($iptc[$name], $value);
  $this->_changed=true;
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
  @return Returns NULL if no record is available. */
function get_record($name)
{
  if (!$this->_is_single_record($name))
    return NULL;
    
  if (isset($this->iptc) && isset($this->iptc[$name]))
    return $this->iptc[$name][0];
  return NULL;
}

/** Return an array of a given record name
  @return Returns NULL if no record is available. */
function get_records($name)
{
  if ($this->_is_single_record($name))
    return NULL;
    
  if (isset($this->iptc) && isset($this->iptc[$name]))
    return $this->iptc[$name];
  return NULL;
}

/** Remove an record with an optional value for multi records
  @param name Name of IPTC record
  @param value Value of IPTC record. If this is an empty string, the record
  will be removed, especially whole multiple records.
  @return true if the iptc changes */
function rem_record($name, $value='')
{
  if (!isset($this->iptc))
  {
    return false;
  }
  $iptc=&$this->iptc;
  if (!isset($iptc[$name]))
  {
    return false;
  }
  
  // Single tags
  if ($this->_is_single_record($name) || $value=='')
  {
    unset($iptc[$name]);
    $this->_changed=true;
    return true;
  }

  // Multiple records
  $key=array_search($value, $iptc[$name]);
  if (is_int($key) && $key>=0)
  {
    unset($iptc[$name][$key]);
    if (count($iptc[$name])==0)
      unset($iptc[$name]);
    $this->_changed=true;
    return true;
  }
  
  return false;
}

/** Remove iptc records. This function is used for multiple records like
  keywords or sets.
  @param name IPTC name of the record. 
  @param array of values. This must be set, otherwise it returns false.
  @return true if iptc changes */
function rem_records($name, $values)
{
  if (count($values)==0)
    return false;

  $changed=false;
  foreach ($values as $value)
  {
    if ($this->rem_record($name, $value))
      $changed=true;
  }
  return $changed;
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
function _read_jpg_segs($jpg)
{
  $fp=$jpg['_fp'];
  $jpg['_segs']=array();
  
  while (true)
  {
    $pos=ftell($fp);
    $hdr=fread($fp, 4);
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
    // end on SOS (start of scan) segment (0xff 0xda)
    if (ord($marker{1})==0xda)
      break;
    // jump to the next segment
    fseek($fp, $size-2, SEEK_CUR);
  }
  return true;
}

/* Read the photoshop meta headers.
 * @param jpg Pointer to the JPEG array
 * @return false on failure with an error string */
function _read_ps_segs($jpg)
{
  if ($jpg==null || !isset($jpg['_segs']))
    return false;

  /*
    jpeg section marker: 0xffed
    photoshop identifier: 'Photoshop 3.0'+\x00
    8BIM sections...
    photoshop termination: 0x00
  */
  for ($i=0; $i<count($jpg['_segs']); $i++)
  {
    $seg=&$jpg['_segs'][$i];
    if ($seg['type']=='ffed')
      break;
  }
  // Photoshop header not found
  if ($i==count($jpg['_segs']))
  {
    $this->_errno=1;
    $this->_errmsg="Photoshop header was not found";
    return false;
  }  
  $fp=$jpg['_fp'];
  fseek($fp, $seg['pos']+4, SEEK_SET);
  
  $marker=fread($fp, 14);
  if ($marker!="Photoshop 3.0\0")
  {
    $this->_errno=-1;
    $this->_errmsg="Wrong photoshop marker $marker";
    return false;
  }
  $jpg['_app13']=array();
  $app13=&$jpg['_app13'];
  $app13['pos']=$seg['pos'];
  //size of segment starting from pos: size+2
  $app13['size']=$seg['size']; 
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
  while (true)
  {
    $data=array();
    $data['pos']=ftell($fp);
    $hdr=fread($fp, 12);
    if (strlen($hdr)!=12)
    {
      $this->_errno=-1;
      $this->_errmsg="Could not read PS segment header";
      return false;
    }
    // size of section starting from pos: size+12
    $data['size']=$this->_byte2short(substr($hdr, 10, 2));
    if ($data['pos']+12+$data['size']>$app13['pos']+2+$app13['size'])
    {
      $this->_errno=-1;
      $this->_errmsg="PS segment size overflow: ".$data['size']." at ".$data['pos'];
      return false;
    }
  
    $data['marker']=substr($hdr, 0, 4);
    if ($data['marker']{0}!='8')
      break;
    if ($data['marker']!='8BIM')
    {
      $this->_errno=-1;
      $this->_errmsg="Wrong 8BIM marker: ".$data['marker'];
      return false;
    }
    $data['type']=$this->_str2hex(substr($hdr, 4, 2));
    $data['padding']=$this->_str2hex(substr($hdr, 6, 4));

    array_push($app13['_segs'], $data);
    
    // Some programs have a padding '0' at the end of the PS segment 
    if ($data['pos']+12+$data['size']>=$app13['pos']+$app13['size']+1)
      break;
    fseek($fp, $data['size'], SEEK_CUR);
  }

  return true;
}

/* Read the IPTC data from textual 8BIM segment (type 0x0404) 
  @param jpg Pointer to the JPEG array
  @return false on failure with an error message */
function _read_iptc_segs($jpg)
{
  if ($jpg==null || !isset($jpg['_app13']))
    return false;

  $app13=&$jpg['_app13'];
  // search for iptc section
  for ($i=0; $i<count($app13['_segs']); $i++)
  {
    $seg=&$app13['_segs'][$i];
    if ($seg['type']=='0404')
      break;
  }
  // iptc section not found
  if ($i==count($app13['_segs']))
  {
    $this->_errno=1;
    $this->_errmsg="iptc section was not found";
    return false;
  }  
  $fp=$jpg['_fp'];
  fseek($fp, $seg['pos']+12, SEEK_SET);
  
  $jpg['_iptc']=array();
  $iptc=&$jpg['_iptc'];
  $this->iptc=array();
  $iptc_keys=&$this->iptc;
  $iptc['pos']=$seg['pos'];
  // size of segment starting from pos: size+12
  $iptc['size']=$seg['size'];
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
    $data=array();
    $data['pos']=ftell($fp);
    $hdr=fread($fp, 5);

    // Header checks
    if (ord($hdr{0})==0x00||ord($hdr{0})==0xff||$hdr{0}=='8')
      break;
    if (ord($hdr{0})!=0x1c)
    {
      $this->_errno=-1;
      $this->_errmsg="Wrong 8BIM segment start at ".$data['pos'];
      break;
    }
    if (strlen($hdr)!= 5)
    {
      $this->_errno=-1;
      $this->_errmsg="Could not read IPTC header at ".$data['pos'];
      return false;
    }
    
    // size of segment starting from pos: size+5
    $data['size']=$this->_byte2short(substr($hdr, 3, 2));
    if ($data['pos']+5+$data['size']>$iptc['pos']+12+$iptc['size'])
    {
      $this->_errno=-1;
      $this->_errmsg="IPTC segment size overflow: ".$data['size']." at ".$data['pos'];
      return false;
    }
    $data['marker']=substr($hdr, 0, 1);
    $data['rec']=substr($hdr, 1, 1);
    $data['type']=substr($hdr, 2, 1);
    $data['data']=fread($fp, $data['size']);

    array_push($iptc['_segs'], $data);

    // Add named tags to array
    $name=sprintf("%d:%03d",ord($data['rec']),ord($data['type']));
    if (!isset($iptc_keys[$name]))
      $iptc_keys[$name]=array();
    array_push($iptc_keys[$name], $data['data']);
   
    if ($data['pos']+5+$data['size']>=$iptc['pos']+12+$iptc['size']-1)
      break;
  }
  return true;
}

/** Adds a iptc field 
 @todo recognize changes in iptc fields */
function add_iptc($key, $value) {
  $iptc=&$this->iptc;
  if (!isset($iptc))
    $iptc=array();

  if (!isset($iptc[$key]))
    $iptc[$key]=array();
 
  array_push($iptc[$key], $value);
}

/** convert iptc values to 8BIM segment in bytes 
  @return Byte string of IPTC block. On failure it returns false */
function _iptc2bytes()
{
  if (!isset($this->iptc))
    return false;
  
  foreach ($this->iptc as $key => $values)
  {  
    list($rec, $type) = split (':', $key, 2);
    foreach ($values as $value)
    {
      $content.=chr(0x1c);
      $content.=chr(intval($rec));
      $content.=chr(intval($type));
      $content.=$this->_short2byte(strlen($value));
      $content.=$value;
    }
  }
  $hdr='8BIM'.chr(0x04).chr(0x04);            // PS header and type
  $hdr.=chr(0).chr(0).chr(0).chr(0);          // padding
  $hdr.=$this->_short2byte(strlen($content));  // size
  return $hdr.$content;
}

/** Replaces the iptc tag in a file.
  This function copys the file and rewrites the iptc section.
  @param do_rename If this value is true, the original file is replaced */
function _replace_iptc($do_rename=false)
{
  if (!isset($this->_jpg))
    return false;
  $jpg=&$this->_jpg;

  $new_iptc=$this->_iptc2bytes();
  if ($new_iptc==false)
    return false;
    
  $new_iptc_len=strlen($new_iptc);
  
  $tmp=$jpg['filename'].'.tmp';
  $fout=@fopen($tmp, 'wb');
  if ($fout==false) 
  {
    $this->_errno=-1;
    $this->_errmsg="Could not write to file $tmp";
    return false;
  }
  $fin=fopen($jpg['filename'], 'rb');
  if (!$fin)
  {
    $this->_errno=-1;
    $this->_errmsg="Could not read the file ".$jpg['filename'];
    return false;
  }
  
  $pos=0;
  if (!isset($jpg['_app13']))
  {
    // Write new photoshop segment before the last jpg segment
    // position points to the last segment
    $last_seg=&$jpg['_segs'][count($jpg['_segs'])-1];
    $pos=$last_seg['pos'];
    $buf=fread($fin, $pos);
    fwrite($fout, $buf); 
    
    $hdr_app13=chr(0xff).chr(0xed);
    $size=2+14+$new_iptc_len+1; // jpg segment size
    $hdr_app13.=$this->_short2byte($size);
    $hdr_app13.='Photoshop 3.0'.chr(0);
    fwrite($fout, $hdr_app13);
    fwrite($fout, $new_iptc);
    fwrite($fout, chr(0)); // Photoshop termination
  } else {
    $app13=&$jpg['_app13'];

    // write jpg data until photoshop section
    // position points to the start of the photoshop segment
    $pos=$app13['pos'];
    $buf=fread($fin, $pos);
    fwrite($fout, $buf);
    
    if (!isset($jpg['_iptc']))
    {
      // IPTC data is not in the photoshop segment
      // write new photoshop header with corrected size
      // position points to the first 8BIM photoshop segment
      $iptc_diff=$new_iptc_len;
      fwrite($fout, chr(0xff).chr(0xed));
      fwrite($fout, $this->_short2byte($app13['size']+$new_iptc_len));
      fwrite($fout, 'Photoshop 3.0'.chr(0));
      $pos=$app13['pos']+4+14;
    } else {
      // Correct photoshop size and write data until iptc data
      // position points to the first byte after original iptc data
      $iptc=&$jpg['_iptc'];
    
      // read photoshop data until iptc data 
      $buf=fread($fin, $iptc['pos']-$pos);
      
      fwrite($fout, substr($buf, 0, 2));
      // correct photoshop size
      $iptc_diff=$new_iptc_len-12 - $iptc['size'];
      fwrite($fout, $this->_short2byte($app13['size']+$iptc_diff));
      // write data until iptc header
      fwrite($fout, substr($buf, 4));
      $pos=$iptc['pos']+$iptc['size']+12;
    }      
    
    // insert new iptc data
    fwrite($fout, $new_iptc);
  }
  
  // Write photoshop and jpg remainings 
  fseek($fin, $pos, SEEK_SET);
  $buf=fread($fin, $jpg['size']-$pos);
  fwrite($fout, $buf);
  
  fclose($fin);
  fclose($fout);

  if ($do_rename)
    rename($jpg['filename'].'.tmp', $jpg['filename']);
}

/** Converts a shor int value (16 bit) a byte sting */
function _short2byte($i)
{
  return chr(($i>>8)&0xff) . chr(($i)&0xff);
}

/** Convert a short byte string (2 bytes) to an integer */
function _byte2short($short)
{
  return ord($short{0})<<8 | ord($short{1});
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

}?>
