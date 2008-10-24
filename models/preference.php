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

  var $_aclMap = array(ACL_LEVEL_GROUP => 'gacl',
                           ACL_LEVEL_USER => 'uacl',
                           ACL_LEVEL_OTHER => 'oacl');

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
 
  /** Returns the value of a preference model data
    @param data Model data
    @param name Name of preference
    @param default Default value, if value is not found 
    @return Value of the preference */
  function _getModelValue($data, $name, $default = null) {
    if (!isset($data['Preference'])) {
      return $default;
    }

    $isArray = false;
    $values = array();
    if (strlen($name) > 2 && substr($name, -2) == '[]') {
      $isArray = true;
    }

    foreach ($data['Preference'] as $pref) {
      if ($pref['name'] === $name) {
        if (!$isArray) {
          return $pref['value'];
        } else {
          $values[] = $pref['value'];
        }
      }
    }
    if ($isArray && count($values)) {
      return $values;
    }

    return $default;
  }

  /** Returns the value of a given path from the data
    @param data Preference tree data or model data
    @param path Path of the data to extract
    @param default Default value, if the path does not exists
    @return Extracted preference (or default value)
    @see _getModelValue */
  function getValue($data, $path, $default = null) {
    if (isset($data['Preference'])) {
      return $this->_getModelValue($data, $path, $default);
    }
    $value = Set::extract($name, $data);
    if (!empty($value)) {
      return $value;
    }
    return $default;
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

  /** Increase the ACL level. It checks the current flag and increases the ACL
   * level of lower ACL levels (first level is ACL_LEVEL_GROUP, second level is
   * ACL_LEVEL_USER and the third level is ACL_LEVEL_OTHER).
    @param data Array of image data
    @param flag Threshold flag which indicates the upper inclusive bound
    @param mask Bit mask of flag 
    @param level Highes ACL level which should be increased */
  function _increaseAcl(&$data, $flag, $mask, $level) {
    //$this->Logger->debug("Increase: {$data['gacl']},{$data['uacl']},{$data['oacl']}: $flag/$mask ($level)");
    if ($level>ACL_LEVEL_OTHER)
      return;

    for ($l=ACL_LEVEL_GROUP; $l<=$level; $l++) {
      $name = $this->_aclMap[$l];
      if (($data[$name]&($mask))<$flag)
        $data[$name]=($data[$name]&(~$mask))|$flag;
    }
    //$this->Logger->debug("Increase (result): {$data['gacl']},{$data['uacl']},{$data['oacl']}: $flag/$mask ($level)");
  }

  /** Decrease the ACL level. Decreases the ACL level of higher ACL levels
   * according to the current flag (first level is ACL_LEVEL_GROUP, second level
   * is ACL_LEVEL_USER and the third level is ACL_LEVEL_OTHER). The decreased ACL
   * value is the ACL value of the higher levels which is less than the current
   * threshold or it is zero if no lower ACL value is available. 
    @param data Array of image data
    @param flag Threshold flag which indicates the upper exlusive bound
    @param mask Bit mask of flag
    @param level Lower ACL level which should be downgraded */
  function _decreaseAcl(&$data, $flag, $mask, $level) {
    //$this->Logger->debug("Decrease: {$data['gacl']},{$data['uacl']},{$data['oacl']}: $flag/$mask ($level)");
    if ($level<ACL_LEVEL_GROUP)
      return;

    for ($l=ACL_LEVEL_OTHER; $l>=$level; $l--) {
      $name = $this->_aclMap[$l];
      // Evaluate the available ACL value which is lower than the threshold
      if ($l==ACL_LEVEL_OTHER) 
        $lower = 0;
      else {
        $next = $this->_aclMap[$l+1];
        $lower = $data[$next]&($mask);
      }
      $lower=($lower<$flag)?$lower:0;
      if (($data[$name]&($mask))>=$flag)
        $data[$name]=($data[$name]&(~$mask))|$lower;
    }
    //$this->Logger->debug("Decrease (result): {$data['gacl']},{$data['uacl']},{$data['oacl']}: $flag/$mask ($level)");
  }

  function setAcl(&$data, $flag, $mask, $level) {
    if ($level<ACL_LEVEL_KEEP || $level>ACL_LEVEL_OTHER)
      return false;

    if ($level==ACL_LEVEL_KEEP)
      return $data;

    if ($level>=ACL_LEVEL_GROUP)
      $this->_increaseAcl(&$data, $flag, $mask, $level);

    if ($level<ACL_LEVEL_OTHER)
      $this->_decreaseAcl(&$data, $flag, $mask, $level+1);

    return $data;
  }

  function getDefaultAcl($data) {
    $tree = $this->buildTree($data);
    
    // default values
    $acl = array(
        'groupId' => $this->getValue($tree, 'acl.group', -1),
        'gacl' => ACL_READ_ORIGINAL | ACL_WRITE_META,
        'uacl' => ACL_READ_PREVIEW | ACL_WRITE_TAG,
        'oacl' => ACL_READ_PREVIEW
      );

    $this->setAcl(&$acl, ACL_WRITE_TAG, ACL_WRITE_MASK, $this->getValue($tree, 'acl.write.tag', ACL_LEVEL_KEEP));
    $this->setAcl(&$acl, ACL_WRITE_META, ACL_WRITE_MASK, $this->getValue($tree, 'acl.write.meta', ACL_LEVEL_KEEP));

    $this->setAcl(&$acl, ACL_READ_PREVIEW, ACL_READ_MASK, $this->getValue($tree, 'acl.read.preview', ACL_LEVEL_KEEP));
    $this->setAcl(&$acl, ACL_READ_ORIGINAL, ACL_READ_MASK, $this->getValue($tree, 'acl.read.original', ACL_LEVEL_KEEP));

    return $acl;
  }  

}
?>
