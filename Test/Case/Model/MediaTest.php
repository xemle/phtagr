<?php
/* Media Test cases generated on: 2012-02-17 23:31:37 : 1329517897*/
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
