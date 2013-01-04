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

App::uses('Comment', 'Model');
App::uses('Media', 'Model');
App::uses('User', 'Model');
App::uses('Group', 'Model');

class CommentControllerTest extends ControllerTestCase {
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
    $this->Comment = ClassRegistry::init('Comment');
    $this->Media = ClassRegistry::init('Media');
    $this->User = ClassRegistry::init('User');
    $this->Group = ClassRegistry::init('Group');
  }

  /**
   * tearDown method
   *
   * @return void
   */
  public function tearDown() {
    unset($this->Comment);
    unset($this->Media);
    unset($this->User);
    unset($this->Group);

    parent::tearDown();
  }

  public function testCommentAccess() {
    $this->User->save($this->User->create(array('username' => 'userA', 'role' => ROLE_USER)));
    $userA = $this->User->findById($this->User->getLastInsertID());
    $this->User->save($this->User->create(array('username' => 'userB', 'role' => ROLE_USER)));
    $userB = $this->User->findById($this->User->getLastInsertID());
    $this->User->save($this->User->create(array('username' => 'userC', 'role' => ROLE_USER)));
    $userC = $this->User->findById($this->User->getLastInsertID());
    // user 'userB' has guest 'guestA'
    $this->User->save($this->User->create(array('username' => 'guestA', 'role' => ROLE_GUEST, 'creator_id' => $userB['User']['id'])));
    $guestA = $this->User->findById($this->User->getLastInsertID());
    $this->User->save($this->User->create(array('username' => 'nobody', 'role' => ROLE_NOBODY)));
    $userNone = $this->User->findById($this->User->getLastInsertID());

    // 'userA' has group 'aGroup'. 'userB' and 'guestA' are member of 'aGroup'
    $this->Group->save($this->Group->create(array('name' => 'aGroup', 'user_id' => $userA['User']['id'])));
    $group = $this->Group->findById($this->Group->getLastInsertID());
    $this->Group->subscribe($group, $userB['User']['id']);
    $group = $this->Group->findById($group['Group']['id']);
    $this->Group->subscribe($group, $guestA['User']['id']);
    // Reload users to refresh model data of groups
    $userA = $this->User->findById($userA['User']['id']);
    $userB = $this->User->findById($userB['User']['id']);
    $guestA = $this->User->findById($guestA['User']['id']);

    // media1 is public
    $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97, 'uacl' => 97, 'oacl' => 97)));
    $media1 = $this->Media->findById($this->Media->getLastInsertID());
    // media2 is visible by users
    $this->Media->save($this->Media->create(array('name' => 'IMG_2345.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97, 'uacl' => 97)));
    $media2 = $this->Media->findById($this->Media->getLastInsertID());
    // media3 is visible by group members of 'aGroup'
    $this->Media->save($this->Media->create(array('name' => 'IMG_3456.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97)));
    $media3 = $this->Media->findById($this->Media->getLastInsertID());
    $this->Media->save(array('Media' => array('id' => $media3['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));
    // media4 is private
    $this->Media->save($this->Media->create(array('name' => 'IMG_4567.JPG', 'user_id' => $userA['User']['id'])));
    $media4 = $this->Media->findById($this->Media->getLastInsertID());

    $this->Comment->save($this->Comment->create(array('text' => 'Ipsum Lorem', 'media_id' => $media1['Media']['id'])));
    $this->Comment->save($this->Comment->create(array('text' => 'Ipsum Lorem', 'media_id' => $media2['Media']['id'])));
    $this->Comment->save($this->Comment->create(array('text' => 'Ipsum Lorem', 'media_id' => $media3['Media']['id'])));
    $this->Comment->save($this->Comment->create(array('text' => 'Ipsum Lorem', 'media_id' => $media4['Media']['id'])));

    // 'userNone' can see only comments of public media
    $Comments = $this->generate('Comments', array('methods' => array('getUser')));
    $Comments->expects($this->any())->method('getUser')->will($this->returnValue($userNone));
    $vars = $this->testAction('/comments/index', array('return' => 'vars'));
    $this->assertEqual(array('IMG_1234.JPG'), Set::extract('/Media/name', $vars['comments']));

    // 'userC' can see only comments of public media and visible by users
    $Comments = $this->generate('Comments', array('methods' => array('getUser')));
    $Comments->expects($this->any())->method('getUser')->will($this->returnValue($userC));
    $vars = $this->testAction('/comments/index', array('return' => 'vars'));
    $this->assertEqual(array('IMG_1234.JPG', 'IMG_2345.JPG'), Set::extract('/Media/name', $vars['comments']));

    // 'userB' can see only comments of public media, visible by users, and of 'aGroup'
    $Comments = $this->generate('Comments', array('methods' => array('getUser')));
    $Comments->expects($this->any())->method('getUser')->will($this->returnValue($userB));
    $vars = $this->testAction('/comments/index', array('return' => 'vars'));
    $this->assertEqual(array('IMG_1234.JPG', 'IMG_2345.JPG', 'IMG_3456.JPG'), Set::extract('/Media/name', $vars['comments']));

    // 'guestA' can see only comments of public media and of 'aGroup'
    $Comments = $this->generate('Comments', array('methods' => array('getUser')));
    $Comments->expects($this->any())->method('getUser')->will($this->returnValue($guestA));
    $vars = $this->testAction('/comments/index', array('return' => 'vars'));
    $this->assertEqual(array('IMG_1234.JPG', 'IMG_3456.JPG'), Set::extract('/Media/name', $vars['comments']));

    // 'userA' can see all its comments
    $Comments = $this->generate('Comments', array('methods' => array('getUser')));
    $Comments->expects($this->any())->method('getUser')->will($this->returnValue($userA));
    $vars = $this->testAction('/comments/index', array('return' => 'vars'));
    $this->assertEqual(array('IMG_1234.JPG', 'IMG_2345.JPG', 'IMG_3456.JPG', 'IMG_4567.JPG'), Set::extract('/Media/name', $vars['comments']));
  }

  public function testRss() {
    $user = $this->User->save($this->User->create(array('username' => 'user', 'role' => ROLE_USER)));
    $user = $this->User->findById($user['User']['id']);
    $media = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $user['User']['id'], 'gacl' => ACL_READ_PREVIEW, 'uacl' => ACL_READ_PREVIEW, 'oacl' => ACL_READ_PREVIEW)));
    $this->Comment->save($this->Comment->create(array('text' => 'Ipsum Lorem', 'media_id' => $media['Media']['id'])));

    $Comments = $this->generate('Comments', array('methods' => array('getUser')));
    $Comments->expects($this->any())->method('getUser')->will($this->returnValue($user));

    $contents = $this->testAction('/comments/rss', array('return' => 'contents'));
    $this->assertEqual($Comments->response->type(), 'application/rss+xml');
    $arrayContent = Xml::toArray(Xml::build($contents));
    $descriptions = Set::extract('/rss/channel/item/description', $arrayContent);
    $this->assertEqual($descriptions, array('Ipsum Lorem'));
  }
}