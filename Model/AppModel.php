<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

App::uses('Model', 'Model');

class AppModel extends Model {

  public function unbindAll($params = array()) {
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
  public function returnCode($code, $message, $id = false) {
    return compact('code', 'message', 'id');
  }

  /** Helper function for HABTM relations via a small dummy model data
    @param id Current Id of this model
    @param habtmName Alias name of HABTM relation
    @param habtmIds Array of Ids for HABTM relation
    @return Returns the save result */
  public function saveHabtm($id, $habtmName, $habtmIds) {
    $dummy = array(
      $this->alias => array('id' => $id),
      $habtmName => array($habtmName => $habtmIds)
      );
    return $this->save($dummy);
  }

  /** Strips the model alias from the moded data */
  public function stripAlias($data = null) {
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
  public function __get($name) {
    if ($this->data) {
      $data = $this->stripAlias();
      $unserscore = Inflector::underscore($name);
      if (isset($data[$unserscore])) {
        return $data[$unserscore];
      }
    }
    return parent::__get($name);
  }

  /** 
   * Get a string representation of a model
   *
   * @param data Model data
   * @return String alias:ID 
   */
  public function toStringModel($data) {
    $data = $this->stripAlias($data);
    if (isset($data['id'])) {
      return $this->alias . ":" . $data['id'];
    }
    return $this->alias;
  }
}

?>
