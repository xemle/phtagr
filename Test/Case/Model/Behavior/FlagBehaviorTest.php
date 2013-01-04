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

App::uses('FlagBehavior', 'Model/Behavior');
App::uses('MyFile', 'Model');
App::uses('Media', 'Model');

/**
 * FlagBehavior Test Case
 *
 */
class FlagBehaviorTestCase extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('app.user', 'app.group', 'app.groups_media', 'app.groups_user', 'app.option', 'app.guest', 'app.media', 'app.file');

  /**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Flag = new FlagBehavior();
		$this->File = ClassRegistry::init('MyFile');
  }

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Flag);
    unset($this->File);

    parent::tearDown();
	}

/**
 * testSetFlag method
 *
 * @return void
 */
	public function testSetFlag() {
    $this->File->save($this->File->createFromFile(__FILE__, 1));
    $fileId = $this->File->getLastInsertID();
    $file = $this->File->findById($fileId);
    $this->assertEqual(0, $file['File']['flag']);

    $this->File->setFlag($file, 1);
    $this->assertEqual(isset($file['File']), true);
    $file = $this->File->findById($fileId);
    $this->assertEqual(1, $file['File']['flag']);

    $this->File->setFlag($file, 4);
    $this->assertEqual(isset($file['File']), true);
    $file = $this->File->findById($fileId);
    $this->assertEqual(5, $file['File']['flag']);
  }
/**
 * testDeleteFlag method
 *
 * @return void
 */
	public function testDeleteFlag() {
    $this->File->save($this->File->createFromFile(__FILE__, 1));
    $fileId = $this->File->getLastInsertID();
    $file = $this->File->findById($fileId);
    $this->assertEqual(0, $file['File']['flag']);

    $this->File->setFlag($file, 2);
    $file = $this->File->findById($fileId);
    $this->File->setFlag($file, 4);

    $file = $this->File->findById($fileId);
    $this->File->deleteFlag($file, 2);
    $this->assertEqual(isset($file['File']), true);
    $file = $this->File->findById($fileId);
    $this->assertEqual(4, $file['File']['flag']);

    $this->File->deleteFlag($file, 4);
    $this->assertEqual(isset($file['File']), true);
    $file = $this->File->findById($fileId);
    $this->assertEqual(0, $file['File']['flag']);
  }
/**
 * testHasFlag method
 *
 * @return void
 */
	public function testHasFlag() {
    $this->File->save($this->File->createFromFile(__FILE__, 1));
    $fileId = $this->File->getLastInsertID();
    $file = $this->File->findById($fileId);
    $this->assertEqual(0, $file['File']['flag']);

    $this->File->setFlag($file, 2);
    $file = $this->File->findById($fileId);
    $this->assertEqual($this->File->hasFlag($file, 2), true);
    $this->assertEqual(isset($file['File']), true);
    $this->assertEqual($this->File->hasFlag($file, 4), false);
    $this->assertEqual(isset($file['File']), true);
  }
}
