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

App::uses('PhtagrTestCase', 'Test/Case');

/**
 * Test read meta data nativly
 */
class MediaReadGetId3TestCase extends PhtagrTestCase {

  var $components = array('FilterManager');
  var $testDir;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

    $this->testDir = $this->createTestDir();

    $admin = $this->Factory->createUser('admin', ROLE_ADMIN);
    $this->Controller->mockUser = $admin;
  }

	public function testNativeRead() {
		$filename = $this->getResource('IMG_7795.JPG');
    // Read file via GetID3 PHP library
		$result = $this->Controller->FilterManager->read($filename);
		$this->assertNotEqual($result, false);

    $media = $this->Media->find('first');
    $this->assertEqual($media['Media']['name'], 'Temple, Ayutthaya');
    // Caption is not supported by GetID3
    $this->assertTrue(!isset($media['Media']['caption']));
    $this->assertEqual($media['Media']['date'], '2009-02-14 14:36:34');
    $this->assertEqual($media['Media']['width'], 800);
    $this->assertEqual($media['Media']['height'], 600);
    $this->assertEqual($media['Media']['orientation'], 6);
    $this->assertEqual($media['Media']['model'], 'Canon PowerShot A570 IS');
    $this->assertEqual($media['Media']['iso'], '80');
    $this->assertEqual($media['Media']['duration'], -1);
    $this->assertEqual($media['Media']['aperture'], 5.65625);
    $this->assertEqual($media['Media']['shutter'], 0.0666963);
    $this->assertEqual($media['Media']['latitude'], 14.3593);
    $this->assertEqual($media['Media']['longitude'], 100.567);
    $this->assertEqual(Set::extract('/Field[name=keyword]/data', $media), array('light', 'night', 'temple'));
    $this->assertEqual(Set::extract('/Field[name=category]/data', $media), array('vacation', 'asia'));
    $this->assertEqual(Set::extract('/Field[name=sublocation]/data', $media), array('wat ratburana'));
    $this->assertEqual(Set::extract('/Field[name=city]/data', $media), array('ayutthaya'));
    $this->assertEqual(Set::extract('/Field[name=state]/data', $media), array('ayutthaya'));
    $this->assertEqual(Set::extract('/Field[name=country]/data', $media), array('thailand'));
	}

  /**
   * Test correct GPS position for negative values
   */
  public function testImageReadGps() {
    $this->copyResource('IMG_1721.JPG', $this->testDir);
    $this->Controller->FilterManager->readFiles($this->testDir);

    $media = $this->Media->find('first');
    $this->assertEqual($media['Media']['latitude'], -13.5698);
    $this->assertEqual($media['Media']['longitude'], -71.7819);
  }

  // Image has two orienations: Main file is 6, embedded thumbnail is 1
  public function testOrientationWithEmbeddedThumbnail() {
    $this->copyResource('IMG_7795.JPG', $this->testDir);

    $this->Controller->FilterManager->readFiles($this->testDir);
    $media = $this->Media->find('first');
    $this->assertEqual($media['Media']['orientation'], 6);
  }
}
