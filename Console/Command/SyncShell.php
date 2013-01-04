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

class SyncShell extends AppShell {

  var $uses = array('User', 'Option', 'MyFile', 'Media');
  var $components = array('FilterManager');

  var $verbose = false;
  var $chunkSize = 100;

  function verboseOut($msg) {
    if ($this->verbose) {
      $this->out($msg);
    }
  }

  public function getOptionParser() {
    $parser = parent::getOptionParser();
    $parser->addOption('max', array(
      'help' => __('Maximum of synchronization count. Default is 100. Use 0 to synchronize all')
    ))->addOption('size', array(
      'help' => __('Set the minimum preview size. Default is thumb'),
      'choices' => $this->chunkSize
    ))->addOption('user', array(
      'help' => __('Synchronize only images for given username')
    ))->addSubcommand('run', array(
      'help' => __('Run the synchronization process')
    ))->description(__('Write all meta data from database to images in batch mode'));
    return $parser;
  }

  function run() {
    $this->verbose = $this->params['verbose'];

    $user = isset($this->params['user']) ? $this->params['user'] : false;
    $syncMax = isset($this->params['max']) ? $this->params['max'] : 100;
    $syncMax = min(100000, max(0, intval($syncMax)));

    $synced = 0;
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
        'limit' => $this->chunkSize,
        'order' => 'Media.id ASC'));
      foreach ($data as $media) {
        $conditions['Media.id >'] = $media['Media']['id'];
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
      if (($syncMax > 0 && $synced >= $syncMax) || count($data) == 0) {
        // fix counting which started by zero
        if ($synced > 0) {
          $synced++;
        }
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
