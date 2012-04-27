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

App::uses('SimilarBehavior', 'Model/Behavior');
App::uses('Tag', 'Model');
App::uses('Logger', 'Lib');
/**
 * SimilarBehavior Test Case
 *
 */
class SimilarBehaviorTestCase extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
  public $fixtures = array('app.file', 'app.media', 'app.user', 'app.group', 'app.groups_media',
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
		$this->Tag = ClassRegistry::init('Tag');
    $this->Tag->Behaviors->load('Similar');
  }

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Similar);
    unset($this->Tag);
    
    parent::tearDown();
	}

/**
 * test similar behavior method
 *
 * @return void
 */
	public function testSimilar() {
    $this->Tag->save($this->Tag->create(array('name' => 'accept')));
    $this->Tag->save($this->Tag->create(array('name' => 'access')));
    $this->Tag->save($this->Tag->create(array('name' => 'account')));
    $this->Tag->save($this->Tag->create(array('name' => 'action')));
    $this->Tag->save($this->Tag->create(array('name' => 'activate')));

    $tags = $this->Tag->similar('access');
		$names = Set::extract('/Tag/name', $tags);
		$this->assertEqual($names, array('access', 'accept', 'account'));

		$tags = $this->Tag->similar('action');
		$names = Set::extract('/Tag/name', $tags);
		$this->assertEqual($names, array('action', 'activate'));
  }
}
