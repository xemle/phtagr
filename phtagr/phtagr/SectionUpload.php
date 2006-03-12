<?php

global $prefix;

include_once("$prefix/SectionBody.php");
include_once("$prefix/Auth.php");
include_once("$prefix/Sql.php");

class SectionUpload extends SectionBody
{

function SectionUpload()
{
  $this->name="upload";
}

/** Returns the final filename of the uploaded image */
function get_upload_filename($path, $filename)
{
  $fileext = substr (strrchr ($filename, '.'), 0);
  $basename = substr ($filename, 0, strlen ($filename)
              - strlen ($fileext));
  $iter_name = $path . $basename . $fileext;
  $i = 0;
  
  # Then we need to check, whether there is already a file with
  # the same name:
  while (file_exists ($iter_name))
  {
    $iter_name = $path . $basename . '-' . $i . $fileext;
    $i++;
  }
  return $iter_name;
}

/** Adds after every third digit a dot, so the file size is
  easier to read 
  @return a string in which after every third digit comes a dot. */
function get_readable_size ($size)
{
  $tmp_size = $size;
  $size = "";
  while ($tmp_size > 1000)
  {
    $block = fmod ($tmp_size , 1000);
    $tmp_size = $tmp_size - $block;
    $tmp_size = $tmp_size / 1000;

    if ($size != "")
      $size = sprintf ("%03d.$size", $block);
    else
      $size = sprintf ("%03d", $block);
  } 
  
  $size = sprintf ("%3d.$size",$tmp_size);
  return ($size);
}

/** Deletes an image in the upload folder
  @returns true on success, false otherwise */
function delete_upload()
{
  global $auth;
  global $db;
  global $prefix;

  if (!$auth->is_auth || $auth->user!=='admin')
  {
    echo "You are not allowed to perform this action!\n";
    return FALSE;
  }

  $pref = $db->read_pref();
  $upload_dir = $pref['upload_dir'];
  $fullpath = $upload_dir . "/" . $auth->user . "/";
  $files = glob ( $fullpath . "*");

  $found = FALSE;

  if (count ($files ) == 0)
  {
    return FALSE;
  }
  if (is_dir ($fullpath))
  {
    if ($dh = opendir ($fullpath))
    {
      while (($file = readdir($dh)) !== false)
      {
        if ($file !== '.' && $file !== '..')
        {
	  /** Uhhh.... Bad hack! It is very weird, but when I try to
	    reference the files directly over POST or GET, all the '.'
	    in the filename get replaced by a '_'. This here check's
	    whether there is a file that would be named like the other
	    one if there was such an replacement. Any hints? */
	  $tmp_name = $file;
	  $tmp_name = preg_replace('/\./','_',$tmp_name);
	  if (isset($_REQUEST[$tmp_name]))
	  {
	    $found = TRUE;
            if (!unlink ($fullpath . $file))
            {
              /* We don't return a false, because maybe there is a
	        file from a former installation. In this case, the
	        file can be added through the browser */
	      //return FALSE;
            }
            $sql = "DELETE FROM ".$db->image." 
	            WHERE filename='".$fullpath . $file ."'";
            if (!$db->query($sql))
            {
              return FALSE;
            }

	    /* TODO Delete the images in the /cache folder! */
	  }
        }
      }
      if ($found)
        return TRUE;
    }
  }

  return FALSE;
}

/** Deletes a file or directory recursively
  @param path The directory that should be deleted
  @returns true if all went fine, else false. */
function rm_rf ($path)
{
  if (is_dir ($path))
  {
    if ($dh = opendir ($path))
    {
      while (($file = readdir($dh)) !== false)
      {
        if ($file !== '.' && $file !== '..')
	{
	  if (is_dir ($file))
	  {
	    if ($this->rm_rf ($path ."/". $file) == false)
	      return false;
	    if (rmdir ($path."/".$file) == false)
	      return false;
          }
	  else 
	  {
            if (unlink ($path."/".$file) == false)
	      return false;
	  }
	}
      }
    }
    closedir($dh);
    if (rmdir ($path) == false)
      return false;
  }

  return true;
}

/** Unpacks a zipfile and adds the images to the gallery
  @param path The path in which the images later should 
         be copied
  @param filename The folder in which a file called 'image.zip'
         lays. This file will be extracted.
  @returns nothing yet */
function zipfile_process($path, $filename)
{
  global $auth;

  /* At first we need to extract the files and need to copy
  every file to the destination directory. While doing so, we need
  to ensure, that no existing file will be overwritten. */
  $result = shell_exec("/usr/bin/unzip -d "
	 . $filename . "/ " 
	 . "-j $filename" . "/images.zip");
  $files = array();
  $files = preg_split ("/\n/",$result);
  $count = 0;
  foreach ($files as $file)
  {
    /* This preg_match is specially for for the output of unzip.
    Does not work as expected, but with some coding around, all works
    fine. */
    preg_match ("/\s+inflating:\s+(\S+)\s*$/", $file, $name);
    if (file_exists ($name[1]))
    {
      $zip_content = substr (strrchr ($name[1], '/'), 1);
      $upload_name = $this->get_upload_filename($path, $zip_content);
      copy ($name[1],$upload_name);
      unlink ($name[1]);
      if (update_file($auth->userid, $upload_name) == false)
      {
        /* If something went wrong, we try to delete as much as possible. */
	echo "<div class=\"error\">Uploading $zip_content.</div>\n";
        $this->rm_rf ($filename);
        return false;
      }
      echo "<div class=\"success\">File $zip_content uploaded.</div>\n";
      $count++;
    }
    
    $i++;
  }

  if ($count == 0)
  {
    echo "<div class=\"warning\">No images in $filename/images.zip found!</div>\n";
  }
  
  if ($this->rm_rf ($filename) == false)
  {
    echo "<div class=\"warning\">Cleaning up after unpacking $filename/images.zip.</div>\n";
    return false;
  }
  return true;
}

/** Adds after every third digit a dot, so the file size is
  easier to read 
  @return a string in which after every third digit comes a dot. */
function get_readable_size ($size)
{
  $tmp_size = $size;
  $size = "";
  while ($tmp_size > 1000)
  {
    $block = fmod ($tmp_size , 1000);
    $tmp_size = $tmp_size - $block;
    $tmp_size = $tmp_size / 1000;

    if ($size != "")
      $size = sprintf ("%03d.$size", $block);
    else
      $size = sprintf ("%03d", $block);
  } 
  
  $size = sprintf ("%3d.$size",$tmp_size);
  return ($size);
}

/** Deletes an image in the upload folder
  @returns true on success, false otherwise */
function delete_upload()
{
  global $auth;
  global $db;
  global $prefix;

  if (!$auth->is_auth || $auth->user!=='admin')
  {
    echo "You are not allowed to perform this action!\n";
    return FALSE;
  }

  $pref = $db->read_pref();
  $upload_dir = $pref['upload_dir'];
  $fullpath = $upload_dir . "/" . $auth->user . "/";
  $files = glob ( $fullpath . "*");

  $found = FALSE;

  if (count ($files ) == 0)
  {
    return FALSE;
  }
  if (is_dir ($fullpath))
  {
    if ($dh = opendir ($fullpath))
    {
      while (($file = readdir($dh)) !== false)
      {
        if ($file !== '.' && $file !== '..')
        {
	  /** Uhhh.... Bad hack! It is very weird, but when I try to
	    reference the files directly over POST or GET, all the '.'
	    in the filename get replaced by a '_'. This here check's
	    whether there is a file that would be named like the other
	    one if there was such an replacement. Any hints? */
	  $tmp_name = $file;
	  $tmp_name = preg_replace('/\./','_',$tmp_name);
	  if (isset($_REQUEST[$tmp_name]))
	  {
	    $found = TRUE;
            if (!unlink ($fullpath . $file))
            {
              /* We don't return a false, because maybe there is a
	        file from a former installation. In this case, the
	        file can be added through the browser */
	      //return FALSE;
            }
            $sql = "DELETE FROM ".$db->image." 
	            WHERE filename='".$fullpath . $file ."'";
            if (!$db->query($sql))
            {
              return FALSE;
            }
	  }
        }
      }
      if ($found)
        return TRUE;
    }
  }

  return FALSE;
}

/** Upload all images 
  @return true on success, false otherwise */
function upload_process()
{
  global $db;
  global $auth;
  global $prefix;
 
  if (!$auth->is_auth || $auth->user!='admin')
  {
    echo "<div class=\"warning\">You are not allowed to upload a file.</div>\n";
    return false;
  }
  
  $pref = $db->read_pref();
  $upload_dir = $pref['upload_dir'];

  # At first we must ensure, that the directories exist:
  $path = $upload_dir . "/". $auth->user . "/";
  if (!file_exists($path))
  {
    if (!mkdir ($path))
    {
      echo "Couldn't create directory $path!<br>\n";
      return false;
    }
  }
 
  foreach ($_FILES["images"]["error"] as $key => $error) 
  {
    if ($_FILES["images"]['size'][$key]==0)
      continue;
      
    if ($error == UPLOAD_ERR_OK) {
      $tmp_name = $_FILES["images"]["tmp_name"][$key];
      $name = $_FILES["images"]["name"][$key];
      $upload_name=$this->get_upload_filename($path, $name);

      if (preg_match("/\.zip$/i", $upload_name))
      {
        mkdir ($upload_name);
        if (!move_uploaded_file($tmp_name, $upload_name ."/images.zip"))
	  continue;
        $this->zipfile_process($path, $upload_name);
      }
      else {
        if (!move_uploaded_file($tmp_name, $upload_name))
          continue;
    
        chmod($upload_name, 0644);
        update_file($auth->userid, $upload_name);
        echo "<div class=\"success\">File $name uploaded.</div>\n";
      }
    }
    else
    {
      echo "<div class=\"error\">Error at retrieving file $file</div>\n";
      return false;
    }
  }
 return true;
}

/** Prints the upload form */
function print_form_upload($dir)
{
  echo "<form action=\"./index.php\" method=\"POST\" enctype=\"multipart/form-data\">
<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"8000000\" />
<input type=\"hidden\" name=\"section\" value=\"upload\" />
<table>
  <tr>
    <td>Upload image 1:</td><td><input name=\"images[]\" type=\"file\" /></td>
  </tr><tr>
    <td>Upload image 2:</td><td><input name=\"images[]\" type=\"file\" /></td>
  </tr><tr>
    <td>Upload image 3:</td><td><input name=\"images[]\" type=\"file\" /></td>
  </tr><tr>
    <td>Upload image 4:</td><td><input name=\"images[]\" type=\"file\" /></td>
  </tr><tr>
    <td>Upload image 5:</td><td><input name=\"images[]\" type=\"file\" /></td>
  </tr>
</table>
<input type=\"submit\" value=\"Upload Files\" class=\"submit\" />
</form>";
}

/** Lists all the already uploaded images
  @return nicely formatted html list of the uploaded images */
function print_uploaded()
{
  global $auth;
  global $prefix;
  global $db;

  # Now we want to list all files, the user has already uploaded
  $pref = $db->read_pref();
  $upload_dir = $pref['upload_dir'];
  $fullpath = $upload_dir . "/" . $auth->user . "/";
  $files = glob ( $fullpath . "*");
  if (count ($files) == 0)
    return;
  sort ($files, SORT_STRING);

  echo "<h2>Available images</h2>
<form action=\"./index.php\" method=\"POST\">
  <input type=\"hidden\" name=\"section\" value=\"upload\" />
  <input type=\"hidden\" name=\"delete_upload\" />
  <table border=\"1\">
    <th width=\"5%\"></th>
    <th width=\"5%\">Image</th>
    <th>Name</th>
    <th width=\"20%\">Size</th>
    <th width=\"1%\"></th>
";

  $file_size_sum = 0;
  if (is_dir ($fullpath))
  {
    if ($dh = opendir ($fullpath))
    {
      while (($file = readdir($dh)) !== false)
      {
        if ($file !== '.' && $file !== '..' && !is_dir($file))
        {
          $sql = "SELECT id FROM ".$db->image."
	          WHERE filename='". $fullpath . $file . "'";
          $result = $db->query($sql);
          $v = mysql_fetch_array ($result, MYSQL_ASSOC);

          echo "\t\t<tr>\n";
	  echo "\t\t\t<td align=\"center\" width=\"5\"><input type=\"checkbox\" name=\"".$file."\" /></td>\n";
          echo "\t\t\t<td align=\"center\">\n";
          echo print_mini($v['id']) . "</td>\n";
          echo "\t\t\t<td align=\"center\">$file</td>\n";
          echo "\t\t\t<td align=\"right\">"
             . $this->get_readable_size ( filesize($fullpath . $file) ) 
             . " Bytes</td>\n";
	  echo "<td><div class=\"headerright\"><a href='./index.php?section=upload&delete_upload=&".urlencode($file)."=on'>Delete</a></div></td>\n";
          echo "\t\t</tr>\n";
          $file_size_sum += filesize($fullpath . $file) ;
        }
      }
      echo "\t\t<tr>\n";
      echo "\t\t\t<td></td>\n";
      echo "\t\t\t<td></td>\n";
      echo "\t\t\t<td</td>\n";
      echo "\t\t\t<td align=\"right\">"
	 . $this->get_readable_size ( $file_size_sum )
	 . " Bytes</td>\n";
      echo "\t\t</tr>\n";

      closedir ($dh);
    }
    else
    {
      echo "Couldn't open dir: $fullpath\n";
    }
  }
  else
  {
    echo "$fullpath doesn't seem to be a directory\n";
  }
  echo "</table>\n";
  echo "\t<input type=\"submit\" value=\"Delete\" class=\"delete\" />\n";
  echo "</form>\n";

}


/** Lists all the already uploaded images
  @return nicely formatted html list of the uploaded images */
function print_uploaded()
{
  global $auth;
  global $prefix;
  global $db;

  # Now we want to list all files, the user has already uploaded
  $pref = $db->read_pref();
  $upload_dir = $pref['upload_dir'];
  $fullpath = $upload_dir . "/" . $auth->user . "/";
  $files = glob ( $fullpath . "*");
  if (count ($files) == 0)
    return;
  sort ($files, SORT_STRING);

  echo "<h2>Available images</h2>\n";
  echo "<form action=\"./index.php\" method=\"POST\">\n";
  echo "\t<input type=\"hidden\" name=\"section\" value=\"upload\" />\n";
  echo "\t<input type=\"hidden\" name=\"delete_upload\" />\n";
  echo "\t<table border=\"1\">\n";
  echo "\t<th width=\"5%\"></th>\n";
  echo "\t<th width=\"5%\">Image</th>\n";
  echo "\t<th>Name</th>\n";
  echo "\t<th width=\"20%\">Size</th>\n";
  echo "\t<th width=\"1%\"></th>\n";

  $filesum = 0;
  if (is_dir ($fullpath))
  {
    if ($dh = opendir ($fullpath))
    {
      while (($file = readdir($dh)) !== false)
      {
        if ($file !== '.' && $file !== '..')
        {
          $sql = "SELECT id FROM ".$db->image."
	          WHERE filename='". $fullpath . $file . "'";
          $result = $db->query($sql);
          $v = mysql_fetch_array ($result, MYSQL_ASSOC);

          echo "\t\t<tr>\n";
	  echo "\t\t\t<td align=\"center\" width=\"5\"><input type=\"checkbox\" name=\"".$file."\" /></td>\n";
          echo "\t\t\t<td align=\"center\">\n";
          echo print_mini($v['id']) . "</td>\n";
          echo "\t\t\t<td align=\"center\">$file</td>\n";
          echo "\t\t\t<td align=\"right\">"
             . $this->get_readable_size ( filesize($fullpath . $file) ) 
             . " Bytes</td>\n";
	  echo "<td><div class=\"headerright\"><a href='./index.php?section=upload&delete_upload=&".urlencode($file)."=on'>Delete</a></div></td>\n";
          echo "\t\t</tr>\n";
          $filesum += filesize($fullpath . $file) ;
        }
      }
      echo "\t\t<tr>\n";
      echo "\t\t\t<td></td>\n";
      echo "\t\t\t<td></td>\n";
      echo "\t\t\t<td</td>\n";
      echo "\t\t\t<td align=\"right\">"
	 . $this->get_readable_size ( $filesum )
	 . " Bytes</td>\n";
      echo "\t\t</tr>\n";

      closedir ($dh);
    }
    else
    {
      echo "Couldn't open dir: $fullpath\n";
    }
  }
  else
  {
    echo "$fullpath doesn't seem to be a directory\n";
  }
  echo "</table>\n";
  echo "\t<input type=\"submit\" value=\"Delete\" class=\"delete\" />\n";
  echo "</form>\n";

}

function print_content()
{
  global $auth;
  echo "<h2>Image upload</h2>\n";

  // Check for uploaded images
  $do_upload=false;
  if (isset($_FILES) && isset($_FILES['images'])) 
  {
    foreach ($_FILES['images']['size'] as $key)
    {
      if ($_FILES['images']['size'][$key]>0)
        $do_upload=true;
    }
  }
  // Check whether we want to delete some images
  if (isset($_REQUEST['delete_upload']))
  {
    if (!$this->delete_upload())
    {
      $this->error ("Couldn't delete images!\n");
    }
    else
    {
      $this->success ("Deletion successful!\n");
    }
  }
  if ($do_upload)
    $this->upload_process();

  $result = $this->print_form_upload($this->path) . $this->print_uploaded();
}

}

?>
