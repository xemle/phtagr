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

$menu = new SectionMenu();
$menu->add_menu_item("Home", "index.php");
$menu->add_menu_item("Explorer", "index.php?section=explorer");
$menu->add_menu_item("Search", "index.php?section=search");

$db = new Sql();
$db->connect();
if (!($pref=$db->read_pref()))
{
  echo "It looks as if phtagr is not completely configured.\n";
  echo "Please follow <a href=\"./setup.php?action=install\">\n";
  echo "this</a> link to install phtagr.\n";
  $footer = new SectionFooter();
  $page->add_section($footer);
  $page->layout();
  return;
}

$user = new User();
$user->check_session();
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
  $menu->add_menu_item("Setup", "index.php?section=setup");
}

$search= new Search();
$search->from_URL();

//$menu->add_menu_item("Help", "index.php?section=help");
$page->add_section($menu);

if (isset($_REQUEST['section']))
{
  $section=$_REQUEST['section'];
    
  if ($user->is_auth() && 
      $_REQUEST['section']=='account' && isset($_REQUEST['pass-section']))
  {
    $section=$_REQUEST['pass-section'];
  } 

  if ($user->is_logout)
  {
    $section='home';
  }
  
  if($section=='account')
  {
    $account= new SectionAccount();
    $page->add_section($account);
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
    $page->add_section($explorer);
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
    $page->add_section($image);
  } 
  else if($section=='search')
  {
    $search= new SectionSearch();
    $page->add_section($search);
  } 
  else if($section=='browser')
  {
    if ($user->can_browse()) {
      $browser = new SectionBrowser();
      $browser->root='';
      $browser->path='';
      $page->add_section($browser);
    } else {
      $login = new SectionLogin();
      $login->section=$section;
      $login->message="You are not loged in!";
      $page->add_section($login);
    }
  } 
  else if($section=='setup')
  {
    if (!$user->is_admin()) 
    {
      $login=new SectionAccount();
      $login->message='You are not loged in as an admin';
      $login->section='setup';
      $page->add_section($login);
    } else {
      $setup=new SectionSetup();
      $page->add_section($setup);
    }
  }
  else if($section=='upload')
  {
    if ($user->can_upload())
    {
      $upload = new SectionUpload();
      $page->add_section($upload);
    }
  }
  else if($section=='help')
  {
    $help = new SectionHelp();
    $page->add_section($help);
  } 
  else {
    $home = new SectionHome();
    $page->add_section($home);
  }
  //echo "<pre>"; print_r($a);echo "</pre>";
} else {
  $home = new SectionHome();
  $page->add_section($home);
}

$footer = new SectionFooter();
$page->add_section($footer);

//print_r($_SESSION);

$page->layout();

?>
