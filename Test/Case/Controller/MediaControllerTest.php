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
App::uses('MediaController', 'Controller');

class MediaTestController extends MediaController {

  var $mockUser;
  var $zipContent = null;

  public function &getUser() {
    return $this->mockUser;
  }

  function _createZipFile($name, $files) {
    $this->zipContent = $files;
  }

}

class MediaControllerTest extends PhtagrTestCase {

	var $uses = array('Media', 'Option');
	var $components = array('FilterManager');

  var $testDir;

  var $testController = 'MediaTestController';
  var $autostartController = false;

  /**
   * setUp method
   *
   * @return void
   */
  public function setUp() {
    parent::setUp();
    $this->testDir = $this->createTestDir();
    $this->setOptionsForExternalTools();

    $admin = $this->Factory->createUser('admin', ROLE_ADMIN);
    $this->Controller->mockUser = $admin;
    $this->Controller->startupProcess();
  }

  public function testZip() {
    $this->copyResource(array('IMG_4145.JPG', 'MVI_7620.OGG', 'MVI_7620.THM'), $this->testDir);
    $this->FilterManager->readFiles($this->testDir);

    $media = $this->Media->find('all');
    $data = array('Media' => array('ids' => join(',', Set::extract('/Media/id', $media))));
    $this->Controller->request->data = $data;

    $this->Controller->zip('original');
    $result = $this->Controller->zipContent;
    $this->assertEqual(count($result), 3);
    $names = Set::extract('/name', $result);
    sort($names);
    $this->assertEqual($names, array('IMG_4145.JPG', 'MVI_7620.OGG', 'MVI_7620.THM'));

    $this->Controller->zip('preview');
    $result = $this->Controller->zipContent;
    $this->assertEqual(count($result), 2);
    $names = Set::extract('/name', $result);
    sort($names);
    $this->assertEqual($names, array('preview/IMG_4145.JPG', 'preview/MVI_7620.flv'));
  }

}
