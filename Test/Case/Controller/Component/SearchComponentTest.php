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

App::uses('SearchComponent', 'Controller/Component');
if (!class_exists('TestControllerMock')) {
  App::import('File', 'TestControllerMock', array('file' => dirname(dirname(__FILE__)) . DS . 'TestControllerMock.php'));
}
/**
 * SearchComponent Test Case
 *
 */
class SearchComponentTestCase extends CakeTestCase {
	var $controllerMock;
	var $uses = array();
	var $components = array('Search');
	
/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		
    $this->loadControllerMock();
    $this->bindCompontents();
		
    $this->Search->validate = array(
      'page' => 'numeric',
      'show' => array('rule' => array('inList', array(12, 24, 64))),
      'tags' => array(
        'wordRule' => array('rule' => array('custom', '/^[-]?\w+$/')),
        'minRule' => array('rule' => array('minLength', 3))
        ),
      'user' => 'alphaNumeric', // disabled 
      'visibility', // no validation
      'world' // no validation but disabled
      );
    $this->Search->disabled = array('user', 'world');
    $this->Search->defaults = array();
    $this->Search->clear();
	}

	/**
   * Load ShellControllerMock with models and components
   */
  function loadControllerMock() {
    $this->ControllerMock =& new TestControllerMock();
    $this->ControllerMock->setRequest(new CakeRequest());
    $this->ControllerMock->response = new CakeResponse();
    $this->ControllerMock->uses = $this->uses;
    $this->ControllerMock->components = $this->components;
    $this->ControllerMock->constructClasses();
    $this->ControllerMock->startupProcess();
  }
  
  /**
   * Bind controller's components to shell
   */
  function bindCompontents() {
    foreach($this->ControllerMock->components as $key => $component) {
      if (!is_numeric($key)) {
        $component = $key;
      }
      if (empty($this->ControllerMock->{$component})) {
        $this->out("Could not load component $component");
        exit(1);
      }  
      $this->{$component} = $this->ControllerMock->{$component};
    }
  }
  
  function mockUser($user) {
    $this->ControllerMock->mockUser($user);
  }

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Search);

		parent::tearDown();
	}

  function testValidation() {

    // simple rule, false test
    $this->Search->setPage('two');
    $result = $this->Search->getPage(1);
    $this->assertEqual($result, 1);

    // simple rule, true test
    $this->Search->setPage('2');
    $result = $this->Search->getPage();
    $this->assertEqual($result, 2);

    // one rule, false test
    $this->Search->setShow(13);
    $result = $this->Search->getShow();
    $this->assertEqual($result, null);

    // one rule, true test
    $this->Search->setShow(12);
    $result = $this->Search->getShow();
    $this->assertEqual($result, 12);

    // one rule, disabled validation
    $this->Search->setShow('no validation', false);
    $result = $this->Search->getShow();
    $this->assertEqual($result, 'no validation');

    // multple rules
    $this->Search->addTag(array('he', 'the'));
    $result = $this->Search->getTags();
    $this->assertEqual($result, array('the'));

    // multple rules, disabled validation
    $result = $this->Search->delTags();
    $this->Search->addTag(array('he', '+_?'), false);
    $result = $this->Search->getTags();
    $this->assertEqual($result, array('he', '+_?'));

    // disabled parameter
    $this->Search->setUser('joe');
    $result = $this->Search->getUser();
    $this->assertEqual($result, null);

    // parameter without validation
    $this->Search->setVisibility("no validation");
    $result = $this->Search->getVisibility();
    $this->assertEqual($result, "no validation");
    
    // disabled parameter without validation
    $this->Search->setWorld("rule it");
    $result = $this->Search->getWorld();
    $this->assertEqual($result, null);
    
    // disabled parameter with disabled validation
    $this->Search->setWorld("rule it", false);
    $result = $this->Search->getWorld();
    $this->assertEqual($result, "rule it");
  }

}
