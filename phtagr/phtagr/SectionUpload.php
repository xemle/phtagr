<?php

global $prefix;

include_once("$prefix/SectionBody.php");
include_once("$prefix/Auth.php");
include_once("$prefix/upload.php");
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
  $fileext = substr (strrchr ($filename_full, '.'), 0);
  $basename = substr ($filename_full, 0, strlen ($filename_full) - strlen ($fileext));
  $iter_name = $path . $filename . $fileext;
  $i = 0;
  
  # Then we need to check, whether there is already a file with
  # the same name:
  while (file_exists ($iter_name))
  {
    $iter_name = $path . $filename . '-' . $i . $fileext;
    $i++;
  }
  return $iter_name;
}

/** Upload all images 
  @return true on success, false otherwise */
function upload_process()
{
  global $db;
  global $auth;
 
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
      if (!move_uploaded_file($tmp_name, $upload_name))
        continue;
      
      chmod($upload_name, 644);
      update_file($auth->userid, $upload_name);
      echo "<div class=\"success\">File $name uploaded.</div>\n";
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
<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"30000000\" />
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
  if ($do_upload)
    $this->upload_process();
  else
    $result = $this->print_form_upload($this->path);
}

}

?>
