<?php

include_once("$phtagr_lib/SectionBase.php");
include_once("$phtagr_lib/Image.php");
include_once("$phtagr_lib/Sql.php");

class SectionImage extends SectionBase
{

var $img;
function SectionImage($id=0)
{
  $this->name="image";
  $this->img=null;
  if ($id>0)
    $this->img=new Image($id);
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
    $search->set_pos($cur_pos);
    // skip current image
    if ($cur_pos==$pos)
    {
      $url="index.php?section=explorer";
      $url.=$search->to_URL();
      echo "<a href=\"$url\">"._("up")."</a>&nbsp;";
      $cur_pos++;
      continue;
    }

    $id=$row[0];
    $search->set_pos($cur_pos);
    
    $url="index.php?section=image&amp;id=$id";
    $url.=$search->to_URL();
    
    if ($cur_pos<$pos)
      echo "<a href=\"$url\">"._("prev")."</a>&nbsp;";
    else
      echo "<a href=\"$url\">"._("next")."</a>";
    
    $cur_pos++;
  }
  echo "</div>\n";
}

/** Print the caption of an image. 
  @param id ID of current image
  @param caption String of the caption
  @param docut True if a long caption will be shorted. False if the whole
  caption will be printed. Default true */
function print_caption($docut=true)
{
  global $user;

  $img=$this->img;
  $id=$img->get_id();
  $caption=$img->get_caption();
  
  $can_edit=$user->can_edit($img);
  
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
    $b64=base64_encode($caption);
    if ($docut=true)
      $text=$this->_cut_caption($id, &$caption);
    else {
      $text=htmlspecialchars($caption);
    }

    echo "$text <a href=\"javascript:void()\" class=\"jsbutton\" onclick=\"add_form_caption($id, '$b64') \">"._("edit")."</a>";
  }
  else
  {
    echo " <span onclick=\"add_form_caption($id, '')\">"._("Click here to add a caption")."</span>";
  }
  
  echo "</div>\n";
}

/** Cut the caption by words. If the length of the caption is longer than 20
 * characters, the caption will be cutted into words and reconcartenated to the
 * length of 20.  */
function _cut_caption($id, $caption)
{
  $b64=base64_encode($caption);
  $caption=htmlspecialchars($caption);

  if (strlen($caption)< 60) 
    return $caption;

  $words=split(" ", $caption);
  foreach ($words as $word)
  {
    if (strlen($result) > 40)
      break;

    $result.=" $word";
  }
  $result="<span id=\"caption-text-$id\">".$result;
  $result.=" <a href=\"javascript:void()\" class=\"jsbutton\" onclick=\"print_caption($id, '$b64')\">[...]</a>";
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
  global $pref;
  $img=$this->img;
  $id=$img->get_id();
  $votes=$img->get_votes();
  $voting=sprintf("%.2f", $img->get_voting());

  $url.="index.php?section=".$_REQUEST['section'];
  $url.=$search->to_URL();

  $can_vote=false;
  if (!isset($_SESSION['img_voted'][$id]))
    $can_vote=true;

  $none=$pref['path.theme'].'/vote-none.png';
  $set=$pref['path.theme'].'/vote-set.png';

  echo "<div class=\"voting\"><p>\n";
  for ($i=0; $i<=VOTING_MAX; $i++)
  {
    $title='';
    $fx='';
    if ($can_vote) {
      echo "<a href=\"$url&amp;action=edit&amp;image=$id&amp;voting=$i#img-$id\">";
      $title=" title=\"".
        sprintf(_("Vote the image with %d points!"), $i)."\"";
      $fx=" onmouseover=\"vote_highlight($id, $voting, $i)\" onmouseout=\"vote_reset($id, $voting)\"";
    } 

    if ($voting>0 && $i<=$voting)
      echo "<img id=\"voting-$id-$i\" src=\"$set\" alt=\"*\" $title$fx/>\n";
    else
      echo "<img id=\"voting-$id-$i\" src=\"$none\" alt=\"-\" $title$fx/>\n";

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
  echo "  <tr><th>"._("File:")."</th><td>".$img->get_filename()."</td></tr>\n";
}

function print_row_acl()
{
  $img=$this->img;
  $id=$img->get_id();
  $gacl=$img->get_gacl();
  $oacl=$img->get_oacl();
  $aacl=$img->get_aacl();
  echo "  <tr><th>"._("ACL:")."</th><td id=\"acl-$id\">$gacl,$oacl,$aacl";
  echo " <a href=\"javascript:void()\" class=\"jsbutton\" onclick=\"add_form_acl('$id',$gacl,$oacl,$aacl)\">"._("edit")."</a>";
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
  $search_date=new Search();
  $search_date->date_start=$sec-(60*30*3);
  $search_date->date_end=$sec+(60*30*3);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "<a href=\"$url\">$date</a>\n";

  // day
  $search_date->date_start=$sec-(60*60*12);
  $search_date->date_end=$sec+(60*60*12);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "[<span class=\"day\"><a href=\"$url\">d</a></span>";
  // week 
  $search_date->date_start=$sec-(60*60*12*7);
  $search_date->date_end=$sec+(60*60*12*7);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "<span class=\"week\"><a href=\"$url\">w</a></span>";
  // month 
  $search_date->date_start=$sec-(60*60*12*30);
  $search_date->date_end=$sec+(60*60*12*30);
  $url="index.php?section=explorer";
  $url.=$search_date->to_URL();
  echo "<span class=\"month\"><a href=\"$url\">m</a></span>]";
  echo "\n    </td>\n  </tr>\n";
}

function print_row_tags()
{
  global $db;
  global $user;
  $img=$this->img;
  $id=$img->get_id();
  $sql="SELECT t.name
        FROM $db->tag AS t, $db->imagetag AS it
        WHERE it.imageid=$id 
          AND it.tagid=t.id
        GROUP BY t.name";
  $result = $db->query($sql);
  $tags=array();
  while($row = mysql_fetch_row($result)) {
    array_push($tags, $row[0]);
  }
  sort($tags);
  $num_tags=count($tags);
  
  echo "  <tr>
    <th>"._("Tags:")."</th>
    <td id=\"tag-$id\">";  

  for ($i=0; $i<$num_tags; $i++)
  {
    echo "<a href=\"index.php?section=explorer&amp;tags=" . $tags[$i] . "\">" . $tags[$i] . "</a>";
    if ($i<$num_tags-1)
        echo ", ";
  }
  if ($user->can_edit($img))
  {
    $list='';
    for ($i=0; $i<$num_tags; $i++)
    {
      $list.=$tags[$i];
      if ($i<$num_tags-1)
        $list.=" ";
    }
    echo " <a href=\"javascript:void()\" class=\"jsbutton\" onclick=\"add_form_tags('$id','$list')\">"._("edit")."</a>";
  }
  echo "</td>
  </tr>\n";
}

function print_row_sets()
{
  global $db;
  global $user;
  $img=$this->img;
  $id=$img->get_id();
  $sql="SELECT s.name
        FROM $db->set AS s, $db->imageset AS iset
        WHERE iset.imageid=$id 
          AND iset.setid=s.id
        GROUP BY s.name";
  $result = $db->query($sql);
  $sets=array();
  while($row = mysql_fetch_row($result)) {
    array_push($sets, $row[0]);
  }
  sort($sets);
  $num_sets=count($sets);
  
  echo "  <tr>
    <th>"._("Sets:")."</th>
    <td id=\"set-$id\">";  

  for ($i=0; $i<$num_sets; $i++)
  {
    echo "<a href=\"index.php?section=explorer&amp;sets=" . $sets[$i] . "\">" . $sets[$i] . "</a>";
    if ($i<$num_sets-1)
        echo ", ";
  }
  if ($user->can_edit($img))
  {
    $list='';
    for ($i=0; $i<$num_sets; $i++)
    {
      $list.=$sets[$i];
      if ($i<$num_sets-1)
        $list.=" ";
    }
    echo " <a href=\"javascript:void()\" class=\"jsbutton\" onclick=\"add_form_sets('$id','$list')\">"._("edit")."</a>";
  }
  echo "</td>
  </tr>\n";
}

function print_row_location()
{
  global $db;
  global $user;

  $img=$this->img;
  $id=$img->get_id();
  $sql="SELECT l.name,l.type
        FROM $db->location as l, $db->imagelocation as il
        WHERE il.imageid=$id 
          AND il.locationid=l.id
        ORDER BY l.type";
  $result = $db->query($sql);
  $location=array();
  
  $city='';
  $sublocation='';
  $state='';
  $country='';

  while($row = mysql_fetch_row($result)) {
    switch($row[1]) {
    case LOCATION_CITY:
      $city=$row[0];
      break;
    case LOCATION_SUBLOCATION:
      $sublocation=$row[0];
      break;
    case LOCATION_STATE:
      $state=$row[0];
      break;
    case LOCATION_COUNTRY:
      $country=$row[0];
      break;
    } 
    array_push($location, array($row[1], $row[0]));
  }
   
  echo "  <tr>
    <th>"._("Location:")."</th>
    <td id=\"location-$id\">";  

  $num_location=count($location);
  for ($i=0; $i<$num_location; $i++)
  {
    echo "<a href=\"index.php?section=explorer&amp;location=".$location[$i][1] . "\">" . $location[$i][1] . "</a>";
    if ($i<$num_location-1)
        echo ", ";
  }
  if ($user->can_edit($img))
  {
    $list='';
    for ($i=0; $i<$num_location; $i++)
    {
      $list.=$tags[$i];
      if ($i<$num_tags-1)
        $list.=" ";
    }
    echo " <a href=\"javascript:void()\" class=\"jsbutton\" onclick=\"add_form_location('$id','$city','$sublocation', '$state', '$country')\">"._("edit")."</a>";
  }
  echo "</td>
  </tr>\n";
}

function print_preview($search=null) 
{
  global $db;
  global $user;
  
  $img=$this->img;
  $id=$img->get_id();
  $name=$img->get_name();
  
  echo "\n<div class=\"name\">$name</div>\n";
  echo "<div class=\"thumb\">&nbsp;";
  
  $link="index.php?section=image&amp;id=$id";
  if ($search!=null)
    $link.=$search->to_URL();
  
  $size=$img->get_size(220);

  echo "<a href=\"$link\"><img src=\"./image.php?id=$id&amp;type=thumb\" alt=\"$name\" title=\"$name\" ".$size[2]."/></a></div>\n";
  
  $this->print_caption();
  $this->print_voting();

  echo "<div class=\"imginfo\"><table>\n";
  if ($user->is_owner(&$img))
  {
    $this->print_row_filename();
    $this->print_row_acl();
  }
  $this->print_row_date();
  
  $this->print_row_tags();
  $this->print_row_sets();
  $this->print_row_location();
  if ($user->can_select($id))
  {
    echo "  <tr>
    <th>"._("Select:")."</th>
    <td><input type=\"checkbox\" name=\"images[]\" value=\"$id\" onclick=\"uncheck('selectall')\" /></td>
  </tr>\n";
  }
  
  echo "</table></div>\n";
} 

function print_content()
{
  global $db;
  global $user;
  global $search;
 
  echo "<h2>"._("Image")."</h2>\n";
  
  if (!isset($_REQUEST['id']))
    return;
 
  $id=$_REQUEST['id'];
  $image=new Image($id);
  
  $name=$image->get_name();
  
  $search_nav=clone $search;
  $this->print_navigation($search_nav);

  echo "<div class=\"name\">$name</div>\n";

  $size=$image->get_size(600);
  echo "<div class=\"preview\"><img src=\"./image.php?id=$id&amp;type=preview\" alt=\"$name\" ".$size[2]."/></div>\n";

  $this->print_caption(false);
  $this->print_voting();

  echo "<div class=\"imginfo\"><table>\n";
  
  if ($user->is_owner(&$image)) {
    $this->print_row_filename();
    $this->print_row_acl();
  }

  $this->print_row_date();
  $this->print_row_tags();
  $this->print_row_sets();
  $this->print_row_location();
  $this->print_row_clicks();
  echo "</table></div>\n";

  echo "<form id=\"formImage\" action=\"index.php\" method=\"post\"><div>\n";
  echo "<input type=\"hidden\" name=\"section\" value=\"image\" />\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"edit\" />\n";
  echo "<input type=\"hidden\" name=\"image\" value=\"$id\" />\n";

  echo $search->to_form();
  echo "</div></form>\n";
  if (!isset($_SESSION['img_viewed'][$id]))
    $image->update_ranking();

  $_SESSION['img_viewed'][$id]++;

  $search_nav=clone $search;
  $this->print_navigation($search_nav);
}

}

?>
