<?php

session_start();

$prefix='./phtagr';

include "$prefix/User.php";
include "$prefix/Sql.php";
include "$prefix/Search.php";
include "$prefix/Edit.php";

include "$prefix/PageBase.php";
include "$prefix/SectionHeaderLeft.php";
include "$prefix/SectionHeaderRight.php";
include "$prefix/SectionMenu.php";
include "$prefix/SectionHome.php";
include "$prefix/SectionFooter.php";
include "$prefix/SectionHelp.php";

include "$prefix/SectionAccount.php";

include "$prefix/SectionExplorer.php";
include "$prefix/SectionImage.php";
include "$prefix/SectionBrowser.php";
include "$prefix/SectionSearch.php";
include "$prefix/SectionSetup.php";
include "$prefix/SectionUpload.php";

$page = new PageBase("page");

$hdr = new SectionBase('header');
$headerleft = new SectionHeaderLeft();
$hdr->add_section($headerleft);
$headerright = new SectionHeaderRight();
$hdr->add_section(& $headerright);

$page->add_section($hdr);

$body = new SectionBase("body");

$menu = new SectionMenu();
$menu->add_menu_item("Home", "index.php");
$menu->add_menu_item("Explorer", "index.php?section=explorer");
$menu->add_menu_item("Search", "index.php?section=search");

$db = new Sql();
if (!$db->connect())
{
  echo "It looks as if phtagr is not completely configured.\n";
  echo "Please follow <a href=\"./setup.php?action=install\">\n";
  echo "this</a> link to install phtagr.\n";
  $footer = new SectionFooter();
  $page->add_section($footer);
  $page->layout();
  exit;
}

$user = new User();
$user->check_session();

$pref=$db->read_pref($user->get_userid());

if ($user->can_browse())
{
  $menu->add_menu_item("Browser", "index.php?section=browser");
}
if ($user->can_upload())
{
  $menu->add_menu_item("Upload", "index.php?section=upload");
}
if ($user->is_admin())
{
  $menu->add_menu_item("Account", "index.php?section=account&amp;action=new");
  $menu->add_menu_item("Setup", "index.php?section=setup");
}

$search= new Search();
$search->from_URL();

//$menu->add_menu_item("Help", "index.php?section=help");
$body->add_section($menu);

$cnt = new SectionBase("content");

if (isset($_REQUEST['section']))
{
  $section=$_REQUEST['section'];
    
  if ($user->is_member() && 
      $_REQUEST['section']=='account' && isset($_REQUEST['pass-section']))
  {
    $section=$_REQUEST['pass-section'];
  } 

  if ($_REQUEST['section']=='account' && $_REQUEST['action']=='logout')
  {
    $section='home';
  }
  
  if($section=='account')
  {
    $account= new SectionAccount();
    $cnt->add_section($account);
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
    $cnt->add_section($explorer);
  } 
  else if($section=='image')
  {
    if($_REQUEST['action']=='edit')
    {
      $edit=new Edit();
      $edit->execute();
      print_r($edit);
      unset($edit);
    }
    $image= new SectionImage();
    $cnt->add_section($image);
  } 
  else if($section=='search')
  {
    $search= new SectionSearch();
    $cnt->add_section($search);
  } 
  else if($section=='browser')
  {
    if ($user->can_browse()) {
      $browser = new SectionBrowser();
      $browser->root='';
      $browser->path='';
      $cnt->add_section($browser);
    } else {
      $login = new SectionLogin();
      $login->section=$section;
      $login->message="You are not loged in!";
      $cnt->add_section($login);
    }
  } 
  else if($section=='setup')
  {
    if (!$user->is_admin()) 
    {
      $login=new SectionAccount();
      $login->message='You are not loged in as an admin';
      $login->section='setup';
      $cnt->add_section($login);
    } else {
      $setup=new SectionSetup();
      $cnt->add_section($setup);
    }
  }
  else if($section=='upload')
  {
    if ($user->can_upload())
    {
      $upload = new SectionUpload();
      $cnt->add_section($upload);
    }
  }
  else if($section=='help')
  {
    $help = new SectionHelp();
    $cnt->add_section($help);
  } 
  else {
    $home = new SectionHome();
    $cnt->add_section($home);
  }
  //echo "<pre>"; print_r($a);echo "</pre>";
} else {
  $home = new SectionHome();
  $cnt->add_section($home);
}

$body->add_section($cnt);
$page->add_section($body);

$footer = new SectionBase("footer");
$cnt = new SectionFooter("content");
$footer->add_section($cnt);
$page->add_section($footer);

$page->layout();


/*
echo "<pre>";
print_r($_SESSION);
echo "</pre>\n";
*/
?>
