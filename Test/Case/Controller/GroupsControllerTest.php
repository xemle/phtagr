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
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

App::uses('AppControllerTestCase', 'Test/Case');
App::uses('AppController', 'Controller');

class GroupsControllerTest extends AppControllerTestCase {

  public function testCreateGroup() {
    $user = $this->User->save($this->User->create(array('role' => ROLE_USER, 'username' => 'user')));
    $user = $this->User->findById($user['User']['id']);

    $Groups = $this->generate('Groups', array('methods' => array('getUser')));
    $Groups->expects($this->any())->method('getUser')->will($this->returnValue($user));

    $this->assertEqual(0, count($this->Media->Group->find('all')));
    $data = array('Group' => array('name' => 'Group1'));
    $this->testAction('/groups/create', array('data' => $data));
    $this->assertEqual(1, count($this->Media->Group->find('all')));
  }

  public function testNewGroupShouldNotHaveMedia() {
    $user = $this->User->save($this->User->create(array('role' => ROLE_USER, 'username' => 'user')));
    $user = $this->User->findById($user['User']['id']);
    $group = $this->Media->Group->save($this->Media->Group->create(array('name' => 'Group1')));

    $media = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $user['User']['id'])));

    $Groups = $this->generate('Groups', array('methods' => array('getUser')));
    $Groups->expects($this->any())->method('getUser')->will($this->returnValue($user));

    $vars = $this->testAction('/groups/view/Group1', array('return' => 'vars'));
    $this->assertEqual(array(), $vars['media']);
    $this->assertEqual(0, $vars['mediaCount']);
  }

  public function testGroupShouldShowItsMedia() {
    $user = $this->User->save($this->User->create(array('role' => ROLE_USER, 'username' => 'user')));
    $user = $this->User->findById($user['User']['id']);
    $group = $this->Media->Group->save($this->Media->Group->create(array('name' => 'Group1')));

    $media1 = $this->Media->save($this->Media->create(array('name' => 'IMG_1233.JPG', 'user_id' => $user['User']['id'])));
    $media2 = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $user['User']['id'])));
    $this->Media->save(array('Media' => array('id' => $media2['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));

    $Groups = $this->generate('Groups', array('methods' => array('getUser')));
    $Groups->expects($this->any())->method('getUser')->will($this->returnValue($user));

    $vars = $this->testAction('/groups/view/Group1', array('return' => 'vars'));
    $this->assertEqual(1, $vars['mediaCount']);
    $this->assertEqual(array($media2['Media']['id']), Set::extract('/Media/id', $vars['media']));
  }
}