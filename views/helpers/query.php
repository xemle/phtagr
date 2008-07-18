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

class QueryHelper extends AppHelper {
  var $helpers = array('html'); 

  /** Skip specific query parameters for multiple query */
  var $_excludePage = array('prevPage' => true, 
                    'nextPage' => true,
                    'prevImage' => true, 
                    'nextImage' => true, 
                    'count' => true, 
                    'pages' => true, 
                    'page' => 1, 
                    'show' => 12,
                    'pos' => 1, 
                    'image' => true,
                    'video' => true,
                    'myimage' => true
                    );

  /** Skip specific query parameters for single query */
  var $_excludeImage = array('prevImage' => true,
                    'nextImage' => true,
                    'count' => true,
                    'pages' => true,
                    'page' => 1,
                    'show' => 12,
                    'pos' => 1,
                    'image' => true,
                    'videw' => true,
                    'myimage' => true
                    );

  var $_query = array(); 

  /** Initialize query parameters from the global parameter array, which is
   * set by the query component */
  function initialize() {
    if (!isset($this->params['query']))
      return;
    $this->_query = $this->params['query'];
  }

  /** 
    @param query Optional query array
    @return Array of current query options with the syntax of
    name:value[,value...]
   */
  function _buildParams($query = null, $exclude = null) {
    if ($query == null)
      $query = &$this->_query;
    if ($exclude == null)
      $exclude = &$this->_excludePage;

    $params = array();
    foreach ($query as $name => $value) {
      if (isset($exclude[$name]) && 
        ($exclude[$name] === true || $exclude[$name] == $value))
        continue;
      if (is_array($value)) {
        // arrays like tags, categories, locations
        if (count($value)) {
          $params[] = $name.':'.implode(',', $value);
        }
      } else {
        $params[] = $name.':'.$value;
      }
    }
    return $params;
  }

  /** Returns all parameters of the current query */
  function getQuery() {
    return $this->_query;
  }

  function setQuery($query) {
    $this->_query = $query;
  }

  /** Clear all parameter values of the query */
  function clear() {
    $this->_query = array();
  }

  /** Set the current parameter with the given value. This function will
   * overwrite the existing value(s)
   * @param name Parameter name 
   * @param value Parameter value */
  function set($name, $value) {
    $this->_query[$name] = $value;    
  }

  /** Returns a specific parameter by name
    @param name Parameter name
    @param default Default value, if parameter is not set. Default is null
    @return Parameter value */
  function get($name, $default = null) {
    if (isset($this->_query[$name]))
      return $this->_query[$name];
    else
      return $default;
  }

  /** Adds the value to the current parameter name. If the parameter is not an
   * array, it converts it to an array and adds the values to the stack 
   * @param name Parameter name 
   * @param value Parameter value */
  function add($name, $value) {
    if (!isset($this->_query[$name]))
      $this->_query[$name] = array();
    
    if (!is_array($this->_query[$name])) {
      $value = $this->_query[$name];
      $this->_query[$name] = array($value);
    }
       
    if (!in_array($value, $this->_query[$name]))
      array_push($this->_query[$name], $value);
  }

  /** Removes a parameter value of the parameter name. If more than one value
   * is stacked to the parameter value, is delets only the given value.
   * @param name Parameter name 
   * @param value Parameter value */
  function del($name, $value = null) {
    if (!isset($this->_query[$name]))
      return;

    if (!is_array($this->_query[$name])) {
      unset($this->_query[$name]);
    } elseif ($value !== null) {
      $key = array_search($value, $this->_query[$name]);
      if ($key !== false)
        unset($this->_query[$name][$key]);

      // Removes parameter if no value exists
      if (!count($this->_query[$name]))
        unset($this->_query[$name]);
    }
  }

  function delete($name, $value = null) {
    $this->del($name, $value);
  }

  function getParams($query = null, $exclude = null) {
    return implode('/', $this->_buildParams($query, $exclude));
  }

  /** @param query Optional query array
    @return uri of current query */
  function getUri($query = null, $exclude = null, $base = null) {
    $params = $this->_buildParams($query, $exclude);
    if (!$base) {
      $base = '/explorer/query/';
    }
    return $base.implode('/', $params);
  }

  function hasPages() {
    if (isset($this->params['query']['pages']) &&
      $this->params['query']['pages'] > 1)
      return true;
    return false;
  }

  function hasPrev() {
    return (isset($this->params['query']['prevPage']) &&
      $this->params['query']['prevPage']);
  }

  function getPrevUrl($base = null) {
    if (!$this->hasPrev()) {
      return false;
    }
    $params = $this->params['query'];
    $params['page']--;
    return $this->getUri($params, $this->_excludePage, $base);
  }

  function prev() {
    $prevUrl = $this->getPrevUrl();
    if (!$prevUrl) {
      return false;
    }
    return $this->html->link('prev', $prevUrl, array('class' => 'prev'));
  }

  function numbers() {
    if (!isset($this->params['query']))
      return;
    $output = '';
    $query = $this->params['query'];
    $exclude = am($this->_excludePage, array('pos' => true));
    if ($query['pages'] > 1) {
      $count = $query['pages'];
      $current = $query['page'];
      for ($i = 1; $i <= $count; $i++) {
        if ($i == $current) {
          $output .= " <span class=\"current\">$i</span> ";
        }
        else if ($count <= 12 ||
            ($i < 3 || $i > $count-2 ||
            ($i-$current < 4 && $current-$i<4))) {
          $query['page']=$i;
          $output .= ' '.$this->html->link($i, $this->getUri($query, $exclude));
        }
        else if ($i == $count-2 || $i == 3) {
          $output .= " ... ";
        }
      }
    }
    return $output;
  }

  function hasNext() {
    return (isset($this->params['query']['nextPage']) &&
      $this->params['query']['nextPage']);
  }

  function getNextUrl($base = null) {
    if (!$this->hasNext()) {
      return false;
    }
    $params = $this->params['query'];
    $params['page']++;
    return $this->getUri($params, $this->_excludePage, $base);
  }

  function next() {
    $nextUrl = $this->getNextUrl();
    if (!$nextUrl) {
      return false;
    }
    return $this->html->link('next', $nextUrl, array('class' => 'next'));
  }

  function prevImage() {
    if (!isset($this->params['query']))
      return;
    $query = $this->params['query'];
    if (isset($query['prevImage'])) {
      $query['pos']--;
      $query['page'] = ceil($query['pos'] / $query['show']);
      return $this->html->link('prev', '/images/view/'.$query['prevImage'].'/'.$this->getParams($query, $this->_excludeImage), array('class' => 'prev'));
    }
  }

  function up() {
    if (!isset($this->params['query']))
      return;
    $query = $this->params['query'];
    $query['page'] = ceil($query['pos'] / $query['show']);
    $exclude = am($this->_excludeImage, array('image' => true, 'pos' => true));
    return $this->html->link('up', $this->getUri($query, $exclude).'#image-'.$query['image'], array('class' => 'up'));
  }

  function nextImage() {
    if (!isset($this->params['query']))
      return;
    $query = $this->params['query'];
    if (isset($query['nextImage'])) {
      $query['pos']++;
      $query['page'] = ceil($query['pos'] / $query['show']);
      return $this->html->link('next', '/images/view/'.$query['nextImage'].'/'.$this->getParams($query, $this->_excludeImage), array('class' => 'next'));
    }
  }
}
