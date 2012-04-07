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

App::uses('RelatedBehavior', 'Model/Behavior');
App::uses('Media', 'Model');
App::uses('Tag', 'Model');
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
  public $fixtures = array('app.file', 'app.media', 'app.user', 'app.group',
      'app.groups_user', 'app.option', 'app.guest', 'app.comment', 'app.my_file',
      'app.tag', 'app.media_tag', 'app.category', 'app.categories_media',
      'app.location', 'app.locations_media', 'app.comment');
  /**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Media = ClassRegistry::init('Media');
		$this->Tag = ClassRegistry::init('Tag');
  }

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
    unset($this->Media);
    unset($this->Tag);
    
    parent::tearDown();
	}

/**
 * test similar behavior method
 *
 * @return void
 */
	public function testRelated() {
    $this->Tag->save($this->Tag->create(array('name' => 'flower')));
    $flowerId = $this->Tag->getLastInsertID();
    $this->Tag->save($this->Tag->create(array('name' => 'food')));
    $foodId = $this->Tag->getLastInsertID();
    $this->Tag->save($this->Tag->create(array('name' => 'animal')));
    $animalId = $this->Tag->getLastInsertID();
    $this->Tag->save($this->Tag->create(array('name' => 'color')));
    $colorId = $this->Tag->getLastInsertID();

    $media1 = array('Media' => array('name' => 'IMG_1234.JPG'), 'Tag' => array('Tag' => array($colorId, $flowerId)));
    $this->Media->save($media1);
		$this->Media->create();
    $media2 = array('Media' => array('name' => 'IMG_2345.JPG'), 'Tag' => array('Tag' => array($colorId, $foodId)));
    $this->Media->save($media2);
		$this->Media->create();
    $media3 = array('Media' => array('name' => 'IMG_3456.JPG'), 'Tag' => array('Tag' => array($colorId, $animalId)));
    $this->Media->save($media3);
   
    $tag = $this->Media->Tag->findByName('color');
    $this->Media->Tag->bindModel(array('hasAndBelongsToMany' => array('Media')));
    $this->Media->Tag->Behaviors->load('Related', array('relatedHabtm' => 'Media', 'fields' => array('id', 'name')));
    $tags = $this->Media->Tag->related($tag['Tag']['id']);
		$names = Set::extract('/Tag/name', $tags);
		sort($names);
		$expected = array('flower', 'animal', 'food');
		sort($expected);
		$this->assertEqual($names, $expected);
  }
}
