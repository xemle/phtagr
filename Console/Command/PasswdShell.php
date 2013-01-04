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
App::import('Model', 'User');

class PasswdShell extends Shell {

  var $User = null;

  function initialize() {
    $this->User = new User();

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
      $users = $this->User->find('all', array('conditions' => 'User.password NOT LIKE "$E$%"'));
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
      $user = $this->User->decrypt($user);
      $this->out("Password of user '{$user['User']['username']}' is: '{$user['User']['password']}'");
    }
  }

  /** Test password */
  function verify() {
    if (!isset($this->args[0])) {
      $this->error("Missing Argument", "Username is not given");
    }

    $userIdOrName = $this->args[0];
    if (is_numeric($userIdOrName))
      $user = $this->User->findById($userIdOrName);
    else
      $user = $this->User->findByUsername($userIdOrName);

    $pass = $this->in("Please enter password");
    $user = $this->User->decrypt($user);
    if ($user['User']['password'] != $pass) {
      $this->out("Password is correct");
    } else {
      $this->out("Password is wrong!");
    }
  }

  function show() {
    $users = $this->User->find('all', array('recursive' => 1));
    foreach ($users as $user) {
      $this->out("{$user['User']['id']}\t{$user['User']['username']}");
    }
    $this->hr();
    $this->out(count($users) . " user(s)");
  }

  function help() {
    $this->out("Help screen");
    $this->hr();
    $this->out("show");
    $this->out("\tList all users");
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
