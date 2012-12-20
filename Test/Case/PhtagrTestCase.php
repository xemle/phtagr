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
 * @since         phTagr 2.3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

App::uses('Router', 'Routing');
App::uses('CakeResponse', 'Network');
App::uses('CakeRequest', 'Network');
App::uses('Folder', 'Utility');

App::uses('Logger', 'Lib');

App::uses('PhtagrTestFactory', 'Test/Case');
App::uses('PhtagrTestController', 'Test/Case');

/**
 * Base test case for phtagr
 *
 * The Base test case provides common functions to test cases.
 *
 * It loads a Controller with given models and components which are available
 * through the test.
 */
class PhtagrTestCase extends CakeTestCase {

  var $Factory;
  var $Controller;

  var $uses = array();
  var $components = array();

  public $fixtures = array('app.file', 'app.media', 'app.user', 'app.group', 'app.groups_media',
      'app.groups_user', 'app.option', 'app.guest', 'app.comment', 'app.my_file',
      'app.fields_media', 'app.field', 'app.comment');

  public function setUp() {
    parent::setUp();
    $this->Factory = new PhtagrTestFactory();

    $uses = array_unique(am(array('User', 'Media', 'MyFile'), $this->uses));

    // Load test controller
    $CakeRequest = new CakeRequest();
    $CakeResponse = new CakeResponse();
    $this->Controller = new PhtagrTestController($CakeRequest, $CakeResponse);
    $this->Controller->uses = $uses;
    $this->Controller->components = $this->components;
    $this->Controller->constructClasses();
    $this->Controller->startupProcess();

    // Copy models and compoments from controller to test case for direct access
    foreach ($uses as $modelName) {
      $this->{$modelName} = $this->Controller->{$modelName};
    }
    foreach ($this->components as $componentName) {
      $this->{$componentName} = $this->Controller->{$componentName};
    }
  }

  public function tearDown() {
    $this->Controller->shutdownProcess();
    parent::tearDown();
  }

  /**
   * Finds the absolute filename for a given command
   *
   * @param string $command
   * @return string filename to the executable
   */
  public function findExecutable($command) {
    if (DS != '/') {
      throw new Exception("Non Unix OS are not supported yet");
    }
    $paths = array('/usr/local/bin/', '/usr/bin/');
    foreach ($paths as $path) {
      if (file_exists($path . $command)) {
        return $path . $command;
      }
    }
    $result = array();
    exec('which ' . $command, $result);
    if ($result) {
      return $result[0];
    } else {
      return false;
    }
  }

  /**
   * Returns the filename of a resource file
   *
   * @param string $filename
   * @return string
   */
  public function getResource($filename) {
    $Folder = new Folder(TESTS . 'Resources' . DS);
    $result = $Folder->findRecursive($filename);
    if ($result) {
      return $result[0];
    } else {
      return false;
    }
  }
}
