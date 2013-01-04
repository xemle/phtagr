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

App::uses('RelatedBehavior', 'Model/Behavior');
App::uses('Media', 'Model');
App::uses('Field', 'Model');
App::uses('Logger', 'Lib');
/**
 * RelatedBehavior Test Case
 *
 */
class RelatedBehaviorTestCase extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
  public $fixtures = array('app.file', 'app.media', 'app.user', 'app.group', 'app.groups_media',
      'app.groups_user', 'app.option', 'app.guest', 'app.comment', 'app.my_file',
      'app.field', 'app.field_media', 'app.comment');
  /**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Media = ClassRegistry::init('Media');
		$this->Field = ClassRegistry::init('Field');
  }

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
    unset($this->Media);
    unset($this->Field);

    parent::tearDown();
	}

/**
 * test similar behavior method
 *
 * @return void
 */
	public function testRelated() {
    $flower = $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'flower')));
    $flowerId = $this->Field->getLastInsertID();
    $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'food')));
    $foodId = $this->Field->getLastInsertID();
    $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'animal')));
    $animalId = $this->Field->getLastInsertID();
    $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'color')));
    $colorId = $this->Field->getLastInsertID();

    $media1 = array('Media' => array('name' => 'IMG_1234.JPG'), 'Field' => array('Field' => array($colorId, $flowerId)));
    $this->Media->save($media1);
		$this->Media->create();
    $media2 = array('Media' => array('name' => 'IMG_2345.JPG'), 'Field' => array('Field' => array($colorId, $foodId)));
    $this->Media->save($media2);
		$this->Media->create();
    $media3 = array('Media' => array('name' => 'IMG_3456.JPG'), 'Field' => array('Field' => array($colorId, $animalId)));
    $this->Media->save($media3);

    $field = $this->Media->Field->findByData('color');
    $this->Media->Field->bindModel(array('hasAndBelongsToMany' => array('Media')));
    $this->Media->Field->Behaviors->load('Related', array('relatedHabtm' => 'Media', 'fields' => array('id', 'data')));
    $Fields = $this->Media->Field->related($field['Field']['id']);
		$names = Set::extract('/Field/data', $Fields);
		sort($names);
		$expected = array('flower', 'animal', 'food');
		sort($expected);
		$this->assertEqual($names, $expected);
  }
}
