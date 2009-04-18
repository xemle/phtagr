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
  var $components = array('RequestHandler', 'Cookie');
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

  /** 
   * @todo Add redirection of redirection address, if available */
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
        if($user['User']['password'] == $this->data['User']['password']) {
          if ($this->Session->write('User.id', $user['User']['id'])) {
            $this->Logger->info("Successfull login of user '{$user['User']['username']}' (id {$user['User']['id']})");
            $this->Session->write('User.role',  $user['User']['role']);
            $this->Session->write('User.username',  $user['User']['username']);

            // Save Cookie for 3 months
            $this->Cookie->write('user', $user['User']['id'], true, 92*24*3600);
            $this->Logger->debug("Write authentication cookie for user '{$user['User']['username']}' (id {$user['User']['id']})");
            $this->redirect('/', null, false);
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
    $this->Session->delete('User.id');
    $this->Session->delete('User.role');
    $this->Session->delete('User.username');

    $this->Cookie->name = 'phTagr';
    $this->Cookie->destroy();
 
    $this->redirect('/');
  }

  function admin_index() {
    $this->data = $this->paginate('User', array('User.role>='.ROLE_MEMBER));
  }

  function admin_edit($id) {
    $id = intval($id);

    if (!empty($this->data)) {
      if (!$this->User->isUnique('username')) {
        $this->Session->setFlash('User already exists');
      } else {
        $this->data['User']['id'] = $id;
        // remove password information if not set
        if (!strlen($this->data['User']['password']))
          unset($this->data['User']['password']);

        if (!empty($this->data['Preference']['path']['fspath'])) {
          $fsroot = $this->data['Preference']['path']['fspath'];
          $fsroot = Folder::slashTerm($fsroot);

          if (is_dir($fsroot))
            $this->Preference->addValue('path.fsroot[]', $fsroot, $id);
        }
        if ($this->User->save($this->data))
          $this->Session->setFlash('User data was updated');
        else
          $this->Session->setFlash('Could not be updated');
      }
    }

    $this->data = $this->User->find('User.id='.$id);
    $this->data['User']['password'] = '';

    $this->set('fsroots', $this->Preference->buildTree($this->data, 'path.fsroot'));
  }
 
  function admin_add() {
    $this->requireRole(ROLE_ADMIN);

    if (!empty($this->data)) {
      if ($this->User->hasAny(array('User.username' => '= '.$this->data['User']['username']))) {
        $this->Session->setFlash('Username is already given, please choose another name!');
      } else {
        $this->data['User']['role'] = ROLE_MEMBER;
        if ($this->User->save($this->data)) {
          $this->Session->setFlash('User was created');
          $this->redirect('/admin/users/edit/'.$this->User->id);
        } else {
          $this->Session->setFlash('Could not create user');
        }
      }
    }
  } 
  
  function admin_del($id) {
    $this->requireRole(ROLE_ADMIN);

    $user = $this->User->find("id=$id");
    if (!$user) {
      $this->Session->setFlash("Could not find user to delete");
    } else {
      $this->User->del($id);
      $this->Session->setFlash("User '{$user['User']['username']}' was deleted");
      $this->redirect('/admin/users/');
    }
  }

  function admin_delfsroot($id) {
    $dirs = $this->params['pass'];
    unset($dirs[0]);
    $fsroot = implode(DS, $dirs);
    if (DS == '/')
      $fsroot = '/'.$fsroot;
    $fsroot = Folder::slashTerm($fsroot);
    $this->Preference->delValue('path.fsroot[]', $fsroot, $id);

    $this->redirect("edit/$id");
  }
}
?>
