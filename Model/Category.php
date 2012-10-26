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

class Category extends AppModel
{
  var $name = 'Category';
  var $actsAs = array('WordList');

  public function editMetaSingle(&$media, &$data) {
    if (!isset($data['Category']['names'])) {
      return false;
    }
    $words = $this->splitWords($data['Category']['names'], false);
    $words = $this->removeNegatedWords($words);
    $categories = $this->findAllByField($words);
    $ids = array_unique(Set::extract("/Category/id", $categories));
    $oldIds = Set::extract("/Category/id", $media);
    if (count(array_diff($oldIds, $ids)) || count(array_diff($ids, $oldIds))) {
      return array('Category' => array('Category' => $ids));
    } else {
      return false;
    }
  }

  /**
   * Prepare multi edit data for categories
   *
   * @param type $data
   * @return type
   */
  public function prepareMultiEditData(&$data) {
    if (empty($data['Category']['names'])) {
       return false;
    }
    $names = $data['Category']['names'];
    $words = $this->splitWords($names);
    if (!count($words)) {
      return false;
    }
    $addWords = $this->removeNegatedWords($words);
    $deleteWords = $this->getNegatedWords($words);

    $addCategories = $this->findAllByField($addWords);
    $deleteCategories = $this->findAllByField($deleteWords, false);

    if (count($addCategories) || count($deleteCategories)) {
      return array('Category' => array('addCategory' => Set::extract("/Category/id", $addCategories), 'deleteCategory' => Set::extract("/Category/id", $deleteCategories)));
    } else {
      return false;
    }
  }

  /**
   * Add and delete categories according to the given data
   *
   * @param type $media
   * @param type $data
   * @return type
   */
  public function editMetaMulti(&$media, &$data) {
    if (empty($data['Category'])) {
      return false;
    }
    $oldIds = Set::extract('/Category/id', $media);
    $ids = am($oldIds, $data['Category']['addCategory']);
    $ids = array_unique(array_diff($ids, $data['Category']['deleteCategory']));
    if (array_diff($ids, $oldIds) || array_diff($oldIds, $ids)) {
      return array('Category' => array('Category' => $ids));
    } else {
      return false;
    }
  }

}
?>
