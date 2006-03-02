<?php
/*
 Thanks to Christian Tratz, who has written a nice IPTC howto on
 http://www.codeproject.com/bitmap/iptc.asp
*/

class Iptc {

/** general error string */
var $_error;
/** jpg segents */
var $_jpg;
/** IPTC data, array of array */
var $iptc;
/** If some date is changed */
var $_changed; 

function Iptc()
{
  $this->_jpg=NULL;
  $this->iptc=NULL;
  $this->_error='';
  $this->_changed=false;
}

function is_changed()
{
  return $this->_changed;
}

function get_error()
{
  return $this->_error;
}

function load_from_file($filename)
{
  if (!is_readable($filename))
  {
    $this->_error="$filename could not be read";
    return false;
  }
  $size=filesize($filename);
  if ($size<30)
  {
    $this->_error="Filesize of $size is to small";
    return false;
  }
  
  $this->_jpg=array();
  $jpg=&$this->_jpg;
  $jpg['filename']=$filename;
  $jpg['size']=$size;
  
  $fp=fopen($filename, "rb");
  if ($fp==false) 
  {
    $this->_error="Could not open file for reading";
    return false;
  }
  $jpg['_fp']=$fp;

  $data=fread($fp, 2);
  if (ord($data{0})!=0xff || ord($data{1})!=0xd8)
  {
    $this->_error="JPEG header mismatch";
    return false;
  }
  
  if (!$this->_read_jpg_segs(&$jpg))
    return false;

  if ($this->_read_ps_segs(&$jpg))
  {
    $this->_read_iptc_segs(&$jpg);
  } 
  fclose($fp);
  
  return true;
}

function save_to_file()
{
  if ($this->_changed==true)
  {
    $this->_replace_iptc(true);
  }
}

/** Add iptc value 
  @param name Name of IPTC tag
  @param value Value of IPTC tag. If iptc tag is not keyword or set, the value
  will be replaced 
  @return true if the iptc changes */
function add_iptc_tag($name, $value)
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
  
  // Single tags
  if ($name != '2:025')
  {
    if ($iptc[$name][0]==$value)
      return false;

    $iptc[$name]=array();
    array_push($iptc[$name], $value);
    $this->_changed=true;
    return true;
  }

  // List tags
  $key=array_search($value, $iptc[$name]);
  //echo "<pre>";
  //print_r($iptc[$name]);
  //echo "\n$key</pre>\n";
  if (is_int($key) && $key>=0)
    return false;

  array_push($iptc[$name], $value);
  $this->_changed=true;
  return true;
}

/** Add an iptc tags 
  @return true if iptc changes */
function add_iptc_tags($name, $tags)
{
  if ($tags=='')
    return false;

  $changed=false;
  foreach ($tags as $tag)
  {
    if ($this->add_iptc_tag($name,$tag))
      $changed=true;
  }
  return $changed;
}

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
      $this->_error="Invalid jpeg segment start: ".$this->_str2hex($marker)." at $pos";
      return false;
    }
    // size is excl. marker 
    // size of jpes section starting from pos: size+2
    $size=$this->_byte2short(substr($hdr, 2, 2));
    if ($pos+$size+2>$jpg['size'])
    {
      $this->_error="Invalid segment size of $size";
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

/* Read the photoshop headers 
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
    $this->_error="Photoshop header was not found";
    return false;
  }  
  $fp=$jpg['_fp'];
  fseek($fp, $seg['pos']+4, SEEK_SET);
  
  $marker=fread($fp, 14);
  if ($marker!="Photoshop 3.0\0")
  {
    $this->_error="Wrong photoshop marker $marker";
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
    
    padding: 1 byes=0x00 (only last 8BIM segment)
  */
  while (true)
  {
    $data=array();
    $data['pos']=ftell($fp);
    $hdr=fread($fp, 12);
    if (strlen($hdr)!=12)
    {
      $this->_error="Could not read PS segment header";
      return false;
    }
    // size of section starting from pos: size+12
    $data['size']=$this->_byte2short(substr($hdr, 10, 2));
    if ($data['pos']+12+$data['size']>$app13['pos']+2+$app13['size'])
    {
      $this->_error="PS segment size overflow: ".$data['size']." at ".$data['pos'];
      return false;
    }
  
    $data['marker']=substr($hdr, 0, 4);
    if ($data['marker']{0}!='8')
      break;
    if ($data['marker']!='8BIM')
    {
      $this->_error="Wrong 8BIM marker: ".$data['marker'];
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
    $this->_error="iptc section was not found";
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
    if (ord($hdr{0})==0x00||$hdr{0}=='8')
      break;
    if (ord($hdr{0})!=0x1c)
    {
      $this->_error="Wrong 8BIM segment start at ".$data['pos'];
      break;
    }
    if (strlen($hdr)!= 5)
    {
      $this->_error="Could not read IPTC header at ".$data['pos'];
      return false;
    }
    
    // size of segment starting from pos: size+5
    $data['size']=$this->_byte2short(substr($hdr, 3, 2));
    if ($data['pos']+5+$data['size']>$iptc['pos']+12+$iptc['size'])
    {
      $this->_error="IPTC segment size overflow: ".$data['size']." at ".$data['pos'];
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
   
    if ($data['pos']+5+$data['size']>=$iptc['pos']+12+$iptc['size'])
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

/** convert iptc values to 8BIM segment in bytes */
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
  $new_iptc_len=strlen($new_iptc);
  
  $tmp=$jpg['filename'].'.tmp';
  $fout=@fopen($tmp, 'wb');
  if ($fout==false) 
  {
    echo "<div class=\"error\">Could not write to file $tmp</div>\n";
    return false;
  }
  $fin=fopen($jpg['filename'], 'rb');
  
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

function _short2byte($i)
{
  return chr(($i>>8)&0xff) . chr(($i)&0xff);
}

function _byte2short($short)
{
  return ord($short{0})<<8 | ord($short{1});
}

function _str2hex($string) {
  $hex = '';
  $len = strlen($string);
  
  for ($i = 0; $i < $len; $i++) {
      $hex .= str_pad(dechex(ord($string[$i])), 2, 0, STR_PAD_LEFT);
  }
  return $hex;   
}

}?>
