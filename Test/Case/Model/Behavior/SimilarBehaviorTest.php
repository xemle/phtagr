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

App::uses('SimilarBehavior', 'Model/Behavior');
App::uses('Field', 'Model');
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
      'app.field', 'app.field_media', 'app.comment');
  /**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Field = ClassRegistry::init('Field');
    $this->Field->Behaviors->load('Similar');
  }

  /**
   * tearDown method
   *
   * @return void
   */
	public function tearDown() {
		unset($this->Similar);
    unset($this->Field);

    parent::tearDown();
	}

  /**
   * test similar behavior method
   *
   * @return void
   */
	public function testSimilar() {
    $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'accept')));
    $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'access')));
    $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'account')));
    $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'action')));
    $this->Field->save($this->Field->create(array('name' => 'keyword', 'data' => 'activate')));

    $keywords = $this->Field->similar('access', 'data', 0.2);
		$names = Set::extract('/Field/data', $keywords);
		$this->assertEqual($names, array('access', 'accept', 'account'));

		$keywords = $this->Field->similar('action', 'data');
		$names = Set::extract('/Field/data', $keywords);
		$this->assertEqual($names, array('action', 'activate'));
  }
}
