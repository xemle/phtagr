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

$time_start=microtime();
session_name('sid');
session_start();

include_once("$phtagr_lib/Database.php");
include_once("$phtagr_lib/User.php");
include_once("$phtagr_lib/Config.php");
include_once("$phtagr_lib/Logger.php");

include_once("$phtagr_lib/Search.php");
include_once("$phtagr_lib/Edit.php");

include_once("$phtagr_lib/PageBase.php");
include_once("$phtagr_lib/SectionLogo.php");
include_once("$phtagr_lib/SectionQuickSearch.php");
include_once("$phtagr_lib/SectionMenu.php");
include_once("$phtagr_lib/SectionHome.php");
include_once("$phtagr_lib/SectionFooter.php");
include_once("$phtagr_lib/SectionHelp.php");

include_once("$phtagr_lib/SectionAccount.php");

include_once("$phtagr_lib/SectionExplorer.php");
include_once("$phtagr_lib/SectionBulb.php");
include_once("$phtagr_lib/SectionImage.php");
include_once("$phtagr_lib/SectionComment.php");
include_once("$phtagr_lib/SectionBrowser.php");
include_once("$phtagr_lib/SectionSearch.php");
include_once("$phtagr_lib/SectionUpload.php");
include_once("$phtagr_lib/SectionInstall.php");
include_once("$phtagr_lib/SectionAdmin.php");
include_once("$phtagr_lib/SectionMyAccount.php");
include_once("$phtagr_lib/SectionUnconfigured.php");

$db = new Database();

// initialize the basic objects
$db->connect();
if ($db->is_connected())
{
  $conf = new Config(0);
  $log=new Logger();
  if ($conf->get('log.enabled', 0)==1)
  {
    $log->set_level($conf->get('log.level', LOG_INFO));
    $log->set_type($conf->get('log.type', LOG_DB),
      $conf->get('log.filename', ''));
    // drop old messages
    $log->drop_db_logs(3600*60, 3600*7, 3600, 1800, 3600*7, 3600*3);
    $log->enable();
  }
  $user = new User();
  $user->check_session();
}
else
{
  $conf=new Config(0);
  $log=new Logger(LOG_SESSION, LOG_WARN);
  $log->enable();
  $user = new User();
}

$page = new PageBase("phTagr");

$hdr = new SectionBase('header');
$logo = new SectionLogo();
$hdr->add_section($logo);
$qsearch = new SectionQuickSearch();
$hdr->add_section(&$qsearch);

$page->add_section(&$hdr);

$body = new SectionBase("body");
$page->add_section(&$body);

$menu = new SectionMenu();
$body->add_section(&$menu);

$cnt = new SectionBase("content");
$body->add_section(&$cnt);

$footer = new SectionBase("footer");
$fcnt = new SectionFooter("content");
$footer->add_section(&$fcnt);
$page->add_section(&$footer);

$section="";
$action="";

if (isset($_REQUEST['section']))
  $section=$_REQUEST['section'];
if (isset($_REQUEST['action']))
  $action=$_REQUEST['action'];

// Installation procedure 
if ($section=="install")
{
  $log->set_type(LOG_FILE, getcwd().DIRECTORY_SEPARATOR.'phtagr.log');
  $log->set_level(LOG_DEBUG);
  $log->enable();
  $install = new SectionInstall();
  $cnt->add_section(&$install);
  $page->layout();
  $log->disable();
  return;
}

// Error
if (!$db->is_connected() && $section!="install")
{
  $sec = new SectionUnconfigured();
  $cnt->add_section(&$sec);
    
  $page->layout();
  $log->disable();
  return;
}

$menu=new SectionMenu('menu', _("Menu"));
$menu->set_item_param('section');

$menu->add_item('home', _("Home"));
$menu->add_item('explorer', _("Explorer"));
if ($user->is_member() && $user->get_num_users()>1)
{
  $submenu=new SectionMenu('menu','');
  $submenu->add_param('section', 'explorer');
  $submenu->set_item_param('user');
  $submenu->add_item($user->get_id(), _("My images"));
  $menu->add_submenu('explorer', $submenu);
}
$menu->add_item('search', _("Search"));

if ($user->can_browse())
{
  $menu->add_item('browser', _("Browser"));
}
if ($user->is_member())
{
  $menu->add_item('myaccount', _("MyAccount"));
}
if ($user->is_admin())
{
  $menu->add_item('admin', _("Administration"));
}

if (isset($_REQUEST['section']))
{
  $section=$_REQUEST['section'];
    
  if (!$user->is_anonymous() && 
      $_REQUEST['section']=='account' && isset($_REQUEST['goto']))
  {
    // We need to unset the action field otherwise we might
    // execute an action we did not intend to perform.
    unset ($_REQUEST['action']);
    $section=$_REQUEST['goto'];
  } 

  if ($_REQUEST['section']=='account' && $_REQUEST['action']=='logout')
  {
    $section='home';
  }
  
  if($section=='account')
  {
    $account=new SectionAccount();
    $cnt->add_section(&$account);
  }
  else if($section=='explorer')
  {
    if($_REQUEST['action']=='edit')
    {
      $edit=new Edit();
      $edit->execute();
      unset($edit);
    }
    $explorer= new SectionExplorer();
    $cnt->add_section(&$explorer);
    $bulb = new SectionBulb();
    $body->add_section(&$bulb);
  } 
  else if($section=='image' && isset($_REQUEST['id']))
  {
    if($_REQUEST['action']=='edit')
    {
      $edit=new Edit();
      $edit->execute();
      unset($edit);
    }
    $image=new Image(intval($_REQUEST['id']));
    $sec_image= new SectionImage($image, intval($_REQUEST['pos']));
    $cnt->add_section(&$sec_image);
    $sec_comment=new SectionComment($image, intval($_REQUEST['pos']));
    $cnt->add_section($sec_comment);
    $bulb = new SectionBulb();
    $body->add_section(&$bulb);
  } 
  else if($section=='search')
  {
    $seg_search= new SectionSearch();
    $cnt->add_section(&$seg_search);
  } 
  else if($section=='browser')
  {
    if ($user->can_browse()) {
      $browser = new SectionBrowser();
      $cnt->add_section(&$browser);
    } else {
      $login = new SectionAccount();
      $login->section=$section;
      $login->message=_("You are not loged in!");
      $cnt->add_section(&$login);
    }
  }
  else if($section=='myaccount')
  {
    if ($user->is_member())
    {
      $myaccount=new SectionMyAccount();
      $cnt->add_section(&$myaccount);
    }
    else
    {
      $login=new SectionAccount();
      $login->message=_('You have to be logged in to access the queried page.');
      $login->section='myaccount';
      $cnt->add_section(&$login);
    }
  } 
  else if($section=='admin')
  {
    if (!$db->link || $user->is_admin()) 
    {
      $admin=new SectionAdmin();
      $cnt->add_section(&$admin);
    } else {
      $login=new SectionAccount();
      $login->message=_('You have to be logged in to access the queried page.');
      $login->section='admin';
      $cnt->add_section(&$login);
    }
  }
  else if($section=='help')
  {
    $help = new SectionHelp();
    $cnt->add_section(&$help);
  }
  else if($section=='install')
  {
    $install = new SectionInstall();
    $cnt->add_section(&$install);
  }
  else {
    $home = new SectionHome();
    $cnt->add_section(&$home);
  }
} else {
  $home = new SectionHome();
  $cnt->add_section(&$home);
}

$page->layout();

// statistics for logger
$gentime=sprintf("%.3f", abs(microtime()-$time_start));
$log->warn("phtagr runs for $gentime seconds", -1, $user->get_id());
$log->disable();

?>
