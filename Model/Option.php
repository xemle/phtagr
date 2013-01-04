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
class Option extends AppModel {

  var $name = 'Option';

  var $belongsTo = array('User' => array());

  var $_aclMap = array(ACL_LEVEL_GROUP => 'gacl',
                           ACL_LEVEL_USER => 'uacl',
                           ACL_LEVEL_OTHER => 'oacl');

  public function addDefaults($options) {
    $ownOptions = Set::extract('/name', $options);
    $this->unbindModel(array('belongsTo' => array('User')));
    $defaultOptions = $this->findAllByUserId(0);
    foreach ($defaultOptions as $default) {
      $name = $default[$this->name]['name'];
      if (strlen($name) > 2 && substr($name, -2) == '[]') {
        $options[] = $default[$this->name];
      } else if (!$ownOptions || !in_array($name, $ownOptions)) {
        $options[] = $default[$this->name];
      }
    }
    return $options;
  }

  /**
   * Returns the user options as array
   *
   * @param user User model data
   * @return Array of options
   */
  public function getOptions($user) {
    $options = array();
    if (!isset($user['Option'])) {
      return $options;
    }
    foreach ($user['Option'] as $o) {
      $name = $o['name'];
      $value = $o['value'];
      if (substr($name, -2) == '[]') {
        $name = substr($name, 0, strlen($name) - 2);
        if (!isset($options[$name])) {
          $options[$name] = array();
        }
        $options[$name][] = $value;
      } else {
        $options[$name] = $value;
      }
    }
    return $options;
  }

  public function getTree($userId) {
    $this->unbindModel(array('belongsTo' => array('User')));
    $data = $this->find('all', array('conditions' => "user_id = $userId OR user_id = 0 ORDER BY user_id ASC"));
    return $this->buildTree($data);
  }

  public function buildTree($data, $subPath = null, $strip = false) {
    $tree = array();
    // Option is set as root
    if (isset($data['Option']))
      $data = $data['Option'];

    foreach ($data as $item) {
      // Option is set as item
      if (isset($item['Option'])) {
        $option = $item['Option'];
      } else {
        $option = $item;
      }

      // Skip if subpath does not match
      if (isset($subPath) && strpos($option['name'], $subPath) !== 0) {
        continue;
      } elseif ($strip && strpos($subPath, '.') > 0) {
        $option['name'] = substr($option['name'], strrpos($subPath, '.')+1);
      }
      $node =& $tree;
      $paths = explode('.', $option['name']);
      for($i=0; $i<count($paths); $i++) {
        $path=$paths[$i];

        if (strlen($path)>2 && substr($path, -2) == '[]') {
          $path = substr($path, 0, strlen($path)-2);
          $isArray = true;
        } else {
          $isArray = false;
        }
        if (!isset($node[$path])) {
          $node[$path] = array();
        }
        $node =& $node[$path];
      }
      if ($isArray) {
        $node[] = $option['value'];
      } else {
        $node = $option['value'];
      }
    }
    return $tree;
  }

  /**
   * Returns the value of a option model data
   *
   * @param data Model data
   * @param name Name of option
   * @param default Default value, if value is not found
   * @return Value of the option
   */
  public function _getModelValue($data, $name, $default = null) {
    if (!isset($data['Option'])) {
      return $default;
    }

    $isArray = false;
    $values = array();
    if (strlen($name) > 2 && substr($name, -2) == '[]') {
      $isArray = true;
    }

    foreach ($data['Option'] as $option) {
      if ($option['name'] === $name) {
        if (!$isArray) {
          return $option['value'];
        } else {
          $values[] = $option['value'];
        }
      }
    }
    if ($isArray && count($values)) {
      return $values;
    }

    return $default;
  }

  /**
   * Returns the value of a given path from the data
   *
   * @param data Option tree data or model data
   * @param path Path of the data to extract
   * @param default Default value, if the path does not exists
   * @return Extracted option (or default value)
   * @see _getModelValue
   */
  public function getValue($data, $path, $default = null) {
    if (isset($data['Option'])) {
      return $this->_getModelValue($data, $path, $default);
    }
    $value = Set::extract($data, $path);
    if (!empty($value)) {
      return $value;
    }
    return $default;
  }

  public function setValue($name, $value, $userId = null) {
    if (strlen($name) > 1 && substr($name, -2) == '[]') {
      return $this->addValue($name, $value, $userId);
    }
    if ($userId !== null) {
      $this->data['User']['id'] = $userId;
    } else {
      $userId = $this->data['User']['id'];
    }
    $data = $this->find('first', array('conditions' => array('Option.user_id' => $userId, 'Option.name' => $name)));
    if ($data) {
      $data['Option']['value'] = $value;
      $this->save($data);
    } else {
      $data = $this->create(array('Option' => array('name' => $name, 'value' => $value, 'user_id' => $userId)));
      $this->save($data);
    }
  }

  public function addValue($name, $value, $userId = null) {
    if (strlen($name) < 2 || substr($name, -2) != '[]') {
      return $this->setValue($name, $value, $userId);
    }
    if ($userId !== null) {
      $this->data['User']['id'] = $userId;
    } else {
      $userId = $this->data['User']['id'];
    }
    $data = $this->create(array('Option' => array('name' => $name, 'value' => $value, 'user_id' => $userId)));
    $this->save($data);
  }

  public function delValue($name, $value = null, $userId = null) {
    if ($userId !== null) {
      $this->data['User']['id'] = $userId;
    } else {
      $userId = $this->data['User']['id'];
    }
    $conditions = array('user_id' => $userId, 'name' => $name);
    if ($value) {
      $conditions['value'] = $value;
    }

    $this->deleteAll(array('AND' => $conditions));
  }

  /**
   * Increase the ACL level. It checks the current flag and increases the ACL
   * level of lower ACL levels (first level is ACL_LEVEL_GROUP, second level is
   * ACL_LEVEL_USER and the third level is ACL_LEVEL_OTHER).
   *
   * @param data Array of image data
   * @param flag Threshold flag which indicates the upper inclusive bound
   * @param mask Bit mask of flag
   * @param level Highes ACL level which should be increased
   */
  public function _increaseAcl(&$data, $flag, $mask, $level) {
    //Logger::debug("Increase: {$data['gacl']},{$data['uacl']},{$data['oacl']}: $flag/$mask ($level)");
    if ($level>ACL_LEVEL_OTHER)
      return;

    for ($l=ACL_LEVEL_GROUP; $l<=$level; $l++) {
      $name = $this->_aclMap[$l];
      if (($data[$name]&($mask))<$flag)
        $data[$name]=($data[$name]&(~$mask))|$flag;
    }
    //Logger::debug("Increase (result): {$data['gacl']},{$data['uacl']},{$data['oacl']}: $flag/$mask ($level)");
  }

  /**
   * Decrease the ACL level. Decreases the ACL level of higher ACL levels
   * according to the current flag (first level is ACL_LEVEL_GROUP, second level
   * is ACL_LEVEL_USER and the third level is ACL_LEVEL_OTHER). The decreased ACL
   * value is the ACL value of the higher levels which is less than the current
   * threshold or it is zero if no lower ACL value is available.
   *
   * @param data Array of image data
   * @param flag Threshold flag which indicates the upper exlusive bound
   * @param mask Bit mask of flag
   * @param level Lower ACL level which should be downgraded
   */
  public function _decreaseAcl(&$data, $flag, $mask, $level) {
    //Logger::debug("Decrease: {$data['gacl']},{$data['uacl']},{$data['oacl']}: $flag/$mask ($level)");
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
    //Logger::debug("Decrease (result): {$data['gacl']},{$data['uacl']},{$data['oacl']}: $flag/$mask ($level)");
  }

  public function setAcl(&$data, $flag, $mask, $level) {
    if ($level<ACL_LEVEL_KEEP || $level>ACL_LEVEL_OTHER)
      return false;

    if ($level==ACL_LEVEL_KEEP)
      return $data;

    if ($level>=ACL_LEVEL_GROUP)
      $this->_increaseAcl($data, $flag, $mask, $level);

    if ($level<ACL_LEVEL_OTHER)
      $this->_decreaseAcl($data, $flag, $mask, $level+1);

    return $data;
  }

  public function addDefaultAclTree($tree) {
    if (!isset($tree['acl']['write']['tag'])) {
      $tree['acl']['write']['tag'] = ACL_LEVEL_USER;
    }
    if (!isset($tree['acl']['write']['meta'])) {
      $tree['acl']['write']['meta'] = ACL_LEVEL_GROUP;
    }
    if (!isset($tree['acl']['read']['preview'])) {
      $tree['acl']['read']['preview'] = ACL_LEVEL_OTHER;
    }
    if (!isset($tree['acl']['read']['original'])) {
      $tree['acl']['read']['original'] = ACL_LEVEL_GROUP;
    }
    if (!isset($tree['acl']['group'])) {
      $tree['acl']['group'] = -1;
    }
    return $tree;
  }

  public function getDefaultAcl($data) {
    $tree = $this->buildTree($data);

    // default values
    $acl = array(
        'groupId' => $this->getValue($tree, 'acl.group', -1),
        'gacl' => ACL_READ_ORIGINAL | ACL_WRITE_META,
        'uacl' => ACL_READ_PREVIEW | ACL_WRITE_TAG,
        'oacl' => ACL_READ_PREVIEW
      );

    $this->setAcl($acl, ACL_WRITE_TAG, ACL_WRITE_MASK, $this->getValue($tree, 'acl.write.tag', ACL_LEVEL_OTHER));
    $this->setAcl($acl, ACL_WRITE_META, ACL_WRITE_MASK, $this->getValue($tree, 'acl.write.meta', ACL_LEVEL_USER));

    $this->setAcl($acl, ACL_READ_PREVIEW, ACL_READ_MASK, $this->getValue($tree, 'acl.read.preview', ACL_LEVEL_OTHER));
    $this->setAcl($acl, ACL_READ_ORIGINAL, ACL_READ_MASK, $this->getValue($tree, 'acl.read.original', ACL_LEVEL_GROUP));

    return $acl;
  }

}
?>
