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
App::uses('CakeEmail', 'Network/Email');

class NotifyShell extends AppShell {

  var $uses = array('User', 'Media', 'MyFile');
  var $components = array('Search', 'PreviewManager');

  var $verbose = false;
  var $force = false;
  var $dryrun = false;
  var $noemail = false;
  var $Email = null;

  function initialize() {
		parent::initialize();
    $this->url = Configure::read('Notification.url');
    if (!$this->url) {
      $this->out("Please configure Notification.url in core.php");
      exit(1);
    }
    $this->Email = new CakeEmail('default');
    $this->Email->helpers('Html');
  }

  public function getOptionParser() {
    $parser = parent::getOptionParser();
    $parser->addOption('noemail', array(
      'help' => __('Do not send emails.'),
      'boolean' => true
    ))->addOption('dryrun', array(
      'help' => __('Simulate the run. Do not change anything'),
      'boolean' => true
    ))->addOption('force', array(
      'help' => __('Send emails always even if user already received an email'),
      'boolean' => true
    ))->addSubcommand('run', array(
      'help' => __('Run the notification updates')
    ))->description(__('Notify new media for users via email'));
    return $parser;
  }

  function _buildImages($media) {
    $attachments = array();
    foreach ($media as $m) {
      $file = $this->PreviewManager->getPreview($m, 'mini');
      if (!$file) {
        Logger::err("Could not create preview for media {$m['Media']['id']}");
        continue;
      }
      $filename = 'media-' . $m['Media']['id'] . '.jpg';
      $attachments[$filename] = array('file' => $file, 'contentId' => 'media-' . $m['Media']['id'] . '.jpg', 'mimetype' => 'image/jpg');
    }
    $this->Email->attachments($attachments);
  }

  function _sendNotifaction($user, $media) {
    $this->_buildImages($media);
    $this->Email->viewVars(array('user' => $user, 'media' => $media, 'url' => $this->url));
    $this->Email->template("notify_new_media", "default")
            ->to($user['User']['email'])
            ->subject("New media are available")
            ->emailFormat("both");
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
      if ($this->params[$arg]) {
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