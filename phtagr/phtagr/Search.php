<?php

include_once("$phtagr_prefix/Base.php");
/**
  @class Search Mapping between URLs, HTML forms and SQL queries.
*/
class Search extends Base
{

/** Image id */
var $imageid;
/** String of tags */
var $tags;
var $userid;
/** Tag operation. 
 0 = AND, 1 = OR, 2 = fuzzy */
var $tagop;
var $date_start;
var $date_end;
/** Absolute position of image */
var $pos;
var $page_size;
var $page_num;
/** Sort relation */
var $orderby;

function Search()
{
  $this->imageid=null;
  $this->userid=null;
  $this->tags=array();
  $this->tagop=0;
  $this->date_start=0;
  $this->date_end=0;
  $this->pos=0;
  $this->page_size=10;
  $this->page_num=0;
  $this->orderby='date';
}

function set_imageid($imageid)
{
  if ($imageid==null || $imageid>0)
    $this->imageid=$imageid;
}

function set_userid($userid)
{
  if ($userid>0)
    $this->userid=$userid;
}

function add_tag($tag)
{
  if ($tag=='') return;
  array_push($this->tags, $tag);
  $this->tags=array_unique($this->tags);
}

function clear_tags()
{
  unset($this->tags);
  $this->tags=array();
}

/** Sets the operator of tags
  @param tagop Must be between 0 and 2 */
function set_tagop($tagop)
{
  if ($tagop >=0 && $tagop <=2)
    $this->tagop=$tagop;
}

function set_user($userid)
{
  if ($userid>0)
    $this->userid=$userid;
}

/** Convert input string to unix time. Currently only the format of YYYY-MM-DD
 * and an integer as unix timestamp is supported.
  @param date arbitrary date string
  @return Unix time stamp. False on error. */
function convert_date($date)
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
  $start=$this->convert_date($start);
  if ($start>0)
    $this->date_start=$start;
}

function set_date_end($end)
{
  $end=$this->convert_date($end);
  if ($end>0)
    $this->date_end=$end;
}

/**
  @param pos If is less than 0 set it to 0 */
function set_pos($pos)
{
  if ($pos>=0)
    $this->pos=$pos;
  else
    $this->pos=0;

  $this->set_page_num(floor($this->pos / $this->page_size));
}

function get_pos()
{
  return $this->pos;
}

function set_page_num($page)
{
  if ($page>=0)
    $this->page_num=$page;
  else
    $this->page_num=0;
}

function get_page_num()
{
  return $this->page_num;
}

function set_page_size($size)
{
  if (!is_numeric($size))
    return;

  if ($size<1)
    $size=2;
  if ($size>250)
    $size=250;
      
  $this->page_size=$size;
}

function get_page_size()
{
  return $this->page_size;
}

function set_orderby($orderby)
{
  if ($orderby=='-date' ||
      $orderby=='ranking' ||
      $orderby=='-ranking' ||
      $orderby=='newest' ||
      $orderby=='-newest' )
    $this->orderby=$orderby;
}

function get_orderby()
{
  return $this->orderby;
}

/** Creates a search object from a URL */
function from_URL()
{
  if (isset($_REQUEST['id']))
    $this->set_imageid($_REQUEST['id']);
    
  if (isset($_REQUEST['user']))
    $this->set_userid($_REQUEST['user']);
    
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
  
  if (isset($_REQUEST['start']))
    $this->set_date_start($_REQUEST['start']);
  if (isset($_REQUEST['end']))
    $this->set_date_end($_REQUEST['end']);

  if (isset($_REQUEST['pos']))
    $this->set_pos($_REQUEST['pos']);
  if (isset($_REQUEST['page']))
    $this->set_page_num($_REQUEST['page']);
  if (isset($_REQUEST['pagesize']))
    $this->set_page_size($_REQUEST['pagesize']);
  
  if (isset($_REQUEST['orderby']))
    $this->set_orderby($_REQUEST['orderby']);
}

/** Converts the search to a URL */
function to_URL()
{
  $url='';
  
  if ($this->imageid>0)
    $url .= '&amp;id='.$this->imageid;

  if ($this->userid>0)
    $url .= '&amp;user='.$this->userid;

  $num_tags=count($this->tags);
  if ($num_tags>0)
  {
    $url .= '&amp;tags=';
    for ($i=0; $i<$num_tags; $i++)
    {
      $url .= $this->tags[$i];
      if ($i<$num_tags-1)
        $url .= '+';
    }
    if ($num_tags>1 && $this->tagop!=0)
      $url .= '&amp;tagop='.$this->tagop;
  }
  
  if ($this->date_start>0)
    $url .= '&amp;start='.$this->date_start;
  if ($this->date_end>0)
    $url .= '&amp;end='.$this->date_end;
    
  if ($this->pos>0)
    $url .= '&amp;pos='.$this->pos;
  if ($this->page_num>0)
    $url .= '&amp;page='.$this->page_num;
  if ($this->page_size!=10)
    $url .= '&amp;pagesize='.$this->page_size;
 
  if ($this->orderby!='date')
    $url .= '&amp;orderby='.$this->orderby;
    
  return $url;
}

/** Print a hidden input form 
  @param name name of the hidden parameter
  @param value value of the parameter */
function _input($name, $value) 
{
  return "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
}

/** Print the search as a HTML form */
function to_form()
{
  $form='';
  
  if ($this->imageid>0)
    $form .= $this->_input('id', $this->imageid);

  if ($this->userid>0)
    $form .= $this->_input('user', $this->userid);

  $num_tags=count($this->tags);
  if ($num_tags>0)
  {
    $tags='';
    for ($i=0; $i<$num_tags; $i++)
    {
      $tags.=$this->tags[$i];
      if ($i<$num_tags-1)
        $tags.='+';
    }
    $form .= $this->_input('tags',$tags);
    
    if ($num_tags>1 && $this->tagop!=0)
      $form .= $this->_input('tagop',$this->tagop);
  }
  
  if ($this->date_start>0)
    $form .= $this->_input('start',$this->date_start);
  if ($this->date_end>0)
    $form .= $this->_input('end',$this->date_end);
    
  if ($this->page_num>0)
    $form .= $this->_input('page',$this->page_num);
  if ($this->page_size!=10)
    $form .= $this->_input('pagesize',$this->page_size);
  
  if ($this->orderby!='date')
    $form .= $this->_input('orderby',$this->orderby);
  
  return $form;
}

/** Create a SQL query from a tag array 
  @param tags Array of tags, could be NULL
  @param tagop Operator of tags 
  @return Return the sql statement of the query object corresponding to the
  Seach parameters */
function _get_query_from_tags($tags, $tagop=0)
{
  global $db;
  global $user;
  $num_tags=count($tags);
    
  $sql="SELECT i.id FROM $db->image AS i";
  if ($num_tags)
    $sql .= ",$db->imagetag AS it";

  $sql .= " WHERE 1=1"; // dummy where clause
  
  if ($num_tags)
    $sql .= " AND i.id=it.imageid";

  // handle image id
  if ($this->imageid!=null)
    $sql .= " AND i.id=".$this->imageid;
  
  // handle user id
  if ($this->userid!=null)
    $sql .= " AND i.userid=".$this->userid;
  
  // handle the acl
  $sql .= $this->_handle_acl();
  
  // handle tags
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

  // handle date
  if ($this->date_start>0)
    $sql .= " AND i.date>=FROM_UNIXTIME(".$this->date_start.")";
  if ($this->date_end>0)
    $sql .= " AND i.date<FROM_UNIXTIME(".$this->date_end.")";
  
  $sql .= " GROUP BY i.id";

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

/** Returns sql statement for the where clause which checks the acl */
function _handle_acl()
{
  global $db;
  global $user;
  
  $acl='';

  if ($user->is_admin() || 
    $this->userid == $user->get_userid())
    return $acl;
    
  // if requested user id is not the own user id
  else if ($user->is_member())
  {
    $acl .= " AND (
               (i.groupid in ( 
                SELECT groupid
                FROM $db->usergroup
                WHERE userid=".$user->get_userid().")
              AND i.gacl>=".ACL_PREVIEW." )";
    $acl .= " OR i.oacl>=".ACL_PREVIEW." )";
  }
  else
  {
    $acl .= " AND i.aacl>=".ACL_PREVIEW;
  }

  return $acl;
}

function _get_column_order()
{
  switch ($this->orderby) {
  case 'date':
  case '-date':
    return ",date";
  case 'ranking':
  case '-ranking':
    return ",ranking";
  case 'newest':
  case '-newest':
    return ",created";
  default:
    return '';
  }
}

/** Adds a SQL sort statement 
  @return Retruns an SQL order by statement string */
function _handle_orderby()
{
  switch ($this->orderby) {
  case 'date':
    return " ORDER BY i.date DESC";
  case '-date':
    return " ORDER BY i.date ASC";
  case 'ranking':
    return " ORDER BY i.ranking DESC";
  case '-ranking':
    return " ORDER BY i.ranking ASC";
  case 'newest':
    return " ORDER BY i.created DESC";
  case '-newest':
    return " ORDER BY i.created ASC";
  default:
    return '';
  }
}

/** Adds the SQL limit statement 
  @param limit If 0 do not limit and return an empty string. If it is 1 the
  limit is calculated by page_size and page_num. If it is 2, the limit is set
  by pos and page_size.  Default is 0. 
  @return SQL limit string */
function _handle_limit($limit=0)
{
  if ($limit==1)
  {
    // Limit, use $count
    $pos=$this->page_num*$this->page_size;
    return " LIMIT $pos," . $this->page_size;
  }
  else if ($limit==2)
  {
    return " LIMIT $this->pos," . $this->page_size;
  }
  return '';
}

/** Returns the SQL query of the search i
  @param count 
  @param limit Type of limit the query. 0 means no limit. 1 means limit by page
  size and page num. And 2 means limit by pos and size. 
  @return SQL query string 
  @see _handle_limit */
function get_query($limit=1, $order=true)
{
  global $db;
  $pos_tags=array();
  $neg_tags=array();
  foreach ($this->tags as $tag)
  {
    if ($tag{0}=='-')
      array_push($neg_tags, substr($tag, 1));
    else
      array_push($pos_tags, $tag);
  }
  $num_pos_tags=count($pos_tags);
  $num_neg_tags=count($neg_tags);
  
  if ($num_pos_tags && $num_neg_tags)
  {
    $sql="SELECT id";
    if ($order)
      $sql.=$this->get_column_order();
    $sql.=" FROM (";
    $sql.=$this->_get_query_from_tags($pos_tags, $this->tagop);
    $sql.=" ) AS i AND id NOT IN ( ";
    $sql.=$this->_get_query_from_tags($neg_tags, 1);
    $sql.=" )";
    if ($order)
      $sql.=$this->_handle_orderby();
    $sql.=$this->_handle_limit($limit);
  }
  else 
  {
    $sql=$this->_get_query_from_tags($pos_tags, $this->tagop);
    if ($order)
      $sql.=$this->_handle_orderby();
    $sql.=$this->_handle_limit($limit);
  }

  return $sql; 
}

/** Returns the SQL statement to return the count of the query */
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
