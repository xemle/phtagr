<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2008 Sebastian Felis, sebastian@phtagr.org
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
class Preference extends AppModel {

  var $name = 'Preference';
  var $useTable = 'configs';

  var $belongsTo = array('User' => array());

  function addDefaults($preferences) {
    $ownPreferences = Set::extract($preferences, '{n}.name');
    $this->unbindModel(array('belongsTo' => array('User')));
    $defaultPreferences = $this->findAllByUserId(0);
    foreach ($defaultPreferences as $default) {
      $name = $default[$this->name]['name'];
      if (strlen($name)>2 && substr($name, -2) == '[]') {
        $preferences[] = $default[$this->name];
      } else {
        $exists = in_array($name, $ownPreferences);
        if (!$exists) {
          $preferences[] = $default[$this->name];
        }
      }
    }
    return $preferences;
  }

  function getDefaultAcl($data) {
    $acl = array(
        'group_id' => -1,
        'gacl' => ACL_READ_PREVIEW,
        'macl' => ACL_READ_PREVIEW,
        'pacl' => ACL_READ_PREVIEW
      );
    return $acl;
  }  

  function getTree($userId) {
    $this->unbindModel(array('belongsTo' => array('User')));
    $data = $this->findAll("user_id = $userId OR user_id = 0 ORDER BY user_id ASC");
    return $this->buildTree($data);
  }

  function buildTree($data, $subPath = null, $strip = false) {
    $tree = array();
    // Preference is set as root
    if (isset($data['Preference']))
      $data = &$data['Preference'];

    foreach ($data as $item) {
      // Preference is set as item
      if (isset($item['Preference']))
        $preference = &$item['Preference'];
      else
        $preference = &$item;

      // Skip if subpath does not match
      if (isset($subPath) && strpos($preference['name'], $subPath) !== 0) {
        continue;
      } elseif ($strip && strpos($subPath, '.') > 0) {
        $preference['name'] = substr($preference['name'], strrpos($subPath, '.')+1);
      }
      $node = &$tree;
      $paths = explode('.', $preference['name']);
      for($i=0; $i<count($paths); $i++) {
        $path=$paths[$i];
          
        if (strlen($path)>2 && substr($path, -2) == '[]') {
          $path = substr($path, 0, strlen($path)-2);
          $isArray = true;
        } else {
          $isArray = false;
        }
        $node = &$node[$path];
      }
      if ($isArray)
        $node[] = $preference['value'];
      else
        $node = $preference['value'];
    }
    return $tree;
  }
  
  function setValue($name, $value, $userId = null) {
    if (strlen($name) > 1 && substr($name, -2) == '[]') {
      return $this->addValue($name, $value, $userId);
    }
    if ($userId !== null) {
      $this->data['User']['id'] = $userId;
    } else {
      $userId = $this->data['User']['id'];
    }
    $data = $this->find(array('AND' => array('user_id' => $userId, 'name' => $name)));
    if ($data) {
      $data['Preference']['value'] = $value;
      $this->save($data);
    } else {
      $this->create(array('Preference' => array('name' => $name, 'value' => $value, 'user_id' => $userId)));
      $this->save();
    }
  }

  function addValue($name, $value, $userId = null) {
    if (strlen($name) < 2 || substr($name, -2) != '[]') {
      return $this->setValue($name, $value, $userId);
    }
    if ($userId !== null) {
      $this->data['User']['id'] = $userId;
    } else {
      $userId = $this->data['User']['id'];
    }
    $this->create(array('Preference' => array('name' => $name, 'value' => $value, 'user_id' => $userId)));
    $this->save();
  }

  function delValue($name, $value = null, $userId = null) {
    if ($userId !== null) {
      $this->data['User']['id'] = $userId;
    } else {
      $userId = $this->data['User']['id'];
    }
    $conditions = array('user_id' => $userId, 'name' => $name);
    if ($value)
      $conditions['value'] = $value;

    $this->deleteAll(array('AND' => $conditions));
  }
}
?>
