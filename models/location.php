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

class Location extends AppModel
{
  var $name = 'Location';
  /** Array of valid location types */
  var $types = array(
                LOCATION_ANY => 'any',
                LOCATION_CITY => 'city',
                LOCATION_SUBLOCATION => 'sublocation', 
                LOCATION_STATE => 'state',
                LOCATION_COUNTRY => 'country'); 
  var $actsAs = array('WordList');
  
  /** 
    @param name Location name. Valid values are: city, sublocation, state, country, and any.
    @return Returns the type value of a given location string */
  function nameToType($name) {
    $type = array_search($name, $this->types);
    if ($type !== false)
      return $type;
    else
      return LOCATION_ANY;
  }

  /**
    @param type Location type
    @return Name of the location type */
  function typeToName($type) {
    if (isset($this->types[$type]))
      return $this->types[$type];
    else 
      return $this->types[LOCATION_ANY];
  }

  /** Create a list of location.
    @param locations array of locations, where the hash key is the name of the
    type and the value is the location value.
    \code
    Array
    (
      ['city'] => 'Heidelberg',
      ['state'] => 'Germany'
    )
    \endcode
    @return List of items for model creations
    @see createIdList()
    */
  function createLocationItems($locations) {
    $list = array();
    foreach ($this->types as $type => $name) {
      if (isset($locations[$name])) {
        $value = trim($locations[$name]);
        if (strlen($value) == 0 || $value == '-')
          continue;
        $list[] = array('name' => $value, 'type' => $type);
      }
    }
    return $list;
  }

  function editMetaSingle(&$media, &$data) {
    $ids = array();
    foreach ($this->types as $type => $locationName) {
      if ($type == LOCATION_ANY || !isset($data['Location'][$locationName])) {
        continue;
      }
      $name = trim($data['Location'][$locationName]);
      if (!$name || $name == '-') {
        continue;
      }        
      $location = array('name' => $name, 'type' => $type);
      $found = $this->find('first', array('conditions' => $location));
      if (!$found) {
        if (!$this->save($location)) {
          Logger::warn("Could not create new $locationName '$name'");
        } else {
          Logger::debug("Create new $locationName '$name'");
          $ids[] = $this->getInsertID();
        }
      } else {
        $ids[] = $found['Location']['id'];
      }
    }
    $ids = array_unique($ids);
    $oldIds = Set::extract('/Location/id', $media);
    if (count(array_diff($oldIds, $ids)) || count(array_diff($ids, $oldIds))) {
      return array('Location' => array('Location' => $ids));
    } else {
      return false;
    }
  }
  
  function prepareMultiEditData(&$data) {
    $tmp = array();
    foreach ($this->types as $type => $locationName) {
      if ($type == LOCATION_ANY || empty($data['Location'][$locationName])) {
        continue;
      }
      $camelizedName = Inflector::camelize($locationName);
      $isDelete = false;
      $name = trim($data['Location'][$locationName]);
      if ($name && $name[0] == '-') {
        $name = trim(substr($name, 1));
        if (!$name) {
          // Remove location information
          $tmp['Location']['delete' . $camelizedName] = -1;
          continue;
        }
        $isDelete = true;
      }        
      if (!$name) {
        continue;
      }
      $locationName = array('name' => $name, 'type' => $type);
      $location = $this->find('first', array('conditions' => $locationName));
      if ($isDelete && !$location) {
        continue;
      } elseif (!$location) {
        if (!$this->save($locationName)) {
          Logger::warn("Could not create new $locationName '$name'");
        } else {
          Logger::debug("Create new $locationName '$name'");
          $location = $this->findById($this->getInsertID());
        }
      }
      if ($isDelete) {
        $key = 'delete' . $camelizedName;
      } else {
        $key = 'add' . $camelizedName;
      }
      $tmp['Location'][$key] = $location['Location']['id'];
    }
    
    if (count($tmp)) {
      return $tmp;
    } else {
      return false;
    }
  }
  
  function editMetaMulti(&$media, &$data) {
    if (empty($data['Location'])) {
      return false;
    }

    $ids = array();
    foreach ($this->types as $type => $name) {
      if ($type == LOCATION_ANY) {
        continue;
      }
      $name = Inflector::camelize($name);
      $addKey = 'add' . Inflector::camelize($name);
      $deleteKey = 'delete' . Inflector::camelize($name);
      $locationId = Set::extract("/Location[type=$type]/id", $media);
      if ($locationId) {
        $locationId = $locationId[0];
      }
      if (!empty($data['Location'][$deleteKey])) {
        if ($locationId && $data['Location'][$deleteKey] > 0 && $locationId != $data['Location'][$deleteKey]) {
          // delete id does not match current id
          $ids[] = $locationId; 
        }
      } elseif (!empty($data['Location'][$addKey])) { 
        // overwrite new location
        $ids[] = $data['Location'][$addKey];
      } elseif ($locationId) {
        $ids[] = $locationId;
      }
    }
    
    $ids = array_unique($ids);
    $oldIds = Set::extract("/Location/id", $media);
    if (array_diff($ids, $oldIds) || array_diff($oldIds, $ids)) {
      return array('Location' => array('Location' => $ids));
    }
    return false;
  }

}
?>
