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
	var $uses = array('User', 'Group', 'Media', 'Tag', 'Category');
	var $components = array('Search');

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
		
    $this->loadControllerMock();
    $this->bindModels();
    $this->bindCompontents();
		
    $this->Search->validate = array(
      'categories' => array(
        'wordRule' => array('rule' => array('custom', '/^[-]?\w+$/')),
        'minRule' => array('rule' => array('minLength', 3))
        ),
      'groups' => 'notEmpty',
      'page' => 'numeric',
      'show' => array('rule' => array('inList', array(12, 24, 64))),
      'tags' => array(
        'wordRule' => array('rule' => array('custom', '/^[-]?\w+$/')),
        'minRule' => array('rule' => array('minLength', 3))
        ),
      'tag_op' => array('rule' => array('inList', array('AND', 'OR'))),
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
      $this->{$component} =& $this->ControllerMock->{$component};
    }
  }

  /**
   * Bind controller's model to shell
   */
  function bindModels() {
    foreach($this->ControllerMock->uses as $key => $model) {
      if (!is_numeric($key)) {
        $model = $key;
      }
      if (empty($this->ControllerMock->{$model})) {
        $this->out("Could not load model $model");
        exit(1);
      }  
      $this->{$model} =& $this->ControllerMock->{$model};
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

  function testDecode() {
    $decoded = $this->Search->decode("folder:2012=2f2012-03-10");
    $this->assertEqual($decoded, "folder:2012/2012-03-10");
  }

  function testEncode() {
    $encoded = $this->Search->encode("folder:2012/2012-03-10");
    $this->assertEqual($encoded, "folder:2012=2f2012-03-10");
  }

  function testGroupSearch() {
    $this->User->save($this->User->create(array('username' => 'admin', 'role' => ROLE_ADMIN)));
    $admin = $this->User->findById($this->User->getLastInsertID());
    
    $this->Group->save($this->Group->create(array('name' => 'Group1', 'user_id' => $admin['User']['id'])));
    $group1 = $this->Group->findById($this->Group->getLastInsertID());
    $this->Group->save($this->Group->create(array('name' => 'Group2', 'user_id' => $admin['User']['id'])));
    $group2 = $this->Group->findById($this->Group->getLastInsertID());
    
    // media1 belongs to group1 and group2
    $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $admin['User']['id'], 'gacl' => 97)));
    $media1 = $this->Media->findById($this->Media->getLastInsertID());
    $this->Media->save(array('Media' => array('id' => $media1['Media']['id']), 'Group' => array('Group' => array($group1['Group']['id'], $group2['Group']['id']))));
    // media2 belongs to group1
    $this->Media->save($this->Media->create(array('name' => 'IMG_2345.JPG', 'user_id' => $admin['User']['id'])));
    $media2 = $this->Media->findById($this->Media->getLastInsertID());
    $this->Media->save(array('Media' => array('id' => $media2['Media']['id']), 'Group' => array('Group' => array($group1['Group']['id']))));
    // media2 belongs to no
    $this->Media->save($this->Media->create(array('name' => 'IMG_3456.JPG', 'user_id' => $admin['User']['id'])));
    $media2 = $this->Media->findById($this->Media->getLastInsertID());
    
    $this->mockUser($admin);
    
    // Test inclusion of 'Group2'
    $this->Search->addGroup('Group2');
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_1234.JPG'), Set::extract('/Media/name', $result));

    // Test exclusion of 'Group2'
    $this->Search->delGroup('Group2');
    $this->Search->addGroup('-Group2');
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_2345.JPG', 'IMG_3456.JPG'), Set::extract('/Media/name', $result));

    // Test exclusion of 'Group2'
    $this->Search->addGroup('-Group2');
    $this->Search->addGroup('Group1');
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_2345.JPG'), Set::extract('/Media/name', $result));

    // Test inclusion of 'Group1' and 'Group2'
    $this->Search->delGroup('-Group2');
    $this->Search->addGroup('Group2');
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_1234.JPG'), Set::extract('/Media/name', $result));
  } 
  
  function testAccessForUserRole() {
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
    
    $this->mockUser($userNone);
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_1234.JPG'), Set::extract('/Media/name', $result));

    $this->mockUser($userC);
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_1234.JPG', 'IMG_2345.JPG'), Set::extract('/Media/name', $result));

    $this->mockUser($userB);
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_1234.JPG', 'IMG_2345.JPG', 'IMG_3456.JPG'), Set::extract('/Media/name', $result));

    $this->mockUser($guestA);
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_1234.JPG', 'IMG_3456.JPG'), Set::extract('/Media/name', $result));

    $this->mockUser($userA);
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_1234.JPG', 'IMG_2345.JPG', 'IMG_3456.JPG', 'IMG_4567.JPG'), Set::extract('/Media/name', $result));
  }
  
  public function testExclusion() {
    $userA = $this->User->save($this->User->create(array('username' => 'userA', 'role' => ROLE_USER)));
    $userB = $this->User->save($this->User->create(array('username' => 'userB', 'role' => ROLE_USER)));
    
    // 'userA' has group 'aGroup'. 'userB' and 'guestA' are member of 'aGroup'
    $group = $this->Group->save($this->Group->create(array('name' => 'aGroup', 'user_id' => $userA['User']['id'])));
    $this->Group->subscribe($group, $userB['User']['id']);
    // Reload users to refresh model data of groups
    $userA = $this->User->findById($userA['User']['id']);
    $userB = $this->User->findById($userB['User']['id']);
    
    // media1 is public
    $media1 = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97, 'uacl' => 97, 'oacl' => 97)));
    // media2 is visible by users
    $this->Media->save($this->Media->create(array('name' => 'IMG_2345.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97, 'uacl' => 97)));
    $media2 = $this->Media->findById($this->Media->getLastInsertID());
    // media3 is visible by group members of 'aGroup'
    $media3 = $this->Media->save($this->Media->create(array('name' => 'IMG_3456.JPG', 'user_id' => $userA['User']['id'], 'gacl' => 97)));
    $this->Media->save(array('Media' => array('id' => $media3['Media']['id']), 'Group' => array('Group' => array($group['Group']['id']))));
    // media4 is private
    $media4 = $this->Media->save($this->Media->create(array('name' => 'IMG_4567.JPG', 'user_id' => $userA['User']['id'])));
    
    $skyTag = $this->Tag->save($this->Tag->create(array('name' => 'sky')));
    $vacationTag = $this->Tag->save($this->Tag->create(array('name' => 'vacation')));
    $natureTag = $this->Tag->save($this->Tag->create(array('name' => 'nature')));

    $familyCategory = $this->Category->save($this->Category->create(array('name' => 'family')));
    $friendsCategory = $this->Category->save($this->Category->create(array('name' => 'friends')));
    
    // media1: Tags: sky, vacation. Category: family
    $this->Media->save(array(
        'Media' => array('id' => $media1['Media']['id']), 
        'Tag' => array('Tag' => array($skyTag['Tag']['id'], $vacationTag['Tag']['id'])),
        'Category' => array('Category' => array($familyCategory['Category']['id'])),
        ));
    // media2: Tags: sky, vacation, nature. Category: family, friends
    $this->Media->save(array(
        'Media' => array('id' => $media2['Media']['id']), 
        'Tag' => array('Tag' => array($skyTag['Tag']['id'], $vacationTag['Tag']['id'], $natureTag['Tag']['id'])),
        'Category' => array('Category' => array($familyCategory['Category']['id'], $friendsCategory['Category']['id'])),
        ));
    // media3: Tags: vacation, nature. Category: 
    $this->Media->save(array(
        'Media' => array('id' => $media3['Media']['id']), 
        'Tag' => array('Tag' => array($vacationTag['Tag']['id'], $natureTag['Tag']['id']))
        ));
    // media4: Tags: vacation. Category: friends
    $this->Media->save(array(
        'Media' => array('id' => $media4['Media']['id']), 
        'Tag' => array('Tag' => array($vacationTag['Tag']['id'])),
        'Category' => array('Category' => array($friendsCategory['Category']['id'])),
        ));
    
    $this->mockUser($userB);
    $this->Search->addTag('-nature');
    $this->Search->addCategory('family');
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_1234.JPG'), Set::extract('/Media/name', $result));
    $this->mockUser($userA);
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_1234.JPG'), Set::extract('/Media/name', $result));

    // test with tag OR Operand
    $this->mockUser($userB);
    $this->Search->clear();
    $this->Search->addTag('sky');
    $this->Search->addTag('nature');
    $this->Search->setTagOp('OR');
    $this->Search->addCategory('-friends');
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_1234.JPG', 'IMG_3456.JPG'), Set::extract('/Media/name', $result));
    $this->mockUser($userA);
    $result = $this->Search->paginate();
    $this->assertEqual(array('IMG_1234.JPG', 'IMG_3456.JPG'), Set::extract('/Media/name', $result));
  }
}
