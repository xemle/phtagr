<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2009 Sebastian Felis, sebastian@phtagr.org
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

class Property extends AppModel {

  var $name = 'Property';

  //The Associations below have been created with all possible keys, those that are not needed can be removed
  var $belongsTo = array(
      'File' => array('className' => 'MyFile',
                'foreignKey' => 'file_id',
                'conditions' => '',
                'fields' => '',
                'order' => ''
      )
  );

  function copy($data, $imageId) {
    if (empty($data['Property']))
      return;
    foreach ($data['Property'] as $property) {
      if (!isset($property['Property'])) {
        $property = array('Property' => $property);
      }
      unset($property['Property']['id']);
      $property['Property']['image_id'] = $imageId;
      $property = $this->create($property);
      if (!$this->save($property)) {
        $this->Logger->err("Could not save property");
        $this->Logger->trace($property);
      }
    }
  }
}
?>
