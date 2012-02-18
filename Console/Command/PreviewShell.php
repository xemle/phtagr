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
    $this->out("generate");
    $this->out("\tGenerate previews.");
    $this->hr();
    $this->out("Options:");
    $this->out("-max count");
    $this->out("\tMaximum generation count. Default is 10. Use 0 to generate all previews.");
    $this->out("-start-chunk number");
    $this->out("\tSet the start chunk number. A chunk has a size of 100 media. Default is 1.");
    $this->out("-size (mini|thumb|preview|high)");
    $this->out("\tSet the minimum preview size. Default is thumb.");
    $this->out("-user username");
    $this->out("\tGenerate only previews for given user.");
    $this->out("-verbose");
    $this->out("\tBe verbose");
    $this->hr();
    exit();
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
        $preview = $this->PreviewManager->getPreview(&$media, $size);
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
