<?php

/** Updates the file. If the file does not exist, insert it to the database. If
 * the file exists, the caption and the keywords are removed. Also some data
 * are upteded
 *
 * @param $userid the user id
 * @param $file the filename as string
 */
function update_file($userid, $file)
{
  global $db;
  if (!file_exists($file))
  {
    echo "<div class='error'>File '$file' does not exists</div><br/>";
    return;
  } 
  
  $sql="select id,synced from image where filename='$file' and userid='$userid' ";
  $result = $db->query($sql);
  if (!$result) 
  { 
    echo "<div class='error'>Could not run Query: '$sql'</div><br/>";
    return; 
  }

  if (mysql_num_rows($result)==0) {
    $imageid=-1;
  } else {
    $row=mysql_fetch_row($result);
    $imageid=$row[0];
    $synced=$row[1];
    // ### return if synctime newer than ctime of file
  }
  if (update_exif($imageid, $userid, $file)) { return; }
  if (update_iptc($imageid, $userid, $file)) { return; }
  echo "Updated file '$file'<br />\n";
}  

function update_exif($imageid, $userid, $file)
{
  global $db;
  $exif = exif_read_data($file, 0, true);
  if (!$exif) 
  {
    // ### add image values if no exif data is available
    echo "<div class='warning'>file '$file' does not contain any exif information.</div></br>";
    return -1;
  }
  
  $name=basename($file);
  $date=$exif['EXIF']['DateTimeOriginal'];
  $width=$exif['COMPUTED']['Height'];
  $height=$exif['COMPUTED']['Width'];
  $camera=$exif['IFD0']['Model'];
  if ($imageid == -1 ) {
    $sql="insert into image (
      filename,synced,userid,name,date,width,height,camera,clicks) values (
      '$file',NOW(),$userid,'$name','$date','$width','$height','$camera',0)";
  } else {
    $sql="update image set synced=NOW(),name='$name',date='$date',width='$width',height='$height',camera='$camera' where id=$imageid";
  }
  $result = $db->query($sql);
  if (!$result) 
  { 
    echo "<div class='error'>Could not run Query: '$sql'</div><br/>";
    return -1; 
  }
  return 0;
}

/** Update iptc data for an existing image */
function update_iptc($imageid, $userid, $file)
{
  global $db;
  if ($imageid == -1) {
    // get the new image id
    $sql="select id from image where filename='$file' and userid='$userid' ";
    $result = $db->query($sql);
    if (!$result) 
    { 
      echo "<div class='error'>Could not run Query: '$sql'</div>\n";
      return -1; 
    }
    if (mysql_num_rows($result)==0) {
      echo "<div class='error'>Could not find image ID of file '$file'</div>\n";
      print_r ($result);
      return -1;
    }
    
    $row=mysql_fetch_row($result);
    $imageid=$row[0];
  } else {
    // remove all tags
    $sql="delete from tag where imageid=$imageid";
    $result = $db->query($sql);
    if (!$result) 
    { 
      echo "<div class='error'>Could not run Query: '$sql'</div>\n";
      return -1; 
    }
    // remove caption
    $sql="update image set caption=NULL where id=$imageid";
    $result = $db->query($sql);
    if (!$result) 
    { 
      echo "<div class='error'>Could not run Query: '$sql'</div>\n";
      return -1; 
    }
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
      $sql="insert into tag ( imageid, name ) values ( $imageid, '$key' )";
      $result = $db->query($sql);
      if (!$result) 
      {
        echo "<div class='error'>Could not run Query: '$sql'</div>\n";
        return -1;
      }   
    }
    if (array_key_exists('2#120', $iptc))
    {
      $caption=$iptc['2#120'][0];
      $sql="update image set caption='$caption' where id=$imageid";
      $result = $db->query($sql);
      if (!$result) 
      {
        echo "<div class='error'>Could not run Query: '$sql'</div>\n";
        return -1;
      }
    }
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
  $sql="select id,userid,filename from image";
  $result = $db->query($sql);
  if (!$result) 
  { 
    echo "<div class='error'>Could not run Query: '$sql'</div>\n";
    return; 
  }
  
  while ($row=mysql_fetch_row($result))
  {
    $imageid=$row[0];
    $userid=$row[1];
    $file=$row[2];
    
    if (!file_exists($file)) 
    {
      delete_file($imageid);
    } else {
      update_file($userid, $file);
    }
  }
}

/** Deletes a file from the database */
function delete_file($imageid) 
{
  global $db;
  echo "<div class='error'>Deleting file '$file' from database</div>\n";
  $sql="delete from tag where imageid=$imageid";
  $result = $db->query($sql);
  if (!$result) 
  { 
    echo "<div class='error'>Could not run Query: '$sql'</div>\n";
  }
  $sql="delete from image where id=$imageid";
  $result = $db->query($sql);
  if (!$result) 
  { 
    echo "<div class='error'>Could not run Query: '$sql'</div>\n";
  }
}
