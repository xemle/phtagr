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
      'app.tag', 'app.media_tag', 'app.category', 'app.categories_media', 'app.fields_media', 'app.field',
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
    $Explorer = $this->generate('Explorer');
    $result = $this->testAction('/explorer/points/49/48/-9/-8', array('return' => 'contents'));
    $this->assertEqual($Explorer->response->type(), 'application/xml');
    $this->assertRegExp('/<marker id="' . $mediaId . '"/', $result);
  }

  public function testRss() {
    $user = $this->User->save($this->User->create(array('username' => 'user', 'role' => ROLE_USER)));
    $user = $this->User->findById($user['User']['id']);
    $media = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $user['User']['id'], 'gacl' => ACL_READ_PREVIEW, 'uacl' => ACL_READ_PREVIEW, 'oacl' => ACL_READ_PREVIEW)));

    $Explorer = $this->generate('Explorer', array('methods' => array('getUser')));
    $Explorer->expects($this->any())->method('getUser')->will($this->returnValue($user));

    $contents = $this->testAction('/explorer/rss', array('return' => 'contents'));
    $this->assertEqual($Explorer->response->type(), 'application/rss+xml');
    $arrayContent = Xml::toArray(Xml::build($contents));
    $titles = Set::extract('/rss/channel/item/title', $arrayContent);
    $this->assertEqual($titles, array('IMG_1234.JPG'));
  }

  public function testMediaRss() {
    $user = $this->User->save($this->User->create(array('username' => 'user', 'role' => ROLE_USER)));
    $user = $this->User->findById($user['User']['id']);
    $media = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $user['User']['id'], 'gacl' => ACL_READ_PREVIEW, 'uacl' => ACL_READ_PREVIEW, 'oacl' => ACL_READ_PREVIEW)));

    $Explorer = $this->generate('Explorer', array('methods' => array('getUser')));
    $Explorer->expects($this->any())->method('getUser')->will($this->returnValue($user));

    $contents = $this->testAction('/explorer/media', array('return' => 'contents'));
    $this->assertEqual($Explorer->response->type(), 'application/rss+xml');
    $arrayContent = Xml::toArray(Xml::build($contents));
    $titles = Set::extract('/rss/channel/item/title', $arrayContent);
    $this->assertEqual($titles, array($media['Media']['name'] . ' by ' . $user['User']['username']));
    // Test media content
    $contentUrl = Set::extract('/rss/channel/item/media:content/@url', $arrayContent);
    $this->assertEqual(count($contentUrl), 1);
    $expected = '/media/preview/' . $media['Media']['id'] . '/' . $media['Media']['name'];
    $this->assertEqual(strpos($contentUrl[0], $expected) > 0, true);
    // Test media thumbnail
    $contentUrl = Set::extract('/rss/channel/item/media:thumbnail/@url', $arrayContent);
    $this->assertEqual(count($contentUrl), 1);
    $expected = '/media/thumb/' . $media['Media']['id'] . '/' . $media['Media']['name'];
    $this->assertEqual(strpos($contentUrl[0], $expected) > 0, true);
  }

  /**
   * Test that all new locations will be created
   */
  public function testEditMultiWithNewLocations() {
    $user = $this->User->save($this->User->create(array('username' => 'user', 'role' => ROLE_USER)));
    $user = $this->User->findById($user['User']['id']);
    $media = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $user['User']['id'])));

    $Explorer = $this->generate('Explorer', array('methods' => array('getUser')));
    $Explorer->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $data = array('Media' => array('ids' => $media['Media']['id']), 'Location' => array('sublocation' => 'castle', 'city' => 'karlsruhe', 'state' => 'bw', 'country' => 'germany'));
    $contents = $this->testAction('/explorer/edit', array('data' => $data, 'return' => 'vars'));

    $media = $this->Media->findById($media['Media']['id']);
    $locationNames = Set::extract('/Location/name', $media);
    sort($locationNames);
    $this->assertEqual($locationNames, array('bw', 'castle', 'germany', 'karlsruhe'));
  }

  /**
   * Test that all new locations will be created for single media
   */
  public function testEditSingleWithNewLocations() {
    $user = $this->User->save($this->User->create(array('username' => 'user', 'role' => ROLE_USER)));
    $user = $this->User->findById($user['User']['id']);
    $media = $this->Media->save($this->Media->create(array('name' => 'IMG_2345.JPG', 'user_id' => $user['User']['id'])));

    $Explorer = $this->generate('Explorer', array('methods' => array('getUser'), 'components' => array('RequestHandler' => array('isAjax', 'isMobile'))));
    $Explorer->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $Explorer->RequestHandler->expects($this->once())->method('isAjax')->will($this->returnValue(true));
    $Explorer->RequestHandler->expects($this->once())->method('isMobile')->will($this->returnValue(false));
    $data = array('Location' => array('sublocation' => 'prater', 'city' => 'vienna', 'country' => 'austria'));
    $contents = $this->testAction('/explorer/savemeta/' . $media['Media']['id'], array('data' => $data, 'return' => 'result'));

    $media = $this->Media->findById($media['Media']['id']);
    $locationNames = Set::extract('/Location/name', $media);
    sort($locationNames);
    $this->assertEqual($locationNames, array('austria', 'prater', 'vienna'));
  }

  /**
   * Test explorer/{sublocation, city, state, country}/name URLs
   */
  public function testLocations() {
    $user = $this->User->save($this->User->create(array('username' => 'user', 'role' => ROLE_USER)));

    $media1 = $this->Media->save($this->Media->create(array('name' => 'IMG_1231.JPG', 'user_id' => $user['User']['id'])));
    $media2 = $this->Media->save($this->Media->create(array('name' => 'IMG_1232.JPG', 'user_id' => $user['User']['id'])));
    $media3 = $this->Media->save($this->Media->create(array('name' => 'IMG_1233.JPG', 'user_id' => $user['User']['id'])));
    $media4 = $this->Media->save($this->Media->create(array('name' => 'IMG_1234.JPG', 'user_id' => $user['User']['id'])));

    $sublocation = $this->Media->Location->save($this->Media->Location->create(array('type' => LOCATION_SUBLOCATION, 'name' => 'downtown')));
    $city = $this->Media->Location->save($this->Media->Location->create(array('type' => LOCATION_CITY, 'name' => 'quebec')));
    $state = $this->Media->Location->save($this->Media->Location->create(array('type' => LOCATION_STATE, 'name' => 'quebec')));
    $country = $this->Media->Location->save($this->Media->Location->create(array('type' => LOCATION_COUNTRY, 'name' => 'canada')));

    $this->Media->save(array('Media' => array('id' => $media1['Media']['id']), 'Location' => array('Location' => array($sublocation['Location']['id'], $city['Location']['id'], $state['Location']['id'], $country['Location']['id']))));
    $this->Media->save(array('Media' => array('id' => $media2['Media']['id']), 'Location' => array('Location' => array($city['Location']['id'], $state['Location']['id'], $country['Location']['id']))));
    $this->Media->save(array('Media' => array('id' => $media3['Media']['id']), 'Location' => array('Location' => array($state['Location']['id'], $country['Location']['id']))));
    $this->Media->save(array('Media' => array('id' => $media4['Media']['id']), 'Location' => array('Location' => array($country['Location']['id']))));

    $user = $this->User->findById($user['User']['id']);
    $Explorer = $this->generate('Explorer', array('methods' => array('getUser')));
    $Explorer->expects($this->any())->method('getUser')->will($this->returnValue($user));

    $this->testAction('/explorer/sublocation/downtown', array('return' => 'vars'));
    $this->assertEqual(Set::extract('/Media/id', $Explorer->request->data), array($media1['Media']['id']));

    $Explorer = $this->generate('Explorer', array('methods' => array('getUser')));
    $Explorer->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $this->testAction('/explorer/city/quebec', array('return' => 'vars'));
    $this->assertEqual(Set::extract('/Media/id', $Explorer->request->data), array($media1['Media']['id'], $media2['Media']['id']));

    $Explorer = $this->generate('Explorer', array('methods' => array('getUser')));
    $Explorer->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $this->testAction('/explorer/state/quebec', array('return' => 'vars'));
    $this->assertEqual(Set::extract('/Media/id', $Explorer->request->data), array($media1['Media']['id'], $media2['Media']['id'], $media3['Media']['id']));

    $Explorer = $this->generate('Explorer', array('methods' => array('getUser')));
    $Explorer->expects($this->any())->method('getUser')->will($this->returnValue($user));
    $this->testAction('/explorer/country/canada', array('return' => 'vars'));
    $this->assertEqual(Set::extract('/Media/id', $Explorer->request->data), array($media1['Media']['id'], $media2['Media']['id'], $media3['Media']['id'], $media4['Media']['id']));
  }
}