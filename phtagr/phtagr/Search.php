<?php

include_once("$phtagr_lib/Url.php");
/**
  @class Search Mapping between URLs, HTML forms and SQL queries.
*/
class Search extends Url
{

var $tags;
var $sets;

function Search()
{
  $this->Url();
  $this->tags=array();
  $this->sets=array();
}

function set_imageid($imageid)
{
  $this->add_param('id', $imageid, PARAM_PINT);
}

function set_userid($userid)
{
  $this->add_param('user', $userid, PARAM_PINT);
}

function set_groupid($groupid)
{
  $this->add_param('group', $groupid, PARAM_PINT);
}

function add_tag($tag)
{
  if ($tag=='') return;
  array_push($this->tags, $tag);
  $this->tags=array_unique($this->tags);
}

/** Sets the operator of tags
  @param tagop Must be between 0 and 2 */
function set_tagop($tagop)
{
  $this->add_iparam('tagop', $tagop, null, 1, 2);
}

function clear_tags()
{
  unset($this->tags);
  $this->tags=array();
}

function add_set($set)
{
  if ($set=='') return;
  array_push($this->sets, $set);
  $this->sets=array_unique($this->sets);
}

/** Sets the operator of sets
  @param setop Must be between 0 and 2 */
function set_setop($setop)
{
  $this->add_iparam('setop', $setop, null, 1, 2);
}

function clear_sets()
{
  unset($this->sets);
  $this->sets=array();
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
    $this->rem_param('pos');
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
    $size=10;

  if ($size!=10)
    $this->add_iparam('pagesize', $size, 10, 2, 250);
  else
    $this->rem_param('pagesize');
}

function get_page_size()
{
  return $this->get_param('pagesize', 10);
}

function set_orderby($orderby)
{
  if ($orderby=='-date' ||
      $orderby=='ranking' ||
      $orderby=='-ranking' ||
      $orderby=='voting' ||
      $orderby=='-voting' ||
      $orderby=='newest' ||
      $orderby=='-newest' )
    $this->add_param('orderby', $orderby);
  else 
    $this->rem_param('orderby');
  
}

function get_orderby()
{
  $return=$this->get_param('orderby', 'data');
}

/** Creates a search object from a URL */
function from_URL()
{
  parent::from_URL();

  $this->add_rparam('id', PARAM_PINT, null);
  $this->add_rparam('user', PARAM_PINT, null);
  $this->add_rparam('group', PARAM_PINT, null);
    
  if (isset($_REQUEST['tags']))
  {
    if (strpos($_REQUEST['tags'], ' ')>0)
    {
      foreach (split("[ ]",$_REQUEST['tags']) as $tag)
        $this->add_tag($tag);
    }
    else if (strpos($_REQUEST['tags'], "+")>0)
    {
      foreach (split("[+]",$_REQUEST['tags']) as $tag)
        $this->add_tag($tag);
    }
    else
      $this->add_tag($_REQUEST['tags']);
  }

  if (isset($_REQUEST['tagop']))
    $this->set_tagop($_REQUEST['tagop']);
  
  if (isset($_REQUEST['sets']))
  {
    if (strpos($_REQUEST['sets'], ' ')>0)
    {
      foreach (split("[ ]",$_REQUEST['sets']) as $set)
        $this->add_set($set);
    }
    else if (strpos($_REQUEST['sets'], "+")>0)
    {
      foreach (split("[+]",$_REQUEST['sets']) as $set)
        $this->add_set($set);
    }
    else 
      $this->add_set($_REQUEST['sets']);
  }
  if (isset($_REQUEST['setop']))
    $this->set_setop($_REQUEST['setop']);

  $this->add_rparam('location', PARAM_STRING, null);
  $this->add_rparam('location_type', PARAM_PINT, null);
    
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
}

function _to_params()
{
  $num_tags=count($this->tags);
  if ($num_tags>0)
  {
    $v='';
    for ($i=0; $i<$num_tags; $i++)
    {
      $v.=$this->tags[$i];
      if ($i<$num_tags-1)
        $v.='+';
    }
    $this->add_param('tags', $v);
  }
  
  $num_sets=count($this->sets);
  if ($num_sets>0)
  {
    $v='';
    for ($i=0; $i<$num_sets; $i++)
    {
      $v.=$this->sets[$i];
      if ($i<$num_sets-1)
        $v.='+';
    }
    $this->add_param('sets', $v);
  }
}

/** Converts the search to a URL */
function to_URL()
{
  $this->_to_params();
  return parent::to_URL();
}


/** Print the search as a HTML form */
function to_form()
{
  $this->_to_params();
  return parent::to_form();  
}

/** Create a SQL query from a tag array 
  @param tags Array of tags, could be NULL
  @param sets Array of sets, could be NULL
  @param order Insert order column to select statement if true
  @return Return the sql statement of the query object corresponding to the
  Seach parameters */
function _get_query_from_tags($tags, $sets, $order=false)
{
  global $db;
  global $user;
  $num_tags=count($tags);
  $num_sets=count($sets);
    
  $sql="SELECT i.id";
  if ($order)
    $sql.=$this->_get_column_order();
  $tagop=$this->get_param('tagop', 0);
  if ($tagop==1 || $tagop==2)
    $sql.=", COUNT(i.id) AS hits";

  $sql.=" FROM $db->image AS i";
  if ($num_tags)
    $sql .= ",$db->imagetag AS it";
  if ($num_sets)
    $sql .= ",$db->imageset AS iset";
  $location=$this->get_param('location', '');
  if ($location!='') 
    $sql .= ",$db->imagelocation AS il";
    
  $sql .= " WHERE 1=1"; // dummy where clause
  
  // handle IDs of image
  $imageid=$this->get_param('id', 0);
  $userid=$this->get_param('user', 0);
  $groupid=$this->get_param('group', 0);
  if ($imageid>0)
    $sql .= " AND i.id=".$imageid;
  if ($userid>0)
    $sql .= " AND i.userid=".$userid;
  if ($groupid>0)
    $sql .= " AND i.groupid=".$groupid;
  
  // handle the acl
  $sql .= $this->_handle_acl();
  
  // handle tags
  if ($num_tags)
    $sql .= " AND i.id=it.imageid";
  if ($num_tags>1)
  {
    $sql .= " AND (";
    for ($i=0; $i<$num_tags; $i++)
    {
      $tagid=$db->tag2id($tags[$i]);
      $sql .= " it.tagid=$tagid";
      if ($i != $num_tags-1)
        $sql .= " OR";
    }
    $sql .= " )";
  }
  else if ($num_tags==1)
  {
    $tagid=$db->tag2id($tags[0]);
    $sql .= " AND it.tagid=$tagid";
  }

  // handle sets
  if ($num_sets)
    $sql .= " AND i.id=iset.imageid";
  if ($num_sets>1)
  {
    $sql .= " AND (";
    for ($i=0; $i<$num_sets; $i++)
    {
      $setid=$db->set2id($sets[$i]);
      $sql .= " iset.setid=$setid";
      if ($i != $num_sets-1)
        $sql .= " OR";
    }
    $sql .= " )";
  }
  else if ($num_sets==1)
  {
    $setid=$db->set2id($sets[0]);
    $sql .= " AND iset.setid=$setid";
  }

  // handle location
  if ($location!='')
  {
    $locationtype=$this->get_param('location_type', 0);
    $locationid=$db->location2id($location, $location_type);
    $sql .= " AND i.id=il.imageid AND il.locationid=$locationid";
  }

  // handle date
  $start=$this->get_param('start', 0);
  $end=$this->get_param('end', 0);
  if ($start>0)
    $sql .= " AND i.date>=FROM_UNIXTIME($start)";
  if ($end>0)
    $sql .= " AND i.date<FROM_UNIXTIME($end)";

  return $sql;
}

/** Returns sql statement for the where clause which checks the acl */
function _handle_acl()
{
  global $db;
  global $user;
  
  $acl='';
  $userid=$this->get_param('user', null);
  if ($user->is_admin() || 
    ($userid!=null && $userid == $user->get_id()))
    return $acl;
    
  // if requested user id is not the own user id
  else if ($user->is_member() || $user->is_guest())
  {
    $acl .= " AND (
               (i.groupid in ( 
                SELECT groupid
                FROM $db->usergroup
                WHERE userid=".$user->get_id().")
              AND i.gacl>=".ACL_PREVIEW." )";
    if ($user->is_member())
      $acl .= " OR i.oacl>=".ACL_PREVIEW;
    else
      $acl .= " OR i.aacl>=".ACL_PREVIEW;
    $acl .= " )";
  }
  else {
    $acl .= " AND i.aacl>=".ACL_PREVIEW;
  }

  return $acl;
}

/** 
  @return Returns the column order for the selected column. This is needed for
  passing the order from subqueries to upper queries.*/
function _get_column_order()
{
  $order='';
  $orderby=$this->get_param('orderby', 'date');
  switch ($orderby) {
  case 'date':
  case '-date':
    $order.=",date";
    break;
  case 'ranking':
  case '-ranking':
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
  default:
    break;
  }

  return $order;
}

/** Adds a SQL sort statement 
  @return Retruns an SQL order by statement string */
function _handle_orderby()
{
  $hits='';
  $tagop=$this->get_param('tagop', 0);
  if ($tagop==1 || $tagop==2)
    $hits.=" hits DESC";

  $order='';
  $orderby=$this->get_param('orderby', 'date');
  switch ($orderby) {
  case 'date':
    $order.=" i.date DESC";
    break;
  case '-date':
    $order.=" i.date ASC";
    break;
  case 'ranking':
    $order.=" i.ranking DESC";
    break;
  case '-ranking':
    $order.=" i.ranking ASC";
    break;
  case 'voting':
    $order.=" i.voting DESC, i.votes DESC";
    break;
  case '-voting':
    $order.=" i.voting ASC, i.votes ASC";
    break;
  case 'newest':
    $order.=" i.created DESC";
    break;
  case '-newest':
    $order.=" i.created ASC";
    break;
  default:
    break;
  }
  if ($hits!='' && $order!='')
    return " ORDER BY".$hits.",".$order;
  else if ($hits!='')
    return " ORDER BY".$hits;
  else if ($order!='')
    return " ORDER BY".$order;
    
  return '';
}

/** 
  @para num_tags Count of tags. Should be zero or greater zero
  @para tagop Tag operand (0 is and, 1 is or, 2 is fuzzy)
  @return Returns the having statement */
function _handle_having($num_tags, $tagop)
{
  // handle tag operation
  if ($num_tags>1)
  {
    switch ($tagop) {
    case 0:
      $sql .= " HAVING COUNT(i.id)=$num_tags";
      break;
    case 1:
      //$sql .= " HAVING COUNT(i.id)>=1";
      break;
    case 2:
      $fuzzy=intval($num_tags*0.75);
      $sql .= " HAVING COUNT(i.id)>=$fuzzy";
      break;
    }
  }
  return $sql;
}

/** Adds the SQL limit statement 
  @param limit If 0 do not limit and return an empty string. If it is 1 the
  limit is calculated by page_size and page_num. If it is 2, the limit is set
  by pos and page_size.  Default is 0. 
  @return SQL limit string */
function _handle_limit($limit=0)
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

/** Returns the SQL query of the search. It splits the tags according to
 * positiv or negative tags (negative tags have a minus sign as prefix) and
 * creates subqueries for positive and negative tags.
  @param limit Type of limit the query. 0 means no limit. 1 means limit by page
  size and page num. And 2 means limit by pos and size. 
  @param order If this flag is true, the order column will be included into the
  select statement. Otherwise not. Default is true.
  @return SQL query string 
  @see _get_query_from_tags, _handle_limit, _get_column_order  */
function get_query($limit=1, $order=true)
{
  global $db;
  $pos_tags=array();
  $neg_tags=array();
  $tagop=$this->get_param('tagop', 0);
  foreach ($this->tags as $tag)
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
  foreach ($this->sets as $set)
  {
    if ($set{0}=='-')
      array_push($neg_sets, substr($set, 1));
    else
      array_push($pos_sets, $set);
  }
  $num_pos_sets=count($pos_sets);
  $num_neg_sets=count($neg_sets);
  
  if (($num_pos_tags || $num_pos_sets) && 
      ($num_neg_tags || $num_neg_sets))
  {
    $sql="SELECT id";
    if ($order)
      $sql.=$this->_get_column_order();
    $sql.=" FROM ( ";
    $sql.=$this->_get_query_from_tags($pos_tags, $pos_sets, $order);
    $sql.=" AND id NOT IN ( ";
    $sql.=$this->_get_query_from_tags($neg_tags, $neg_sets, false);
    $sql.=" ) ) AS i";
    $sql.=" GROUP BY i.id";
    $sql.=$this->_handle_having($num_pos_tags, $tagop);

    if ($order)
      $sql.=$this->_handle_orderby();
    $sql.=$this->_handle_limit($limit);
  }
  else 
  {
    $sql=$this->_get_query_from_tags($pos_tags, $pos_sets);
    $sql.=" GROUP BY i.id";
    $sql.=$this->_handle_having($num_pos_tags, $tagop);

    if ($order)
      $sql.=$this->_handle_orderby();
    $sql.=$this->_handle_limit($limit);
  }

  return $sql; 
}

/** Returns the SQL statement to return the count of the query. The query does
 * not order the result. */
function get_num_query()
{
  global $db;
  $sql="SELECT COUNT(*) FROM ( ";
  $sql .= $this->get_query(0, false);
  $sql .= " ) AS num";
  return $sql;
}

}

?>
