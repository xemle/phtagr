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

class Field extends AppModel {
  var $name = 'Field';

  var $actsAs = array('WordList');

  var $singleFields = array(
      'sublocation',
      'city',
      'state',
      'country'
      );
  var $listFields = array(
      'keyword',
      'category'
      );

  /**
   * Evaluates if given field name is a list type
   *
   * @param string $name Field name
   * @return boolean Returns true if field name is a list type
   */
  public function isListField($name) {
    return in_array($name, $this->listFields);
  }

  /**
   * Return all supported fields
   *
   * @return array
   */
  public function getFieldNames() {
    return am($this->singleFields, $this->listFields);
  }

  /**
   * Find or create field by given name and values. Values are trimmed. Empty
   * values are not considered.
   *
   * @param type $name Field name
   * @param type $values string or array of string
   * @return type array of ids
   */
  public function createField($name, $values) {
    $ids = array();
    foreach ((array)$values as $value) {
      $value = trim($value);
      if (!$value) {
        continue;
      }
      $data = array('name' => $name, 'data' => $value);
      $field = $this->find('first', array('conditions' => $data));
      if (!$field) {
        $field = $this->save($this->create($data));
      }
      $ids[] = $field['Field']['id'];
      if (!$this->isListField($name)) {
        break;
      }
    }
    return $ids;
  }

  public function createFields(&$media) {
    if (empty($media['Field'])) {
      return;
    }
    $names = $this->getFieldNames();
    foreach ($names as $name) {
      if (!empty($media['Field'][$name])) {
        $values = (array)$media['Field'][$name];
        $ids = $this->createField($name, $values);
        if (isset($media['Field']['Field'])) {
          $media['Field']['Field'] = am($media['Field']['Field'], $ids);
        } else {
          $media['Field']['Field'] = $ids;
        }
      }
    }
  }

  /**
   * Assign fields for single edit
   *
   * @param type $data
   * @param array $onlyFields Array of allowed fields. If null allow all. Default is null
   * @return array Field assignments
   */
  public function editSingle(&$media, &$data, $onlyFields = null) {
    if (empty($data['Field'])) {
      return array();
    }
    // Save old ids as references
    $oldIds = Set::extract('/Field/id', $media);
    $removeIds = array();
    $addIds = array();
    foreach ($data['Field'] as $name => $values) {
      if (is_numeric($name) || !preg_match('/[\w.-_\/]+/', $name)) {
        continue;
      }
      // Skip disabled fields if set
      if ($onlyFields === null || (is_array($onlyFields) && in_array($name, $onlyFields))) {
        if ($this->isListField($name)) {
          $values = $this->removeNegatedWords($this->splitWords($values));
        }
        $removeIds = am($removeIds, Set::extract("/Field[name=$name]/id", $media));
        $addIds = am($addIds, $this->createField($name, $values));
      }
    }
    $ids = array_diff($oldIds, $removeIds);
    $ids = am($ids, $addIds);
    if (count($oldIds) == count($ids) && !array_diff($oldIds, $ids)) {
      return array();
    }
    return array('Field' => array('Field' => $ids));
  }

  /**
   *
   * @param type $data
   * @return array Array of changes.
   * <code>array(
   *  'Field' => array(
   *    'fieldName' => array(
   *      'add' => array(1, ,2 ,3),
   *      'delete' => array(4, 5, 6),
   *      'deleteAll' )> true
   *    ),
   *    'fieldName2' => array(...)
   *   )
   * </code>
   */
  function prepareMultiEditData(&$data) {
    if (!isset($data['Field'])) {
       return false;
    }
    $validNames = $this->getFieldNames();
    $tmp = array('Field' => array());
    foreach ($data['Field'] as $name => $values) {
      if (!in_array($name, $validNames)) {
        continue;
      }
      $values = $this->splitWords($values);
      foreach ($values as $value) {
        $isDelete = false;
        if ($value && $value[0] == '-') {
          $value = trim(substr($value, 1));
          if (!$name) {
            // Remove field information
            $tmp['Field'][$name]['deleteAll'] = true;
            continue;
          }
          $isDelete = true;
        }
        if (!$value) {
          continue;
        }
        $conditions = array('name' => $name, 'data' => $value);
        $field = $this->find('first', array('conditions' => $conditions));
        if ($isDelete && !$field) {
          continue;
        } elseif (!$field) {
          $field = $this->save($this->create($conditions));
          if (!$field) {
            Logger::error("Could not create new field '$name' with value '$value'");
            continue;
          }
        }
        $action = $isDelete ? 'delete' : 'add';
        if (isset($tmp['Field'][$name][$action])) {
          $tmp['Field'][$name][$action][] = $field['Field']['id'];
        } else {
          $tmp['Field'][$name][$action] = array($field['Field']['id']);
        }
        if (!$this->isListField($name)) {
          break;
        }
      }
    }

    if (count($tmp['Field'])) {
      // unify ids
      foreach ($tmp['Field'] as $name => $actions) {
        foreach ($actions as $action => $ids) {
          if (is_array($ids)) {
            $tmp['Field'][$name][$action] = array_unique($ids);
          }
        }
      }
      return $tmp;
    } else {
      return false;
    }
  }

  /**
   * Apply field changes to given media
   *
   * @param array $media Media model data
   * @param array $data Data from Field::prepareMultiEditData()
   * @param type $allowedFields List of fields to update. If null all field
   * are allowed to update. Default is null.
   * @return array Update data for field changes
   */
  function editMulti(&$media, &$data, $allowedFields = null) {
    if (!isset($data['Field'])) {
      return false;
    }
    $oldIds = Set::extract("/Field/id", $media);
    $newIds = $oldIds;
    foreach ($data['Field'] as $name => $actions) {
      if ($allowedFields && !in_array($name, $allowedFields)) {
        continue;
      }
      $isListField = $this->isListField($name);
      if (isset($actions['deleteAll'])) {
        $ids = Set::extract("/Field[name=$name]/id", $media);
        $newIds = array_diff($newIds, $ids);
        continue;
      }
      if (isset($actions['delete'])) {
        $newIds = array_diff($newIds, $actions['delete']);
      }
      if (isset($actions['add'])) {
        if (!$isListField) {
          $ids = Set::extract("/Field[name=$name]/id", $media);
          $newIds = array_diff($newIds, $ids);
        }
        $newIds = am($newIds, $actions['add']);
      }
    }
    $newIds = array_unique($newIds);

    if (count($oldIds) != count($newIds) || array_diff($oldIds, $newIds)) {
      return array('Field' => array('Field' => $newIds));
    } else {
      return false;
    }
  }

  /**
   * Returns a list of values which start with given prefix
   *
   * @param $type Field name(s) as string or array
   * @param string $start Start value
   * @return Return complete values
   */
  public function complete($type, $prefix, $max = 10) {
    $result = $this->find('all', array(
        'conditions' => array('Field.name' => $type, 'Field.data LIKE' => $prefix.'%'),
        'limit' => $max,
        'recursive' => -1));
    return Set::extract('/Field/data', $result);
  }
}
