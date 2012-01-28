<?php
/*
 * phtagr.
 * 
 * social photo gallery for your community.
 * 
 * Copyright (C) 2006-2010 Sebastian Felis, sebastian@phtagr.org
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

if (!App::uses('Validation', 'Utility')) {
  CakeLog::write('error', 'Could not load validation');
}
if (!class_exists('Search')) {
  App::import('File', 'Search', array('file' => APP.'search.php'));
}

class SearchComponent extends Component
{
  var $components = array('QueryBuilder');

  var $controller = null;
  
  var $_data;

  /** Parameter validation array
    @see http://book.cakephp.org/view/125/Data-Validation */
  var $validate = array(
    'categories' => array('rule' => array('maxLength', 30)),
    'category_op' => array('rule' => array('inList', array('AND', 'OR'))),
    'created_from' => array('rule' => array('custom', '/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2})?$/')),
    'east' => 'decimal',
    'exclude_user' => 'numeric',
    'from' => array('rule' => array('custom', '/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2})?$/')),
    'folder' => 'notEmpty',
    'groups' => 'notEmpty',
    'key' => false,
    'media' => 'numeric',
    'name' => 'notEmpty',
    'north' => 'decimal',
    'locations' => array('rule' => array('maxLength', 30)),
    'location_op' => array('rule' => array('inList', array('AND', 'OR'))),
    'operand' => array('rule' => array('inList', array('AND', 'OR'))),
    'page' => array('numericRule' => 'numeric', 'minRule' => array('rule' => array('comparison', '>=', 1))),
    'pos' => array('numericRule' => 'numeric', 'minRule' => array('rule' => array('comparison', '>=', 1))),
    'show' => array('numericRule' => 'numeric', 'minRule' => array('rule' => array('comparison', '>=', 1)), 'maxRule' => array('rule' => array('comparison', '<=', 240))),
    'sort' => array('rule' => array('inList', array('date', '-date', 'newest', 'changes', 'viewed', 'popularity', 'random', 'name'))),
    'south' => 'decimal',
    'tags' => array('rule' => array('maxLength', 30)),
    'tag_op' => array('rule' => array('inList', array('AND', 'OR'))),
    'to' => array('rule' => array('custom', '/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2})?$/')),
    'type' => array('rule' => array('inList', array('image', 'video'))),
    'user' => 'alphaNumeric',
    'users' => array('rule' => array('custom', '/^-?[\w\d]+$/')),
    'west' => 'decimal',
    'visibility' => array('rule' => array('inList', array('private', 'group', 'user', 'public'))),
    );

  /** Array of disabled parameter names */
  var $disabled = array();

  /** Base URL for search helper */
  var $baseUri = '/explorer/query';

  /** Default values */
  var $defaults = array(
    'page' => '1',
    'pos' => false,
    'show' => '12',
    'sort' => 'default',
    );

  function initialize(&$controller) {
    $this->controller = &$controller;
    if (!$this->QueryBuilder->controller) {
      $this->QueryBuilder->initialize(&$controller);
    }
    $this->clear();
    return true;
  }

  function clear() {
    $this->_data = $this->defaults;
  }

  /** Returns all parameters 
    @return Parameter array */
  function getParams() {
    return $this->_data;
  }

  /** Set all parameters 
    @param data Parameter array
    @note The parameters are not validated! */
  function setParams($data = array()) {
    $this->_data = $data;
  }

  /** Returns parameter 
    @param name Name of parameter 
    @param default Default value, if the parameter does not exists. Default
    value is null */
  function getParam($name, $default = null) {
    if (!empty($this->_data[$name])) {
      return $this->_data[$name];
    } else {
      return $default;
    }
  }

  /** Set a singular parameter 
    @param name Parameter name
    @param value Parameter value
    @param validate Optional parameter to validate the parameter. Default is
    true
    @return True on success */
  function setParam($name, $value, $validate = true) {
    if ($validate === false || $this->validate($name, $value)) {
      $this->_data[$name] = $value;
      return true;
    } else {
      return false;
    }
  }

  /** Add a parameter to an array.
    @param name Parameter name. 
    @param value Parameter value (which will be pluralized)
    @param validate Optional parameter to validate the parameter. Default is
    true
    @note The name will be pluralized. */
  function addParam($name, $value, $validate = true) {
    $name = Inflector::pluralize($name);
    if (is_array($value)) {
      foreach ($value as $v) {
        $this->addParam($name, $v, $validate);
      }
      return;
    }
    
    if ((!isset($this->_data[$name]) || !in_array($value, $this->_data[$name])) &&
      ($validate === false || $this->validate($name, $value))) {
      $this->_data[$name][] = $value;
    }
  }

  function delParam($name, $value = false) {
    if (!isset($this->_data[$name])) {
      return;
    }
    
    if (!empty($value)) {
      if (is_array($value)) {
        foreach ($value as $v) {
          $this->delParam($name, $v);
        }
        return;
      }
      // handle array
      $key = array_search($value, $this->_data[$name]);
      if ($key !== false) {
        unset($this->_data[$name][$key]);
      }
      if (count($this->_data[$name]) == 0) {
        unset($this->_data[$name]);
      }
    } else {
      // handle single value
      unset($this->_data[$name]);
    }
  }

  function decode($input) {
    $out = '';
    $input = (string)$input;
    $len = strlen($input);
    for ($i = 0; $i < $len; $i++) {
      $c = substr($input, $i, 1);
      if ($c == '=') {
        if ($i + 2 >= $len) {
          break;
        }
        $c1 = substr($input, $i + 1, 1);
        $c2 = substr($input, $i + 2, 1);
        $c = $this->_dechex($c1, $c2);
        if ($c !== false) {
          $out .= $c;
        }
        $i += 2;
      } else {
        $out .= $c;
      }
    }
    return $out;
  }

  function __call($name, $args) {
    if (!preg_match('/^(get|set|add|del|delete)(.*)$/', $name, $matches)) {
      $this->log("Undefined function $name");
      return;
    }
    $name = Inflector::underscore($matches[2]);
    switch ($matches[1]) {
      case 'get':
        if (count($args) > 0) {
          return $this->getParam($name, $args[0]);
        } else {
          return $this->getParam($name);
        }
        break;
      case 'set':
        if (count($args) == 1) {
          return $this->setParam($name, $args[0]);
        } elseif (count($args) == 2) {
          return $this->setParam($name, $args[0], $args[1]);
        }
        break;
      case 'add':
        if (count($args) == 1) {
          return $this->addParam($name, $args[0]);
        } elseif (count($args) == 2) {
          return $this->addParam($name, $args[0], $args[1]);
        }
        break;
      case 'del':
      case 'delete':
        if (count($args) == 1) {
          if (!isset($this->_data[$name])) {
            $plural = Inflector::pluralize($name);
            if (isset($this->_data[$plural])) {
              $name = $plural;
            }
          }
          $this->delParam($name, $args[0]);
        } else {
          $this->delParam($name);
        }
        break;
    }
  }

  /** Validates the parameter value
    @param name Parameter name
    @param value Parameter value
    @result True on success validation */
  function validate($name, $value) {
    if (!$value && $value !== 0) {
      Logger::verbose("Parameter value of '$name' is empty!");
      return false;
    }
    if (!isset($this->validate[$name])) { 
      // check for parameter without validation
      $key = array_search($name, $this->validate); 
      if ($key !== false && is_numeric($key)) {
        if (in_array($name, $this->disabled)) {
          Logger::verbose("Parameter '$name' is disabled");
          return false;
        }
        Logger::verbose("Parameter '$name' has no validation!");
        return true;
      }
      Logger::verbose("Parameter '$name' does not exists");
      return false;
    } 
    if (in_array($name, $this->disabled)) {
      Logger::verbose("Parameter '$name' is disabled");
      return false;
    }

    $ruleSet = $this->validate[$name];
    $result = false;
    if (!is_array($ruleSet) || isset($ruleSet['rule'])) {
      $result = $this->_dispatchRule($ruleSet, $value);
    } else {
      $result = true;
      foreach ($ruleSet as $ruleName => $rule) {
        $result &= $this->_dispatchRule($rule, $value);
        if (!$result) {
          Logger::verbose("Failed multiple rules on rule '$ruleName' ($name): $value");
        }
      }
    }
    return $result;
  }

  /** Dispatch the rule 
    @param ruleSet Rule name or single rule array
    @param check Value to check
    @result True on successful validation */
  function _dispatchRule($ruleSet, $check) {
    if (!is_array($ruleSet)) {
      $rule = $ruleSet;
      $params = array($check);
    } elseif (!isset($ruleSet['rule'])) {
      Logger::err("Rule definition is missing");
      return false;
    } elseif (is_array($ruleSet['rule'])) {
      $rule = $ruleSet['rule'][0];
      $params = $ruleSet['rule'];
      $params[0] = $check;
    } else {
      $rule = $ruleSet['rule'];
      $params = array($check);
    }

    if (method_exists(&$this, $rule)) {
      $result = $this->dispatchMethod($rule, $params);
    } elseif (method_exists('Validation', $rule)) {
      $result = call_user_func_array(array('Validation', $rule), $params);
    } else {
      Logger::debug("Rule '$rule' could not be found");
      return false;
    }

    if (!$result) {
      Logger::verbose("Failed rule '$rule': $check");
      if (isset($ruleSet['message'])) {
        Logger::debug($ruleSet['message']);
      }
    }
    return $result;
  }

  /** Set the disabled search fields according to the user role */
  function _setDisabledFields() {
    // disable search parameter after role
    $role = $this->controller->getUserRole();
    $disabled = array();
    switch ($role) {
      case ROLE_NOBODY:
        $disabled[] = 'file';
      case ROLE_GUEST:
        $disabled[] = 'groups';
        $disabled[] = 'visibility';
      case ROLE_USER:
      case ROLE_SYSOP:
      case ROLE_ADMIN:
        break;
      default:
        Logger::err("Unhandled role $role");
    }
    $this->disabled = $disabled;
  }

  /** parse all parameters given in the URL and adds them to the search */
  function parseArgs() {
    $this->_setDisabledFields();
    foreach($this->controller->passedArgs as $name => $value) {
      if (is_numeric($name) || empty($value)) {
        continue;
      }
      $singles = array('pos', 'folder'); // quick fix
      if (!in_array($name, $singles) && $name == Inflector::pluralize($name)) {
        $values = explode(',', $value);
        $this->addParam($name, $values);
      } else {
        $this->setParam($name, $this->decode($value));
      }
    }
  }

  /** Convert an URL to decoded crumbs
   * 
   * @param $url Current URL
   * @param $skip Skip parts splited by slash '/'
   * @return Array of crumbs
   */
  function urlToCrumbs($url, $skip = 2) {
    $encoded = array_splice(split('/', trim($url, '/')), $skip);
    $crumbs = array();
    foreach ($encoded as $crumb) {
      if (!preg_match('/^(\w+):(.+)$/', $crumb, $matches)) {
        continue;
      }
      $crumb = Inflector::singularize($matches[1]) . ":" . trim($matches[2]);
      $crumbs[] = $this->decode($crumb);
    }
    return $crumbs;
  }
  
  /** Encode crumbs for an final URL string
   * 
   * @param $crumbs Array of crumbs
   * @return Array of encoded crumbs for an URL
   */
  function encodeCrumbs($crumbs) {
    $escaped = array();
    foreach ($crumbs as $crumb) {
      if (!preg_match('/^(\w+):(.*)$/', $crumb, $matches)) {
        continue;
      }
      $escaped[] = $matches[1] . ":" . $this->encode($matches[2]);
    }
    return $escaped;
  }
  
  /** Convert the current search parameter to breadcrump stack
    @return Array of crumbs */
  function convertToCrumbs() {
    $params = $this->getParams();
    $crumbs = array();
    foreach ($params as $name => $value) {
      if (is_array($value)) {
        $name = Inflector::singularize($name);
        foreach ($value as $crumb) {
          $crumbs[] = "$name:$crumb";
        }
      } else {
        $crumbs[] = "$name:$value";
      }
    }
    return $crumbs;
  }

  function _getParameterFromCrumbs($crumbs) {
    $listTypes = array('tag', 'category', 'location', 'group');
    foreach ($crumbs as $crumb) {
      if (empty($crumb)) {
        continue;
      }
      if (!preg_match('/^(\w+):(.*)$/', $crumb, $match)) {
        Logger::warn("Invalid crumb: $crumb");
        continue;
      }
      $type = Inflector::singularize($match[1]);
      $value = $match[2];
      if (in_array($type, $listTypes)) {
        $values = split(',', $value);
        foreach ($values as $value) {
          $this->addParam($type, $value);
        }
      } else {
        $this->setParam($type, $value);
      }
    }
  }

  function paginateByCrumbs($crumbs) {
    $tmp = $this->getParams();
    $this->clear();
    $this->_getParameterFromCrumbs($crumbs);
    $data = $this->paginate();
    $this->setParams($tmp);
    return $data;
  }

  function paginate() {
    $query = $this->QueryBuilder->build($this->getParams()); 
    $tmp = $query;
    unset($query['limit']);
    unset($query['page']);
    $count = $this->controller->Media->find('count', $query);
    $query = $tmp;

    $params = array(
      'pageCount' => 0, 
      'current' => 0, 
      'nextPage' => false, 
      'prevPage' => false,
      'baseUri' => $this->baseUri,
      'afterUri' => false,
      'defaults' => $this->defaults,
      'data' => $this->getParams()
      );

    if ($count == 0) {
      $this->controller->request->params['search'] = $params;
      return array();
    }
    $params['pageCount'] = ceil($count / $this->getShow(12));
    if ($this->getPage() > $params['pageCount']) {
      $this->setPage($params['pageCount']);
      $params['data'] = $this->getParams();
      $query['page'] = $params['pageCount'];
    }
    if ($this->getPage() > 1) {
      $params['prevPage'] = true;
    }
    if ($this->getPage() < $params['pageCount']) {
      $params['nextPage'] = true;
    }
    $params['page'] = $this->getPage();

    // get all media and set access flags
    $data = $this->controller->Media->find('all', $query);
    $user = $this->controller->getUser();
    for ($i = 0; $i < count($data); $i++) {
      $this->controller->Media->setAccessFlags(&$data[$i], $user);
    }
    
    // Set data for search helper
    $this->controller->request->params['search'] = $params;

    return $data;
  }

  /** 
    Sets the data for the search helper 
    */
  function setHelperData() {
    $params = array(
      'pageCount' => 0, 
      'current' => 0, 
      'nextPage' => false, 
      'prevPage' => false,
      'baseUri' => $this->baseUri,
      'afterUri' => false,
      'defaults' => $this->defaults,
      'data' => $this->getParams()
      );
    $this->controller->request->params['search'] = $params;
  }

  function paginateMediaByCrumb($id, $crumbs) {
    $tmp = $this->getParams();
    $this->clear();
    $this->_getParameterFromCrumbs($crumbs);
    $data = $this->paginateMedia($id);
    $this->setParams($tmp);
    return $data;
  }

  function paginateMedia($id) {
    $query = $this->QueryBuilder->build($this->getParams()); 
    unset($query['limit']);
    unset($query['offset']);
    $count = $this->controller->Media->find('count', $query);

    $params = array(
      'pos' => 0, 
      'current' => false,
      'prevMedia' => false,
      'nextMedia' => false, 
      'afterUri' => false,
      'baseUri' => $this->baseUri,
      'defaults' => $this->defaults,
      'data' => $this->getParams()
      );

    $data = $this->controller->Media->findById($id);
    $user = $this->controller->getUser();
    $access = $this->controller->Media->checkAccess(&$data, $user, ACL_READ_PREVIEW, ACL_READ_MASK);
    if ($count == 0 || !$data || !$access) {
      if (!$data) {
        Logger::info("Media $id not found");
      } else {
        Logger::verbose("Deny access to media $id");
      }
      $this->controller->request->params['search'] = $params;
      return array();
    }
    $this->controller->Media->setAccessFlags(&$data, $user);

    $pos = $this->getPos(1);
    $mediaOffset = 1; // offset from previews media
    $show = 3; // show size
    if ($count > 2) {
      if ($count <= $pos) {
        // last media [current, next]
        $this->setPos($count);
        $params['data'] = $this->getParams();
        $pos = $count - 1;
        $show = 2;
      } elseif ($pos == 1) {
        // first media [prev, current]
        $show = 2;
        $mediaOffset = 0;
      } else {
        // [prev, current, next]
        $pos--;
      }
    } elseif ($count == 2) {
      $show = 2;
      if ($pos == 1) {
        // [current, next]
        $mediaOffset = 0;
      } // else [prev, current]
    } else {
      // [current]
      $show = 1;
      $mediaOffset = 0;
    }

    // get neigbors
    $query['fields'] = 'Media.id';
    $query['offset'] = $pos - 1;
    $query['limit'] = $show;
    if ($show > 1) {
      $result = $this->controller->Media->find('all', $query);
      $ids = Set::extract('/Media/id', $result);
      if ($mediaOffset == 1) {
        $params['prevMedia'] = $ids[0];
      } 
      if (isset($ids[$mediaOffset + 1])) {
        $params['nextMedia'] = $ids[$mediaOffset + 1];
      } 
    }

    $params['current'] = $id;
    $params['pos'] = $this->getPos(1);

    // Set data for search helper
    $this->controller->request->params['search'] = $params;
 
    return $data;
  }

  function quicksearch($text, $show = 12) {
    $words = preg_split('/\s+/', trim($text));

    $tmp = array();
    foreach($words as $word) {
      $tmp[] = '*' . $word . '*';
    }
    $words = $tmp;

    $this->addTags($words, false);
    $this->addCategories($words, false);
    $this->addLocations($words, false);
    $this->setOperand('OR');

    $this->setSort('default', false);
    $this->setShow($show);

    return $this->paginate();
  }    
}
?>
