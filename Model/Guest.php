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

class Guest extends AppModel
{
  var $name = 'Guest';
  var $useTable = 'users';

  var $actsAs = array('Cipher' => array());

  var $belongsTo = array('Creator' => array('className' => 'User'));

  var $hasMany = array(
                  'Option' => array('dependent' => true),
                  );

  var $hasAndBelongsToMany = array(
                  'Member' => array(
                      'className' => 'Group'
                    )
                  );

  var $validate = array(
    'username' => array(
      'rule' => array('between', 3, 32),
      'message' => 'Guestname must be between 3 and 32 characters long.'),
    'password' => array(
      'rule' => array('between', 6, 32),
      'message' => 'Password must be between 6 and 32 characters long.'),
    'email' => array(
      'rule' => array('email'),
      'message' => 'Email address is not valid'),
    'notify_interval' => array(
      'rule' => array('inList', array('0', '1800', '3600', '86400', '604800', '2592000')),
      'message' => 'Invalid notification interval')
    );

  public function afterFind($result, $primary = false) {
    if ($primary && isset($result[0]['Option'])) {
      $result[0]['Option'] = $this->Option->addDefaults($result[0]['Option']);
    }
    return $result;
  }

  public function beforeValidate($options = array()) {
    if (isset($this->data['Guest']['password']) &&
      isset($this->data['Guest']['confirm'])) {
      if (empty($this->data['Guest']['password']) &&
        empty($this->data['Guest']['confirm'])) {
        // both are empty - clear it
        unset($this->data['Guest']['confirm']);
        unset($this->data['Guest']['password']);
      } elseif (empty($this->data['Guest']['password'])) {
        $this->invalidate('password', __('Password not given'));
      } elseif (empty($this->data['Guest']['confirm'])) {
        $this->invalidate('confirm', __('Password confirmation is missing'));
      } elseif ($this->data['Guest']['password'] != $this->data['Guest']['confirm']) {
        $this->invalidate('password', __('Password confirmation mismatch'));
        $this->invalidate('confirm', __('Password confirmation mismatch'));
      }
    }
  }

  public function beforeSave($options = array()) {
    if (isset($this->data['Guest']['webdav']) && $this->data['Guest']['webdav'] > 0) {
      $this->data['Guest']['quota'] = 1;
    } else {
      $this->data['Guest']['quota'] = 0;
    }

    if (empty($this->data['Guest']['expires'])) {
      $this->data['Guest']['expires'] = null;
    }

    return true;
  }

  public function generateKey($data) {
    srand(microtime(true)*1000);
    $h = '';
    for ($i = 0; $i < 128; $i++) {
      $h .= chr(rand(0, 255));
    }
    $h .= time();
    $data['Guest']['key'] = md5($h);
    return $data;
  }

}
