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
  $userinfo=$account->get_info($user->get_id());

  $url=new Url();
  $url->add_param('section', 'myaccount');
  $url->add_param('page', MYACCOUNT_TAB_GENERAL);
  $url->add_param('action', 'edit');

  echo "<h3>General</h3>\n";
  echo "<form action=\"./index.php\" method=\"post\">\n";
  echo $url->to_form();
  echo "<table>
  <tr><td>First Name:</td><td><input name=\"firstname\" type=\"text\" value=\"".$userinfo['firstname']."\"></td></tr>
  <tr><td>Last Name:</td><td><input name=\"lastname\" type=\"text\" value=\"".$userinfo['lastname']."\"></td></tr>
  <tr><td>email:</td><td><input name=\"email\" type=\"text\" value=\"".$userinfo['email']."\"></td></tr>
</table>\n";
  echo "<input type=\"submit\" value=\"Save\" />\n";

  echo "</form>\n";
}

function exec_general ()
{
  global $user;
  $action="";

  if (isset($_REQUEST['action']))
    $action=$_REQUEST['action'];

  if($action=='edit')
  {
    $account=new SectionAccount();
    $info=$account->get_info($user->get_id());
    $info['email']=$_REQUEST['email'];
    $info['firstname']=$_REQUEST['firstname'];
    $info['lastname']=$_REQUEST['lastname'];
    if ($account->set_info($info))
      $this->success(_("Update successful!"));
    else
      $this->error(_("Error updating userdata!"));

    return;
  }
}

function print_upload ()
{
  global $user;

  echo "<h3>Upload</h3>\n";
  $url=new Url();
  $url->add_param('section', 'myaccount');
  $url->add_param('page', MYACCOUNT_TAB_UPLOAD);
  $url->add_param('action', 'upload');

  echo "<form action=\"./index.php\" method=\"post\" enctype=\"multipart/form-data\">\n";
  echo $url->to_form();
  echo "<div class=\"upload_files\" \>\n";
  echo "<table id=\"upload_files\">
<tr id=\"upload_file-1\">
<td>Upload image: </td><td><input name=\"images[]\" type=\"file\"/></td>
<td id=\"action-1\" class=\"add\" onclick=\"add_file_input(1)\"></td>
</tr>
</table>\n";
  echo "</div>\n";
  echo "<input type=\"submit\" value=\"Upload\" />\n";

  echo "</form>\n";
}

function exec_upload ()
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
