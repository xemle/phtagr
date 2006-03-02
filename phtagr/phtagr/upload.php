<?php

function upload_process()
{
  global $db;
  global $auth;
 
  $pref = $db->read_pref();
  $upload_dir = $pref['upload_dir'];
  
  if ($auth->is_auth && $auth->user=='admin')
  {
    # At first we must ensure, that the directories exist:
    $fullpath = $upload_dir . "/". $auth->user . "/";
    if (!file_exists($fullpath))
    {
      if (!mkdir ($fullpath))
      {
        return "Couldn't create directory $fullpath!<br>\n";
      }
    }
   
    $filename_full = $_FILES['imagefile']['name'];
    $fileext = substr (strrchr ($filename_full, '.'), 0);
    $filename = substr ($filename_full, 0, strlen ($filename_full) - strlen ($fileext));
    $iter_name = $fullpath . $filename . $fileext;
    $i = 0;
    # Then we need to check, whether there is already a file with
    # the same name:
    while (file_exists ($iter_name))
    {
      $iter_name = $fullpath . $filename . '-' . $i . $fileext;
      $i++;
    }
    
    if (move_uploaded_file($_FILES['imagefile']['tmp_name'], $iter_name))
    {
      chmod($itername, 644);
      return "OK:".$iter_name;
    
    }
    else
    {
      return "<div class=\"error\">Error retrieving file " . $_FILES['imagefile']['name']. "</div>\n";
    }
  }
  else
  {
    return "Possible file upload attack!\n";
  }
}

?>
