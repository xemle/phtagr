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
