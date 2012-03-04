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

App::uses('Media', 'Model');

/**
 * Media Test Case
 *
 */
class MediaTestCase extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array();

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->Media = ClassRegistry::init('Media');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Media);

		parent::tearDown();
	}

  public function testRotate() {
    $data = array();
    $this->Media->rotate(&$data, 1, 90);
    $this->assertSame(6, $data['Media']['orientation']);
    $this->Media->rotate(&$data, 1, 180);
    $this->assertSame(3, $data['Media']['orientation']);
    $this->Media->rotate(&$data, 1, 270);

    $this->Media->rotate(&$data, 6, 90);
    $this->assertSame(3, $data['Media']['orientation']);
    $this->Media->rotate(&$data, 3, 90);
    $this->assertSame(8, $data['Media']['orientation']);
    $this->Media->rotate(&$data, 8, 90);
    $this->assertSame(1, $data['Media']['orientation']);
  }
}
