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
App::uses('User', 'Model');

class ExplorerControllerTest extends ControllerTestCase {
  /**
   * Fixtures
   *
   * @var array
   */
  public $fixtures = array('app.file', 'app.media', 'app.user', 'app.group', 'app.groups_media',
      'app.groups_user', 'app.option', 'app.guest', 'app.comment', 'app.my_file',
      'app.tag', 'app.media_tag', 'app.category', 'app.categories_media',
      'app.location', 'app.locations_media', 'app.comment');

  /**
   * setUp method
   *
   * @return void
   */
  public function setUp() {
    parent::setUp();
    $this->Media = ClassRegistry::init('Media');
    $this->User = ClassRegistry::init('User');
  }

  /**
   * tearDown method
   *
   * @return void
   */
  public function tearDown() {
    unset($this->Media);
    unset($this->User);

    parent::tearDown();
  }

  public function testPoints() {
    $this->Media->save($this->Media->create(array('oacl' => ACL_READ_HIGH, 'latitude' => 48.342, 'longitude' => -8.858)));
    $mediaId = $this->Media->getLastInsertId();
    $result = $this->testAction('/explorer/points/49/48/-9/-8', array('return' => 'contents'));
    $this->assertRegExp('/<marker id="' . $mediaId . '"/', $result);
  }
}