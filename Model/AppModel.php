<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

App::uses('Model', 'Model');

class AppModel extends Model
{
  /** Create an id list of specifc items
   @param items Array of items with partial row information like the following.

  \code
  Array
  (
      [0] => Array
          (
              [name] => germany
              [type] => 1
          )

      [1] => Array
          (
              [name] => canada
          )

  )
  \endcode
   @param create If true, missing items are created. Default is false
   @return Array of ids */
  function createIdList($items, $create=false) {
    $ids = array();

    if (empty($items))
      return $ids;

    foreach ($items as $item) {
      if ($item['name'][0]=='-') {
        $item['name'] = substr($item['name'], 1);
      }
      $data = $this->find('all', array('conditions' => array('and' => $item), 'fields' => array('id')));
      if (!empty($data)) {
        $newIds = Set::extract($data, '{n}.'.$this->name.'.id');
        $ids = array_merge($ids, $newIds);
      } else {
        if ($create==true) {
          // no item was found, but create a new one
          //Logger::trace('Create item of '.$this->name);
          $new = $this->create($item);
          if ($new && $this->save($new)) {
            $ids[] = $this->getInsertID();
          } else {
            Logger::err($this->name.": Could not create new item ".$item['name']);
          }
        } else {
          // add dummy empty entry
          if (!in_array(-1, $ids))
            $ids[] = -1;
        }
      }
    }

    return array_unique($ids);
  }

  /** Create model simple items from a text by splitting the text by the separator ','
    @param text Text to be splitted in items
    @param name Name of column. Default is 'name'
    @param sep Optional separator. Default is ','
    @return Array of items, which can be created by the model */
  function createItems($text, $name='name', $sep=',') {
    $items = explode($sep, $text);
    foreach ($items as $key => $item) {
      $item = trim($item);
      if (strlen($item) == 0 || $item == '-') {
        unset($items[$key]);
        continue;
      }
      $items[$key] = $item;
    }
    $items = array_unique($items);

    $list = array();
    foreach ($items as $item) {
      $list[] = array($name => $item);
    }
    return $list;
  }

  /** Filter the items list according to inclusion or exclusion
    @param item Item list
    @param includes If true, only items for inclusition are returned. If false,
    only items for exclusion are returned. In this case the minus char is
    stripped. Default is true
    @param Filtered list */
  function filterItems($items, $includes=true, $name='name') {
    $new = array();
    foreach ($items as $item) {
      if (!isset($item[$name]))
        continue;
      if (strlen($item[$name])>0 && $item[$name][0] == '-')
        $isExclude = true;
      else
        $isExclude = false;

      if ($includes && ! $isExclude) {
        $new[] = $item;
      } elseif (!$includes && $isExclude) {
        $item[$name] = substr($item[$name], 1);
        $new[] = $item;
      }
    }
    return $new;
  }

  /** This function creates the input array for a single column.
    @param values Text
    @param name Name of the name. Default is 'name'.
    @param create If true, items are created if not exists.
    @return Array of ids
    @see createIdList */
  function createIdListFromText($text, $name='name', $create=false) {
    if (!strlen($text))
      return array();

    $items = $this->createItems($text, $name);
    $items = $this->filterItems($items);
    return $this->createIdList($items, $create);
  }

  function unbindAll($params = array()) {
    $bindings = array(
        'belongsTo' => array_keys($this->belongsTo),
        'hasOne' => array_keys($this->hasOne),
        'hasMany' => array_keys($this->hasMany),
        'hasAndBelongsToMany' => array_keys($this->hasAndBelongsToMany));
    $this->unbindModel($bindings);
    return true;
  }

  /** Convert the return code and the message to an array
    @param code Return code (Inspired by HTML return codes)
      - 100 Continue
      - 200 OK
      - 201 Created
      - 202 Accepted
      - 204 No Content
      - 205 Reset Content
      - 300 Multiple Choices
      - 301 Moved Permanently
      - 302 Moved Temporarily
      - 303 See Other
      - 304 Not Modified
      - 400 Bad Request
      - 401 Unauthorized
      - 403 Forbidden
      - 404 Not Found
      - 405 Method Not Allowed
      - 406 Not Acceptable
      - 409 Conflict
      - 410 Gone
      - 412 Precondition Failed
      - 415 Unsupported Media Type
      - 500 Server Error
      - 501 Not Implemented
      - 502 Bad Gateway
      - 503 Out of Resources
      - 504 Gateway Time-Out
      - 505 Version not supported
    @param message Message text
    @return array */
  function returnCode($code, $message, $id = false) {
    return compact('code', 'message', 'id');
  }

  /** Helper function for HABTM relations via a small dummy model data
    @param id Current Id of this model
    @param habtmName Alias name of HABTM relation
    @param habtmIds Array of Ids for HABTM relation
    @return Returns the save result */
  function saveHabtm($id, $habtmName, $habtmIds) {
    $dummy = array(
      $this->alias => array('id' => $id),
      $habtmName => array($habtmName => $habtmIds)
      );
    return $this->save($dummy);
  }

  /** Strips the model alias from the moded data */
  function stripAlias($data = null) {
    if (!$data) {
      $data = $this->data;
    }
    if (isset($data[$this->alias])) {
      return $data[$this->alias];
    } else {
      return $data;
    }
  }

  /** Magic method to fetch model fields. Use Model::set() before */
  function __get($name) {
    if ($this->data) {
      $data = $this->stripAlias();
      $unserscore = Inflector::underscore($name);
      if (isset($data[$unserscore])) {
        return $data[$unserscore];
      }
    }
    return parent::__get($name);
  }

  /** Get a string representation of a model
    @param data Model data
    @return String alias:ID */
  function toString($data) {
    $data = $this->stripAlias($data);
    if (isset($data['id'])) {
      return $this->alias . ":" . $data['id'];
    }
    return $this->alias;
  }
}

?>