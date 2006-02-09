<?php

$prefix='./phtagr';

include "$prefix/Auth.php";
include "$prefix/Sql.php";

include "$prefix/PageBase.php";
include "$prefix/SectionHeaderLeft.php";
include "$prefix/SectionMenu.php";
include "$prefix/SectionHome.php";
include "$prefix/SectionFooter.php";
include "$prefix/SectionHelp.php";

include "$prefix/SectionAccount.php";

include "$prefix/SectionExplorer.php";
include "$prefix/SectionBrowser.php";
include "$prefix/SectionSetup.php";


$setup = new PageBase();

$hdr = new SectionBase('header');
$header = new SectionHeaderLeft();
$hdr->add_section($header);
$setup->add_section($hdr);

$menu = new SectionMenu();
//$menu->add_menu_item("Home", "index.php");
//$menu->add_menu_item("Explorer", "index.php?section=explorer");
//$menu->add_menu_item("Browser", "index.php?section=browser");
//$menu->add_menu_item("Login", "index.php?section=login");
//$menu->add_menu_item("Logout", "index.php?section=logout");
$menu->add_menu_item("Help", "setup.php?section=help");


error_reporting(0);
$db = new Sql();
$db->connect();
error_reporting(E_ERROR | E_WARNING | E_PARSE);
  
$auth = new Auth();
$auth->user='admin';
$auth->is_auth=true;
$menu->add_menu_item("Setup", "setup.php?section=setup");
$setup->add_section($menu);

if (isset($_REQUEST['section']))
{
    $section=$_REQUEST['section'];
    
    if($section=='help')
    {
        $help = new SectionHelp();
        $setup->add_section($help);
    } 
    else {
        $ssetup=new SectionSetup();
        $setup->add_section($ssetup);
    }
    //echo "<pre>"; print_r($a);echo "</pre>";
} else {
    $home = new SectionHome();
    $setup->add_section($home);
}

$footer = new SectionFooter();
$setup->add_section($footer);

//print_r($_SESSION);

$setup->layout();

?>
