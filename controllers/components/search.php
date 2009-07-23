<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2009 Sebastian Felis, sebastian@phtagr.org
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

App::import('Core', array('Validation'));
App::import('File', 'Search', array('file' => APP.'search.php'));

class SearchComponent extends Search
{
  var $components = array('QueryBuilder');

  var $Validation = null;

  /** Parameter validation array
    @see http://book.cakephp.org/view/125/Data-Validation */
  var $validate = array(
    'categories' => array('rule' => array('custom', '/^[-]?[\w\d]+$/')),
    'categoryOp' => array('rule' => array('inList', array('AND', 'OR'))),
    'east' => 'decimal',
    'file' => 'alphaNumeric',
    'from' => 'date',
    'group' => 'alphaNumeric',
    'groups' => array('rule' => array('custom', '/^[-]?[\w\d]+$/')),
    'media' => 'numeric',
    'north' => 'decimal',
    'locations' => array('rule' => array('custom', '/^[-]?[\w\d]+$/')),
    'locationOp' => array('rule' => array('inList', array('AND', 'OR'))),
    'page' => array('numericRule' => 'numeric', 'minRule' => array('rule' => array('range', 0))),
    'pos' => array('numericRule' => 'numeric', 'minRule' => array('rule' => array('range', 0))),
    'show' => array('rule' => array('inList', array(6, 12, 24, 60, 120, 240))),
    'sort' => array('rule' => array('inList', array('date', '-date', 'newest', '-newest', 'random'))),
    'tags' => array('rule' => array('custom', '/^[-]?[\w\d]+$/')),
    'tagOp' => array('rule' => array('inList', array('AND', 'OR'))),
    'south' => 'decimal',
    'to' => 'date',
    'user' => 'alphaNumeric',
    'users' => array('rule' => array('custom', '/^[-]?[\w\d]+$/')),
    'west' => 'decimal',
    );

  /** Array of disabled parameter names */
  var $disabled = array();

  /** Base URL for search helper */
  var $base = '/explorer/query/';

  /** Default values */
  var $defaults = array(
    'categoryOp' => 'AND',
    'locationOp' => 'AND',
    'page' => '1',
    'show' => '12',
    'sort' => 'date',
    'tagOp' => 'AND'
    );

  /** Parameter data */
  var $data = array();

  var $controller = null;

  function initialize(&$controller) {
    $this->controller = &$controller;
    $this->Validation =& Validation::getInstance();
    $this->clear();
  }

  function clear() {
    $this->data = $this->defaults;
  }

  /** Validates the parameter value
    @param name Parameter name
    @param value Parameter value
    @result True on success validation */
  function validate($name, $value) {
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
      foreach ($ruleSet as $name => $rule) {
        $result &= $this->_dispatchRule($rule, $value);
        if (!$result) {
          Logger::verbose("Failed multiple rules on rule '$name': $value");
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
    } elseif (method_exists(&$this->Validation, $rule)) {
      $result = $this->Validation->dispatchMethod($rule, $params);
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

  /** parse all parameters given in the URL and adds them to the search */
  function parseArgs() {
    foreach($this->controller->passedArgs as $name => $value) {
      if (is_numeric($name) || empty($value)) {
        continue;
      }
      if ($name == Inflector::pluralize($name)) {
        $values = explode(',', $value);
        $this->addParam($name, $values);
      } else {
        $this->setParam($name, $value);
      }
    }
  }

  function paginate() {
    $params = $this->getParams();
    $this->controller->paginate = $this->QueryBuilder->build($params); 
    $data = $this->controller->paginate('Media');

    // repage to last page if page exceeds the page count 
    if (!$data and $this->controller->params['paging']['Media']['count'] > 0) {
      $paging = $this->controller->params['paging']['Media'];
      $offset = ($paging['pageCount'] - 1) * $this->controller->paginate['limit'];
      $this->controller->paginate['offset'] = $offset;
      $data = $this->controller->paginate('Media');

      // update search parameter
      $this->setPage($paging['pageCount']);
    }

    // Set data for search helper
    $params = $this->controller->params['paging']['Media'];
    $params['base'] = $this->base;
    $params['defaults'] = $this->defaults;
    $params['data'] = $this->getParams();
    $this->controller->params['search'] = $params;

    return $data;
  }
}
?>
