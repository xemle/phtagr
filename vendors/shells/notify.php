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

App::import('Model', array('User', 'Media'));
App::import('Core', array('Controller'));
App::import('Component', array('Email', 'Search'));
App::import('File', 'Logger', array('file' => APP . 'logger.php'));

class AppControllerMock extends Controller {
  var $uses = array('User', 'Media');
  var $components = array('Email', 'Search');
  var $user = null;
  
  function startup() {
    $this->constructClasses();
    $this->Component->_loadComponents(&$this);
    $this->Component->initialize(&$this);
    $this->_configureEmail();
  }

  /** Configure email component on any SMTP configuration values in core.php */
  function _configureEmail() {
    if (Configure::read('Mail.from')) {
      $this->Email->from = Configure::read('Mail.from');
    } else {
      $this->Email->from = "phTagr <noreply@phtagr.org>";
    }
    if (Configure::read('Mail.replyTo')) {
      $this->Email->replyTo = Configure::read('Mail.replyTo');
    }
    $names = array('host', 'port', 'username', 'password');
    foreach($names as $name) {
      $value = Configure::read("Smtp.$name");
      if ($value) {
        $this->Email->smtpOptions[$name] = $value;
      }
    }
    if (!empty($this->Email->smtpOptions['host'])) {
      $this->Email->delivery = 'smtp';
    }
  }
  
  function getUser() {
    return $this->user;
  }
}

class NotifyShell extends Shell {

  var $Controller = null;
 
  var $verbose = false;
  var $force = false;
  var $dryrun = false;
  var $noemail = false;

  function initialize() {
    if (function_exists('ini_set') && !ini_set('include_path', ROOT . DS . APP_DIR . DS . 'vendors' . DS . 'Pear' . DS . PATH_SEPARATOR . ini_get('include_path'))) {
      $this->out("Could not set include_path");
      exit(1);
    }
    $this->Controller =& new AppControllerMock();
    $this->Controller->startup();
    foreach($this->Controller->uses as $model) {
      if (empty($this->Controller->{$model})) {
        $this->out("Could not use model $model");
        exit(1);
      }  
      $this->{$model} =& $this->Controller->{$model};
    }
    foreach($this->Controller->components as $component) {
      if (empty($this->Controller->{$component})) {
        $this->out("Could not use model $component");
        exit(1);
      }  
      $this->{$component} =& $this->Controller->{$component};
    }

    $this->url = Configure::read('Notification.url');
    if (!$this->url) {
      $this->out("Please configure Notification.url in core.php");
      exit(1);
    }
  }

  function startup() {
  }

  function main() {
    $this->help();
  }

  function help() {
    $this->out("Help screen");
    $this->hr();
    $this->out("run [noemail] [dryrun] [verbose] [force]");
    $this->out("\tNotify new media for users via email");
    $this->hr();
    exit();
  }

  function _sendNotifaction($user, $media) {
    $this->Email->to = $user['User']['email'];
    $this->Email->subject = "New media are available";
    $this->Email->template = 'notify_new_media';

    $this->Controller->set('user', $user);
    $this->Controller->set('media', $media);
    $this->Controller->set('url', $this->url);

    if (!$this->Email->send()) {
      $this->out("Could not send new media notification email to {$user['User']['email']}");
      Logger::err("Could not send new media notification email to {$user['User']['email']}");
    } else {
      if ($this->verbose) {
        $this->out("Send new media notification email to {$user['User']['email']}");
      }
      Logger::info("Send new media notification email to {$user['User']['email']}");
    }
  }

  function run() {
    $args = array('dryrun', 'verbose', 'noemail', 'force');
    foreach($args as $arg) {
      if (in_array($arg, $this->args)) {
        $this->{$arg} = true;
      }
    }

    $users = $this->User->find('all');
    if ($this->verbose) {
      $this->out(sprintf("Found %d users: %s", count($users), implode(', ', Set::extract('/User/username', $users))));
    }
    $now = time();
    foreach($users as $user) {
      if ($user['User']['notify_interval'] == 0) {
        if ($this->verbose) {
          $this->out("Skip disabled user {$user['User']['username']}");
        }
        continue;
      } 
      $nextNotify = strtotime($user['User']['last_notify']);
      if ($nextNotify > $now && !$this->force) {
        if ($this->verbose) {
          $this->out("Update interval to short for user {$user['User']['username']}");
        }
        continue;
      }
      $this->Controller->user =& $user;

      $this->Search->setShow(24);
      $this->Search->setSort('newest');
      $this->Search->setExcludeUser($user['User']['id']);
      $this->Search->setCreatedFrom($user['User']['last_notify']);
      
      $media = $this->Search->paginate();
      if ($this->verbose) {
        $this->out(sprintf("Found %d new media for user %s", count($media), $user['User']['username']));
      }
      if (count($media) && !$this->noemail && !$this->dryrun) {
        $this->_sendNotifaction($user, $media);
      }
      if (!$this->dryrun) {
        $user['User']['last_notify'] = date('Y-m-d h:m:s', $now);
        if (!$this->User->save($user['User'], true, array('last_notify'))) {
          $this->out("Could not save user data of {$user['User']['username']}");
          Logger::err("Could not save user data of {$user['User']['username']}");
          Logger::debug($user);
        }
      }
    }
  }
}
?>
