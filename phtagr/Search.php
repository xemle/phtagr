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

include_once("$phtagr_lib/Url.php");
include_once("$phtagr_lib/Constants.php");
/**
  @class Search Mapping between URLs, HTML forms and SQL queries.
  @todo Rename get_url to get_url
  @todo Rename get_form to get_form
*/
class Search extends Url
{

var $_tags;
var $_sets;
var $_locs;

function Search($baseurl='')
{
  global $search;
  $this->Url($baseurl);
  $this->_tags=array();
  $this->_sets=array();
  $this->_locs=array();

  $this->add_param('section', 'explorer');
  if ($search && $search->get_userid()>0)
    $this->set_userid($search->get_userid());
}

function set_imageid($imageid)
{
  $this->add_iparam('id', $imageid, null, 1);
}

/** Set the user id
  @param userid If the userid is not numeric, it converts the name to the id */
function set_userid($userid)
{
  global $user;
  if (!is_numeric($userid))
    $userid=$user->get_id_by_name($userid);
  $this->add_iparam('user', $userid, null, 1);
}

function get_userid()
{
  return $this->get_param('user', 0);  
}

function set_groupid($groupid)
{
  $this->add_iparam('group', $groupid, null, 0);
}

function set_visibility($visibility)
{
  switch ($visibility) {
  case 'private': 
  case 'group': 
  case 'member':
  case 'public':
    $this->add_param('visibility', $visibility);
    break;
  default:
    break;
  }
}

/** Set the file type. 
  @param type Valid values are 'any', 'image', and 'video'
*/
function set_filetype($type)
{
  switch ($type) {
  case 'any':
    $this->del_param('filetype');
    break;
  case 'image': 
  case 'video':
    $this->add_param('filetype', $type);
    break;
  default:
    break;
  }
}

function set_name_mask($mask)
{
  $this->add_param('image_name', $mask);
}

function get_name_mask()
{
  return $this->get_param('image_name', '');
}

function add_tag($tag)
{
  $tag=trim($tag);
  if ($tag=='') 
    return false;
  if ($this->has_tag($tag))
    return true;

  array_push($this->_tags, $tag);
  return true;
}

/** @param tag Tag name
  @return True of the search has already that given tag */
function has_tag($tag)
{
  for ($i=0; $i<count($this->_tags); $i++)
    if ($this->_tags[$i]==$tag)
      return true;
  return false;
}

/** @param tag Tag to delete
  @return True if the tag could be deleted. Otherwise false (e.g. if the tag
  could not be found) */
function del_tag($tag)
{
  for ($i=0; $i<count($this->_tags); $i++)
  {
    if ($this->_tags[$i]==$tag)
    {
      array_splice($this->_tags, $i, 1);
      $this->_tags=array_merge($this->_tags);
      return true;
    }
  }
  return false;
}

/** @return Returns the tags of the current search */
function get_tags()
{
  return $this->_tags;
}

/** Sets the operator of tags
  @param tagop Must be between 0 and 2 */
function set_tagop($tagop)
{
  $this->add_iparam('tagop', $tagop, null, 1, 2);
}

function clear_tags()
{
  unset($this->_tags);
  $this->_tags=array();
}

function add_set($set)
{
  $set=trim($set);
  if ($set=='') return false;
  if ($this->has_set($set))
    return true;
  array_push($this->_sets, $set);
  return true;
}

/** @param set Set name
  @return True of the search has already that given set */
function has_set($set)
{
  for ($i=0; $i<count($this->_sets); $i++)
    if ($this->_sets[$i]==$set)
      return true;
  return false;
}

/** @param set Set to delete
  @return True if the set could be deleted. Otherwise false (e.g. if the set
  could not be found) */
function del_set($set)
{
  for ($i=0; $i<count($this->_sets); $i++)
  {
    if ($this->_sets[$i]==$set)
    {
      unset($this->_sets[$i]);
      $this->_sets=array_merge($this->_sets);
      return true;
    }
  }
  return false;
}

/** @return Returns the sets of the current search */
function get_sets()
{
  return $this->_sets;
}

/** Sets the operator of sets
  @param setop Must be between 0 and 2 */
function set_setop($setop)
{
  $this->add_iparam('setop', $setop, null, 1, 2);
}

function clear_sets()
{
  unset($this->_sets);
  $this->_sets=array();
}

function add_location($location)
{
  $location=trim($location);

  if ($location=='')
    return false;    
  if ($this->has_location($location)) 
    return true;

  array_push($this->_locs, $location);
  return true;
}

/** @param set Set name
  @return True of the search has already that given set */
function has_location($location)
{
  for ($i=0; $i<count($this->_locs); $i++)
    if ($this->_locs[$i]==$location)
      return true;
  return false;
}

/** @param set Set to delete
  @return True if the set could be deleted. Otherwise false (e.g. if the set
  could not be found) */
function del_location($location)
{
  for ($i=0; $i<count($this->_locs); $i++)
  {
    if ($this->_locs[$i]==$location)
    {
      unset($this->_locs[$i]);
      $this->_locs=array_merge($this->_locs);
      return true;
    }
  }
  return false;
}

/** @return Returns the locations of the current search */
function get_locations()
{
  return $this->_locs;
}

/** Sets the operator of locations
  @param setop Must be between 0 and 2 */
function set_locop($locop)
{
  $this->add_iparam('locop', $locop, null, 1, 2);
}

function clear_locations()
{
  unset($this->_locs);
  $this->_locs=array();
}

function set_location_type($location_type)
{
  $this->add_iparam('location_type', $location_type, LOCATION_UNDEFINED, LOCATION_UNDEFINED, LOCATION_COUNTRY);
}

/** Convert input string to unix time. Currently only the format of YYYY-MM-DD
 * and an integer as unix timestamp is supported.
  @param date arbitrary date string
  @return Unix time stamp. False on error. */
function _convert_date($date)
{
  if (is_numeric($date) && $date >= 0)
    return $date;

  // YYYY-MM-DD
  if (strlen($date)==10 && strpos($date, '-')>0)
  {
    $s=strtr($date, '-', ' ');
    $a=split(' ', $s);
    $sec=mktime(0, 0, 0, $a[1], $a[2], $a[0]);
    return $sec;
  }
  return false;
}

function set_date_start($start)
{
  $start=$this->_convert_date($start);
  $this->add_iparam('start', $start, null, 1);
}

function set_date_end($end)
{
  $end=$this->_convert_date($end);
  $this->add_iparam('end', $end, null, 1);
}

/**
  @param pos If is less than 0 set it to 0 */
function set_pos($pos)
{
  if (!is_numeric($pos) || $pos<0)
    $this->del_param('pos');
  else
    $this->add_iparam('pos', $pos, null, 1);

  $pos=$this->get_param('pos', 0);
  $size=$this->get_page_size();
  $this->set_page_num(floor($pos / $size));
}

function get_pos()
{
  return $this->get_param('pos', 0);
}

function set_page_num($page)
{
  $this->add_iparam('page', $page, null, 1);
}

function get_page_num()
{
  return $this->get_param('page', 0);
}

/**
  @param size If 0, set it to default. */
function set_page_size($size)
{
  if (!is_numeric($size))
    $size=12;

  if ($size!=12)
    $this->add_iparam('pagesize', $size, 12, 2, 250);
  else
    $this->del_param('pagesize');
}

function get_page_size()
{
  return $this->get_param('pagesize', 12);
}

function set_orderby($orderby)
{
  if ($orderby=='-date' ||
      $orderby=='popularity' ||
      $orderby=='-popularity' ||
      $orderby=='voting' ||
      $orderby=='-voting' ||
      $orderby=='newest' ||
      $orderby=='-newest' ||
      $orderby=='changes' ||
      $orderby=='-changes' ||
      $orderby=='random' )
    $this->add_param('orderby', $orderby);
  else 
    $this->del_param('orderby');
  
}

function get_orderby()
{
  $return=$this->get_param('orderby', 'date');
}

function del_orderby()
{
  $this->del_param('orderby');
}

/** Creates a search object from a URL */
function from_url()
{
  global $conf, $user;
  parent::from_url();

  $this->add_riparam('id', null, 1);
   
  if (isset($_REQUEST['tags']))
  {
    $sep=$conf->get('meta.separator', ';');
    $sep=($sep==" ")?"+\s":$sep;
    $tags=preg_split("/[$sep]+/", $_REQUEST['tags']);
    foreach ($tags as $tag)
    {
      $tag=preg_replace('/[+]/', " ", $tag);
      $this->add_tag($tag);
    }
  }

  if (isset($_REQUEST['tagop']))
    $this->set_tagop($_REQUEST['tagop']);
  
  if (isset($_REQUEST['sets']))
  {
    $sep=$conf->get('meta.separator', ';');
    $sep=($sep==" ")?"+\s":$sep;
    $sets=preg_split("/[$sep]+/", $_REQUEST['sets']);
    foreach ($sets as $set)
    {
      $set=preg_replace('/[+]/', " ", $set);
      $this->add_set($set);
    }
  }
  if (isset($_REQUEST['setop']))
    $this->set_setop($_REQUEST['setop']);

  if (isset($_REQUEST['locations']))
  {
    $sep=$conf->get('meta.separator', ';');
    $sep=($sep==" ")?"+\s":$sep;
    $locs=preg_split("/[$sep]+/", $_REQUEST['locations']);
    foreach ($locs as $loc)
    {
      $loc=preg_replace('/[+]/', " ", $loc);
      $this->add_location($loc);
    }
  }
  $this->add_riparam('location_type', null, LOCATION_UNKNOWN, LOCATION_COUNTRY);

  if (isset($_REQUEST['filetype']))
    $this->set_filetype($_REQUEST['filetype']);
    
  if (isset($_REQUEST['user']))
    $this->set_userid($_REQUEST['user']);
  
  if ($user->is_member())
  {
    if (isset($_REQUEST['group']))
      $this->set_groupid($_REQUEST['group']);
    if (isset($_REQUEST['visibility'])) 
      $this->set_visibility($_REQUEST['visibility']);
  }
  if ($user->is_member() || $user->is_guest())
  {
    if (isset($_REQUEST['image_name'])) 
      $this->set_name_mask($_REQUEST['image_name']);
  }

  if (isset($_REQUEST['start']))
    $this->set_date_start($_REQUEST['start']);
  if (isset($_REQUEST['end']))
    $this->set_date_end($_REQUEST['end']);
  $this->add_iparam('pos', $_REQUEST['pos'], null, 1);
  $this->add_iparam('page', $_REQUEST['page'], null, 1);

  if (isset($_REQUEST['pagesize']))
    $this->set_page_size($_REQUEST['pagesize']);
  
  if (isset($_REQUEST['orderby']))
    $this->set_orderby($_REQUEST['orderby']);
  
  if (isset($_REQUEST['pos']))
    $this->set_pos($_REQUEST['pos']);
}

/** Sets the tag and set array as parameter */
function _to_params()
{
  global $conf;
  $sep=$conf->get('meta.separator', ';');

  $num_tags=count($this->_tags);
  if ($num_tags>0)
  {
    $v='';
    for ($i=0; $i<$num_tags; $i++)
    {
      $v.=$this->_tags[$i];
      if ($i<$num_tags-1)
      {
        $v.=($sep!=" ")?$sep:"";
        $v.='+';
      }
    }
    $this->add_param('tags', $v);
  }
  else 
    $this->del_param('tags');
  
  $num_sets=count($this->_sets);
  if ($num_sets>0)
  {
    $v='';
    for ($i=0; $i<$num_sets; $i++)
    {
      $v.=$this->_sets[$i];
      if ($i<$num_sets-1)
      {
        $v.=($sep!=" ")?$sep:"";
        $v.='+';
      }
    }
    $this->add_param('sets', $v);
  }
  else
    $this->del_param('sets');

  $num_locs=count($this->_locs);
  if ($num_locs>0)
  {
    $v='';
    for ($i=0; $i<$num_locs; $i++)
    {
      $v.=$this->_locs[$i];
      if ($i<$num_locs-1)
      {
        $v.=($sep!=" ")?$sep:"";
        $v.='+';
      }
    }
    $this->add_param('locations', $v);
  }
  else
    $this->del_param('locations');
}

/** Converts the search to a URL */
function get_url()
{
  $this->_to_params();
  return parent::get_url();
}


/** Print the search as a HTML form */
function get_form()
{
  $this->_to_params();
  return parent::get_form();  
}

/** Join tags. Add a query for specific tags and/or specific tag count
  @param tags Array of required tags. Could be an empty array, to query a
  specific tag count
  @param count Count of required tags. If count is greater zero, the tags will
  be counted. Default is 0.
  @param op Count operant. -2 for not equal, -1 for less or equal, 0 for equal,
  1 for greater or equal. Default is 1 (greater) */
function _add_sql_join_tags($tags, $count=0, $op=1)
{
  global $db;
  if (!count($tags))
    return "";

  $sql=" JOIN ( SELECT imageid, COUNT(imageid) AS thits".
       " FROM $db->imagetag";
  $sql.=" WHERE";

  $ids=array();
  foreach ($tags as $tag)
    array_push($ids, "tagid=".$db->tag2id($tag));
  $sql.=" ".implode(" OR ", $ids);

  $sql.=" GROUP BY imageid";
  if ($count>0) 
  {
    $sql.=" HAVING COUNT(imageid)";
    switch ($op) 
    {
      case -2: $sql.="!="; break;
      case -1: $sql.="<="; break;
      case 0: $sql.="="; break;
      default: $sql.=">="; break;
    }
    $sql.="$count";
  }
  $sql.=" ) AS it ON ( i.id=it.imageid )";
  return $sql;
}

/** Join sets. Add a query for specific sets and/or specific set count
  @param sets Array of required sets. Could be an empty array, to query a
  specific set count
  @param count Count of required sets. If count is greater zero, the sets will
  be counted. Default is 0.
  @param op Count operant. -2 for not equal, -1 for less or equal, 0 for equal,
  1 for greater or equal. Default is 1 (greater) */
function _add_sql_join_sets($sets, $count=0, $op=1)
{
  global $db;
  if (!count($sets))
    return "";

  $sql=" JOIN ( SELECT imageid, COUNT(imageid) AS shits".
       " FROM $db->imageset";
  $sql.=" WHERE";

  $ids=array();
  foreach ($sets as $set)
    array_push($ids, "setid=".$db->set2id($set));
  $sql.=" ".implode(" OR ", $ids);

  $sql.=" GROUP BY imageid";
  if ($count>0) 
  {
    $sql.=" HAVING COUNT(imageid)";
    switch ($op) 
    {
      case -2: $sql.="!="; break;
      case -1: $sql.="<="; break;
      case 0: $sql.="="; break;
      default: $sql.=">="; break;
    }
    $sql.="$count";
  }
  $sql.=" ) AS iset ON ( i.id=iset.imageid )";
  return $sql;
}

/** Join locations. Add a query for specific locs and/or specific set count
  @param locs Array of required locations. Could be an empty array, to query a
  specific set count
  @param count Count of required locations. If count is greater zero, the
  locations will be counted. Default is 0.
  @param op Count operant. -2 for not equal, -1 for less or equal, 0 for equal,
  1 for greater or equal. Default is 1 (greater) */
function _add_sql_join_locations($locs, $count=0, $op=1)
{
  global $db, $log;
  if (!count($locs))
    return "";

  $sql=" JOIN ( SELECT imageid, COUNT(imageid) AS lhits".
       " FROM $db->imagelocation";
  $sql.=" WHERE";

  $ids=array();
  foreach ($locs as $loc)
  {
    $locids=$db->location2ids($loc);
    foreach ($locids as $locid)
      array_push($ids, "locationid=".$locid);
  }
  $sql.=" ".implode(" OR ", $ids);

  $sql.=" GROUP BY imageid";
  if ($count>0) 
  {
    $sql.=" HAVING COUNT(imageid)";
    switch ($op) 
    {
      case -2: $sql.="!="; break;
      case -1: $sql.="<="; break;
      case 0: $sql.="="; break;
      default: $sql.=">="; break;
    }
    $sql.="$count";
  }
  $sql.=" ) AS l ON ( i.id=l.imageid )";
  return $sql;
}

/** The meta data inclusion is solved by a conjunction via sql joints in the
 * sql from statement. The meta data joins are also subqueries to count the
 * meta data hits.
  @param tags Array of tags, could be NULL
  @param sets Array of sets, could be NULL
  @param locs Array of locations, could be NULL
  @return Returns the sql join statements for meta data inclusion. May be an
  empty string, if no meta data is given. 
  @see _add_sql_join_tags _add_sql_join_sets _add_sql_join_locations */
function _add_sql_join_meta_inclusion($tags, $sets, $locs)
{
  global $db, $user, $log;

  $num_tags=count($tags);
  $num_sets=count($sets);
  $num_locs=count($locs);

  if ($num_tags) 
  {
    $tagop=$this->get_param('tagop', 0);
    switch ($tagop)
    {
      case 1: $count=0; break; /* OR */
      case 2: $count=intval($num_tags*0.75); break; /* FUZZY */
      default: $count=$num_tags; break; /* AND */
    }
    $sql.=$this->_add_sql_join_tags($tags, $count, 1);
  }

  if ($num_sets) 
  {
    $setop=$this->get_param('setop', 0);
    switch ($setop)
    {
      case 1: $count=0; break; /* OR */
      case 2: $count=intval($num_sets*0.75); break; /* FUZZY */
      default: $count=$num_sets; break; /* AND */
    }
    $sql.=$this->_add_sql_join_sets($sets, $count, 1);
  }

  if ($num_locs) 
  {
    $locop=$this->get_param('locop', 0);
    switch ($locop)
    {
      case 1: $count=0; break; /* OR */
      case 2: $count=intval($num_locs*0.75); break; /* FUZZY */
      default: $count=$num_locs; break; /* AND */
    }
    $sql.=$this->_add_sql_join_locations($locs, $count, 1);
  }
  return $sql;
}

/** The meta data exclusion is solved by a disjunction of the meta data set and
 * is in a negated sql where statement (NOT IN).
  @param tags Array of exclued tags, could be NULL
  @param sets Array of exclued sets, could be NULL
  @param locs Array of exclued locations, could be NULL
  @return Returns the sql where statements for meta data exclusion. May be an
  empty string is not meta data exclusion is given */
function _add_sql_where_meta_exclusion($tags, $sets, $locs)
{
  global $db;

  $num_tags=count($tags);
  $num_sets=count($sets);
  $num_locs=count($locs);

  if ($num_tags+$num_sets+$num_locs==0)
    return "";

  $sql.=" AND i.id NOT IN (";
  $sql.=" SELECT i.id".
        " FROM $db->images AS i";

  if ($num_tags)
  {
    $sql.=" LEFT JOIN $db->imagetag AS it"
        ." ON ( i.id=it.imageid )";
  }

  if ($num_sets)
  {
    $sql.=" LEFT JOIN $db->imageset AS iset"
        ." ON ( i.id=iset.imageid )";
  }

  if ($num_locs)
  {
    $sql.=" LEFT JOIN $db->imagelocation AS il"
        ." ON ( i.id=il.imageid )";
  }

  $sql.=" WHERE 0";
  
  if ($num_tags)
  {
    foreach ($tags as $tag)
    {
      $tid=$db->tag2id($tag);
      if ($tid<0)
        continue;
      $sql.=" OR it.tagid=$tid";
    }
  }
  
  if ($num_sets)
  {
    foreach ($sets as $set)
    {
      $sid=$db->set2id($set);
      if ($sid<0)
        continue;
      $sql.=" OR iset.setid=$sid";
    }
  }
  
  if ($num_locs)
  {
    foreach ($locs as $loc)
    {
      $lids=$db->location2ids($loc);
      foreach ($lids as $lid)
        $sql.=" OR il.locationid=$lid";
    }
  }
  $sql.=$this->_add_sql_where_acl();

  $sql.=" )";
  return $sql;
}

/** Returns sql statement for the where clause which checks the acl */
function _add_sql_where_acl()
{
  global $db;
  global $user;
  
  $acl='';
  if ($user->is_admin() || 
    $this->get_userid()==$user->get_id())
    return $acl;
    
  // if requested user id is not the own user id
  else if ($user->is_member() || $user->is_guest())
  {
    $acl.=" AND ( i.groupid IN (".
          " SELECT groupid".
          " FROM $db->usergroup".
          " WHERE userid=".$user->get_id().
          " AND i.gacl>=".ACL_READ_PREVIEW." )";
    if ($user->is_member())
      $acl.=" OR i.macl>=".ACL_READ_PREVIEW;
    else
      $acl.=" OR i.pacl>=".ACL_READ_PREVIEW;
    $acl.=" )";
  }
  else {
    $acl.=" AND i.pacl>=".ACL_READ_PREVIEW;
  }

  return $acl;
}

/** Sets the visiblity of an image. It selects images which are only visible
 * for the group, only for members or visible for the public */
function _add_sql_where_visibility()
{
  $acl='';
  $visible=$this->get_param('visibility', '');
  switch ($visible) {
  case 'private':
    $acl .= " AND i.gacl<".ACL_READ_PREVIEW; 
    break;
  case 'group':
    $acl .= " AND i.gacl>=".ACL_READ_PREVIEW." AND i.macl<".ACL_READ_PREVIEW; 
    break;
  case 'member':
    $acl .= " AND i.macl>=".ACL_READ_PREVIEW." AND i.pacl<".ACL_READ_PREVIEW; 
    break;
  case 'public':
    $acl .= " AND i.pacl>=".ACL_READ_PREVIEW; 
    break;
  default:
    break;
  }
  return $acl;
}
/** 
  @return Returns the column order for the selected column. This is needed for
  passing the order from subqueries to upper queries.*/
function _add_sql_column_order($num_tags, $num_sets, $num_locs)
{
  $order='';
  $orderby=$this->get_param('orderby', 'date');
  switch ($orderby) {
  case 'date':
  case '-date':
    $order.=",date";
    break;
  case 'popularity':
  case '-popularity':
    $order.=",ranking";
    break;
  case 'voting':
  case '-voting':
    $order.=",voting,votes";
    break;
  case 'newest':
  case '-newest':
    $order.=",created";
    break;
  case 'changes':
  case '-changes':
    $order.=",modified";
    break;
  case 'random':
    break;
  default:
    break;
  }

  $hits=array();
  if ($num_tags) array_push($hits, "thits");
  if ($num_sets) array_push($hits, "shits");
  if ($num_locs) array_push($hits, "lhits");

  if (count($hits))
  {
    $order.=",".implode(",", $hits);
    // hits is the product of all meta hits
    for ($i=0; $i<count($hits); $i++)
      $hits[$i]="(".$hits[$i]."+1)";
    $order.=",".implode("*", $hits)." AS hits";
  }

  return $order;
}

/** Adds a SQL sort statement 
  @return Retruns an SQL order by statement string */
function _add_sql_orderby($num_tags, $num_sets)
{
  $order=array();

  if ($num_tags>0 && $num_sets>0)
    array_push($order, "hits DESC");

  $tagop=$this->get_param('tagop', 0);
  if ($num_tags>0 && ($tagop==1 || $tagop==2))
    array_push($order, "thits DESC");

  $setop=$this->get_param('setop', 0);
  if ($num_sets>0 && ($setop==1 || $setop==2))
    array_push($order, "shits DESC");

  $orderby=$this->get_param('orderby', 'date');
  $values=array('date' => "date DESC", 
         '-date' => "date ASC", 
         'popularity' => "ranking DESC",
         '-popularity' => "ranking ASC",
         'voting' => "voting DESC,votes DESC",
         '-voting' => "voting ASC,votes ASD",
         'newest' => "created DESC",
         '-newest' => "created ASC",
         'changes' => "modified DESC",
         '-changes' => "modified ASC",
         'random' => "RAND()");
  if (isset($values[$orderby]))
    array_push($order, $values[$orderby]);

  $sql='';
  if (count($order)>0) 
    $sql=" ORDER BY ".implode(",", $order);
    
  return $sql;
}

/** Adds the SQL limit statement 
  @param limit If 0 do not limit and return an empty string. If it is 1 the
  limit is calculated by page_size and page_num. If it is 2, the limit is set
  by pos and page_size.  Default is 0. 
  @return SQL limit string */
function _add_sql_limit($limit=0)
{
  $pos=$this->get_param('pos', 0);
  $page=$this->get_param('page', 0);
  $size=$this->get_page_size();

  if ($limit==1)
  {
    // Limit, use $count
    $pos=$page*$size;
    return " LIMIT $pos," . $size;
  }
  else if ($limit==2)
  {
    return " LIMIT $pos," . $size;
  }
  return '';
}

function _add_sql_where()
{
  $sql="";

  // handle IDs of image
  $imageid=$this->get_param('id', 0);
  $userid=$this->get_param('user', 0);
  $groupid=$this->get_param('group', -1);

  if ($imageid>0)  $sql .= "AND i.id=".$imageid;
  if ($userid>0)   $sql .= "AND i.userid=".$userid;
  if ($groupid>=0) $sql .= "AND i.groupid=".$groupid;
  
  // handle the acl and visibility level
  $sql.=$this->_add_sql_where_acl();
  $sql.=$this->_add_sql_where_visibility();

  // handle date
  $start=$this->get_param('start', 0);
  $end=$this->get_param('end', 0);
  if ($start>0)
    $sql .= " AND i.date>=FROM_UNIXTIME($start)";
  if ($end>0)
    $sql .= " AND i.date<FROM_UNIXTIME($end)";

  $type=$this->get_param('filetype');
  if ($type=='image')
    $sql.=" AND i.duration=-1";
  if ($type=='video')
    $sql.=" AND i.duration>=0";

  $name_mask=$this->get_name_mask();
  if (strlen($name_mask))
  {
    $sname_mask=mysql_escape_string($name_mask);
    $sql.=" AND i.name LIKE '%$sname_mask%'";
  }
  return $sql;
}

/** Returns the SQL query of the search. It splits the tags according to
 * positiv or negative tags (negative tags have a minus sign as prefix) and
 * creates subqueries for positive and negative tags.
  @param limit Type of limit the query. 0 means no limit. 1 means limit by page
  size and page num. And 2 means limit by pos and size. 
  @param order If this flag is true, the order column will be included into the
  select statement. Otherwise not. Default is true.
  @return SQL query string 
  @see _add_sql_limit, _add_sql_column_order  */
function get_query($limit=1, $order=true)
{
  global $db;

  $pos_tags=array();
  $neg_tags=array();
  foreach ($this->_tags as $tag)
  {
    if ($tag{0}=='-')
      array_push($neg_tags, substr($tag, 1));
    else
      array_push($pos_tags, $tag);
  }
  $num_pos_tags=count($pos_tags);
  $num_neg_tags=count($neg_tags);
  
  $pos_sets=array();
  $neg_sets=array();
  foreach ($this->_sets as $set)
  {
    if ($set{0}=='-')
      array_push($neg_sets, substr($set, 1));
    else
      array_push($pos_sets, $set);
  }
  $num_pos_sets=count($pos_sets);
  $num_neg_sets=count($neg_sets);
 
  $pos_locs=array();
  $neg_locs=array();
  foreach ($this->_locs as $loc)
  {
    if ($loc{0}=='-')
      array_push($neg_locs, substr($loc, 1));
    else
      array_push($pos_locs, $loc);
  }
  $num_pos_locs=count($pos_locs);
  $num_neg_locs=count($neg_locs);
  
  $sql="SELECT i.id";
  if ($order)
    $sql.=$this->_add_sql_column_order($num_pos_tags, $num_pos_sets, $num_pos_locs);

  $sql.=" FROM $db->images AS i";
  $sql.=$this->_add_sql_join_meta_inclusion($pos_tags, $pos_sets, $pos_locs);
  // Consider only imported files
  $sql.=" WHERE i.flag && ".IMAGE_FLAG_IMPORTED;
  $sql.=$this->_add_sql_where_meta_exclusion($neg_tags, $neg_sets, $neg_locs);
  $sql.=$this->_add_sql_where();
  $sql.=" GROUP BY i.id";

  if ($order)
    $sql.=$this->_add_sql_orderby($num_pos_tags, $num_pos_sets);
  $sql.=$this->_add_sql_limit($limit);

  return $sql; 
}

/** Returns the SQL statement to return the count of the query. The query does
 * not order the result. */
function get_num_query()
{
  global $db;
  $sql="SELECT COUNT(*) FROM ( ".
       $this->get_query(0, false).
       " ) AS num";
  return $sql;
}

/** @return Returns the number of readable image */
function get_num_images()
{
  global $db;
  $sql="SELECT COUNT(id)".
       " FROM $db->images".
       " WHERE 1".
       $this->_add_sql_where_acl();
  return $db->query_cell($sql);
}

/** 
  @param num Number of the returned tags. 
  @return Returns an table with 2 columns. The first column is the set name,
the second column is the number of hits. The table is ordered by descending
hits */
function get_popular_tags($num=50)
{
  global $db;

  $sql="SELECT t.name,COUNT(t.name) AS hits".
       " FROM $db->tags AS t, $db->imagetag AS it, $db->images as i".
       " WHERE t.id=it.tagid AND it.imageid=i.id".
       "   AND i.flag && ".IMAGE_FLAG_IMPORTED.
       $this->_add_sql_where_acl().
       " GROUP BY t.name ".
       " ORDER BY hits DESC LIMIT 0,".intval($num);
 
  $table=$db->query_table($sql);
  if (!$table)
    return null;
  $cloud=array();
  foreach($table as $row)
    $cloud[$row['name']]=$row['hits'];
  return $cloud;
}

/** 
  @param num Number of the returned tags. 
  @return Returns an table with 2 columns. The first column is the tag name,
the second column is the number of hits. The table is ordered by descending
hits */
function get_popular_sets($num=50)
{
  global $db;

  $sql="SELECT s.name,COUNT(s.name) AS hits". 
       " FROM $db->sets AS s, $db->imageset AS iset, $db->images as i".
       " WHERE s.id=iset.setid and iset.imageid=i.id".
       "   AND i.flag && ".IMAGE_FLAG_IMPORTED.
       $this->_add_sql_where_acl().
       " GROUP BY s.name". 
       " ORDER BY hits DESC LIMIT 0,".intval($num);
  
  $table=$db->query_table($sql);
  if (!$table)
    return null;
  $cloud=array();
  foreach($table as $row)
    $cloud[$row['name']]=$row['hits'];
  return $cloud;
}

}

?>
