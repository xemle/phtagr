<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/SectionAccount.php");
include_once("$phtagr_lib/SectionUpload.php");
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Thumbnail.php");

define("MYACCOUNT_TAB_GENERAL", "1");
define("MYACCOUNT_TAB_UPLOAD", "2");

class SectionMyAccount extends SectionBase
{

function SectionMyAccount()
{
  $this->SectionBase("myaccount");
}

function print_general ()
{
  global $user;
  $account=new SectionAccount();

  $url=new Url();
  $url->add_param('section', 'myaccount');
  $url->add_param('page', MYACCOUNT_TAB_GENERAL);
  $url->add_param('action', 'edit');

  echo "<h3>General</h3>\n";
  echo "<form action=\"./index.php\" method=\"post\">\n";
  echo $url->to_form();
  echo "<table>
  <tr>
    <td>"._("First Name:")."</td>
    <td><input type=\"text\" name=\"firstname\" value=\"".$user->get_firstname()."\" /><td>
  </tr>
  <tr>
    <td>"._("Last Name:")."</td>
    <td><input type=\"text\" name=\"lastname\" value=\"".$user->get_lastname()."\" /><td>
  </tr>
  <tr>
    <td>"._("Email:")."</td>
    <td><input type=\"text\" name=\"email\" value=\"".$user->get_email()."\" /><td>
  </tr>
  <tr>
    <td></td>
    <td><input type=\"submit\" class=\"submit\"value=\"Save\"/>
      <input type=\"reset\" class=\"reset\" value=\"Reset\"/></td>
  </tr>
</table>
</form>\n\n";
}

function exec_general ()
{
  global $user;

  $action="";
  if (isset($_REQUEST['action']))
    $action=$_REQUEST['action'];

  if($action=='edit')
  {
    if (isset($_REQUEST['email']))
      $user->set_email($_REQUEST['email']);
    if (isset($_REQUEST['firstname']))
      $user->set_firstname($_REQUEST['firstname']);
    if (isset($_REQUEST['lastname']))
      $user->set_lastname($_REQUEST['lastname']);

    $user->commit_changes();

    return;
  }
}

function print_upload ()
{
  global $user;

  echo "<h3>"._("Upload")."</h3>\n";
  $url=new Url();
  $url->add_param('section', 'myaccount');
  $url->add_param('page', MYACCOUNT_TAB_UPLOAD);
  $url->add_param('action', 'upload');

  $qslice=$user->get_qslice();
  $qinterval=$user->get_qinterval();
  $quota=$user->get_quota();
  $used=$user->get_image_bytes(true);
  $upload_max=$user->get_upload_max();
  printf(_("You have %.3f MB already uploaded. Your total limit is %.3f MB. Currently you are allowed to upload %.3f MB."), $used/(1024*1024), $quota/(1024*1024), $upload_max/(1024*1024));

  echo "<form action=\"./index.php\" method=\"post\" enctype=\"multipart/form-data\">\n";
  echo $url->to_form();
  echo "<div class=\"upload_files\" \>\n";
  echo "<table id=\"upload_files\">
<tr id=\"upload_file-1\">
<td>"._("Upload image:")."</td><td><input name=\"images[]\" type=\"file\"/></td>
<td id=\"action-1\" class=\"add\" onclick=\"add_file_input(1)\"></td>
</tr>
</table>\n";
  echo "</div>\n";
  echo "<input type=\"submit\" class=\"submit\" value=\"Upload\" />\n";

  echo "</form>\n";
}

function exec_upload()
{
  $upload=new SectionUpload();
  $upload->upload_process();
}

function print_content()
{
  global $db;
  global $user;
  global $search;
  
  echo "<h2>"._("My Account")."</h2>\n";
  $tabs2=new SectionMenu('tab', _("Actions:"));
  $tabs2->add_param('section', 'myaccount');
  $tabs2->set_item_param('page');

  $tabs2->add_item(MYACCOUNT_TAB_GENERAL, _("General"), MYACCOUNT_TAB_GENERAL==$curid );
  $tabs2->add_item(MYACCOUNT_TAB_UPLOAD, _("Upload"));
  $tabs2->print_sections();
  $cur=$tabs2->get_current();

  echo "\n";

  if (isset ($_REQUEST["action"]))
  {
    // @todo: Get rid of the <br> but keep the success boxes in correct
    //        positions.
    echo "<br/>\n";

    switch ($cur)
    {
    case MYACCOUNT_TAB_UPLOAD: 
      $this->exec_upload();
      break;
    case MYACCOUNT_TAB_GENERAL:
      $this->exec_general();
      break;
    default:
      $this->warning(_("No valid action found"));
      break;
    }

    $url=new Url();
    $url->add_param('section', 'myaccount');
    $url->add_param('page', $cur);
    $href=$url->to_URL();
    echo "<div class=\"button\">
<a href=\"$href\">Back</a>
</div>\n";
    echo "<p></p>\n";
 
    return; // Uncomment this if you still want to see the contents.
  }

  switch ($cur)
  {
  case MYACCOUNT_TAB_UPLOAD: 
    $this->print_upload (); 
    break;
  default:
    $this->print_general (); 
    break;
  }
}

}
?>
