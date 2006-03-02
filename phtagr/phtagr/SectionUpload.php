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

function print_upload($dir)
{
  # The upload form:
  # echo "<form action=\"./index.php\" method=\"GET\">\n";
  $this->p( "<form action=\"./index.php\" method=\"POST\" enctype=\"multipart/form-data\">\n");
  $this->p( "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"30000000\" />\n");
  $this->p( "<input type=\"hidden\" name=\"section\" value=\"upload\" />\n");
  $this->p( "Upload image: <input name=\"imagefile\" type=\"file\" />\n");
  $this->p( "<input type=\"submit\" value=\"Upload File\" class=\"submit\" />\n");
  $this->p( "</form>\n");
}

function print_content()
{
  global $auth;
  echo "<h2>Image upload</h2>\n";

  if (is_uploaded_file($_FILES['imagefile']['tmp_name'])) {
    $result = upload_process();
    if (!strncmp ($result, "OK:", 3))
    {
      $file = substr ($result, 3);
      $result = update_file($auth->userid,$file);
      if ($result == 1)
      {
        $this->success ("<h3>Successful!</h3>\n");
        $this->p ("File ".$_FILES['imagefile']['name']." was saved as $file\n");
      }
      else
      {
        $this->error ("Unknown error occurred!\n");
      }
    }
    else
    {
      $this->error ($result);
    }
  }
  else
  {
    $result = $this->print_upload($this->path);
  }
}

}

?>
