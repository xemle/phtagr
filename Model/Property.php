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

  public function copy($data, $imageId) {
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
        Logger::err("Could not save property");
        Logger::trace($property);
      }
    }
  }
}
?>
