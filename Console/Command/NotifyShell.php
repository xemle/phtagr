<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

class NotifyShell extends AppShell {

  var $uses = array('User', 'Media', 'MyFile');
  var $components = array('ImageEmail', 'Search', 'PreviewManager');
	
  var $verbose = false;
  var $force = false;
  var $dryrun = false;
  var $noemail = false;

  function initialize() {
		parent::initialize();
    $this->url = Configure::read('Notification.url');
    if (!$this->url) {
      $this->out("Please configure Notification.url in core.php");
      exit(1);
    }
		$this->_configureEmail();
  }
	
  /** 
	 * Configure email component on any SMTP configuration values in core.php 
	 */
  function _configureEmail() {
    if (Configure::read('Mail.from')) {
      $this->ImageEmail->from = Configure::read('Mail.from');
    } else {
      $this->ImageEmail->from = "phTagr <noreply@phtagr.org>";
    }
    if (Configure::read('Mail.replyTo')) {
      $this->ImageEmail->replyTo = Configure::read('Mail.replyTo');
    }
    $names = array('host', 'port', 'username', 'password');
    foreach($names as $name) {
      $value = Configure::read("Smtp.$name");
      if ($value) {
        $this->ImageEmail->smtpOptions[$name] = $value;
      }
    }
    if (!empty($this->ImageEmail->smtpOptions['host'])) {
      $this->ImageEmail->delivery = 'smtp';
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

  function _buildImages($media) {
    $this->ImageEmail->images = array();
    foreach ($media as $m) {
      $file = $this->Controller->PreviewManager->getPreview($m, 'mini');
      if (!$file) {
        Logger::err("Could not create preview for media {$m['Media']['id']}");
        continue;
      }
      $attachment = array('file' => $file, 'id' => 'media-' . $m['Media']['id'] . '.jpg', 'mime' => 'image/jpeg');
      $this->ImageEmail->images[] = $attachment;
    }
  }

  function _sendNotifaction($user, $media) {
    $this->ImageEmail->to = $user['User']['email'];
    $this->ImageEmail->subject = "New media are available";
    $this->ImageEmail->template = 'notify_new_media';
    $this->ImageEmail->sendAs = 'both';

    $this->Controller->set('user', $user);
    $this->Controller->set('media', $media);
    $this->Controller->set('url', $this->url);
    $this->_buildImages($media);

    if (!$this->ImageEmail->send()) {
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
      $nextNotify = strtotime($user['User']['last_notify']) + $user['User']['notify_interval'];
      if ($nextNotify > $now && !$this->force) {
        if ($this->verbose) {
          $this->out("Update interval to short for user {$user['User']['username']}");
        }
        continue;
      }
      $this->mockUser($user);

      $this->Search->setShow(12);
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
