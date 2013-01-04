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

App::import('File', 'Search', array('file' => APP.'search.php'));

class SearchTest extends CakeTestCase {
  var $Search;

  public function setUp() {
    parent::setUp();
    $this->Search = new Search();

    $this->Search->clear();
  }

  public function tearDown() {
    unset($this->Search);

    parent::tearDown();
  }

  function testParam() {

    // get not existing values
    $result = $this->Search->getParam('notExists');
    $this->assertEqual($result, null);

    // get default value of not existing value
    $result = $this->Search->getParam('notExists', 'default');
    $this->assertEqual($result, 'default');

    // set and get
    $this->Search->setParam('page', 2);
    $result = $this->Search->getParam('page');
    $this->assertEqual($result, 2);

    // delete
    $this->Search->delParam('page');
    $result = $this->Search->getParam('page');
    $this->assertEqual($result, null);

    // add single value
    $this->Search->addParam('tag', 'tag1');
    $result = $this->Search->getParam('tag');
    $this->assertEqual($result, null);
    $result = $this->Search->getParam('tags');
    $this->assertEqual($result, array('tag1'));

    // add array
    $this->Search->addParam('tag', array('tag2', 'tag3'));
    $result = $this->Search->getParam('tags');
    $this->assertEqual($result, array('tag1', 'tag2', 'tag3'));

    // delete singel value from array
    $this->Search->delParam('tags', 'tag2');
    $result = $this->Search->getParam('tags');
    $this->assertEqual($result, array('tag1', 2 => 'tag3'));

    // delete array
    $this->Search->delParam('tags');
    $result = $this->Search->getParam('tags');
    $this->assertEqual($result, null);
  }

  function testSingle() {
    // set and delete
    $this->Search->setPage(1);
    $result = $this->Search->getPage();
    $this->assertEqual($result, 1);

    $this->Search->delPage();
    $result = $this->Search->getPage();
    $this->assertEqual($result, null);

    // get default value
    $result = $this->Search->getPage(2);
    $this->assertEqual($result, 2);
  }

  function testMultiple() {
    // add multiple tags

    $this->Search->addTag('tag1');
    $result = $this->Search->getTags();
    $this->assertEqual($result, array('tag1'));

    $this->Search->addTag('tag2');
    $result = $this->Search->getTags();
    $this->assertEqual($result, array('tag1', 'tag2'));

    $this->Search->delTag('tag1');
    $result = $this->Search->getTags();
    $this->assertEqual($result, array(1 => 'tag2'));

    // delete non existsing value
    $this->Search->delTag('tag3');
    $result = $this->Search->getTags();
    $this->assertEqual($result, array(1 => 'tag2'));

    $this->Search->delTag('tag2');
    $result = $this->Search->getTags();
    $this->assertEqual($result, null);

    // add and delete multiple
    $this->Search->addTag(array('-tag1', 'tag2', 'tag3'));
    $result = $this->Search->getTags();
    $this->assertEqual($result, array('-tag1', 'tag2', 'tag3'));

    $this->Search->delTag(array('-tag1', 'tag2'));
    $result = $this->Search->getTags();
    $this->assertEqual($result, array(2 => 'tag3'));
  }

  function testEncode() {
    $result = $this->Search->encode('=,/');
    $this->assertEqual('=3d=2c=2f', $result);
    $result = $this->Search->decode('=3d=2c=2f');
    $this->assertEqual('=,/', $result);
    // invalid hex
    $result = $this->Search->decode('=/:=@F=`g=2');
    $this->assertEqual('', $result);

    $result = $this->Search->encode('2010/2010-08-13/photo.jpg');
    $this->assertEqual('2010=2f2010-08-13=2fphoto.jpg', $result);
    $result = $this->Search->decode('2010=2F2010-08-13=2Fphoto.jpg');
    $this->assertEqual('2010/2010-08-13/photo.jpg', $result);
  }
}
?>
