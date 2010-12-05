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

App::import('Core', array('Controller'));
App::import('Controller', array('AppController'));
App::import('File', 'Logger', array('file' => APP . 'logger.php'));

class AppControllerMock extends AppController {
  var $uses = array('User', 'Option', 'MyFile', 'Media');
  var $components = array('FilterManager');
  var $user = null;
  
  function startup() {
    $this->constructClasses();
    $this->Component->_loadComponents(&$this);
    $this->Component->initialize(&$this);
  }

  function checkAndBindComponents($obj) {
    foreach($this->uses as $model) {
      if (empty($this->{$model})) {
        $this->out("Could not load model $model");
        exit(1);
      }  
      $obj->{$model} =& $this->{$model};
    }
    foreach($this->components as $key => $component) {
      if (!is_numeric($key)) {
        $component = $key;
      }
      if (empty($this->{$component})) {
        $this->out("Could not load component $component");
        exit(1);
      }  
      $obj->{$component} =& $this->{$component};
    }
  }

  function getUser() {
    return $this->user;
  }
}

class SyncShell extends Shell {

  var $Controller = null;
 
  var $verbose = false;
  var $chunkSize = 100;

  function initialize() {
    $this->Controller =& new AppControllerMock();
    $this->Controller->startup();
    $this->Controller->checkAndBindComponents(&$this);

    $shellAdmin = $this->User->getNobody();
    $shellAdmin['User']['role'] = ROLE_ADMIN;
    $this->Controller->user = $shellAdmin;

    $this->PreviewManager->Media = &$this->Media;
  }

  function startup() {
  }

  function main() {
    $this->help();
  }

  function verboseOut($msg) {
    if ($this->verbose) {
      $this->out($msg);
    }
  }

  function help() {
    $this->out("Help screen");
    $this->hr();
    $this->out("This shell generates preview images in batch mode.");
    $this->out("");
    $this->out("run");
    $this->out("\tGenerate previews.");
    $this->hr();
    $this->out("Options:");
    $this->out("-max count");
    $this->out("\tMaximum generation count. Default is 10. Use 0 to generate all previews.");
    $this->out("-user username");
    $this->out("\tGenerate only previews for given user.");
    $this->out("-verbose");
    $this->out("\tBe verbose");
    $this->hr();
    exit();
  }

  function run() {
    $this->verbose = isset($this->params['verbose']) ? true : false;

    $user = isset($this->params['user']) ? $this->params['user'] : false;
    $syncMax = isset($this->params['max']) ? $this->params['max'] : 100;
    $syncMax = min(100000, max(0, intval($syncMax)));

    $synced = 1;
    @clearstatcache();
    $errors = array();
    $conditions = array('Media.flag & '.MEDIA_FLAG_DIRTY.' > 0');
    if ($user) {
      $conditions['User.username'] = $user;
    }
    $count = $this->Media->find('count', array('conditions' => $conditions));
    $this->verboseOut(sprintf("%d media are unsynced", $count));
    while (true) {
      $data = $this->Media->find('all', array(
        'conditions' => $conditions, 
        'limit' => 10, 
        'order' => 'Media.modified ASC'));
      foreach ($data as $media) {
        $conditions['Media.modified >'] = $media['Media']['modified'];
        if (!$this->FilterManager->write($media)) {
          $this->out("Error: Could not sync metadata of media {$media['Media']['id']}");
          $errors[] = $media['Media']['id'];
          continue;
        }
        $this->verboseOut(sprintf("Synced metadata #%d for media #%d by %s: %s", $synced, $media['Media']['id'], $media['User']['username'], $media['Media']['name']));
        if ($syncMax > 0 && $synced >= $syncMax) {
          break;
        }
        $synced++; 
      }
      if ($syncMax > 0 && $synced >= $syncMax) {
        $this->out("Synced $synced media. Exit.");
        break;
      }
    }
    if (count($errors)) {
      $this->out("Errors of media: " . implode(', ', $errors));
    }
  }
}
?>
