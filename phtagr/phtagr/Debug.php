<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/SectionAccount.php");
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Thumbnail.php");

/** Synchronize files between the database and the filesystem. If a file not
 * exists delete its data. If a file is newer since the last update, update its
 * data. */
function sync_files()
{
  global $db;

  $sql="SELECT id,filename
        FROM $db->image";
  $result=$db->query($sql);
  if (!$result)
    return;
    
  $count=0;
  $updated=0;
  $deleted=0;
  while ($row=mysql_fetch_row($result))
  {
    $id=$row[0];
    $filename=$row[1];
    $count++;
    
    if (!file_exists($filename))
    {
      $this->delete_image_data($id,$filename);
      $deleted++;
    }
    else 
    {
      $image=new Image($id);
      if ($image->update())
        $updated++;
      unset($image);
    }
  }
  echo "<p>All $count images are now synchronized. $deleted images are deleted. $updated images are updated.</p>\n";
}

/** Create all preview images */
function create_all_previews()
{
  global $db;

  $sql="SELECT id
        FROM $db->image";
  $result=$db->query($sql);
  if (!$result)
    return;
    
  $count=0;
  $updated=0;
  $deleted=0;
  while ($row=mysql_fetch_row($result))
  {
    $id=$row[0];
    $count++;
    
    $img=new Thumbnail($id);
    $img->create_all_previews();
  }
  echo "<p>All preview images of $count images are now created.</p>\n";
}

/** Deletes a file from the database */
function delete_image_data($id, $file)
{
  global $db;
  echo "<div class='warning'>File '$file' does not exists. Deleting its data form database</div>\n";
  $sql="DELETE FROM $db->imagetag 
        WHERE imageid=$id";
  $result = $db->query($sql);

  $sql="DELETE FROM $db->image 
        WHERE id=$id";
  $result = $db->query($sql);
}

?>
