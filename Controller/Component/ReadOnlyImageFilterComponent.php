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
App::uses('BaseFilter', 'Component');

class ReadOnlyImageFilterComponent extends BaseFilterComponent {
  var $controller = null;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  public function getName() {
    return "ReadOnlyImage";
  }

  public function getExtensions() {
    return array('bmp', 'gif', 'png', 'psd', 'tif', 'tiff');
  }

  /**
   * Read the meta data from the file
   *
   * @param file File model data
   * @param media Reference of Media model data
   * @param options Options
   *  - noSave if set dont save model data
   * @return The image data array or False on error
   */
  public function read(&$file, &$media = null, $options = array()) {
    $options = am(array('noSave' => false), $options);
    $filename = $this->controller->MyFile->getFilename($file);

    $isNew = false;
    if (!$media) {
      $media = $this->controller->Media->create(array(
        'type' => MEDIA_TYPE_IMAGE,
        ), true);
      if ($this->controller->getUserId() != $file['File']['user_id']) {
        $user = $this->controller->Media->User->findById($file['File']['user_id']);
      } else {
        $user = $this->controller->getUser();
      }
      $media = $this->controller->Media->addDefaultAcl($media, $user);

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
    $media['Media']['name'] = basename($filename);
    if (!isset($media['Media']['date'])) {
      $media['Media']['date'] = date('Y-m-d H:i:s', time());
    }
    if ($options['noSave']) {
      return $media;
    } elseif (!$this->controller->Media->save($media)) {
      Logger::err("Could not save Media");
      Logger::trace($media);
      $this->FilterManager->addError($filename, 'MediaSaveError');
      return false;
    }
    if ($isNew) {
      $mediaId = $this->controller->Media->getLastInsertID();
      if (!$this->controller->MyFile->setMedia($file, $mediaId)) {
        $this->controller->Media->delete($mediaId);
        $this->FilterManager->addError($filename, 'FileSaveError');
        return false;
      } else {
        Logger::info("Created new Media (id $mediaId)");
        $media = $this->controller->Media->findById($mediaId);
      }
    } else {
      Logger::verbose("Updated media (id ".$media['Media']['id'].")");
    }
    $this->controller->MyFile->updateReaded($file);
    $this->controller->MyFile->setFlag($file, FILE_FLAG_DEPENDENT);
    return $media;
  }

  /**
   * Write the meta data to an image file
   *
   * @param file File model data
   * @param media Media model data
   * @param options Array of options
   * @return False on error
   */
  public function write(&$file, &$media, $options = array()) {
    Logger::warn("Write action is not supported for {$file['File']['file']}");
    $filename = $this->controller->MyFile->getFilename($file);
    $this->FilterManager->addError($filename, 'MetaDataWriteNotSupported');
    return false;
  }

}

?>
