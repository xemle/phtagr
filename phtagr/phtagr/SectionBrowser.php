<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/Filesystem.php");
include_once("$phtagr_lib/Url.php");
include_once("$phtagr_lib/ImageSync.php");

class SectionBrowser extends SectionBase
{

var $_fs;

function SectionBrowser()
{
  $this->SectionBase("browser");
  $this->_fs=new Filesystem();
}

function add_root($root, $alias)
{
  $this->_fs->add_root($root, $alias);
}

function reset_roots()
{
  $this->_fs->reset_roots();
}

function print_paths($dir)
{
  $fs=$this->_fs;
  $url=new Url();
  $url->add_param('section', 'browser');

  echo "<div class=\"path\">"._("Current path:")."&nbsp;".
    "<a href=\"".$url->get_url()."\">"._("Root")."</a>";

  $path='';
  if ($dir!='')
  {
    if (DIRECTORY_SEPARATOR!='\\')
    {
      $parts=split(DIRECTORY_SEPARATOR, $dir);
    } else {
      $dir=str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $dir);
      $parts=split(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, $dir);
    }
    foreach ($parts as $part)
    {
      if ($part=='' || $path=='/') continue;
  
      if ($path!='')
        $path.=DIRECTORY_SEPARATOR.$part;
      else 
      {
        if ($fs->get_num_roots()<2)
          $path=DIRECTORY_SEPARATOR.$part;
        else
          $path=$part;
      }
      echo "&nbsp;/&nbsp;";
      
      $url->add_param('cd', $path);
      echo "<a href=\"".$url->get_url()."\">".$this->escape_html($part)."</a>\n";
    }
  }
  echo "&nbsp;/&nbsp;</div>";
  unset($url);
}

/** Prints the subdirctories as list with checkboxes */
function print_browser($dir)
{
  $fs=$this->_fs;
  $url=new Url();
  $url->add_param('section', 'browser');

  $this->print_paths($dir);

  echo "<form action=\"./index.php\" method=\"post\">\n<p>\n";
  echo $url->get_form();

  $cur=$dir;
  if ($cur=='')
    $cur=DIRECTORY_SEPARATOR;
  echo "<input type=\"checkbox\" name=\"add[]\" value=\"".$this->escape_html($cur)."\" />&nbsp;. (this dir)<br />\n";

  $alias=$dir; // alias changes, if only one root is set
  if ($fs->is_dir($dir))
  {
    $subdirs=$fs->get_subdirs($dir);
  } else {
    $subdirs=$fs->get_roots();
    // OK, just one root is set. Hide alias name
    if (count($subdirs)==1) 
    {
      $dir=$subdirs[0];
      $subdirs=$fs->get_subdirs($dir);
      $alias='';
    }
  }

  foreach($subdirs as $sub) 
  {
    if ($dir!='')
      $cd=$alias.DIRECTORY_SEPARATOR.$sub;
    else 
      $cd=$sub;

    $url->add_param('cd', $cd);
    $href=$url->get_url();
    echo "<input type=\"checkbox\" name=\"add[]\" value=\"".$this->escape_html($cd)."\" />&nbsp;<a href=\"$href\">$sub</a><br />\n";
  }
  echo "<br/>\n";
  echo "<input type=\"checkbox\" name=\"create_all_previews\"/>&nbsp;"._("Create all previews.")."<br />\n";
  echo "<input type=\"checkbox\" name=\"insert_recursive\" checked=\"checked\" />&nbsp;"._("Insert images also from subdirectories.")."<br />\n";
  echo "<input type=\"submit\" class=\"submit\" value=\""._("Add images")."\" />&nbsp;";
  echo "<input type=\"reset\" class=\"reset\" value=\""._("Clear")."\" />";
  
  echo "\n</p>\n</form>\n";
}

function print_content()
{
  global $user; 
  $fs=$this->_fs;
  echo "<h2>"._("Browser")."</h2>\n";
  if (isset($_REQUEST['add'])) {
    $images=array();

    $recursive=false;
    if (isset($_REQUEST['insert_recursive']))
      $recursive=true;

    foreach ($_REQUEST['add'] as $d)
    {
      if ($fs->is_windows())
        $d=str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $d);
      $images=array_merge($images, $fs->find_images($d, $recursive));
    }

    if (count($images))
      asort($images);

    printf (_("Found %d images")."<br/>\n", count($images));
    foreach ($images as $img)
    {
      $image=new ImageSync();
      $result=$image->import($fs->get_realname($img), 0);

      switch ($result)
      {
      case 0:
        printf(_("Image '%s' was successfully inserted.")."<br/>\n", $img);
        break;
      case 1:
        printf(_("Image '%s' was updated.")."<br/>\n", $img);
        break;
      case 2:
        printf(_("Image '%s' is already the database.")."<br/>\n", $img);
        break;
      default:
        printf(_("A error occured with file '%s'.")."<br/>\n", $img);
      }

      unset($image);
    }
    if ($_REQUEST['create_all_previews'])
    {
      echo _("Now creating the previews. This can take a while...")."<br/>";
      foreach ($images as $img)
      {
        $thumb=new Thumbnail();
        $thumb->init_by_filename($fs->get_realname($img));
        $thumb->create_previews();
        unset($thumb);
      }
      $this->info(_("All previews successfully created"));
    }
    $this->info(_("Images inserted"));
    $url=new Url();
    $url->add_param('section', 'browser');
    $url->add_param('cd', $_REQUEST['cd']);
    $href=$url->get_url();
    echo "<br/><a href=\"$href\">"._("Search again")."</a><br/>\n";
  } else if (isset($_REQUEST['cd'])) 
  {
    $this->print_browser($_REQUEST['cd']);
  } else {
    $this->print_browser('');
  }
}

}

?>
