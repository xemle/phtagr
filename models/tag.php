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
