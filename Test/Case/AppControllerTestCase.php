<?php
/**
 * PHP versions 5
 *
 * phTagr : Organize, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

App::uses('Router', 'Routing');
App::uses('CakeResponse', 'Network');
App::uses('CakeRequest', 'Network');
App::uses('Folder', 'Utility');

App::uses('Media', 'Model');
App::uses('AppTestFactory', 'Test/Case');

if (!defined('RESOURCES')) {
  define('RESOURCES', TESTS . 'Resources' . DS);
}

abstract class AppControllerTestCase extends ControllerTestCase {

  public $fixtures = array('app.file', 'app.media', 'app.user', 'app.group', 'app.groups_media',
      'app.groups_user', 'app.option', 'app.guest', 'app.comment',
      'app.fields_media', 'app.field', 'app.comment');

  var $_tmpDirs = array();

  public function setUp() {
    parent::setUp();
    Configure::write('user.home.dir', $this->createTestDir());
    $this->Factory = new AppTestFactory();

    $this->Media = ClassRegistry::init('Media');
    $this->Field = $this->Media->Field;
    $this->Group = $this->Media->Group;
    $this->User = $this->Media->User;
    $this->Option = $this->User->Option;
  }

  /**
   * Create a uniq test directory. This directory will be deleted on tearDown()
   * automatically
   *
   * @param string $prefix Prefix of testdir. Default is 'test-'
   * @param string $postfix Postfix of testdir. Default is '.tmp'
   * @return string Temporary directory
   */
  public function createTestDir($prefix = 'test-', $postfix = '.tmp') {
    $path = TMP . $prefix . rand(10000, 100000) . $postfix;
    $Folder = new Folder();
    $Folder->create($path);
    $this->_tmpDirs[] = $path;

    unset($this->Media);
    unset($this->Field);
    unset($this->Group);
    unset($this->User);
    unset($this->Option);

    return Folder::slashTerm($path);
  }

  public function tearDown() {
    $Folder = new Folder();
    foreach ($this->_tmpDirs as $path) {
      $Folder->delete($path);
    }

    parent::tearDown();
  }

}