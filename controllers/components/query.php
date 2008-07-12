<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2008 Sebastian Felis, sebastian@phtagr.org
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

class QueryComponent extends Object
{
  var $component = array('Logger');
  var $_params = array('page' => 1, 'show' => 12, 'pos' => 1);
  var $_tags = array();
  var $_categories = array();
  var $_locations = array();
  var $_tp = ""; 

  var $controller = null;

  function startup(&$controller) {
    $this->controller = &$controller;
    $this->_tp=$this->controller->Image->tablePrefix;
  }

  function getParam($name, $default = null) {
    if (isset($this->_params[$name]))
      return $this->_params[$name];
    return $default;
  }

  function getParams() {
    $data = $this->_params;
    $data['tags'] = $this->_tags;
    $data['categories'] = $this->_categories;
    $data['locations'] = $this->_locations;
    return $data;
  }

  function setParam($name, $value) {
    $this->_params[$name] = $value;    
  }

  function delParam($name, $value = null) {
    if (isset($this->_params[$name])) {
      if ($value === null || $this->_params[$name] == $value)
        unset($this->_params[$name]);
    }
  }

  /** Parse passed arguments for the query and check them againse the role of
   * the user */
  function parseArgs() {
    $userRole = $this->controller->getUserRole();
    foreach($this->controller->passedArgs as $name => $value) {
      if (is_numeric($name))
        continue;
      switch($name) {
        case 'page': $this->setPageNum(intval($value)); break;
        case 'show': $this->setPageSize(intval($value)); break;
        case 'pos': $this->setPosition(intval($value)); break;
        case 'sort': $this->setOrder($value); break;

        case 'image': $this->setImageId(intval($value)); break;
        case 'user': $this->setUserId($value); break;
        case 'group': if ($userRole >= ROLE_USER) $this->setGroupId(intval($value)); break;
        case 'visibility': if ($userRole >= ROLE_USER) $this->setVisibility($value); break;

        case 'from': $this->setDateStart($value); break;
        case 'to': $this->setDateEnd($value); break;

        case 'tags': $this->addTags(preg_split('/\s*,\s*/', $value)); break;
        case 'categories': $this->addCategories(preg_split('/\s*,\s*/', trim($value))); break;
        case 'locations': $this->addLocations(preg_split('/\s*,\s*/', trim($value))); break;

        default:
          $this->Logger->err("Unknown argument: $name:$value");
      }
    }
  }

  function setImageId($imageId) {
    $imageId = intval($imageId);
    $this->setParam('image', $imageId);
  }

  /** Set the user id
    @param userid If the userid is not numeric, it converts the name to the id */
  function setUserId($idOrName) {
    if (!is_numeric($idOrName)) {
      $user = $this->controller->User->findByUsername($idOrName);
      if ($user !== false)
        $userid=$user['User']['id'];
      else
        $userid=-1;
    }
    $this->setParam('user', $idOrName);
  }

  function getUserId() {
    return $this->getParam('user', 0);  
  }

  function setGroupId($groupId) {
    $groupId = intval($groupId);
    $this->setParam('group', $groupId);
  }

  function setVisibility($visibility) {
    switch ($visibility) {
    case 'private': 
    case 'group': 
    case 'member':
    case 'public':
      $this->setParam('visibility', $visibility);
      break;
    default:
      break;
    }
  }

  /** Set the file type. 
    @param type Valid values are 'any', 'image', and 'video'
  */
  function setFiletype($type) {
    switch ($type) {
    case 'any':
      $this->delParam('filetype');
      break;
    case 'image': 
    case 'video':
      $this->setParam('filetype', $type);
      break;
    default:
      break;
    }
  }

  function setNameMask($mask) {
    $this->setParam('image_name', $mask);
  }

  function getNameMask() {
    return $this->getParam('image_name', '');
  }

  function addTag($tag) {
    $tag=trim($tag);
    if (!strlen($tag)) 
      return false;
    if ($this->hasTag($tag))
      return true;

    array_push($this->_tags, $tag);
    return true;
  }

  function addTags($tags) {
    foreach ($tags as $tag)
      $this->addTag($tag);
  }

  /** @param tag Tag name
    @return True of the query has already that given tag */
  function hasTag($tag) {
    return in_array($tag, $this->_tags);
  }

  /** @param tag Tag to delete
    @return True if the tag could be deleted. Otherwise false (e.g. if the tag
    could not be found) */
  function delTag($tag) {
    $key = array_search($tag, $this->_tags);
    if ($key !== false) {
      unset($this->_tags[$key]);
      return true;
    }
    return false;
  }

  /** @return Returns the tags of the current query */
  function getTags() {
    return $this->_tags;
  }

  /** Sets the operator of tags
    @param tagop Must be between 0 and 2 */
  function setTagOp($tagop) {
    $tagop = intval($tagop);
    if ($tagop < 0 || $tagop > 2)
      $tagop = 0; 

    $this->setParam('tagop', $tagop);
  }

  function clearTags()
  {
    unset($this->_tags);
    $this->_tags=array();
  }

  function addCategory($cat) {
    $cat=trim($cat);
    if ($cat=='') return false;
    if ($this->hasCategory($cat))
      return true;
    array_push($this->_categories, $cat);
    return true;
  }

  function addCategories($categories) {
    foreach($categories as $category)
      $this->addCategory($category);
  }

  /** @param cat Name of category
    @return True of the query has already that given set */
  function hasCategory($category) {
    return in_array($category, $this->_categories);
  }

  /** @param cat Category to delete
    @return True if the set could be deleted. Otherwise false (e.g. if the set
    could not be found) */
  function delCategory($category) {
    $key = array_search($category, $this->_categories);
    if ($key !== false) {
      unset($this->_categories[$key]);
      return true;
    }
    return false;
  }

  /** @return Returns the sets of the current query */
  function getCategories() {
    return $this->_categories;
  }

  /** Sets the operator of sets
    @param catop Must be between 0 and 2 */
  function setCategoryOp($catop) {
    $catop = intval($catop);
    if ($catop < 0 || $catop > 2)
      $catop = 0; 

    $this->setParam('catop', $catop);
  }

  function clearCategories() {
    unset($this->_categories);
    $this->_categories=array();
  }

  function addLocation($location) {
    $location=trim($location);

    if ($location=='')
      return false;    
    if ($this->hasLocation($location)) 
      return true;

    array_push($this->_locations, $location);
    return true;
  }

  function addLocations($locations) {
    foreach($locations as $location)
      $this->addLocation($location);
  }

  /** @param set Set name
    @return True of the query has already that given set */
  function hasLocation($location) {
    return in_array($location, $this->_locations);
  }

  /** @param set Set to delete
    @return True if the set could be deleted. Otherwise false (e.g. if the set
    could not be found) */
  function delLocation($location) {
    $key = array_search($location, $this->_locations);
    if ($key !== false) {
      unset($this->_locations[$key]);
      return true;
    }
    return false;
  }

  /** @return Returns the locations of the current query */
  function getLocations() {
    return $this->_locations;
  }

  /** Sets the operator of locations
    @param catop Must be between 0 and 2 */
  function setLocationOp($locop) {
    $locop = intval($locop);
    if ($locop < 0 || $locop > 2)
      $locop = 0; 

    $this->setParam('locop', $locop);
  }

  function clearLocations() {
    unset($this->_locations);
    $this->_locations=array();
  }

  function setLocationType($locationType) {
    $locationType = intval($locationType);
    if ($locationType < 0 || $locationType > 4)
      $locationType = 0; 

    $this->setParam('locationType', $locationType);
  }

  /** Convert input string to unix time. Currently only the format of YYYY-MM-DD
   * and an integer as unix timestamp is supported.
    @param date arbitrary date string
    @return Unix time stamp. False on error. */
  function _convertDate($date) {
    if (is_numeric($date) && $date >= 0)
      return $date;

    // YYYY-MM-DD
    if (strlen($date)==10 && strpos($date, '-')>0) {
      $s=strtr($date, '-', ' ');
      $a=split(' ', $s);
      $sec=mktime(0, 0, 0, $a[1], $a[2], $a[0]);
      return $sec;
    }
    return false;
  }

  function setDateStart($start) {
    $start=$this->_convertDate($start);
    $this->setParam('from', $start);
  }

  function setDateEnd($end) {
    $end=$this->_convertDate($end);
    $this->setParam('to', $end);
  }

  /**
    @param pos If is less than 0 set it to 1. If 0, delete it */
  function setPosition($pos) {
    $pos = intval($pos);
    if ($pos == 0) {
      $this->delParam('pos');
      return;
    } elseif ($pos < 1) {
      $pos = 1; 
    }
    $this->setParam('pos', $pos);
    $size=$this->getPageSize();
    $this->setPageNum(floor($pos / $size));
  }

  function getPosition() {
    return $this->getParam('pos', 1);
  }

  function setPageNum($page) {
    $page = intval($page);
    if ($page < 1)
      $page = 1;
    $this->setParam('page', $page);
  }

  function getPageNum() {
    return $this->getParam('page', 1);
  }

  /**
    @param size If 0, set it to default. */
  function setPageSize($size) {
    $size = intval($size);
    if ($size < 1)
      $size=12;
    if ($size > 240)
      $size = 240;

    $this->setParam('show', $size);
  }

  function getPageSize() {
    return $this->getParam('show', 12);
  }

  function setOrder($order) {
    $map = array('date', 'popularity', 'voting', 
            'newest', 'changes', 'random');
    if (substr($order, 0, 1) == '-') {
      $tmpOrder = substr($order, 1);
    } else {
      $tmpOrder = $order;
    }
    if (in_array($tmpOrder, $map)) {
      $this->setParam('sort', $order);
    } else {
      $this->delParam('sort');
    }
  }

  function getOrder() {
    return $this->getParam('sort', 'date');
  }

  function delOrder() {
    $this->delParam('sort');
  }

  /** Join tags. Add a query for specific tags and/or specific tag count
    @param tags Array of required tags. Could be an empty array, to query a
    specific tag count
    @param count Count of required tags. If count is greater zero, the tags will
    be counted. Default is 0.
    @param op Count operant. -2 for not equal, -1 for less or equal, 0 for equal,
    1 for greater or equal. Default is 1 (greater) */
  function _addSqlJoinTags($tags, $count=0, $op=1) {
    $data = array();
    foreach ($tags as $tag) {
      $tag = trim($tag);
      if (strlen($tag)==0)
        continue;
      $data[] = array('name' => $tag); 
    }
    $ids=$this->controller->Tag->createIdList($data);
    if (!count($ids))
      return "";

    $sql=" JOIN ( SELECT image_id, COUNT(image_id) AS thits".
         " FROM {$this->_tp}images_tags AS ImageTag";
    $sql.=" WHERE";
    $sql.=" tag_id IN (".implode(", ", $ids).")";
    $sql.=" GROUP BY image_id";
    if ($count>0) {
      $sql.=" HAVING COUNT(image_id)";
      switch ($op) {
        case -2: $sql.="!="; break;
        case -1: $sql.="<="; break;
        case 0: $sql.="="; break;
        default: $sql.=">="; break;
      }
      $sql.="$count";
    }
    $sql.=" ) AS ImageTag ON ( Image.id = ImageTag.image_id )";
    return $sql;
  }

  /** Join sets. Add a query for specific sets and/or specific set count
    @param sets Array of required sets. Could be an empty array, to query a
    specific set count
    @param count Count of required sets. If count is greater zero, the sets will
    be counted. Default is 0.
    @param op Count operant. -2 for not equal, -1 for less or equal, 0 for equal,
    1 for greater or equal. Default is 1 (greater) */
  function _addSqlJoinCategories($cats, $count=0, $op=1) {
    $categories = array();
    foreach ($cats as $cat) {
      $cat = trim($cat);
      if (strlen($cat)==0)
        continue;
      $categories[] = array('name' => $cat); 
    }
    $ids=$this->controller->Category->createIdList($categories);
    if (!count($ids))
      return "";

    $sql=" JOIN ( SELECT image_id, COUNT(image_id) AS chits".
         " FROM {$this->_tp}categories_images AS CategoryImage";
    $sql.=" WHERE";
    $sql.=" category_id IN (".implode(", ", $ids).")";

    $sql.=" GROUP BY image_id";
    if ($count>0) {
      $sql.=" HAVING COUNT(image_id)";
      switch ($op) {
        case -2: $sql.="!="; break;
        case -1: $sql.="<="; break;
        case 0: $sql.="="; break;
        default: $sql.=">="; break;
      }
      $sql.="$count";
    }
    $sql.=" ) AS CategoryImage ON ( Image.id = CategoryImage.image_id )";
    return $sql;
  }

  /** Join locations. Add a query for specific locs and/or specific set count
    @param locs Array of required locations. Could be an empty array, to query a
    specific set count
    @param count Count of required locations. If count is greater zero, the
    locations will be counted. Default is 0.
    @param op Count operant. -2 for not equal, -1 for less or equal, 0 for equal,
    1 for greater or equal. Default is 1 (greater) */
  function _addSqlJoinLocations($locs, $count=0, $op=1) {
    $locations = array();
    foreach ($locs as $loc) {
      $loc = trim($loc);
      if (strlen($loc)==0)
        continue;
      $locations[] = array('name' => $loc); 
    }
    $ids=$this->controller->Location->createIdList($locations);
    if (!count($ids))
      return "";

    $sql=" JOIN ( SELECT image_id, COUNT(image_id) AS lhits".
         " FROM {$this->_tp}images_locations AS ImageLocation";
    $sql.=" WHERE";
    $sql.=" location_id IN (".implode(", ", $ids).")";

    $sql.=" GROUP BY image_id";
    if ($count>0) {
      $sql.=" HAVING COUNT(image_id)";
      switch ($op) {
        case -2: $sql.="!="; break;
        case -1: $sql.="<="; break;
        case 0: $sql.="="; break;
        default: $sql.=">="; break;
      }
      $sql.="$count";
    }
    $sql.=" ) AS ImageLocation ON ( Image.id = ImageLocation.image_id )";
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
    @see _addSqlJoinTags _addSqlJoinCategories _addSqlJoinLocations */
  function _addSqlJoinMetaInclusion($tags, $cats, $locs) {
    $numTags=count($tags);
    $numCats=count($cats);
    $numLocs=count($locs);

    $sql = "";

    if ($numTags)  {
      $tagop=$this->getParam('tagop', 0);
      switch ($tagop)
      {
        case 1: $count=0; break; /* OR */
        case 2: $count=intval($numTags*0.75); break; /* FUZZY */
        default: $count=$numTags; break; /* AND */
      }
      $sql.=$this->_addSqlJoinTags($tags, $count, 1);
    }

    if ($numCats)  {
      $catop=$this->getParam('catop', 0);
      switch ($catop)
      {
        case 1: $count=0; break; /* OR */
        case 2: $count=intval($numCats*0.75); break; /* FUZZY */
        default: $count=$numCats; break; /* AND */
      }
      $sql.=$this->_addSqlJoinCategories($cats, $count, 1);
    }

    if ($numLocs)  {
      $locop=$this->getParam('locop', 0);
      switch ($locop)
      {
        case 1: $count=0; break; /* OR */
        case 2: $count=intval($numLocs*0.75); break; /* FUZZY */
        default: $count=$numLocs; break; /* AND */
      }
      $sql.=$this->_addSqlJoinLocations($locs, $count, 1);
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
  function _addSqlWhereMetaExclusion($tags, $cats, $locs) {
    $numTags=count($tags);
    $numCats=count($cats);
    $numLocs=count($locs);

    if ($numTags+$numCats+$numLocs==0)
      return "";

    $sql =" AND Image.id NOT IN (";
    $sql.=" SELECT Image.id".
          " FROM {$this->_tp}images AS Image";

    if ($numTags) {
      $sql.=" LEFT JOIN {$this->_tp}images_tags AS ImageTag"
          ." ON ( Image.id = ImageTag.image_id )";
    }

    if ($numCats) {
      $sql.=" LEFT JOIN {$this->_tp}categories_images AS CategoryImage"
          ." ON ( Image.id = CategoryImage.image_id )";
    }

    if ($numLocs) {
      $sql.=" LEFT JOIN {$this->_tp}images_locations AS ImageLocation"
          ." ON ( Image.id = ImageLocation.image_id )";
    }

    $sql.=" WHERE 0";
    
    if ($numTags) {
      $data = array();
      foreach ($tags as $tag)
        $data[] = array('name' => $tag); 
      $ids=$this->controller->Tag->createIdList($data);
      if (count($ids)>0)
        $sql.=" OR ImageTag.tag_id IN (".implode(", ", $ids).")";
    }
    
    if ($numCats) {
      $data = array();
      foreach ($cats as $cat)
        $data[] = array('name' => $cat); 
      $ids=$this->controller->Category->createIdList($data);
      if (count($ids)>0)
        $sql.=" OR CategoryImage.category_id IN (".implode(", ", $ids).")";
    }
    
    if ($numLocs) {
      $data = array();
      foreach ($locs as $loc)
        $data[] = array('name' => $loc); 
      $ids=$this->controller->Location->createIdList($data);
      if (count($ids)>0)
        $sql.=" OR ImageLocation.location_id IN (".implode(", ", $ids).")";
    }
    $sql.=$this->controller->Image->buildWhereAcl($this->controller->getUser()).

    $sql.=" )";
    return $sql;
  }

  /** Sets the visiblity of an image. It selects images which are only visible
   * for the group, only for members or visible for the public */
  function _addSqlWhereVisibility() {
    $acl='';
    $visible=$this->getParam('visibility', '');
    switch ($visible) {
    case 'private':
      $acl .= " AND Image.gacl<".ACL_READ_PREVIEW; 
      break;
    case 'group':
      $acl .= " AND Image.gacl>=".ACL_READ_PREVIEW." AND Image.uacl<".ACL_READ_PREVIEW; 
      break;
    case 'member':
      $acl .= " AND Image.uacl>=".ACL_READ_PREVIEW." AND Image.oacl<".ACL_READ_PREVIEW; 
      break;
    case 'public':
      $acl .= " AND Image.oacl>=".ACL_READ_PREVIEW; 
      break;
    default:
      break;
    }
    return $acl;
  }

  /** 
    @return Returns the column order for the selected column. This is needed for
    passing the order from subqueries to upper queries.*/
  function _addSqlColumnOrder($numTags, $numCats, $numLocs) {
    $order='';
    $orderBy=$this->getOrder();
    switch ($orderBy) {
    case 'date':
    case '-date':
      $order.=",Image.date";
      break;
    case 'popularity':
    case '-popularity':
      $order.=",Image.ranking";
      break;
    case 'voting':
    case '-voting':
      $order.=",Image.voting,Image.votes";
      break;
    case 'newest':
    case '-newest':
      $order.=",Image.created";
      break;
    case 'changes':
    case '-changes':
      $order.=",Image.modified";
      break;
    case 'random':
      break;
    default:
      break;
    }

    $hits=array();
    if ($numTags) 
      $hits[] = "thits";
    if ($numCats) 
      $hits[] = "chits";
    if ($numLocs) 
      $hits[] = "lhits";

    if (count($hits)) {
      $order.=",".implode(",", $hits);
      // hits is the product of all meta hits
      for ($i=0; $i<count($hits); $i++)
        $hits[$i]="(".$hits[$i]."+1)";
      $order.=",".implode("*", $hits)." AS hits";
    }

    return $order;
  }

  /** Adds a SQL order statement 
    @return Retruns an SQL order by statement string */
  function _addSqlOrderBy($numTags, $numCats) {
    $order=array();

    if ($numTags>0 && $numCats>0)
      $order[] = "hits DESC";

    $tagop=$this->getParam('tagop', 0);
    if ($numTags>0 && ($tagop==1 || $tagop==2))
      $order[] = "thits DESC";

    $catop=$this->getParam('catop', 0);
    if ($numCats>0 && ($catop==1 || $catop==2))
      $order[] = "chits DESC";

    $orderMap=array('date' => "Image.date DESC", 
           '-date' => "Image.date ASC", 
           'popularity' => "Image.ranking DESC",
           '-popularity' => "Image.ranking ASC",
           'voting' => "Image.voting DESC, Image.votes DESC",
           '-voting' => "Image.voting ASC, Image.votes ASC",
           'newest' => "Image.created DESC",
           '-newest' => "Image.created ASC",
           'changes' => "Image.modified DESC",
           '-changes' => "Image.modified ASC",
           'random' => "RAND()",
           '-random' => "RAND()");
    $tmpOrder = $this->getOrder();
    if (isset($orderMap[$tmpOrder]))
      $order[] = $orderMap[$tmpOrder];

    // At least order by Image ID if order is not deterministic
    $order[] = "Image.id DESC";
    $sql = " ORDER BY ".implode(", ", $order);
      
    return $sql;
  }

  /** Adds the SQL limit statement 
    @param limit If 0 do not limit and return an empty string. If it is 1 the
    limit is calculated by page_size and page_num. If it is 2, the limit is set
    by pos and page_size.  Default is 0. 
    @return SQL limit string */
  function _addSqlLimit($limit=0) {
    $pos=$this->getParam('pos', 1);
    $page=$this->getParam('page', 1);
    $size=$this->getPageSize();

    if ($limit==1) {
      // Limit, use $count
      $pos=($page-1)*$size;
      return " LIMIT $pos," . $size;
    } else if ($limit==2) {
      $pos--;
      return " LIMIT $pos," . $size;
    }
    return '';
  }

  function _addSqlWhere() {
    $sql="";

    // handle IDs of image
    $imageId=$this->getParam('image', 0);
    $userId=$this->getParam('user', 0);
    $groupId=$this->getParam('group', -1);

    if ($imageId>0)  $sql .= " AND Image.id=".$imageId;
    // userId is handled by acl
    if ($groupId>=0) $sql .= " AND Image.group_id=".$groupId;
    
    // handle the acl and visibility level
    $sql.=$this->controller->Image->buildWhereAcl($this->controller->getUser(), $userId);
    $sql.=$this->_addSqlWhereVisibility();

    // handle date
    $start=$this->getParam('from', 0);
    $end=$this->getParam('to', 0);
    if ($start>0)
      $sql .= " AND Image.date>=FROM_UNIXTIME($start)";
    if ($end>0)
      $sql .= " AND Image.date<=FROM_UNIXTIME($end)";

    $type=$this->getParam('filetype');
    if ($type=='image')
      $sql.=" AND Image.duration=-1";
    if ($type=='video')
      $sql.=" AND Image.duration>=0";

    $nameMask=$this->getNameMask();
    if (strlen($nameMask)) {
      $snameMask=mysql_escape_string($nameMask);
      $sql.=" AND Image.name LIKE '%$snameMask%'";
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
    @see _addSqlLimit, _addSqlColumnOrder  */
  function getQuery($limit=1, $order=true) {
    $posTags=array();
    $negTags=array();
    foreach ($this->_tags as $tag) {
      if ($tag[0]=='-')
        array_push($negTags, substr($tag, 1));
      else
        array_push($posTags, $tag);
    }
    $numPosTags=count($posTags);
    $num_negTags=count($negTags);
    
    $posCats=array();
    $negCats=array();
    foreach ($this->_categories as $cat) {
      if ($cat[0]=='-')
        array_push($negCats, substr($cat, 1));
      else
        array_push($posCats, $cat);
    }
    $numPosCats=count($posCats);
    $num_negCats=count($negCats);
   
    $posLocs=array();
    $negLocs=array();
    foreach ($this->_locations as $loc) {
      if ($loc[0]=='-')
        array_push($negLocs, substr($loc, 1));
      else
        array_push($posLocs, $loc);
    }
    $numPosLocs=count($posLocs);
    $num_negLocs=count($negLocs);
    
    $sql="SELECT Image.id";
    if ($order)
      $sql.=$this->_addSqlColumnOrder($numPosTags, $numPosCats, $numPosLocs);

    $sql.=" FROM {$this->_tp}images AS Image";
    $sql.=$this->_addSqlJoinMetaInclusion($posTags, $posCats, $posLocs);
    // Consider only active files
    $sql.=" WHERE Image.flag & ".IMAGE_FLAG_ACTIVE;
    $sql.=$this->_addSqlWhereMetaExclusion($negTags, $negCats, $negLocs);
    $sql.=$this->_addSqlWhere();
    $sql.=" GROUP BY Image.id";

    if ($order)
      $sql.=$this->_addSqlOrderBy($numPosTags, $numPosCats);
    $sql.=$this->_addSqlLimit($limit);

    return $sql; 
  }

  /** Returns the SQL statement to return the count of the query. The query does
   * not order the result. */
  function getNumQuery() {
    $sql="SELECT COUNT(*) AS num FROM ( ".
         $this->getQuery(0, false).
         " ) AS num";
    return $sql;
  }

  /** @return Returns the number of readable image */
  function getNumImages() {
    $sql="SELECT COUNT(id)".
         " FROM {$this->_tp}images AS Image".
         " WHERE 1".
         $this->controller->Image->buildWhereAcl($this->controller->getUser());
    return $this->controller->Image->query($sql);
  }

  function paginate() {
    // get total number of images
    $sql = $this->getNumQuery();
    $result = $this->controller->Image->query($sql);
    $this->_params['count'] = $result[0][0]['num'];

    // adjust page count if required
    $pages = ceil($this->_params['count']/$this->getPageSize());
    if ($this->getPageNum() > $pages)
      $this->setPageNum($pages);

    $data = array();
    if ($this->_params['count']>0) {
      // get the final query
      $sql = $this->getQuery();
      $results = $this->controller->Image->query($sql);
      $user = $this->controller->getUser();
      foreach ($results as $result) {
        $image = $this->controller->Image->optimizedRead($result['Image']['id']);
        $this->controller->Image->setAccessFlags(&$image, &$user);
        $data[] = $image;
      }
    } else {
      $this->controller->Session->setFlash("Sorry. No image or files found!");
    }
    // pagination values
    $this->_params['pages'] = ceil($this->_params['count'] / $this->_params['show']);;
    $this->_params['prevPage'] = $this->_params['page']>1?1:0;
    $this->_params['nextPage'] = $this->_params['page']<$this->_params['pages']?1:0;
    return $data;
  }

  function paginateImage() {
    $tmpImage = $this->getParam('image');
    $this->delParam('image');
    
    // get total number of images
    $sql = $this->getNumQuery();
    $result = $this->controller->Image->query($sql);
    $this->_params['count'] = $result[0][0]['num'];

    // adjust page count and pos if required
    $pages = ceil($this->_params['count']/$this->getPageSize());
    $this->setPageNum(min($pages, $this->getPageNum()));
    $this->_params['pos'] = max(0, min($this->_params['pos'], $this->_params['count']));

    $data = null;
    if ($this->_params['count']>0) {
      // parameter adjustment 
      $tmpShow = $this->_params['show'];
      $tmpPos = $this->_params['pos'];
      $index = 1;
      $this->_params['show'] = 2;
      if ($this->_params['pos'] == 1) {
        $index = 0; // first
      } elseif ($this->_params['pos'] == $this->_params['count']) {
        $this->_params['pos']--; // last
      } else {
        $this->_params['show'] = 3; // middle
        $this->_params['pos']--;
      }

      // get the final query
      $sql = $this->getQuery(2);
      $results = $this->controller->Image->query($sql);
      if (isset($results[$index])) {
        $data = $this->controller->Image->optimizedRead($tmpImage);
        $this->controller->Image->setAccessFlags(&$data, $this->controller->getUser());
        if ($index > 0 && isset($results[$index-1]))
          $this->_params['prevImage'] = $results[$index-1]['Image']['id'];
        if (isset($results[$index+1]))
          $this->_params['nextImage'] = $results[$index+1]['Image']['id'];
      } else {
        $this->controller->Logger->err("Unexpected results of query: $sql");
        $this->controller->Session->setFlash("Sorry. No image or files found!");
      }
    } else {
      $this->controller->Session->setFlash("Sorry. No image or files found!");
    }
    // pagination values
    $this->_params['show'] = $tmpShow;
    $this->_params['pos'] = $tmpPos;
    $this->_params['image'] = $tmpImage;
    $this->_params['pages'] = ceil($this->_params['count'] / $this->_params['show']);
    return $data;
  }

  /** 
    @param num Number of the returned tags. 
    @return Returns an table with 2 columns. The first column is the set name,
  the second column is the number of hits. The table is ordered by descending
  hits */
  function getCloud($num=50, $model='Tag') {
    $results = $this->controller->Image->queryCloud($this->controller->getUser(), $model, $num);
    // Remap and sort could by name
    if (!$results)
      return false;

    $map = Set::combine($results, "{n}.$model.name", "{n}.0.hits");
    ksort($map);
    
    $cloud=array();
    $min=2147483647;
    $max=0;
    foreach($map as $name => $hits) {
      $max = max($hits, $max);
      $min = min($hits, $min);
      $cloud[] = array($model => array('name' => $name, 'hits' => $hits));
    }
    $cloud['_min'] = $min;
    $cloud['_max'] = $max;
    return $cloud;
  }

}
?>
