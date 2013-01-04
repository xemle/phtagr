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
App::uses('Tag', 'Model');
App::uses('Group', 'Model');
App::uses('User', 'Model');

/**
 * Media Test Case
 *
 */
class CommentTestCase extends CakeTestCase {
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
		$this->Comment = ClassRegistry::init('Comment');
		$this->Group = ClassRegistry::init('Group');
		$this->User = ClassRegistry::init('User');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Media);
		unset($this->Comment);
		unset($this->Group);
		unset($this->User);

		parent::tearDown();
	}

  /**
   * Test comment pagination if a user has access via multiple groups
   * to a media. A comment should be counted only once.
   */
  public function testPaginateWithMultipleGroups() {
    $userA = $this->User->save($this->User->create(array('username' => 'userA', 'role' => ROLE_USER)));
    $userB = $this->User->save($this->User->create(array('username' => 'userB', 'role' => ROLE_USER)));
    
    $groupA = $this->Group->save($this->Group->create(array('name' => 'GroupA', 'user_id' => $userA['User']['id']))); 
    $groupB = $this->Group->save($this->Group->create(array('name' => 'GroupB', 'user_id' => $userA['User']['id']))); 
    $this->Group->subscribe($groupA, $userB['User']['id']);
    $this->Group->subscribe($groupB, $userB['User']['id']);
    
    $media = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $userA['User']['id'], 'gacl' => ACL_READ_PREVIEW)));
    $this->Media->save(array('Media' => array('id' => $media['Media']['id']), 'Group' => array('Group' => array($groupA['Group']['id'], $groupB['Group']['id']))));
    
    $comment = $this->Comment->save($this->Comment->create(array('name' => 'John Dow', 'media_id' => $media['Media']['id'], 'text' => 'Cool Picture')));

    $userB = $this->User->findById($userB['User']['id']);
    $this->Comment->currentUser = $userB;
    $count = $this->Comment->paginateCount();
    $this->assertEqual($count, 1);
    $comments = $this->Comment->paginate(array(), array('Comment.id', 'Comment.name', 'Media.id'), 'Comment.created', 10);
    $this->assertEqual(count($comments), 1);
  }

}
