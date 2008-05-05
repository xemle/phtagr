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

class AppController extends Controller
{
  var $helpers = array('html', 'session', 'javascript', 'menu');
  var $components = array('Cookie', 'Logger');
  var $uses = array('User');
  
  var $_nobody = null;
  var $_user = null;

  /** Calls _checkSession() to check the credentials of the user 
    @see _checkSession() */
  function beforeFilter() {
    $this->_checkSession();
  }

  /** Checks a cookie for a valid user id. If a id found, the user is load to
   * the session 
   * @todo Check expired user */
  function _checkSession() {
    $this->Session->activate();
    if ($this->Session->check('User.id'))
      return true;

    $this->Cookie->name = 'phTagr';

    // Fetch Cookie
    $id = $this->Cookie->read('user');
    if ($id == null) 
      return false;

    // Read user
    $user = $this->User->findById($id);
    if (!$user)
      return false;

    if ($this->User->isExpired($user)) {
      $this->Logger->warn("User account of '{$user['User']['username']}' (id {$user['User']['id']}) is expired!");
      return false;
    }

    // Valid cookie found. Loading the user to the session
    //$this->Logger->debug("Start new session for '{$user['User']['username']}' (id {$user['User']['id']})");
    $this->Session->write('User.id', $user['User']['id']);
    $this->Session->write('User.role', $user['User']['role']);
    $this->Session->write('User.username', $user['User']['username']);

    $this->Logger->info("User '{$user['User']['username']}' (id {$user['User']['id']}) authenticated through the cookie!");
    return true;
  }

  /** Checks the session for valid user. If no user is found, it checks for a
   * valid cookie
   * @return True if the correct session correspond to an user */ 
  function _checkUser() {
    if (!$this->_checkSession())
      return false;

    if ($this->_user)
      return true;

    $userId = $this->Session->read('User.id');
    $user = $this->User->findById($userId);
    if (!$user) {
      return false;
    }

    $this->_user = $user;
    return true;
  }
 
  function getUser() {
    if (!$this->_checkUser() || !$this->_user) {
      if (!$this->_nobody)
        $this->_nobody = $this->User->getNobody();
      return $this->_nobody;
    }
    return $this->_user;
  }

  function getUserRole() {
    $user =& $this->getUser();
    return $user['User']['role'];
  }
  
  function getUserId() {
    $user =& $this->getUser();
    return $user['User']['id'];
  }

  function hasRole($requiredRole=ROLE_NOBODY) {
    if ($requiredRole <= $this->getUserRole())
      return true;
    return false;
  }

  function requireRole($requiredRole=ROLE_NOBODY, $redirectTo = '/users/login') {
    if (!$this->hasRole($requiredRole)) {
      $this->redirect($redirectTo);
      exit();
    }
    return true;
  }
  
  function getPreferenceValue($name, $default=null) {
    $user = $this->getUser();
    if (!isset($user['Preference']))
      return $default;

    $isArray = false;
    $values = array();
    if (strlen($name) > 2 && substr($name, -2) == '[]')
      $isArray = true;
      
    foreach ($user['Preference'] as $pref) {
      if ($pref['name'] === $name) {
        if ($isArray)
          $values[] = $pref['value'];
        else
          return $pref['value'];
      }
    }
    if ($isArray && count($values))
      return $values;

    return $default;
  }
 
}
?>
