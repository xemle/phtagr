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

App::uses('Component', 'Controller');

class ComponentAComponent extends Component {
  var $controller = null;
  var $components = array();

  public function initialize(Controller $controller) {
    parent::initialize($controller);
    $this->controller = $controller;
    $this->controller->loadComponent('ComponentB', $this);
  }
}

class ComponentBComponent extends Component {

  var $controller = null;
  var $components = array('ComponentC');

  public function initialize(Controller $controller) {
    parent::initialize($controller);
    $this->controller = $controller;
  }

}

class ComponentCComponent extends Component {

  var $controller = null;
  var $components = array('ComponentB');

  public function initialize(Controller $controller) {
    parent::initialize($controller);
    $this->controller = $controller;
  }
}

class LoadComponentController extends AppController {

  var $components = array('ComponentA');

  public function __construct($request = null, $response = null) {
    parent::__construct($request, $response);
  }
}

class AppControllerTest extends ControllerTestCase {

  /**
   * Fixtures
   *
   * @var array
   */
  public $fixtures = array('app.file', 'app.media', 'app.user', 'app.group', 'app.groups_media',
      'app.groups_user', 'app.option', 'app.guest', 'app.comment', 'app.my_file',
      'app.fields_media', 'app.field', 'app.comment');

  /**
   * setUp method
   *
   * @return void
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * tearDown method
   *
   * @return void
   */
  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Test recursive load and initialization of components
   */
  public function testLoadComponent() {
    $controller = new LoadComponentController(new CakeRequest(), new CakeResponse());
		$controller->constructClasses();
    $controller->startupProcess();

    $this->assertNotEqual($controller->ComponentA, null);
    $this->assertNotEqual($controller->ComponentA->ComponentB, null);
    $this->assertNotEqual($controller->ComponentA->ComponentB->ComponentC, null);
    $this->assertNotEqual($controller->ComponentA->ComponentB->ComponentC->ComponentB, null);
    $this->assertEqual($controller->ComponentA->ComponentB->ComponentC->controller, $controller);
  }

}