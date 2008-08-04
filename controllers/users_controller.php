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

class UsersController extends AppController
{
  var $components = array('RequestHandler', 'Cookie', 'Email');
  var $uses = array('Preference'); 
  var $helpers = array('form', 'formular', 'number');
  var $paginate = array('limit' => 10, 'order' => array('User.username' => 'asc')); 

  function beforeRender() {
    $this->_setMenu();
  }

  function _setMenu() {
    $items = array();
    if ($this->hasRole(ROLE_ADMIN)) {
      $items[] = array('text' => 'List users', 'link' => 'index');
      $items[] = array('text' => 'Add user', 'link' => 'add');
    }
    $menu = array('items' => $items);
    $this->set('mainMenu', $menu);
  }

  /** Checks the login of the user. If the session variable 'loginRedirect' is
   * set the user is forwarded to this given address on successful login. */
  function login() {
    $failedText = "Sorry. Username is unkonwn or password was wrong";
    if (!empty($this->data) && !$this->RequestHandler->isPost()) {
      $this->Logger->warn("Authentication failed: Request was not HTTP POST");
      $this->Session->setFlash($failedText);
      $this->data = null;
    }
    if (empty($this->data['User']['username']) xor empty($this->data['User']['password'])) {
      $this->Logger->warn("Authentication failed: Username or password are not set");
      $this->Session->setFlash("Please enter username and password!");
      $this->data = null; 
    }

    if (!empty($this->data)) {
      $user = $this->User->findByUsername($this->data['User']['username']);

      if (!$user) {
        $this->Logger->warn("Authentication failed: Unknown username '{$this->data['User']['username']}'!");
        $this->Session->setFlash($failedText);
      } elseif ($this->User->isExpired($user)) {
        $this->Logger->warn("User account of '{$user['User']['username']}' (id {$user['User']['id']}) is expired!");
        $this->Session->setFlash("Sorry. Your account is expired!");
      } else {
        $user = $this->User->decrypt(&$user);
        if ($user['User']['password'] == $this->data['User']['password']) {
          $this->Session->renew();
          $this->Session->activate();
          if (!$this->Session->check('User.id') || $this->Session->read('User.id') != $user['User']['id']) {
            $this->Logger->info("Start new session for '{$user['User']['username']}' (id {$user['User']['id']})");
            $this->Session->write('User.id', $user['User']['id']);
            $this->Session->write('User.role',  $user['User']['role']);
            $this->Session->write('User.username',  $user['User']['username']);

            // Save Cookie for 3 months
            $this->Cookie->write('user', $user['User']['id'], true, 92*24*3600);
            $this->Logger->debug("Write authentication cookie for user '{$user['User']['username']}' (id {$user['User']['id']})");

            $this->Logger->info("Successfull login of user '{$user['User']['username']}' (id {$user['User']['id']})");
            if ($this->Session->check('loginRedirect')) {
              $this->redirect($this->Session->read('loginRedirect'));
              $this->Session->delete('loginRedirect');
            } else {
              $this->redirect('/');
            }
          } else {
            $this->Logger->err("Could not write session information of user '{$user['User']['username']}' ({$user['User']['id']})");
            $this->Session->setFlash("Sorry. Internal login procedure failed!");
          }
        } else {
          $this->Logger->warn("Authentication failed: Incorrect password of username '{$this->data['User']['username']}'!");
          $this->Session->setFlash($failedText);
        }
      }
      unset($this->data['User']['password']);
    }
  }

  function logout() {
    $user = $this->getUser();
    $this->Logger->info("Delete session for user '{$user['User']['username']}' (id {$user['User']['id']})");

    $this->Session->destroy();

    $this->Cookie->name = 'phTagr';
    $this->Cookie->destroy();
 
    $this->redirect('/');
  }

  function admin_index() {
    $this->requireRole(ROLE_ADMIN, array('loginRedirect' => '/admin/users'));

    $this->data = $this->paginate('User', array('User.role>='.ROLE_USER));
  }

  function admin_edit($id) {
    $this->requireRole(ROLE_ADMIN, array('loginRedirect' => '/admin/users'));

    $id = intval($id);

    if (!empty($this->data)) {
      $this->data['User']['id'] = $id;

      $this->User->set($this->data);
      if ($this->User->save(null, true, array('username', 'password', 'email', 'expires', 'quota', 'firstname', 'lastname'))) {
        $this->Logger->debug("Data of user {$this->data['User']['username']} was updated");
        $this->Session->setFlash('User data was updated');
      } else {
        $this->Logger->err("Could not save user data");
        $this->Logger->debug($this->User->validationErrors);
        $this->Session->setFlash('Could not be updated');
      }

      if (!empty($this->data['Preference']['path']['fspath'])) {
        $fsroot = $this->data['Preference']['path']['fspath'];
        $fsroot = Folder::slashTerm($fsroot);

        if (is_dir($fsroot))
          $this->Preference->addValue('path.fsroot[]', $fsroot, $id);
      }
    }

    $this->data = $this->User->findById($id);
    unset($this->data['User']['password']);

    $this->set('fsroots', $this->Preference->buildTree($this->data, 'path.fsroot'));
  }
 
  function admin_add() {
    $this->requireRole(ROLE_ADMIN, array('loginRedirect' => '/admin/users'));

    if (!empty($this->data)) {
      if ($this->User->hasAny(array('User.username' => '= '.$this->data['User']['username']))) {
        $this->Session->setFlash('Username is already given, please choose another name!');
      } else {
        $this->data['User']['role'] = ROLE_USER;
        if ($this->User->save($this->data, true, array('username', 'password', 'role', 'email'))) {
          $this->Logger->info("New user {$this->data['User']['username']} was created");
          $this->Session->setFlash('User was created');
          $this->redirect('/admin/users/edit/'.$this->User->id);
        } else {
          $this->Logger->warn("Creation of user {$this->data['User']['username']} failed");
          $this->Session->setFlash('Could not create user');
        }
      }
    }
  } 
  
  function admin_del($id) {
    $this->requireRole(ROLE_ADMIN, array('loginRedirect' => '/admin/users'));

    $id = intval($id);
    $user = $this->User->findById($id);
    if (!$user) {
      $this->Session->setFlash("Could not find user to delete");
      $this->redirect('/admin/users/');
    } else {
      $this->User->del($id);
      $this->Logger->notice("All data of user '{$user['User']['username']}' ($id) deleted");
      $this->Session->setFlash("User '{$user['User']['username']}' was deleted");
      $this->redirect('/admin/users/');
    }
  }

  function admin_delfsroot($id) {
    $this->requireRole(ROLE_ADMIN);

    $id = intval($id);
    $dirs = $this->params['pass'];
    unset($dirs[0]);
    $fsroot = implode(DS, $dirs);
    if (DS == '/')
      $fsroot = '/'.$fsroot;
    $fsroot = Folder::slashTerm($fsroot);
    $this->Preference->delValue('path.fsroot[]', $fsroot, $id);

    $this->redirect("edit/$id");
  }

  /** Password recovery */
  function password() {
    if (!empty($this->data)) {
      $user = $this->User->find(array(
          'username' => $this->data['User']['username'], 
          'email' => $this->data['User']['email']));
      if (empty($user)) {
        $this->Session->setFlash('No user with this email was found');
        $this->Logger->warn(sprintf("No user '%s' with email %s was found",
            $this->data['User']['username'], $this->data['User']['email']));
      } else {
        $this->Email->to = sprintf("%s %s <%s>", 
            $user['User']['firstname'],
            $user['User']['lastname'],
            $user['User']['email']);
  
        $this->Email->subject = 'Password Request';
        $this->Email->replyTo = 'noreply@phtagr.org';
        $this->Email->from = 'phTagr <noreply@phtagr.org>';

        $this->Email->template = 'password';
        $this->set('user', $user);

        if ($this->Email->send()) {
          $this->Logger->info(sprintf("Sent password mail of '%s' (id %d) to %s",
            $user['User']['username'], 
            $user['User']['id'],
            $user['User']['email']));
          $this->Session->setFlash('Mail was sent');
        } else {
          $this->Logger->err(sprintf("Could not sent password mail of '%s' (id %d) to '%s'",
            $user['User']['username'],
            $user['User']['id'],
            $user['User']['email']));
          $this->Session->setFlash('Mail could not sent');
        }
      }
    }
  }
}
?>
