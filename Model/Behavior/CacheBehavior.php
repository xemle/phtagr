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

/** Cache File behavior for media
  @see CacheFileComponent */
class CacheBehavior extends ModelBehavior 
{
  var $config = array();

  function setup(&$model, $config = array()) {
    $this->config[$model->name] = $config;
  }

  /** Deletes all cache files of a given media
    @param model Reference of model
    @param data Model data 
    @return True on success */
  function deleteCache(&$model, &$data = null) {
    if (!$data) {
      $data =& $model->data;
    }
    if (isset($data[$model->alias])) {
      $data = $data[$model->alias];
    }
    if (!isset($data['user_id']) || !isset($data['id'])) {
      Logger::err("Precondition failed");
      return false;
    }
    
    $cacheDir = USER_DIR.$data['user_id'].DS.'cache'.DS;
    $cacheDir .= sprintf("%04d", ($data['id'] / 1000)).DS;
    if (!is_dir($cacheDir)) {
      return true;
    }

    // catch all cache files and delete them
    $pattern = sprintf("%07d-.*", $data['id']);
    $folder =& new Folder($cacheDir);
    $files = $folder->find($pattern);
    if (!$files) {
      Logger::trace("No cache files found for media {$data['id']}");
    } else {
      foreach ($files as $file) {
        if (!@unlink($folder->addPathElement($cacheDir, $file))) {
          Logger::err("Could not delete cache file ".$folder->addPathElement($cacheDir, $file));
        } else {
          Logger::trace("Deleted cache file '$file'");
        }
      }
      Logger::debug("Deleted cache files of media {$data['id']}");
    }
    return true;
  }
}
?>
