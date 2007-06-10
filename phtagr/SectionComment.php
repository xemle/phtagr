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
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Comment.php");
include_once("$phtagr_lib/Database.php");

/** Prints preview of an image
  @class SectionComment
*/
class SectionComment extends SectionBase
{

var $_img;
var $_search;

/** Creates a new section for an Comment. 
  @param image The current image object 
  @param pos Current possition of the image in the current search. This value
is optional 
  @note The access rights of the image havte be checked on the output functions
  */
function SectionComment($image, $pos=-1)
{
  $this->SectionBase("comment");
  $this->_img=$image;

  $this->_search=new Search();
  $this->_search->from_url();
  if ($pos>=0)
    $this->_search->set_pos($pos);
}

/** Returns the image object of the section */
function get_image()
{
  return $this->_img;
}

function get_search()
{
  return $this->_search;
}

/** Checks the input of the comment and created a new comment if all
 * requirements fits. It also saved the inputs of an anonymouse user for
 * further comments 
  @param image Current image object */
function exec_request()
{
  global $db, $user, $log;

  $img=$this->get_image();
  if ($img==null || $img->get_id()<0)
    return false;

  if ($_REQUEST['action']=='add_comment')
  {
    if (!$img->can_comment($user))
      return false;

    $name=$_REQUEST['name'];
    $email=$_REQUEST['email'];
    $text=$_REQUEST['text'];
    $userid=$user->get_id();

    if (strlen($text)==0)
      return false;

    // comment is from a user
    if ($user->get_id()>0)
    {
      $name=$user->get_name();
      $email=$user->get_email();
    } 
    elseif (isset($_SESSION['comment_name']))
    {
      $name=$_SESSION['comment_name'];
      $email=$_SESSION['comment_email'];
    }
    // anonymous comment
    elseif (strlen($name)==0 || strlen($email)==0)
    {
      return false;
    }

    $comment=new Comment();
    $id=$comment->create($img->get_id(), $name, $email, $text);

    // Remember anonymouse user within the session
    if ($id>0)
    {
      $_SESSION['comment_name']=$name;
      $_SESSION['comment_email']=$email;
      $this->success(_("Your comment was added"));
    }
    else
    {
      $this->warning(_("Could not add the comment"));
    }
  }
  else if ($_REQUEST['action']=='del_comment' && 
    is_numeric($_REQUEST['comment_id']))
  {
    $comment=new Comment($_REQUEST['comment_id']);
    if ($img->get_userid()==$user->get_id() ||
      $comment->get_userid()==$user->get_id())
    {
      if ($comment->delete())
        $this->success(_("Comment was deleted"));
    }
  }
  return true;
}


function print_comment($id)
{
  global $user;
  $comment=new Comment($id);
  if ($comment->get_id()!=$id)
    return;

  echo "  <li>";
  echo "    <p class=\"who\">";

  $userid=$comment->get_userid();
  if ($userid>0)
  {
    $url=new Search();
    $url->add_param('section', 'explorer');
    $url->set_userid($userid);
    echo "<a href=\"".$url->get_url()."\">".
      $this->escape_html($comment->get_name()).
      "</a>";
  }
  else
  {
    echo $this->escape_html($comment->get_name());
  }

  // head line
  echo " "._("says at ").
    strftime("%Y-%m-%d %H:%M", $comment->get_date(true));

  // Delete button for image owner or comment owner
  $img=$this->get_image();
  if ($user->get_id()>0 &&
    ($img->get_userid()==$user->get_id() ||
    $comment->get_userid()==$user->get_id()))
  {
    $search=new Search();
    $search->from_url();
    $search->add_param('action', 'del_comment');
    $search->add_param('comment_id', $comment->get_id());
    echo "<a href=\"".$search->get_url()."\" class=\"jsbutton\">"._("Delete")."</a>";
  }
  echo  "</p>";

  // comment
  echo "    <p class=\"commenttext\">".
    preg_replace('/\n/', '<br \/>', $this->escape_html($comment->get_comment())).
    "</p>";
  echo "</li>\n";  
}

function print_comments()
{
  $image=$this->get_image();
  if (!$image)
    return;

  $comment=new Comment();
  $ids=$comment->get_comment_ids($image->get_id());

  if (!count($ids))
    return;

  echo "<div class=\"commentlist\">\n";
  echo "<h2>"._("Comments:")."</h2>\n";
  echo "<ul>\n";
  foreach ($ids as $id)
    $this->print_comment($id);
  echo "</ul>\n</div>\n";
}

/** @todo Remind an anonymous commentator within one session */
function print_comment_form()
{
  global $user;

  $img=$this->get_image();
  $id=$img->get_id();

  // Can user comment this image?
  if ($id<0 || !$img->can_comment(&$user))
    return;

  $search=$this->get_search();
  $name=$img->get_name();

  $url=new Url();
  echo "<form id=\"formImage\" action=\"".$url->get_url()."\" method=\"post\" accept-charset=\"UTF-8\"><div>\n";
  echo "<input type=\"hidden\" name=\"section\" value=\"image\" />\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"add_comment\" />\n";
  echo "<input type=\"hidden\" name=\"image\" value=\"$id\" />\n";

  echo $search->get_form();

  echo "<h2>"._("Leave A Comment:")."</h2>\n";

  if ($user->get_id()<0 && !isset($_SESSION['comment_name']))
  {
    $this->label(_("Your Name:"));
    $this->input_text("name", "", 30);
    $this->label(_("Your Email (will not be published):"));
    $this->input_text("email", "", 30);
  }
  $this->label(_("Your Comment:"));
  echo "<textarea name=\"text\" cols=\"50\" rows=\"5\"></textarea>";
  $this->input_submit(_("Add Comment"));
  echo "</div></form>\n";
}

function print_content()
{
  global $db;
  global $user;
 
  $img=$this->get_image();

  if (strlen($_REQUEST['action']))
    $this->exec_request();
 
  if (!$img || $img->get_id()<0) 
  {
    $this->warning(_("Sorry, the requested image is not available."));
    return;
  } elseif (!$img->can_read_preview($user)) {
    $this->warning(_("Sorry, you are not allowed to access this file."));
    return;
  }

  $this->print_comments();

  $this->print_comment_form();
}

}

?>
