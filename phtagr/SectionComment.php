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

function print_comment($id)
{
  $comment=new Comment($id);
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

  echo " "._("says at ").
    strftime("%Y-%m-%d %H:%M", $comment->get_date(true)).
    "</p>";
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

function print_comment_form()
{
  global $user;

  $img=$this->get_image();
  $id=$img->get_id();

  // Can user comment this image?
  if ($id<0 || $img->can_comment(&$user))
    return;

  $search=$this->get_search();
  $name=$img->get_name();

  $url=new Url();
  echo "<form id=\"formImage\" action=\"".$url->get_url()."\" method=\"post\" accept-charset=\"UTF-8\"><div>\n";
  echo "<input type=\"hidden\" name=\"section\" value=\"image\" />\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"comment\" />\n";
  echo "<input type=\"hidden\" name=\"image\" value=\"$id\" />\n";

  echo $search->get_form();

  echo "<h2>"._("Leave A Comment:")."</h2>\n";

  if ($user->get_id()<0)
  {
    echo "<label>"._("Your Name:")."</label><input type=\"text\" name=\"name\" size=\"30\"/>
    <label>"._("Your Email (will not be published):")."</label><input type=\"text\" name=\"email\" size=\"30\" />\n";
  }
  echo "<label>"._("Your Comment:")."</label><textarea name=\"comment\" cols=\"50\" rows=\"5\"></textarea>
    <input type=\"submit\" value=\""._("Add Comment")."\" class=\"submit\" />";
  echo "</div></form>\n";
}

function print_content()
{
  global $db;
  global $user;
 
  if ($_REQUEST['action']=='comment')
  {
    $comment=new Comment();
    $id=$comment->handle_request($this->get_image());
    if ($id<0)
      $this->warn(_("Could not add the comment"));
    else
      $this->success(_("Your comment was added"));
  }
  
  $img=$this->get_image();
  if (!$img || $img->get_id()<0) 
  {
    $this->warning(_("Sorry, the requested image is not available."));
    return;
  } elseif (!$img->can_preview($user)) {
    $this->warning(_("Sorry, you are not allowed to access this file."));
    return;
  }

  $this->print_comments();

  $this->print_comment_form();
}

}

?>
