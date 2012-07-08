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

class Field extends AppModel {
  var $name = 'Field';

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

  public function createField(&$media, $name, $values) {
    foreach ($values as $value) {
      $data = array('name' => $name, 'data' => $value);
      $field = $this->find('first', array('conditions' => $data));
      if (!$field) {
        $field = $this->save($this->create($data));
      }
      if (!empty($media['Field']['Field'])) {
        $media['Field']['Field'][] = $field['Field']['id'];
      } else {
        $media['Field']['Field'] = array($field['Field']['id']);
      }
      if (!$this->isListField($name)) {
        return;
      }
    }
  }

  public function createFields(&$media) {
    $names = $this->getFieldNames();
    foreach ($names as $name) {
      if (!empty($media['Field'][$name])) {
        $values = (array)$media['Field'][$name];
        $this->createField(&$media, $name, $values);
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
    $names = $this->getFieldNames();
    $result = array();
    foreach ($names as $name) {
      if (!empty($data['Field'][$name])) {
        // Skip disabled fields if set
        if ($onlyFields === null || (is_array($onlyFields) && in_array($name, $onlyFields))) {
          $values = (array)$data['Field'][$name];
          $this->createField(&$result, $name, $values);
        } else {
          $oldIds = Set::extract("/Field[name=$name]/data");
          if (!oldIds) {
            continue;
          } else if (!empty($result['Field']['Field'])) {
            $result['Field']['Field'] = am($result['Field']['Field'], $oldIds);
          } else {
            $result['Field']['Field'] = $oldIds;
          }
        }
      }
    }
    return $result;
  }
}
