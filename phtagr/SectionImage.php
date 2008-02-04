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
include_once("$phtagr_lib/Database.php");

/** Prints preview of an image
  @class SectionImage
*/
class SectionImage extends SectionBase
{

var $_img;
var $_search;

/** Creates a new section for an image. 
  @param image The current image object 
  @param pos Current possition of the image in the current search. This value
is optional 
  @note The access rights of the image havte be checked on the output functions
  */
function SectionImage($image, $pos=-1)
{
  $this->SectionBase("image");
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

/** @return Returns an hash of tags from the displayed images of the current 
 * page.  The hash key is the tag itself and the hash value is the number of 
 * occurences of the tag */
function get_tags()
{
  $img=$this->get_image();
  if (!$img)
    return null;

  $tags=$img->get_tags();
  $hits=array();
  foreach ($tags as $tag)
    $hits[$tag]=1;
  return $hits;
}

/** @return Returns an hash of sets from the displayed images of the current 
 * page.  The hash key is the set itself and the hash value is the number of 
 * occurences of the set */
function get_categories()
{
  $img=$this->get_image();
  if (!$img)
    return null;

  $cats=$img->get_categories();
  $hits=array();
  foreach ($cats as $cat)
    $hits[$cat]=1;
  return $hits;
}

/** @return Returns an hash of locations from the displayed images of the 
 * current page.  The hash key is the location itself and the hash value is 
 * the number of occurences of the location */
function get_locations()
{
  $img=$this->get_image();
  if (!$img)
    return null;

  $locs=$img->get_locations();
  $hits=array();
  foreach ($locs as $loc)
    $hits[$loc]=1;
  return $hits;
}

/** Prints prev, up, and next buttons */
function print_navigation($search)
{
  global $db;
  
  if ($search==null)
    return;
  $img=$this->get_image();
  if (!$img)
    return;

  $pos=$search->get_pos();
  $page_size=$search->get_page_size();

  $cur_pos=$pos;
  if ($pos>1)
    $cur_pos-=1;
  else 
    $cur_pos=0;

  $search->set_pos($cur_pos);
  $search->set_page_size($pos-$cur_pos+2);
  $search->set_imageid(0);
  $sql=$search->get_query(2);

  $result=$db->query($sql);
  // we need at least 2 lines.
  if (!$result || mysql_num_rows($result)<2)
    return;

  // restore old page style
  $search->set_page_size($page_size);

  echo "\n<div class=\"navigator\">\n";
  while ($row=mysql_fetch_row($result))
  {
    $id=$row[0];
    $search->set_pos($cur_pos);
    // skip current image
    if ($cur_pos==$pos)
    {
      $search->add_param('section', 'explorer');
      $search->del_param('id');
      $search->set_anchor('img-'.$img->get_id());
      $url=$search->get_url();
      $search->del_anchor();
      echo "<a href=\"$url\">"._("up")."</a>&nbsp;";
      $cur_pos++;
      continue;
    }

    $search->add_param('section', 'image');
    $search->set_imageid($id);
    $url=$search->get_url();
    
    if ($cur_pos<$pos)
      echo "<a href=\"$url\">"._("prev")."</a>&nbsp;";
    else
      echo "<a href=\"$url\">"._("next")."</a>";
    
    $cur_pos++;
  }
  echo "</div>\n";
}

function from($return=false)
{
  global $db;
  global $user;

  $num=$user->get_num_users();
  if ($num==1)
    return;

  $img=$this->get_image();
  if (!$img)
    return;

  $search=new Search();
  $search->add_param('section', 'explorer');
  $search->set_userid($img->get_userid());
  if ($user->get_id() != $img->get_userid()) 
  {
    $name=$user->get_name_by_id($img->get_userid());
  } else {
    $name=$user->get_name();
  }

  return $this->output("<div class=\"from\">by <a href=\"".$search->get_url()."\">$name</a></div>\n", $return);
}

/** Print the caption of an image. 
  @param docut True if a long caption will be shorted. False if the whole
  caption will be printed. Default true */
function caption($docut=true, $return=false)
{
  global $user;

  $img=$this->get_image();
  if (!$img)
    return;

  $id=$img->get_id();
  $caption=$img->get_caption();
  
  $can_write_caption=$img->can_write_caption($user);
  
  $output="<div class=\"caption\" id=\"caption-$id\">";
  // the user can not edit the image
  if (!$can_write_caption)
  {
    if ($caption!="")
      $output.=$this->_cut_caption($id, &$caption);

    $output.="</div>\n";
    return $this->output($output, $return);
  }
  
  // The user can edit the image
  if ($caption != "") 
  {
    if ($docut=true)
      $text=$this->_cut_caption($id, &$caption);
    else {
      $text=$this->escape_html($caption);
    }

    $output.="$text <a href=\"javascript:void();\" class=\"jsbutton\" onclick=\"edit_caption($id) \">"._("edit")."</a>";
  }
  else
  {
    $output.=" <span onclick=\"edit_caption($id)\">"._("Click here to add a caption")."</span>";
  }
  
  $output.="</div>\n";
  return $this->output($output, $return);
}

/** Cut the caption by words. If the length of the caption is longer than 20
 * characters, the caption will be cutted into words and reconcartenated to the
 * length of 20.  
  @todo Improve the word split function through a trim() and preg_split() */
function _cut_caption($id, $caption)
{
  $caption=htmlspecialchars($caption);

  if (strlen($caption)< 50) 
    return $caption;

  $words=split(" ", $caption);
  foreach ($words as $word)
  {
    if (strlen($result) > 30)
      break;

    $result.=" $word";
  }
  $result="<span id=\"caption-text-$id\">".$result;
  $result.=" <a href=\"javascript:void();\" class=\"jsbutton\" onclick=\"print_caption($id)\">[...]</a>";
  $result.="</span>";
  return $result;
}

function clicks($return=false)
{
  $img=$this->get_image();
  if (!$img)
    return;

  $ranking=sprintf("%.3f", $img->get_ranking());
  $output=sprintf(_("%d (Popularity: %.3f)"), $img->get_clicks(), $ranking);

  return $this->output($output, $return);
}

function voting($return=false)
{
  global $user;

  $img=$this->get_image();
  if (!$img)
    return;

  $output="";

  $id=$img->get_id();
  $votes=$img->get_votes();
  $voting=sprintf("%.2f", $img->get_voting());

  $can_vote=false;
  if (!isset($_SESSION['img_voted'][$id]) && $_SESSION['nrequests']>1)
  {
    $can_vote=true;
    $vote_url=clone $this->get_search();
    $vote_url->add_param('action', 'edit');
    $vote_url->add_param('image', $id);
    $vote_url->set_anchor('img-'.$id);
  }

  $none=$user->get_theme_dir().'/vote-none.png';
  $set=$user->get_theme_dir().'/vote-set.png';

  $output.="<div class=\"voting\"><p>\n";
  for ($i=0; $i<=VOTING_MAX; $i++)
  {
    $title='';
    $fx='';
    if ($can_vote) {
      $vote_url->add_param('voting', $i);
      $url=$vote_url->get_url();
      $output.="<a href=\"$url\">";
      $title=" title=\"".
        sprintf(_("Vote the image with %d points!"), $i)."\"";
      $fx=" onmouseover=\"vote_highlight($id, $voting, $i)\" onmouseout=\"vote_reset($id, $voting)\"";
    } 

    if ($voting>0 && $i<=$voting)
      $output.="<img id=\"voting-$id-$i\" src=\"$set\" alt=\"#\" $title$fx/>\n";
    else
      $output.="<img id=\"voting-$id-$i\" src=\"$none\" alt=\".\" $title$fx/>\n";

    if ($can_vote)
      $output.="</a>\n";
  }

  $output.="&nbsp;";
  if ($votes==1)
    $output.=sprintf(_("(%.1f, %d vote)"), $img->get_voting(), $votes);
  else if ($votes>1) 
    $output.=sprintf(_("(%.1f, %d votes)"), $img->get_voting(), $votes);
  else
    $output.=_("No votes");

  $output.="</p></div>\n";

  return $this->output($output, $return);
}

function filename($return=false)
{
  global $user;
  $img=$this->get_image();
  if (!$img)
    return;

  $filename=$img->get_filename();
  $path=$user->get_upload_dir();

  $pos=strpos($filename, $path);
  if ($pos===false || $pos>0)
    $output=$this->escape_html($filename);
  else
    $output=$this->escape_html(substr($filename, strlen($path)));
  return $this->output($output, $return);
}

function _acl_to_text($acl)
{
  $t='';
  // Write access
  if (($acl & ACL_WRITE_MASK) == ACL_WRITE_CAPTION) $t.='c';
  elseif (($acl & ACL_WRITE_MASK) == ACL_WRITE_META) $t.='m';
  elseif (($acl & ACL_WRITE_MASK) == ACL_WRITE_TAG) $t.='t';

  // Read access
  if (($acl & ACL_READ_MASK) == ACL_READ_ORIGINAL) $t.='o';
  elseif (($acl & ACL_READ_MASK) == ACL_READ_HIGHSOLUTION) $t.='h';
  elseif (($acl & ACL_READ_MASK) == ACL_READ_PREVIEW) $t.='v';

  if ($t=='') $t='-';
  return $t;
}

function acl($return=false)
{
  $img=$this->get_image();
  if (!$img)
    return;

  $gid=$img->get_groupid();
  $output="";
  if ($gid>0)
  {
    $group=new Group($gid);
    $name=$group->get_name();

    $url=new Search();
    $url->set_groupid($gid);
    $output.="<a href=\"".$url->get_url()."\">$name</a>: ";
  }
  $output.=$this->_acl_to_text($img->get_gacl()).',';
  $output.=$this->_acl_to_text($img->get_macl()).',';
  $output.=$this->_acl_to_text($img->get_pacl());

  return $this->output($output, $return);
}

/** Prints the date
  @param date_fmt Date format. The date is formated with the system function
date() and the given format. If $date_fmt is null, the format "Y-m-d H:i:s" is
used.
  @param return If true, the given output string is returned. Otherwise it is
printed directly via echo. */
function date($date_fmt=null, $return=false)
{
  $img=$this->get_image();
  if (!$img)
    return;

  $sec=$img->get_date(true);
  
  if ($date_fmt===null)
    $date_fmt="Y-m-d H:i:s";
  $date=date($date_fmt, $sec);
  if (substr($date, 10)==" 00:00:00")
    $date=substr($date, 0, 10);

  $date_url=new Search();
  $date_url->add_param('start', $sec-(60*30*3));
  $date_url->add_param('end', $sec+(60*30*3));
  $url=$date_url->get_url();
  $title=_("Show images around this time");

  $output="<a href=\"$url\" title=\"$title\">$date</a>\n";

  return $this->output($output, $return);
}

/** Prints date navigation with prev images, image within the same day, the
 * same week, the same month followed by next images */
function date_navigator($return=false)
{
  $img=$this->get_image();
  if (!$img)
    return;

  $sec=$img->get_date(true);

  $date_url=new Search();

  // before
  $date_url->del_param('start');
  $date_url->add_param('end', $sec+1);
  $url=$date_url->get_url();
  $title=_("Show older images");
  $output="[<span class=\"prev\"><a href=\"$url\" title=\"$title\">&lt;</a></span>";

  // day
  $date_url->add_param('start', $sec-(60*60*12));
  $date_url->add_param('end', $sec+(60*60*12));
  $url=$date_url->get_url();
  $title=_("Show images within the same day");
  $output.="<span class=\"day\"><a href=\"$url\" title=\"$title\">d</a></span>";
  // week 
  $date_url->add_param('start', $sec-(60*60*12*7));
  $date_url->add_param('end', $sec+(60*60*12*7));
  $url=$date_url->get_url();
  $title=_("Show images within the same week");
  $output.="<span class=\"week\"><a href=\"$url\" title=\"$title\">w</a></span>";
  // month 
  $date_url->add_param('start', $sec-(60*60*12*30));
  $date_url->add_param('end', $sec+(60*60*12*30));
  $url=$date_url->get_url();
  $title=_("Show images within the same month");
  $output.="<span class=\"month\"><a href=\"$url\" title=\"$title\">m</a></span>";
  // after
  $date_url->del_param('end');
  $date_url->add_param('start', $sec-1);
  $date_url->add_param('orderby', '-date');
  $url=$date_url->get_url();
  $title=_("Show newer images");
  $output.="<span class=\"next\"><a href=\"$url\" title=\"$title\">&gt;</a></span>";
  $output.="]";
  unset($date_url);

  return $this->output($output, $return);
}

/** Prints the list of tags inclusive html links */
function tags($return=false)
{
  global $user;
  global $conf;
  $img=$this->get_image();
  if (!$img)
    return;

  $id=$img->get_id();
  $tags=$img->get_tags();
  $num_tags=count($tags);
 
  if ($num_tags==0)
    return;

  $output="";

  $tag_url=new Search();
  for ($i=0; $i<$num_tags; $i++)
  {
    $tag_url->add_tag($tags[$i]);
    $url=$tag_url->get_url();
    $output.="<a href=\"$url\">".$this->escape_html($tags[$i])."</a>";
    $tag_url->del_tag($tags[$i]);
    if ($i<$num_tags-1)
      $output.=$conf->get('meta.separator', ';')." ";
  }
  unset($tag_url);
  return $this->output($output, $return);
}

/** Prints the list of sets inclusive html links */
function categories($return=false)
{
  global $user;
  global $conf;
  $img=$this->get_image();
  if (!$img)
    return;

  $id=$img->get_id();
  $cats=$img->get_categories();
  $num_cats=count($cats);
  
  if ($num_cats==0)
    return;

  $output="";

  $cat_url=new Search();
  for ($i=0; $i<$num_cats; $i++)
  {
    $cat_url->add_category($cats[$i]);
    $url=$cat_url->get_url();
    $output.="<a href=\"$url\">".$this->escape_html($cats[$i])."</a>";
    $cat_url->del_category($cats[$i]);
    if ($i<$num_cats-1)
      $output.=$conf->get('meta.separator', ';')." ";
  }
  unset($set_url);
  return $this->output($output, $return);
}

/** Prints the list of locations inclusive html links */
function locations($return=false)
{
  global $db;
  global $user;
  global $conf;

  $img=$this->get_image();
  if (!$img)
    return;
 
  $id=$img->get_id();
  $locations=$img->get_locations();
  $num_locations=count($locations);

  if ($num_locations==0)
    return;

  $output="";
  
  $loc_url=new Search();
  foreach ($locations as $type => $location)
  {
    $loc_url->add_location($location);
    $url=$loc_url->get_url();
    $output.="<a href=\"$url\">".$this->escape_html($location)."</a>";
    if ($i<$num_locations-1)
      $output.=$conf->get('meta.separator', ';')." ";
    $i++;
    $loc_url->del_location($location);
  }
  unset($loc_url);
  return $this->output($output, $return);
}

/** Prints the preview image or the video object */
function preview($return=false)
{
  $img=$this->get_image();

  if (!$img)
    return;

  $output="";
  $url=new Url('image.php');
  $url->add_param('id', $img->get_id());

  if ($img->is_video())
  {
    global $phtagr_htdocs;
    $url->add_param('type', 'vpreview');
    $url->set_mode(URL_MODE_JS);
    list($width, $height, $s)=$img->get_size(320);
    $height+=22;
    $player="$phtagr_htdocs/js/flowplayer/FlowPlayer.swf";
    $output.="<div class=\"preview\">
  <object type=\"application/x-shockwave-flash\" data=\"$player\" width=\"$width\" height=\"$height\">
    <param name=\"allowScriptAccess\" value=\"sameDomain\" />
    <param name=\"movie\" value=\"$player\" />
    <param name=\"quality\" value=\"high\" />
    <param name=\"scale\" value=\"noScale\" />
    <param name=\"wmode\" value=\"transparent\" />
    <param name=\"flashvars\" value=\"config={videoFile: '".$url->get_url()."', loop: 'false', initialScale: 'orig'}\" />
  </object>
</div>";
  }
  else
  {
    $size=$img->get_size(600);
    $url->add_param('type', 'preview');
    $output.="<div class=\"preview\"><img src=\"".$url->get_url()."\" alt=\"$name\" ".$size[2]."/></div>\n";
  }
  return $this->output($output, $return);
}

/** Prints the image info table 
  @param is_thumb True if image is printed in the explorer
  @param return If true the output will be returned as string. Otherwise it
will be printed */
function imginfo($is_thumb, $return=false)
{
  global $user;

  $img=$this->get_image();
  if (!$img)
    return;
    
  $id=$img->get_id();
  $output="<div class=\"imginfo\" id=\"info-$id\"><table>\n";
  if ($img->is_owner(&$user))
  {
    $output.="<tr><th>"._("File:")."</th><td>".
      $this->filename(true)."</td></tr>\n";
    $output.="<tr><th>"._("Rights:")."</th><td>".
      $this->acl(true)."</td></tr>\n";
  }
  $output.="<tr><th>"._("Date:")."</th><td>".
    $this->date(null, true)." ".
    $this->date_navigator(true)."</td></tr>\n";
  
  if ($img->has_tags())
  {
    $output.="<tr><th>"._("Tags:")."</th><td id=\"tag-$id\">".
      $this->tags(true)."</td></tr>\n";
  }

  if ($img->has_categories())
  {
    $output.="<tr><th>"._("Cat.:")."</th><td>".
      $this->categories(true)."</td></tr>\n";
  }

  if ($img->has_locations())
  {
    $output.="<tr><th>"._("Loc.:")."</th><td>".
      $this->locations(true)."</td></tr>\n";
  }
  
  if (!$is_thumb)
  {
    $output.="<tr><th>"._("Clicks:")."</th><td>".
      $this->clicks(true)."</td></tr>\n";
  }

  if ($img->can_write_meta(&$user) || 
    $img->can_write_tag(&$user))
  {
    $output.="  <tr>
    <th>"._("Select:")."</th>
    <td>";
    if ($is_thumb && $img->can_select($user))    
      $output.="<input type=\"checkbox\" name=\"images[]\" value=\"$id\" onclick=\"uncheck('selectall')\" /> ";

    if ($img->can_write_meta(&$user))
      $output.=" <a href=\"javascript:void();\" class=\"jsbutton\" onclick=\"edit_meta($id);\">"._("Edit Metadata")."</a>";
    elseif ($img->can_write_tag(&$user))
      $output.=" <a href=\"javascript:void();\" class=\"jsbutton\" onclick=\"edit_tag($id);\">"._("Edit Tags")."</a>";
    if ($img->is_owner(&$user))
      $output.="<a href=\"javascript:void();\" class=\"jsbutton\" onclick=\"edit_acl($id);\">".("Edit ACL")."</a>";
    if ($img->can_read_original(&$user))
    {
      $url=new Url('image.php');
      $url->add_param('id', $img->get_id());
      $url->add_param('type', 'original');
      $output.="<a href=\"".$url->get_url()."\" class=\"jsbutton\">"._("Download")."</a>";
    }
    $output.="</td>
  </tr>\n";
  }
  $output.="</table></div>\n";

  return $this->output($output, $return);
}

/** Escapes all special characters for javascript 
  @param s String to escape
  @return Escaped string */
function _escape_js($s)
{
  $patterns[0]='/\'/';
  $patterns[1]="/\//";
  $patterns[2]="/[\r]?\n/si";
  $replaces[0]="\'";
  $replaces[1]="\/";
  $replaces[2]="\\n";
  return preg_replace($patterns, $replaces, $s);
}

/** Prints the image information as javascript data array */
function print_js()
{
  global $user;
  global $conf;
  $img=$this->get_image();
  if (!$img)
    return;

  $id=$img->get_id();
  echo "<script type=\"text/javascript\">\n";
  echo "  images[$id]=new Array();\n";
  if ($img->is_owner(&$user)) 
  {
    echo "  images[$id]['gid']=".$img->get_groupid().";\n";
    echo "  images[$id]['gacl']=".$img->get_gacl().";\n";
    echo "  images[$id]['macl']=".$img->get_macl().";\n";
    echo "  images[$id]['pacl']=".$img->get_pacl().";\n";
  }
  $caption=$img->get_caption();
  echo "  images[$id]['caption']='".$this->_escape_js($caption)."';\n";

  $sep=$conf->get('meta.separator', ';');
  $sep.=($sep!=' ')?' ':'';
  if ($img->can_write_tag(&$user))
  {
    $tags=$img->get_tags();
    $tag_list=$this->_escape_js(implode($sep, $tags));
    echo "  images[$id]['tags']='$tag_list';\n";
  }
  if ($img->can_write_meta(&$user))
  {
    $date=$img->get_date();
    echo "  images[$id]['date']='".$this->_escape_js($date)."';\n";

    $cats=$img->get_categories();
    $cat_list=$this->_escape_js(implode($sep, $cats));
    echo "  images[$id]['categories']='$cat_list';\n";

    $locs=$img->get_locations();
    echo "  images[$id]['city']='".
      $this->_escape_js($locs[LOCATION_CITY])."';\n";
    echo "  images[$id]['sublocation']='".
      $this->_escape_js($locs[LOCATION_SUBLOCATION])."';\n";
    echo "  images[$id]['state']='".
      $this->_escape_js($locs[LOCATION_STATE])."';\n";
    echo "  images[$id]['country']='".
      $this->_escape_js($locs[LOCATION_COUNTRY])."';\n";
  }
  echo "</script>\n";
}

function print_js_groups()
{ 
  global $user;
  $groups=$user->get_groups();
  if (count($groups)==0)
    return;

  echo "<script type=\"text/javascript\">
  var groups=new Array();\n";
  foreach ($groups as $gid => $name)
    echo "  groups[$gid]='".$this->_escape_js($name)."';\n";
  echo "</script>\n";
}

function print_preview() 
{
  global $db;
  global $user;
  
  $img=$this->get_image();
  if (!$img)
    return;

  $search=$this->get_search();
  $id=$img->get_id();
  $name=$img->get_name();

  if ($id<0 || !$img->can_read_preview($user))
    return;
  
  echo "<div class=\"thumbcell\"><a name=\"img-$id\"/>\n";
  $this->print_js();
  echo "\n<div class=\"name\">$name</div>\n";
  echo "<div class=\"thumb\">&nbsp;";
  
  $img_url=clone $search;
  $img_url->add_param('section', 'image');
  $img_url->add_param('id', $id);
  $url=$img_url->get_url(); 
  $size=$img->get_size(220);

  $iurl=new Url('image.php');
  $iurl->add_param('id', $id);
  $iurl->add_param('type', 'thumb');
  echo "<a href=\"$url\"><img src=\"".$iurl->get_url()."\" alt=\"$name\" title=\"$name\" ".$size[2]."/></a></div>\n";
  
  $this->caption();
  $this->from();
  $this->voting();

  $this->imginfo(true);

  echo "</div>\n";
} 

function print_content()
{
  global $db;
  global $user;
 
  echo "<h2>"._("Image")."</h2>\n";
  
  $img=$this->get_image();
  if (!$img || $img->get_id()<0) 
  {
    $this->warning(_("Sorry, the requested image is not available."));
    return;
  } elseif (!$img->can_read_preview($user)) {
    $this->warning(_("Sorry, you are not allowed to access this file."));
    return;
  }

  $search=$this->get_search();

  $id=$img->get_id();
  $name=$img->get_name();
 
  $search_nav=clone $search;
  $this->print_navigation($search_nav);

  echo "<div class=\"name\">$name</div>\n";
  $this->print_js();
  $this->print_js_groups();

  $this->preview();

  $this->caption(false);
  $this->from();
  $this->voting();
  
  $this->imginfo(false);

  $url=new Url();
  echo "<form id=\"formImage\" action=\"".$url->get_url()."\" method=\"post\" accept-charset=\"UTF-8\"><div>\n";
  $this->input_hidden("section", "image");
  $this->input_hidden("action", "edit");
  $this->input_hidden("image", $id);

  echo $search->get_form();
  echo "</div></form>\n";
  if (!isset($_SESSION['img_viewed'][$id]))
    $img->update_ranking();

  $_SESSION['img_viewed'][$id]++;

  $search_nav=clone $search;
  $this->print_navigation($search_nav);

  global $bulb;
  if (isset($bulb))
    $bulb->set_data($this->get_tags(), $this->get_categories(), $this->get_locations());
}

}

?>
