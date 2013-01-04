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

App::uses('Media', 'Model');
App::uses('User', 'Model');
App::uses('Option', 'Model');

class SystemControllerTest extends ControllerTestCase {
  var $Folder;

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

    $this->Media = ClassRegistry::init('Media');
    $this->User = ClassRegistry::init('User');
    $this->Option = ClassRegistry::init('Option');
  }

  /**
   * tearDown method
   *
   * @return void
   */
  public function tearDown() {
    unset($this->Media);
    unset($this->User);
    unset($this->Option);

    parent::tearDown();
  }

  public function testDeleteUnusedMetaData() {
    $admin = $this->User->save($this->User->create(array('role' => ROLE_ADMIN, 'username' => 'admin')));
    $admin = $this->User->findById($admin['User']['id']);

    $this->Media->Field->save($this->Media->Field->create(array('name' => 'keyword', 'data' => 'unused')));
    $vacation = $this->Media->Field->save($this->Media->Field->create(array('name' => 'keyword', 'data' => 'vacation')));
    $media = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $admin['User']['id'])));
    $this->Media->save(array('Media' => array('id' => $media['Media']['id']), 'Field' => array('Field' => array($vacation['Field']['id']))));

    $System = $this->generate('System', array('methods' => array('getUser')));
    $System->expects($this->any())->method('getUser')->will($this->returnValue($admin));

    $this->testAction('/system/deleteUnusedMetaData');
    $this->assertEqual($System->request->data['unusedFieldCount'], 1);
    $this->assertEqual(2, count($this->Media->Field->find('all')));

    $System = $this->generate('System', array('methods' => array('getUser')));
    $System->expects($this->any())->method('getUser')->will($this->returnValue($admin));

    $this->testAction('/system/deleteUnusedMetaData/delete');
    $this->assertEqual($System->request->data['unusedFieldCount'], 0);
    $this->assertEqual(array('vacation'), Set::extract('/Field/data', $this->Media->Field->find('all')));
  }

}