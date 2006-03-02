<?php

/** Updates the file. If the file does not exist, insert it to the database. If
 * the file exists, the caption and the keywords are removed. Also some data
 * are upteded
 *
 * @param $userid the user id
 * @param $file the filename as string
 * @return 1 if the file insert or changed, -1 on error, 0 otherwise
 */
function update_file($userid, $file)
{
  global $db;
  if (!file_exists($file))
  {
    echo "<div class='error'>File '$file' does not exists</div><br/>";
    return -1;
  } 
  
  $sql="SELECT id,UNIX_TIMESTAMP(synced) from $db->image WHERE filename='$file' and userid='$userid' ";
  $result = $db->query($sql);
  if (!$result) 
    return -1; 

  if (mysql_num_rows($result)==0) {
    $imageid=-1;
  } else {
    $row=mysql_fetch_row($result);
    $imageid=$row[0];
    $synced=$row[1];
    
    // return if synctime newer than ctime of file
    if (filectime($file) < $synced)
      return 0;
  }
  $exif=update_exif($imageid, $userid, $file);
  if ($exif<0)
    return -1;
  
  $iptc=update_iptc($imageid, $userid, $file);
  if ($exif>0 || $iptc>0)
    return 1;

  return 0;
}  

function update_exif($imageid, $userid, $file)
{
  global $db;
  $exif = exif_read_data($file, 0, true);
  if (!$exif) 
  {
    // ### add image values if no exif data is available
    echo "<div class='warning'>file '$file' does not contain any exif information.</div></br>";
    return 0;
  }
  
  $name=basename($file);
  $date=$exif['EXIF']['DateTimeOriginal'];
  $width=$exif['COMPUTED']['Width'];
  $height=$exif['COMPUTED']['Height'];
  $camera=$exif['IFD0']['Model'];
  if ($imageid == -1 ) {
    $sql="insert into $db->image (
      filename,synced,userid,name,date,width,height,camera,clicks,lastview,ranking) values (
      '$file',NOW(),$userid,'$name','$date','$width','$height','$camera',0,NOW(),0)";
  } else {
    $sql="update $db->image set synced=NOW(),name='$name',date='$date',width='$width',height='$height',camera='$camera' where id=$imageid";
  }
  $result = $db->query($sql);
  if (!$result) 
    return -1; 
  
  return 1;
}

/** Update iptc data for an existing image */
function update_iptc($imageid, $userid, $file)
{
  global $db;
  if ($imageid == -1) {
    // get the new image id
    $sql="select id from $db->image where filename='$file' and userid='$userid' ";
    $result = $db->query($sql);
    if (!$result) 
      return -1; 
    
    if (mysql_num_rows($result)==0) {
      echo "<div class='error'>Could not find image ID of file '$file'</div>\n";
      print_r ($result);
      return -1;
    }
    
    $row=mysql_fetch_row($result);
    $imageid=$row[0];
  } else {
    // remove all tags
    $sql="delete from $db->tag where imageid=$imageid";
    $result = $db->query($sql);
    if (!$result) 
      return -1; 
    
    // remove caption
    $sql="update image set caption=NULL where id=$imageid";
    $result = $db->query($sql);
    if (!$result) 
      return -1; 
  }

  if ($imageid <= 0) {
    echo "<div class='error'>Insert image id is not greater 0</div>\n";
    return -1;
  }

  // get iptc
  $size = getimagesize ($file, $info);       
  if(is_array($info)) 
  {
    $iptc = iptcparse($info["APP13"]);
    if (!$iptc) return -1;
    // keywords
    $c = count ($iptc['2#025']);
    for ($i=0; $i < $c; $i++)
    {
      $key=$iptc['2#025'][$i];
      $sql="insert into $db->tag ( imageid, name ) values ( $imageid, '$key' )";
      $result = $db->query($sql);
      if (!$result) 
        return -1;
    }
    if (array_key_exists('2#120', $iptc))
    {
      $caption=$iptc['2#120'][0];
      $sql="update $db->image set caption='$caption' where id=$imageid";
      $result = $db->query($sql);
      if (!$result) 
        return -1;
    }
    return 1;
  } else {
    echo "<div class='warning'>File '$file' does not contain iptc data</div>\n";
  }
  return 0;
}

/** Prints the Exif data from a file. Just for debugging only */
function print_exif($file)
{
  if (!file_exists($file))
  {
    echo "<div class='error'>File '$file' does not exists</div><br/>";
    return;
  } 
  
  $exif = exif_read_data($file, 0, true);
  if (!$exif) 
  {
    echo "<div class='error'>File '$file' does not contain any exif data</div>\n";
    return;
  }  
  echo "Exif data of '$file':<br />\n";
  foreach ($exif as $key => $section) {
     foreach ($section as $name => $val) {
         echo "$key.$name: $val<br />\n";
     }
  }
}

/** Print the IPTC data from a file. Just for debugging only */
function print_iptc($file)
{
  if (!file_exists($file))
  {
    echo "<div class='error'>File '$file' does not exists</div><br/>";
    return;
  } 

  $size = getimagesize ( $file, $info);       
  if(is_array($info)) 
  {   
    echo "IPTC data of '$file':<br />\n";
    $iptc = iptcparse($info["APP13"]);
    foreach (array_keys($iptc) as $s) {             
      $c = count ($iptc[$s]);
      for ($i=0; $i <$c; $i++)
      {
        echo $s.' = '.$iptc[$s][$i].'<br>';
      }
    }                 
  } else {
    echo "<div class='error'>File '$file' does not contain any iptc data</div>\n";
    return;
  }
}

function sync_files()
{
  global $db;
  $sql="SELECT id,userid,filename FROM $db->image";
  $result = $db->query($sql);
  if (!$result) 
    return; 
  
  while ($row=mysql_fetch_row($result))
  {
    $imageid=$row[0];
    $userid=$row[1];
    $file=$row[2];
    
    if (!file_exists($file)) 
    {
      delete_image_data($imageid, $file);
    } else {
      update_file($userid, $file);
    }
  }
}

/** Deletes a file from the database */
function delete_image_data($imageid, $file) 
{
  global $db;
  echo "<div class='warning'>File '$file' does not exists. Deleting its data form database</div>\n";
  $sql="delete from $db->tag where imageid=$imageid";
  $result = $db->query($sql);
  
  $sql="delete from $db->image where id=$imageid";
  $result = $db->query($sql);
}
