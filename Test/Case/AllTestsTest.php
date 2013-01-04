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
class AllTests extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All phTagr Tests');

		$path = dirname(__FILE__);
    $suite->addTestDirectory($path);
    $suite->addTestDirectory($path . DS . 'Controller');
    $suite->addTestDirectory($path . DS . 'Controller' . DS . 'Component');
    $suite->addTestDirectory($path . DS . 'Model');
    $suite->addTestDirectory($path . DS . 'Model' . DS . 'Behavior');
    $suite->addTestDirectory($path . DS . 'View');
    $suite->addTestDirectory($path . DS . 'View' . DS . 'Helper');
		//$suite->addTestFile($path . 'SingleTest.php');
		return $suite;
	}
}
