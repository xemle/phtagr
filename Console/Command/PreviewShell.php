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

class PreviewShell extends AppShell {
  var $uses = array('User', 'Option', 'MyFile', 'Media');
  var $components = array('Search', 'FileCache', 'PreviewManager');

  var $verbose = false;
  var $chunkSize = 100;
  var $sizes = array('mini', 'thumb', 'preview', 'high');

  function initialize() {
    parent::initialize();
    $mockUser = $this->User->getNobody();
    $mockUser['User']['role'] = ROLE_ADMIN;
    $this->mockUser($mockUser);
  }

  public function getOptionParser() {
    $parser = parent::getOptionParser();
    $parser->addOption('max', array(
      'help' => __('Maximum generation count. Default is 10. Use 0 to generate all previews')
    ))->addOption('start-chunk', array(
      'help' => __('Set the start chunk number. A chunk has a size of 100 media. Default is 1')
    ))->addOption('size', array(
      'help' => __('Set the minimum preview size. Default is thumb'),
      'choices' => $this->sizes
    ))->addOption('user', array(
      'help' => __('Generate only previews for given user')
    ))->addSubcommand('generate', array(
      'help' => __('Create preview files')
    ))->description(__('Generate preview files'));
    return $parser;
  }

  function verboseOut($msg) {
    if ($this->verbose) {
      $this->out($msg);
    }
  }

  function generate() {
    $this->verbose = isset($this->params['verbose']) ? true : false;

    $size = (isset($this->params['size']) && in_array($this->params['size'], $this->sizes)) ? $this->params['size'] : 'thumb';
    $user = isset($this->params['user']) ? $this->params['user'] : false;
    $chunk = isset($this->params['start-chunk']) ? max(1, intval($this->params['start-chunk'])) : 1;
    $generateMax = isset($this->params['max']) ? $this->params['max'] : 100;
    $generateMax = min(100000, max(0, intval($generateMax)));

    $generated = 1;
    @clearstatcache();
    $errors = array();
    while (true) {
      $this->Search->setShow($this->chunkSize, false);
      $this->Search->setPage($chunk);
      if ($user) {
        $this->Search->setUser($user);
      }
      $data = $this->Search->paginate();
      $chunkCount = $this->ControllerMock->params['search']['pageCount'];
      if (count($data) == 0 || $chunkCount == 0) {
        $this->out("No previews found. Exit.");
        break;
      }
      $this->verboseOut(sprintf("Page %d/%d (%.2f%%)", $chunk, $chunkCount, 100*$chunk/$chunkCount));
      //$this->verboseOut('found ' . implode(', ', Set::extract('/Media/id', $data)));
      foreach ($data as $media) {
        $file = $this->FileCache->getFilePath($media, $size);
        if (file_exists($file)) {
          continue;
        }
        //$this->out($file);
        $preview = $this->PreviewManager->getPreview($media, $size);
        if (!$preview) {
          $this->out("Error: Could not create preview of media {$media['Media']['id']}");
          $errors[] = $media['Media']['id'];
          continue;
        }
        $this->verboseOut(sprintf("Generated preview #%d for media #%d by %s: %s", $generated, $media['Media']['id'], $media['User']['username'], $media['Media']['name']));
        if ($generateMax > 0 && $generated >= $generateMax) {
          break;
        }
        $generated++;
      }
      if ($generateMax > 0 && $generated >= $generateMax) {
        $this->out("Generated $generated previews. Exit.");
        break;
      }
      if ($chunk >= $chunkCount) {
        $this->out("Last chunk $chunk reached");
        break;
      }
      $chunk++;
    }
    if (count($errors)) {
      $this->out("Errors of media: " . implode(', ', $errors));
    }
  }
}
?>
