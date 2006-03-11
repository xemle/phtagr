<?php

/**
  @class Search Mapping between URLs, HTML forms and SQL queries.
*/
class Search {

var $tags;
var $userid;
/** Tag operation. 
 0 = AND, 1 = OR, 2 = fuzzy */
var $tagop;
var $date_start;
var $date_end;
var $page_size;
var $page_num;
var $orderby;

function Search()
{
    $this->userid=NULL;
    $this->tags=array();
    $this->tagop=0;
    $this->date_start=0;
    $this->date_end=0;
    $this->page_size=10;
    $this->page_num=0;
    $this->orderby='date';
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

function set_date_start($start)
{
  if ($start>0)
    $this->date_start=$start;
}

function set_date_end($end)
{
  if ($end>0)
    $this->date_end=$end;
}

function set_page_num($page)
{
  if ($page>0)
    $this->page_num=$page;
}

function set_page_size($size)
{
  if ($size<1)
    $size=2;
  if ($size>250)
    $size=250;
      
  $this->page_size=$size;
}

/** Creates a search object from a URL */
function from_URL()
{
  if (isset($_REQUEST['user']))
    $this->set_userid($_REQUEST['user']);
    
  if (isset($_REQUEST['tags']))
  {
    if (strpos($_REQUEST['tags'], ' '))
    {
      foreach (split(' ',$_REQUEST['tags']) as $tag)
        $this->add_tag($tag);
    }
    else if (strpos($_REQUEST['tags'], '+'))
    {
      foreach (split('+',$_REQUEST['tags']) as $tag)
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

  if (isset($_REQUEST['page']))
    $this->set_page_num($_REQUEST['page']);
  if (isset($_REQUEST['pagesize']))
    $this->set_page_size($_REQUEST['pagesize']);
}

/** Converts the search to a URL */
function to_URL()
{
  $url='';
  
  if ($this->userid>0)
    $url .= '&user='.$this->userid;

  $num_tags=count($this->tags);
  if ($num_tags>0)
  {
    $url .= '&tags=';
    for ($i=0; $i<$num_tags; $i++)
    {
      $url .= $this->tags[$i];
      if ($i<$num_tags-1)
        $url .= '+';
    }
    if ($num_tags>1 && $this->tagop!=0)
      $url .= '&tagop='.$this->tagop;
  }
  
  if ($this->date_start>0)
    $url .= '&start='.$this->date_start;
  if ($this->date_end>0)
    $url .= '&end='.$this->date_end;
    
  if ($this->page_num>0)
    $url .= '&page='.$this->page_num;
  if ($this->page_size!=10)
    $url .= '&pagesize='.$this->page_size;
  
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
  
  return $form;
}

/** Create a SQL query from a tag array 
  @param tags Array of tags, could be NULL
  @param tagop Operator of tags */
function get_query_from_tags($tags, $tagop=0)
{
  global $db;
  $num_tags=count($tags);
    
  $sql="SELECT i.id FROM $db->image AS i";
  if ($num_tags)
    $sql .= ",$db->tag AS t";

  // check for where condition 
  if ($this->userid==NULL &&
    $num_tags==0 && 
    $this->date_start==0 && $this->date_end==0 &&
    $this->page_num==0 && $this->page_size==10)
  {
    return $sql;
  }
    
  $sql .= " WHERE 1=1"; // dummy where clause
  if ($num_tags)
    $sql .= " AND i.id=t.imageid";

  // handle user id
  if ($this->userid!=NULL)
    $sql .= " AND i.userid=".$this->userid;
  
  // handle tags
  if ($num_tags>1)
  {
    $sql .= " AND (";
    for ($i=0; $i<$num_tags; $i++)
    {
      $sql .= " t.name='" . $tags[$i] . "'";
      if ($i != $num_tags-1)
        $sql .= " OR";
    }
    $sql .= " )";
  }
  else if ($num_tags==1)
  {
    $sql .= " AND t.name='" . $tags[0] . "'";
  }

  // handle date
  if ($this->date_start>0)
    $sql .= " AND UNIX_TIMESTAMP(i.date)>=".$this->date_start;
  if ($this->date_end>0)
    $sql .= " AND UNIX_TIMESTAMP(i.date)<".$this->date_end;
  
  $sql .= " GROUP BY i.id";

  // handle tag operation
  if ($num_tags>1)
  {
    switch ($tagop) {
    case 0:
      $sql .= " HAVING COUNT(i.id)=$num_tags";
      break;
    case 1:
      //$sql .= " HAVING COUNT(image.id)>=1";
      break;
    case 2:
      $fuzzy=intval($num_pos_tags*0.7);
      $sql .= " HAVING COUNT(i.id)>=$num_tags";
      break;
    }
  }

  return $sql;
}

/** Adds a SQL sort statement */
function handle_orderby()
{
  if ($this->orderby=='date')
    return " ORDER BY i.date DESC";
  return '';
}

/** Adds the SQL limit statement 
  @param nolimit If true do not limit. Default is false */
function handle_limit($nolimit=false)
{
  if (!$nolimit)
  {
    // Limit, use $count
    $page_pos=$this->page_num*$this->page_size;
    return " LIMIT $page_pos," . $this->page_size;
  }
  return '';
}

/** Returns the SQL query of the search */
function get_query($count=0, $nolimit=false)
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
    $sql="SELECT id FROM $db->image AS i";
    $sql.=" WHERE id IN ( ";
    $sql.=$this->get_query_from_tags($pos_tags, $this->tagop);
    $sql.=" ) AND id NOT IN ( ";
    $sql.=$this->get_query_from_tags($neg_tags, 1);
    $sql.=" )";
    $sql.=$this->handle_orderby();
    $sql.=$this->handle_limit($nolimit);
  }
  else 
  {
    $sql=$this->get_query_from_tags($pos_tags, $this->tagop);
    $sql.=$this->handle_orderby();
    $sql.=$this->handle_limit($nolimit);
  }

  // For debuggin: echo "<!-- $sql -->"; 
  return $sql; 
}

/** Returns the SQL statement to return the count of the query */
function get_num_query()
{
  global $db;
  $sql="SELECT COUNT(*) FROM $db->image AS i WHERE id IN ( ";
  $sql .= $this->get_query(0, true);
  $sql .= " )";
  return $sql;
}

}

?>
