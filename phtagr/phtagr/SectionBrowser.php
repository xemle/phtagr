<?php

global $prefix;
include_once("$phtagr_prefix/SectionBase.php");
include_once("$phtagr_prefix/Image.php");

class SectionBrowser extends SectionBase
{

/** Relative root directory to emulate chroot() at userspace */
var $root;
var $path;
var $images;

function SectionBrowser()
{
  $this->name="browser";
  $this->root='';
  $this->path=getcwd();
  $this->images=array();
}

/** is_dir function with root prefix
 @param dir Directory without the root directory
 @return true if given dir is a directory. Otherwise false */
function is_dir($dir)
{
  if (is_dir("$this->root".DIRECTORY_SEPARATOR."$dir"))
    return true;
  else
    return false;
}

/** chdir function with root prefix
 If $dir is not a directory, it will change the directory to the root directory
 */
function chdir($dir)
{
  if ($this->is_dir($dir)) 
    chdir("$this->root".DIRECTORY_SEPARATOR."$dir");
  else 
    chdir($this->root);
}

/** opendir function with root prefix
 @param dir Directory without root prefix */
function opendir($dir)
{
  return opendir("$this->root".DIRECTORY_SEPARATOR."$dir");
}

/** is_readable function with root prefix 
 @return true if directory is readable. false otherwise */
function is_readable($dir)
{
  return is_readable("$this->root".DIRECTORY_SEPARATOR."$dir");
}

/** Search images recursively of a directory. 
 The function search only for jpg or JPG files. */
function find_images($dir)
{
  $subdirs=array();
    
  if (!$this->is_readable($dir) || !$this->is_dir($dir)) return;
  $this->chdir($dir);
  if (!$handle = $this->opendir($dir)) return;
  
  while (false != ($file = readdir($handle))) {
    if (!is_readable($file) || $file=='.' || $file=='..') continue;

    $file="$dir".DIRECTORY_SEPARATOR."$file";
    if ($this->is_dir($file)) {
      array_push($subdirs, "$file");
    } else if (strtolower(substr($file, -3, 3))=='jpg') {
      array_push($this->images, "$file");
    }
  }
  closedir($handle);
  
  foreach ($subdirs as $sub) {
    $this->find_images($sub);
  }
}

/** Prints the subdirctories as list with checkboxes */
function print_browser($dir)
{
  if (!$this->is_readable($dir) || !$this->is_dir($dir)) return;
  $this->chdir($dir);
  if (!$handle = $this->opendir($dir)) return;
  
  $subdirs=array();
  
  echo "Path:&nbsp;";
  $dirs=split('/', $dir);
  echo "<a href=\"./index.php?section=browser&amp;cd=/\">root</a>";
  $path='';
  foreach ($dirs as $cd)
  {
    if ($cd == '' || $path == '/') continue;

    $path = "$path".DIRECTORY_SEPARATOR."$cd";
    echo "&nbsp;/&nbsp;";
    
    if ($this->is_dir($path)) {
      echo "<a href=\"./index.php?section=browser&amp;cd=$path\">$cd</a>";
    } else {
      echo "$cd";
    }
  }
  echo "&nbsp;/&nbsp;";
  
  $handle=$this->opendir($dir);
  while (false != ($file = readdir($handle))) {
    if (!is_readable($file) || $file=='.' || $file=='..') continue;
    if (substr($file, 0, 1)=='.') continue; 

    if ($this->is_dir("$dir".DIRECTORY_SEPARATOR."$file")) {
      array_push($subdirs, "$file");
    }
  }
  closedir($handle);

  echo "<form section=\"./index.php\" method=\"post\">\n<p>\n";
  echo "<input type=\"hidden\" name=\"section\" value=\"browser\" />";

  asort($subdirs);
  echo "<input type=\"checkbox\" name=\"add[]\" value=\"$dir\" />&nbsp;. (this dir)<br />\n";
  foreach($subdirs as $sub) 
  {
    if ($dir != '/') {
      $cd="$dir".DIRECTORY_SEPARATOR."$sub";
    } else {
      $cd=$sub;
    }
    echo "<input type=\"checkbox\" name=\"add[]\" value=\"$cd\" />&nbsp;<a href=\"?section=browser&amp;cd=$cd\">$sub</a><br />\n";
  }
  echo "<input type=\"submit\" value=\"Add images\" />&nbsp;";
  echo "<input type=\"reset\" value=\"Clear\" />";
  
  echo "\n<p>\n<form>\n";
}

function print_content()
{
  global $user; 
  echo "<h2>Browser</h2>\n";
  if (isset($_REQUEST['add'])) {
    foreach ($_REQUEST['add'] as $d)
    {
      $this->find_images($d);
    }
    if (count($this->images))
    { 
      asort($this->images);
    }
    printf ("Found %d images<br/>\n", count($this->images));
    foreach ($this->images as $img)
    {
      $image=new Image();
      $return=$image->insert($this->root . $img, 0);
      switch ($return)
      {
      case 0:
        echo "Image '$img' was successfully inserted.<br/>\n";
        break;
      case 1:
        echo "Image '$img' was updated.<br/>\n";
        break;
      case 2:
        echo "Image '$img' is already the database.<br/>\n";
        break;
      default:
        echo "A error occured with file '$img'.<br/>\n";
      }

      unset($image);
    }
    echo "<a href=\"./index.php?section=browser&amp;cd=".$_REQUEST['cd']."\">Search again</a><br/>\n";
  } else if (isset($_REQUEST['cd'])) 
  {
    $this->path=$_REQUEST['cd'];
    $this->print_browser($this->path);
  } else {
    $this->print_browser($this->path);
  }

  //echo '<pre>'; print_r($_REQUEST); echo '</pre>';
}

}

?>
