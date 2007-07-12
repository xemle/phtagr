<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006,2007 Sebastian Felis, sebastian@phtagr.org
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the 
 * License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

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

function print_paths($path)
{
  $fs=$this->_fs;
  $url=new Url();
  $url->add_param('section', 'browser');
  $url->add_param('cd', '/');

  echo "<div class=\"path\">"._("Current path:")."&nbsp;".
    "<a href=\"".$url->get_url()."\">"._("Root")."</a>";

  while ($path[0] == '/')
    $path = substr($path, 1);

  if ($path != "") 
  {
    $paths=explode('/', trim($path));
    $cur = array();
    foreach ($paths as $path)
    {
      array_push($cur, $path);
      echo "&nbsp;/&nbsp;";
      
      $url->add_param('cd', '/'.implode('/', $cur));
      echo "<a href=\"".$url->get_url()."\">".$this->escape_html($path)."</a>\n";
    }
  }
  echo "&nbsp;/&nbsp;</div>";
  unset($url);
}

/** Prints the subdirctories as list with checkboxes */
function print_browser($path)
{
  global $log;
  $log->trace("Current path: ".$path);

  $fs=$this->_fs;

  $this->print_paths($path);

  $url=new Url();
  $url->add_param("section", "browser");
  echo "<form action=\"".$url->get_url()."\" method=\"post\">\n<p>\n";
  $cur=$path;
  if ($cur=='')
    $cur='/';

  $this->input_hidden("cd", $cur);

  echo "<dir>\n";
  echo "<li>";
  $this->input_checkbox("add[]", $cur);
  echo "&nbsp;"._("Current directory");
  echo "</li>\n";

  if (strlen($path) > 0 && $path != '/')
    $subdirs=$fs->get_subdirs($path);
  else
    $subdirs=$fs->get_roots();

  if (count($subdirs) == 0)
  {
    $log->info("No sub directories found for $path");
  }
  else
  {
    $path = $fs->slashify($path);
    foreach($subdirs as $sub) 
    {
      $cd=$path.$sub;

      $url->add_param('cd', $cd);
      $href=$url->get_url();
      echo "<li>";
      $this->input_checkbox("add[]", $cd);
      echo "&nbsp;<a href=\"$href\">$sub</a>";
      echo "</li>\n";
    }
  }
  echo "<br/>\n";
  $this->input_checkbox("create_all_previews", 1);
  echo "&nbsp;"._("Create all previews.")."<br />\n";
  $this->input_checkbox("insert_recursive", 1, true);
  echo "&nbsp;"._("Insert images also from subdirectories.")."<br />\n";

  $this->input_submit(_("Add images"));
  $this->input_reset(_("Clear"));
  
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

    foreach ($_REQUEST['add'] as $path)
      $images=array_merge($images, $fs->find_images($path, $recursive));

    if (count($images))
      asort($images);

    $this->info(sprintf(_("Found %d images"), count($images)));
    foreach ($images as $img)
    {
      $image=new ImageSync();
      $result=$image->import($fs->get_fspath($img), 0);

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
      $this->info(_("Now creating the previews. This can take a while..."));
      foreach ($images as $img)
      {
        $image=new Image();
        $image->init_by_filename($fs->get_fspath($img));
        $previewer=$image->get_preview_handler();
        if ($previewer)
        {
          $previewer->create_previews();
          unset($previewer);
        }
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
    $this->print_browser('/');
  }
}

}

?>
