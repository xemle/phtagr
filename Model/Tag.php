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

class Tag extends AppModel
{
  var $name = 'Tag';
  var $actsAs = array('WordList');
  
  /**
   * Prepare multi ediet data for tags
   * 
   * @param type $data
   * @return type 
   */
  function prepareMultiEditData(&$data) {
    $names = $data['Tag']['names'];
    $words = $this->splitWords($names);
    if (!count($words)) {
      return false;
    }
    $addWords = $this->removeNegatedWords($words);
    $deleteWords = $this->getNegatedWords($words);

    $addTags = $this->findAllByField($addWords);
    $deleteTags = $this->findAllByField($deleteWords, false);
    
    if (count($addTags) || count($deleteTags)) {
      return array('Tag' => array('addTag' => Set::extract("/Tag/id", $addTags), 'deleteTag' => Set::extract("/Tag/id", $deleteTags)));
    } else {
      return false;
    }
  }
  
  /**
   * Add and delete tags according to the given data
   * 
   * @param type $media
   * @param type $data
   * @return type 
   */
  function editMetaMulti(&$media, &$data) {
    if (empty($data['Tag'])) {
      return false;
    }
    $oldIds = Set::extract('/Tag/id', $media);
    $ids = am($oldIds, $data['Tag']['addTag']);
    $ids = array_unique(array_diff($ids, $data['Tag']['deleteTag']));
    if (array_diff($ids, $oldIds) || array_diff($oldIds, $ids)) {
      return array('Tag' => array('Tag' => $ids));
    } else {
      return false;
    }
  }
  
  function editMetaSingle(&$media, &$data) {
    if (!isset($data['Tag']['names'])) {
      return false;
    }
    $words = $this->splitWords($data['Tag']['names'], false);
    $words = $this->removeNegatedWords($words);
    $tags = $this->findAllByField($words);
    $ids = array_unique(Set::extract("/Tag/id", $tags));
    $oldIds = Set::extract("/Tag/id", $media);
    if (count(array_diff($oldIds, $ids)) || count(array_diff($ids, $oldIds))) {
      return array('Tag' => array('Tag' => $ids));
    } else {
      return false;
    }
  }
}
?>
