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

class SimpleImageFilterComponent extends BaseFilterComponent {
  var $controller = null;

  function initialize(&$controller) {
    $this->controller =& $controller;
  }

  function getName() {
    return "SimpleImage";
  }

  function getExtensions() {
    return array('bmp', 'gif', 'png', 'psd', 'tif', 'tiff');
  }

  /** Read the meta data from the file 
   * @param file File model data
   * @param media Reference of Media model data
   * @param options Options
   *  - noSave if set dont save model data
   * @return The image data array or False on error */
  function read($file, &$media, $options = array()) {
    $options = am(array('noSave' => false), $options);
    $filename = $this->MyFile->getFilename($file);

    $isNew = false;
    if (!$media) {
      $media = $this->Media->create(array(
        'type' => MEDIA_TYPE_IMAGE,
        ), true);
      if ($this->controller->getUserId() != $file['File']['user_id']) {
        $user = $this->Media->User->findById($file['File']['user_id']);
      } else {
        $user = $this->controller->getUser();
      }
      $media = $this->Media->addDefaultAcl(&$media, &$user);
      
      $isNew = true;
    };

    if (!isset($media['Media']['width']) || $media['Media']['width'] == 0 ||
      !isset($media['Media']['height']) || $media['Media']['height'] == 0) {
      $size = getimagesize($filename);
      if ($size) {
        $media['Media']['width'] = $size[0];
        $media['Media']['height'] = $size[1];
      } else {
        Logger::error("Could not determine image size of $filename");
        return false;
      }
    }
    if (!isset($media['Media']['date'])) {
      $media['Media']['date'] = date('Y-m-d H:i:s', time());
    }
    $media['Media']['name'] = basename($filename);
    if ($options['noSave']) {
      return $media;
    } elseif (!$this->Media->save($media)) {
      Logger::err("Could not save Media");
      Logger::trace($media);
      $this->FilterManager->addError($filename, 'MediaSaveError');
      return false;
    } 
    if ($isNew) {
      $mediaId = $this->Media->getLastInsertID();
      if (!$this->MyFile->setMedia($file, $mediaId)) {
        $this->Media->delete($mediaId);
        $this->FilterManager->addError($filename, 'FileSaveError');
        return false;
      } else {
        Logger::info("Created new Media (id $mediaId)");
        $media = $this->Media->findById($mediaId);
      }
    } else {
      Logger::verbose("Updated media (id ".$media['Media']['id'].")");
    }
    $this->MyFile->updateReaded($file);
    $this->MyFile->setFlag($file, FILE_FLAG_DEPENDENT);
    return $media;
  }

  /** Write the meta data to an image file 
   * @param file File model data
   * @param media Media model data
   * @param options Array of options
   * @return False on error */
  function write($file, $media = null, $options = array()) {
    Logger::warn("Write action is not supported");
    return false;
  }

}

?>
