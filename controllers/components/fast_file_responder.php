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

class FastFileResponderComponent extends Object {
  var $controller = null;
  var $components = array('Session', 'FileCache');
  var $sessionKey = 'fastFile.items';
  var $expireOffset = 10; // seconds
  var $excludeMediaIds = array();  

  function initialize(&$controller) {
    $this->controller = $controller;
  }

  function add($media, $name, $ext = 'jpg') {
    $file = $this->FileCache->getFilePath($media, $name);
    if (!is_readable($file) || in_array($media['Media']['id'], $this->excludeMediaIds)) {
      return false;
    }
    $key = $name . '-' . $media['Media']['id'];
    $files = (array) $this->Session->read($this->sessionKey);
    $files[$key] = array('expires' => time() + $this->expireOffset, 'file' => $file);
    $this->Session->write($this->sessionKey, $files);
    return true;
  }

  function addAll($data, $name, $ext = 'jpg') {
    foreach ($data as $media) {
      $this->add($media, $name, $ext);
    }
  }

  function excludeMedia($media) {
    $this->excludeMediaIds[] = $media['Media']['id'];
  }
}
?>
