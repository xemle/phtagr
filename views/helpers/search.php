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

class SearchHelper extends AppHelper {
  var $helpers = array('html'); 

  /** Skip specific search parameters */
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
                    'video' => true
                    );

  var $_excludeImage = array('prevImage' => true,
                    'nextImage' => true,
                    'count' => true,
                    'pages' => true,
                    'page' => 1,
                    'show' => 12,
                    'pos' => 1,
                    'image' => true,
                    'videw' => true
                    );

  var $_search = array(); 

  /** Initialize search parameters from the global parameter array, which is
   * set by the search component */
  function initialize() {
    if (!isset($this->params['search']))
      return;
    $this->_search = $this->params['search'];
  }

  /** 
    @param search Optional search array
    @return Array of current search options with the syntax of
    name:value[,value...]
   */
  function _buildParams($search = null, $exclude = null) {
    if ($search == null)
      $search = &$this->_search;
    if ($exclude == null)
      $exclude = &$this->_excludePage;

    $params = array();
    foreach ($search as $name => $value) {
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

  /** Returns all parameters of the current search */
  function getSearch() {
    return $this->_search;
  }

  function setSearch($search) {
    $this->_search = $search;
  }

  /** Clear all parameter values of the search */
  function clear() {
    $this->_search = array();
  }

  /** Set the current parameter with the given value. This function will
   * overwrite the existing value(s)
   * @param name Parameter name 
   * @param value Parameter value */
  function set($name, $value) {
    $this->_search[$name] = $value;    
  }

  /** Returns a specific parameter by name
    @param name Parameter name
    @param default Default value, if parameter is not set. Default is null
    @return Parameter value */
  function get($name, $default = null) {
    if (isset($this->_search[$name]))
      return $this->_search[$name];
    else
      return $default;
  }

  /** Adds the value to the current parameter name. If the parameter is not an
   * array, it converts it to an array and adds the values to the stack 
   * @param name Parameter name 
   * @param value Parameter value */
  function add($name, $value) {
    if (!isset($this->_search[$name]))
      $this->_search[$name] = array();
    
    if (!is_array($this->_search[$name])) {
      $value = $this->_search[$name];
      $this->_search[$name] = array($value);
    }
       
    if (!in_array($value, $this->_search[$name]))
      array_push($this->_search[$name], $value);
  }

  /** Removes a parameter value of the parameter name. If more than one value
   * is stacked to the parameter value, is removes only the given value.
   * @param name Parameter name 
   * @param value Parameter value */
  function remove($name, $value = null) {
    if (!isset($this->_search[$name]))
      return;

    if (!is_array($this->_search[$name])) {
      unset($this->_search[$name]);
    } elseif ($value !== null) {
      $key = array_search($value, $this->_search[$name]);
      if ($key !== false)
        unset($this->_search[$name][$key]);

      // Removes parameter if no value exists
      if (!count($this->_search[$name]))
        unset($this->_search[$name]);
    }
  }

  function getParams($search = null, $exclude = null) {
    return implode('/', $this->_buildParams($search, $exclude));
  }

  /** @param search Optional search array
    @return uri of current search */
  function getUri($search = null, $exclude = null) {
    $params = $this->_buildParams($search, $exclude);
    return '/'.$this->params['controller'].'/search/'.implode('/', $params);
  }

  function prev() {
    if (!isset($this->params['search']))
      return;
    $search = $this->params['search'];
    $exclude = am($this->_excludePage, array('pos' => true));
    if ($search['prevPage']) {
      $search['page']--;
      return $this->html->link('prev', $this->getUri($search, $exclude));
    }
  }
  
  function numbers() {
    if (!isset($this->params['search']))
      return;
    $output = '';
    $search = $this->params['search'];
    $exclude = am($this->_excludePage, array('pos' => true));
    if ($search['pages'] > 1) {
      $count = $search['pages'];
      $current = $search['page'];
      for ($i = 1; $i <= $count; $i++) {
        if ($i == $current) {
          $output .= " <span class=\"current\">$i</span> ";
        }
        else if ($count <= 12 ||
            ($i < 3 || $i > $count-2 ||
            ($i-$current < 4 && $current-$i<4))) {
          $search['page']=$i;
          $output .= ' '.$this->html->link($i, $this->getUri($search, $exclude));
        }
        else if ($i == $count-2 || $i == 3) {
          $output .= " ... ";
        }
      }
    }
    return $output;
  }

  function next() {
    if (!isset($this->params['search']))
      return;
    $search = $this->params['search'];
    $exclude = am($this->_excludePage, array('pos' => true));
    if ($search['nextPage']) {
      $search['page']++;
      return $this->html->link('next', $this->getUri($search, $exclude));
    }
  }

  function prevImage() {
    if (!isset($this->params['search']))
      return;
    $search = $this->params['search'];
    if (isset($search['prevImage'])) {
      $search['pos']--;
      $search['page'] = ceil($search['pos'] / $search['show']);
      return $this->html->link('prev', '/explorer/image/'.$search['prevImage'].'/'.$this->getParams($search, $this->_excludeImage));
    }
  }

  function up() {
    if (!isset($this->params['search']))
      return;
    $search = $this->params['search'];
    $search['page'] = ceil($search['pos'] / $search['show']);
    $exclude = am($this->_excludeImage, array('image' => true, 'pos' => true));
    return $this->html->link('up', $this->getUri($search, $exclude).'#image-'.$search['image']);
  }

  function nextImage() {
    if (!isset($this->params['search']))
      return;
    $search = $this->params['search'];
    if (isset($search['nextImage'])) {
      $search['pos']++;
      $search['page'] = ceil($search['pos'] / $search['show']);
      return $this->html->link('next', '/explorer/image/'.$search['nextImage'].'/'.$this->getParams($search, $this->_excludeImage));
    }
  }
}
