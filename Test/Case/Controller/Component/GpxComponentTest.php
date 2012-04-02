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

App::uses('ComponentCollection', 'Controller');
App::uses('GpxComponent', 'Controller/Component');
App::uses('Logger', 'Lib');

/**
 * GpxComponent Test Case
 *
 */
class GpxComponentTestCase extends CakeTestCase {
/**
 * setUp method
 *
 * @return void
 */
  public function setUp() {
    parent::setUp();
    $Collection = new ComponentCollection();
    $this->Gpx = new GpxComponent($Collection);
  }

/**
 * tearDown method
 *
 * @return void
 */
  public function tearDown() {
    unset($this->Gpx);

    parent::tearDown();
  }

/**
 * testReadFile method
 *
 * @return void
 */
  public function testReadFile() {
    $filename = dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'Resources' . DS . 'example.gpx';
    $points = $this->Gpx->readFile($filename);
    $this->assertEqual(count($points), 7);
  }
}
