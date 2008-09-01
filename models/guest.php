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

class Guest extends AppModel
{
  var $name = 'Guest';
  var $useTable = 'users';

  var $actsAs = array('Cipher' => array());

  var $belongsTo = array('Creator' => array('className' => 'User'));

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
      'rule' => array('between', 6, 20),
      'message' => 'Password must be between 6 and 20 characters long.'),
    'email' => array(
      'rule' => array('email'),
      'message' => 'Email address is not valid')
    );

  function beforeValidate() {
    if (isset($this->data['Guest']['password']) && 
      isset($this->data['Guest']['confirm'])) {
      if (empty($this->data['Guest']['password']) && 
        empty($this->data['Guest']['confirm'])) {
        // both are empty - clear it
        unset($this->data['Guest']['confirm']);
        unset($this->data['Guest']['password']);
      } elseif (empty($this->data['Guest']['password'])) {
        $this->invalidate('password', 'Password not given');
      } elseif (empty($this->data['Guest']['confirm'])) {
        $this->invalidate('confirm', 'Password confirmation is missing');
      } elseif ($this->data['Guest']['password'] != $this->data['Guest']['confirm']) {
        $this->invalidate('password', 'Password confirmation mismatch');
        $this->invalidate('confirm', 'Password confirmation mismatch');
      }
    }
  }

  function beforeSave() {
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

  function generateKey($data) {
    srand(getMicrotime()*1000);
    $h = '';
    for ($i = 0; $i < 128; $i++) {
      $h .= chr(rand(0, 255));
    }
    $h .= time();
    $data['Guest']['key'] = md5($h);
    return $data;
  }


}
?>
