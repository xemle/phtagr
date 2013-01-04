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
 * @since         phTagr 2.3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

App::uses('Media', 'Model');

/**
 * Factory for test data
 */
class PhtagrTestFactory {
  var $Media;
  var $User;
  var $Group;
  var $Option;
  var $Field;

  function __construct() {
    $this->Media = ClassRegistry::init('Media');
    $this->User = $this->Media->User;
    $this->Group = $this->Media->Group;
    $this->Option = $this->User->Option;
    $this->Field = $this->Media->Field;
  }


  public function createUser($username, $role = ROLE_USER, $options = array()) {
    $data = am(array('username' => $username, 'role' => $role), $options);
    $this->User->save($this->User->create($data));
    return $this->User->findById($this->User->getLastInsertID());
  }

  public function createMedia($name = null, $user = null, $options = array()) {
    $data = array();
    $data['name'] = $name ? $name : 'IMG_1234.JPG';
    if ($user) {
      $data['user_id'] = $user['User']['id'];
    }
    $data = am($data, $options);
    $this->Media->save($this->Media->create($data));
    return $this->Media->findById($this->Media->getLastInsertID());
  }

  public function createGroup($name, $user = null, $options = array()) {
    $data = array('name' => $name);
    if ($user) {
      $data['user_id'] = $user['User']['id'];
    }
    $data = am($data, $options);
    $this->Group->save($this->Group->create($data));
    return $this->Group->findById($this->Group->getLastInsertID());
  }
}
