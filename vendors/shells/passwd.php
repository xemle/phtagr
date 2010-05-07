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
App::import('model', array('user', 'image'));

class PasswdShell extends Shell {

  var $User = null;
  var $Image = null;

  function initialize() {
    $this->User =& new User();
    $this->Image =& new Image();

    $this->out("phtagr shell utilities");
    $this->hr();
  }

  function startup() {
  }

  function main() {
    $this->help();
  }

  /** Decrypt all passwords */
  function encrypt() {
    if ('y' == $this->in('Are you sure you want to upgrade all passwords?', array('y', 'n'), 'n')) {
      $users = $this->User->findall('User.password NOT LIKE "$E$%"');
      if (!count($users)) {
        $this->out("No users found with plain passwords");
      } else {
        foreach ($users as $user) {
          if (!$this->User->save($user, true, array('password'))) {
            $this->out(print_r($this->User->validationErrors, true));
            $this->error("Database Error", "Could not save encrypted password");
          }
          $this->out("Encrypt password of user '{$user['User']['username']}'");
        }
      }
    }
  }

  /** Set new passwords */
  function passwd() {
    if (!isset($this->args[0]))
      $this->error("Missing Argument", "Username is not given");

    $userIdOrName = $this->args[0];
    if (is_numeric($userIdOrName))
      $user = $this->User->findById($userIdOrName);
    else
      $user = $this->User->findByUsername($userIdOrName);

    if (!$user) 
      $this->error("Unknown user", "Could not find user with id or username '$userIdOrName'");

    $password = $this->in("Enter new password for '{$user['User']['username']}':");

    if ('y' == $this->in('Are you sure you want to set the new password?', array('y', 'n'), 'n')) {
      $user['User']['password'] = $password;
      if (!$this->User->save($user, true, array('password'))) {
        $this->out(print_r($this->User->validationErrors, true));
        $this->error("Save Error", "Could not save new password");
      } else {
        $this->out("Password successfully changed");
      }
    }
  }

  /** Print unencrypted password */
  function decrypt() {
    if (!isset($this->args[0]))
      $this->error("Missing Argument", "Username is not given");

    $userIdOrName = $this->args[0];
    if (is_numeric($userIdOrName))
      $user = $this->User->findById($userIdOrName);
    else
      $user = $this->User->findByUsername($userIdOrName);

    if (!$user) {
      $this->out("Could not find user '$userIdOrName'");
    } else {
      $user = $this->User->decrypt(&$user);
      $this->out("Password of user '{$user['User']['username']}' is: '{$user['User']['password']}'");
    }
  }

  /** Test password */
  function verify() {
    if (!isset($this->args[0]))
      $this->error("Missing Argument", "Username is not given");

    $userIdOrName = $this->args[0];
    if (is_numeric($userIdOrName))
      $user = $this->User->findById($userIdOrName);
    else
      $user = $this->User->findByUsername($userIdOrName);

    $pass = $this->in("Please enter password");
    $user = $this->User->decrypt(&$user);
    if ($user['User']['password'] != $pass) {
      $this->out("Password is correct");
    } else {
      $this->out("Password is wrong!");
    }
  }

  function help() {
    $this->out("Help screen");
    $this->hr();
    $this->out("passwd <username|user id>");
    $this->out("\tChange password of a given user");
    $this->out("encrypt");
    $this->out("\tEncrypt all plain text passwords to ciphered passwords");
    $this->out("decrypt <username|user id>");
    $this->out("\tPrints the decrypted password of an user");
    $this->out("verify");
    $this->out("\tTests the password validation");
    $this->hr();
    exit();
  }
}
?>
