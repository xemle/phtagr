<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Sql.php");

class SectionImage extends SectionBase
{

var $img;
function SectionImage($id=0)
{
  $this->SectionBase("image");
  $this->img=null;
  if ($id>0)
    $this->img=new Image($id);
}

/** Returns the image object of the section */
function get_img()
{
  return $this->img;
}

/** print image preview table */
function print_navigation($search)
{
  global $db;
  
  if ($search==null)
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
      if ($this->img) {
        $search->set_anchor('img-'.$this->img->get_id());
      }
      $url=$search->to_URL();
      $search->del_anchor();
      echo "<a href=\"$url\">"._("up")."</a>&nbsp;";
      $cur_pos++;
      continue;
    }

    $search->add_param('section', 'image');
    $search->set_imageid($id);
    $url=$search->to_URL();
    
    if ($cur_pos<$pos)
      echo "<a href=\"$url\">"._("prev")."</a>&nbsp;";
    else
      echo "<a href=\"$url\">"._("next")."</a>";
    
    $cur_pos++;
  }
  echo "</div>\n";
}

function print_from()
{
  global $db;
  global $user;

  $num=$user->get_num_users();
  if ($num==1)
    return;

  $img=$this->img;
  $search=new Search();
  $search->add_param('section', 'explorer');
  $search->set_userid($img->get_userid());
  if ($user->get_id() != $img->get_userid()) 
  {
    $name=$user->get_name_by_id($img->get_userid());
  } else {
    $name=$user->get_name();
  }
  echo "<div class=\"from\">by <a href=\"".$search->to_URL()."\">$name</a></div>\n";
}
/** Print the caption of an image. 
  @param docut True if a long caption will be shorted. False if the whole
  caption will be printed. Default true */
function print_caption($docut=true)
{
  global $user;

  $img=$this->img;
  $id=$img->get_id();
  $caption=$img->get_caption();
  
  $can_edit=$img->can_edit($user);
  
  echo "<div class=\"caption\" id=\"caption-$id\">";
  // the user can not edit the image
  if (!$can_edit)
  {
    if ($caption!="")
      echo $this->_cut_caption($id, &$caption);

    echo "</div>\n";
    return;
  }
  
  // The user can edit the image
  if ($caption != "") 
  {
    if ($docut=true)
      $text=$this->_cut_caption($id, &$caption);
    else {
      $text=htmlspecialchars($caption);
    }

    echo "$text <a href=\"javascript:void();\" class=\"jsbutton\" onclick=\"edit_caption($id) \">"._("edit")."</a>";
  }
  else
  {
    echo " <span onclick=\"edit_caption($id)\">"._("Click here to add a caption")."</span>";
  }
  
  echo "</div>\n";
}

/** Cut the caption by words. If the length of the caption is longer than 20
 * characters, the caption will be cutted into words and reconcartenated to the
 * length of 20.  */
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

function print_row_clicks()
{
  $img=$this->img;
  $ranking=sprintf("%.3f", $img->get_ranking());
  echo "  <tr><th>"._("Clicks:")."</th><td>"
    .sprintf(_("%d (Popularity: %.3f)"), $img->get_clicks(), $ranking)
    ."</td></tr>\n";
}

function print_voting()
{
  global $search;
  global $user;

  $img=$this->img;
  $id=$img->get_id();
  $votes=$img->get_votes();
  $voting=sprintf("%.2f", $img->get_voting());

  $can_vote=false;
  if (!isset($_SESSION['img_voted'][$id]) && $_SESSION['nrequests']>1)
  {
    $can_vote=true;
    $vote_url=clone $search;
    $vote_url->add_param('action', 'edit');
    $vote_url->add_param('image', $id);
    $vote_url->set_anchor('img-'.$id);
  }

  $none=$user->get_theme_dir().'/vote-none.png';
  $set=$user->get_theme_dir().'/vote-set.png';

  echo "<div class=\"voting\"><p>\n";
  for ($i=0; $i<=VOTING_MAX; $i++)
  {
    $title='';
    $fx='';
    if ($can_vote) {
      $vote_url->add_param('voting', $i);
      $url=$vote_url->to_URL();
      echo "<a href=\"$url\">";
      $title=" title=\"".
        sprintf(_("Vote the image with %d points!"), $i)."\"";
      $fx=" onmouseover=\"vote_highlight($id, $voting, $i)\" onmouseout=\"vote_reset($id, $voting)\"";
    } 

    if ($voting>0 && $i<=$voting)
      echo "<img id=\"voting-$id-$i\" src=\"$set\" alt=\"#\" $title$fx/>\n";
    else
      echo "<img id=\"voting-$id-$i\" src=\"$none\" alt=\".\" $title$fx/>\n";

    if ($can_vote)
      echo "</a>\n";
  }

  echo "&nbsp;";
  if ($votes==1)
    echo sprintf(_("(%.1f, %d vote)"), $img->get_voting(), $votes);
  else if ($votes>1) 
    echo sprintf(_("(%.1f, %d votes)"), $img->get_voting(), $votes);
  else
    echo _("No votes");

  echo "</p></div>\n";
}

function print_row_filename()
{
  $img=$this->img;
  echo "  <tr><th>"._("File:")."</th>"
    ."<td>".htmlentities($img->get_filename())."</td></tr>\n";
}

function _acl_to_text($acl)
{
  $t='';
  if (($acl & ACL_WRITE_MASK) == ACL_EDIT) $t.='e';
  if (($acl & ACL_READ_MASK) == ACL_PREVIEW) $t.='v';
  if (($acl & ACL_READ_MASK) == ACL_HIGHSOLUTION) $t.='f';
  if ($t=='') $t='-';
  return $t;
}

function print_row_acl()
{
  $img=$this->img;
  echo "  <tr><th>"._("ACL:")."</th><td>";

  $gid=$img->get_groupid();
  if ($gid>0)
  {
    $group=new Group($gid);
    $name=$group->get_name();
    echo "$name: ";
  }
  echo $this->_acl_to_text($img->get_gacl()).',';
  echo $this->_acl_to_text($img->get_macl()).',';
  echo $this->_acl_to_text($img->get_aacl());
  echo "</td></tr>\n";
}

function print_row_date()
{
  $img=$this->img;
  $sec=$img->_sqltime2unix($img->get_date());
  
  echo "  <tr>
    <th>"._("Date:")."</th>
    <td>";
  $date=date("Y-m-d H:i:s", $sec);
  if (substr($date, 10)==" 00:00:00")
    $date=substr($date, 0, 10);

  $date_url=new Search();
  $date_url->add_param('start', $sec-(60*30*3));
  $date_url->add_param('end', $sec+(60*30*3));
  $url=$date_url->to_URL();
  echo "<a href=\"$url\">$date</a>\n";

  // before
  $date_url->del_param('start');
  $date_url->add_param('end', $sec+1);
  $url=$date_url->to_URL();
  $title=_("Show older images");
  echo "[<span class=\"prev\"><a href=\"$url\" title=\"$title\">&lt;</a></span>";

  // day
  $date_url->add_param('start', $sec-(60*60*12));
  $date_url->add_param('end', $sec+(60*60*12));
  $url=$date_url->to_URL();
  $title=_("Show images within the same day");
  echo "<span class=\"day\"><a href=\"$url\" title=\"$title\">d</a></span>";
  // week 
  $date_url->add_param('start', $sec-(60*60*12*7));
  $date_url->add_param('end', $sec+(60*60*12*7));
  $url=$date_url->to_URL();
  $title=_("Show images within the same week");
  echo "<span class=\"week\"><a href=\"$url\" title=\"$title\">w</a></span>";
  // month 
  $date_url->add_param('start', $sec-(60*60*12*30));
  $date_url->add_param('end', $sec+(60*60*12*30));
  $url=$date_url->to_URL();
  $title=_("Show images within the same month");
  echo "<span class=\"month\"><a href=\"$url\" title=\"$title\">m</a></span>";
  // after
  $date_url->del_param('end');
  $date_url->add_param('start', $sec-1);
  $date_url->add_param('orderby', '-date');
  $url=$date_url->to_URL();
  $title=_("Show newer images");
  echo "<span class=\"next\"><a href=\"$url\" title=\"$title\">&gt;</a></span>";
  echo "]\n    </td>\n  </tr>\n";
  unset($date_url);
}

function print_row_tags()
{
  global $user;
  $img=$this->img;
  $id=$img->get_id();
  $tags=$img->get_tags();
  $num_tags=count($tags);
 
  if ($num_tags==0)
    return;

  echo "  <tr>
    <th>"._("Tags:")."</th>
    <td id=\"tag-$id\">";  

  $tag_url=new Search();
  for ($i=0; $i<$num_tags; $i++)
  {
    $tag_url->add_tag($tags[$i]);
    $url=$tag_url->to_URL();
    echo "<a href=\"$url\">" . htmlentities($tags[$i]) . "</a>";
    $tag_url->del_tag($tags[$i]);
    if ($i<$num_tags-1)
        echo ", ";
  }
  echo "</td>
  </tr>\n";
  unset($tag_url);
}

function print_row_sets()
{
  global $db;
  global $user;
  $img=$this->img;
  $id=$img->get_id();
  $sets=$img->get_sets();
  $num_sets=count($sets);
  
  if ($num_sets==0)
    return;

  echo "  <tr>
    <th>"._("Sets:")."</th>
    <td id=\"set-$id\">";  

  $set_url=new Search();
  for ($i=0; $i<$num_sets; $i++)
  {
    $set_url->add_set($sets[$i]);
    $url=$set_url->to_URL();
    echo "<a href=\"$url\">" . htmlentities($sets[$i]) . "</a>";
    $set_url->del_set($sets[$i]);
    if ($i<$num_sets-1)
        echo ", ";
  }
  echo "</td>
  </tr>\n";
  unset($set_url);
}

function print_row_location()
{
  global $db;
  global $user;

  $img=$this->img;
  $id=$img->get_id();
  $locations=$img->get_locations();
  $num_locations=count($locations);

  if ($num_locations==0)
    return;
  
  echo "  <tr>
    <th>"._("Location:")."</th>
    <td id=\"location-$id\">";  

  $loc_url=new Search();
  foreach ($locations as $type => $location)
  {
    $loc_url->add_param('location', $location);
    $url=$loc_url->to_URL();
    echo "<a href=\"$url\">" . htmlentities($location) . "</a>";
    if ($i<$num_locations-1)
        echo ", ";
    $i++;
  }
  echo "</td>
  </tr>\n";
  unset($loc_url);
}

/** Escapes all special characters for javascript 
  @param s String to escape
  @return Escaped string */
function _escape_js($s)
{
  $patterns[0]='/\'/';
  $patterns[1]="/\//";
  $replaces[0]="\'";
  $replaces[1]="\/";
  return preg_replace($patterns, $replaces, $s);
}

/** Prints the image information as javascript data array */
function print_js()
{
  global $user;
  $img=$this->img;
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
    echo "  images[$id]['aacl']=".$img->get_aacl().";\n";
  }
  $caption=$img->get_caption();
  echo "  images[$id]['caption']='".$this->_escape_js($caption)."';\n";
  if ($img->can_edit(&$user))
  {
    $date=$img->get_date();
    echo "  images[$id]['date']='".$this->_escape_js($date)."';\n";

    $tags=$img->get_tags();
    $ltags='';
    foreach ($tags as $tag)
      $ltags.=$tag.' ';
    echo "  images[$id]['tags']='".$this->_escape_js($ltags)."';\n";

    $sets=$img->get_sets();
    $lsets='';
    foreach ($sets as $set)
      $lsets.=$set.' ';
    echo "  images[$id]['sets']='".$this->_escape_js($lsets)."';\n";

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

function print_preview($search=null) 
{
  global $db;
  global $user;
  
  $img=$this->img;
  $id=$img->get_id();
  $name=$img->get_name();
  
  $this->print_js();
  echo "\n<div class=\"name\">$name</div>\n";
  echo "<div class=\"thumb\">&nbsp;";
  
  $img_url=clone $search;
  $img_url->add_param('section', 'image');
  $img_url->add_param('id', $id);
  $url=$img_url->to_URL(); 
  $size=$img->get_size(220);

  $iurl=new Url('image.php');
  $iurl->add_param('id', $id);
  $iurl->add_param('type', 'preview');
  echo "<a href=\"$url\"><img src=\"".$iurl->to_URL()."\" alt=\"$name\" title=\"$name\" ".$size[2]."/></a></div>\n";
  
  $this->print_from();
  $this->print_caption();
  $this->print_voting();

  echo "<div class=\"imginfo\" id=\"info-$id\"><table>\n";
  if ($img->is_owner(&$user))
  {
    if (!$img->is_upload())
      $this->print_row_filename();
    $this->print_row_acl();
  }
  $this->print_row_date();
  
  $this->print_row_tags();
  $this->print_row_sets();
  $this->print_row_location();
  if ($img->can_select($user))
  {
    echo "  <tr>
    <th>"._("Select:")."</th>
    <td><input type=\"checkbox\" name=\"images[]\" value=\"$id\" onclick=\"uncheck('selectall')\" />";
    if ($img->can_edit(&$user))
    { 
      echo "<a href=\"javascript:void()\" class=\"jsbutton\" onclick=\"edit_meta($id)\">"._("Edit Metadata")."</a>";
      if ($img->is_owner(&$user))
        echo "<a href=\"javascript:void()\" class=\"jsbutton\" onclick=\"edit_acl($id)\">"._("Edit ACL")."</a>";
    }
    echo "</td>\n</tr>\n";
  }
  
  echo "</table></div>\n";
} 

function print_content()
{
  global $db;
  global $user;
  global $search;
 
  echo "<h2>"._("Image")."</h2>\n";
  
  $img=$this->img;
  if (!$img)
    return;
 
  $id=$img->get_id();
  $name=$img->get_name();
  
  $search_nav=clone $search;
  $this->print_navigation($search_nav);

  echo "<div class=\"name\">$name</div>\n";
  $this->print_js();
  $this->print_js_groups();

  $size=$img->get_size(600);
  $url=new Url('image.php');
  $url->add_param('id', $id);
  $url->add_param('type', 'preview');
  echo "<div class=\"preview\"><img src=\"".$url->to_URL()."\" alt=\"$name\" ".$size[2]."/></div>\n";

  $this->print_from();
  $this->print_caption(false);
  $this->print_voting();
  echo "<div class=\"imginfo\" id=\"info-$id\"><table>\n";
  
  if ($img->is_owner(&$user)) {
    $this->print_row_filename();
    $this->print_row_acl();
  }

  $this->print_row_date();
  $this->print_row_tags();
  $this->print_row_sets();
  $this->print_row_location();
  $this->print_row_clicks();
  if ($img->can_edit(&$user))
  {
    echo "  <tr>
    <th>"._("Edit:")."</th>
    <td><a href=\"javascript:void()\" class=\"jsbutton\" onclick=\"edit_meta($id)\">"._("Edit Metadata")."</a>";
    if ($img->is_owner(&$user))
      echo "<a href=\"javascript:void()\" class=\"jsbutton\" onclick=\"edit_acl($id)\">".("Edit ACL")."</a>";
    echo "</td>
  </tr>\n";
  }
  echo "</table></div>\n";

  echo "<form id=\"formImage\" action=\"index.php\" method=\"post\"><div>\n";
  echo "<input type=\"hidden\" name=\"section\" value=\"image\" />\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"edit\" />\n";
  echo "<input type=\"hidden\" name=\"image\" value=\"$id\" />\n";

  echo $search->to_form();
  echo "</div></form>\n";
  if (!isset($_SESSION['img_viewed'][$id]))
    $img->update_ranking();

  $_SESSION['img_viewed'][$id]++;

  $search_nav=clone $search;
  $this->print_navigation($search_nav);
}

}

?>
