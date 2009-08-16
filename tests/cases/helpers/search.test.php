<?php

App::import('Core', array('Helper', 'AppHelper', 'Controller', 'View'));
App::import('Helper', array('Search'));
App::import('File', 'Logger', array('file' => APP.'logger.php'));
 
class SearchHelperTest extends CakeTestCase {

  function setUp() {
    $this->Search = new SearchHelper();
    $this->Search->params['search']['data'] = array();
    $this->Search->params['search']['uriBase'] = '/explorer/query';
    $this->Search->params['search']['defaults'] = array(
      'show' => 12,
      'page' => 1,
      'tagOp' => 'AND'
      );
    $this->Search->initialize();
  }

  function tearDown() {
    unset($this->Search);
  }

  function testConfig() {
    $result = $this->Search->config;
    $this->assertEqual($result, array(
      'uriBase' => '/explorer/query',
      'defaults' => array(
        'show' => 12,
        'page' => 1,
        'tagOp' => 'AND'
        )
      ));
  }

  function testSerialize() {
    // set intial parameter
    $this->Search->_data = array(
      'tags' => array('tag1', 'tag2'),
      'tagOp' => 'AND'
      );

    // default
    $result = $this->Search->serialize();
    $this->assertEqual($result, 'tags:tag1,tag2');

    // add parameter
    $result = $this->Search->serialize(false, array('tags' => 'tag3'));
    $this->assertEqual($result, 'tags:tag1,tag2,tag3');

    $result = $this->Search->serialize(false, array('tags' => 'tag0'));
    $this->assertEqual($result, 'tags:tag0,tag1,tag2');

    $result = $this->Search->serialize(false, array('tag' => array('tag3', 'tag0')));
    $this->assertEqual($result, 'tags:tag0,tag1,tag2,tag3');

    $result = $this->Search->serialize(false, array('tag' => array('tag3', 'tag0', 'tags' => 'tag4')));
    $this->assertEqual($result, 'tags:tag0,tag1,tag2,tag3,tag4');

    // del parameter
    $result = $this->Search->serialize(false, false, array('tags' => 'tag2'));
    $this->assertEqual($result, 'tags:tag1');

    $result = $this->Search->serialize(false, false, array('tag' => array('tag2', 'tag1')));
    $this->assertEqual($result, '');

    // del after add test
    $result = $this->Search->serialize(false, array('tags' => 'tag3'), array('tags' => 'tag3'));
    $this->assertEqual($result, 'tags:tag1,tag2');

    // set own search
    $result = $this->Search->serialize(array('user' => 'admin'));
    $this->assertEqual($result, 'user:admin');

    // change search
    $params = $this->Search->getParams();
    $this->Search->setUser('admin');
    $result = $this->Search->serialize();
    $this->assertEqual($result, 'tags:tag1,tag2/user:admin');
    $this->Search->setParams($params);

    $result = $this->Search->serialize(array('tags' => 'tag1'), array('tags' => 'tag1'));
    $this->assertEqual($result, 'tags:tag1');

    $result = $this->Search->serialize(array('tags' => array('tag1')), false, array('tags' => 'tag1'));
    $this->assertEqual($result, '');

    $this->Search->delTags();
    $this->Search->addTag('tag1');
    $result = $this->Search->serialize(false, array('tags' => 'tag1'));
    $this->assertEqual($result, 'tags:tag1');
  }

  function testSerializeDefaults() {
    // set intial parameter
    $this->Search->_data = array(
      'tags' => array('tag1', 'tag2'),
      'tagOp' => 'AND'
      );

    // disabled default
    $result = $this->Search->serialize(false, array('pos' => 2), false, array('defaults' => array('pos' => false)));
    $this->assertEqual($result, 'tags:tag1,tag2');
 
    $result = $this->Search->serialize(array('pos' => 2), false, false, array('defaults' => array('pos' => false)));
    $this->assertEqual($result, '');
    
    // array default
    $result = $this->Search->serialize(false, false, false, array('defaults' => array('tags' => 'tag1')));
    $this->assertEqual($result, 'tags:tag2');
    
    $result = $this->Search->serialize(array('tagOp' => 'AND', 'tags' => array('tag1', 'tag2')), false, false, array('defaults' => array('tagOp' => 'OR')));
    $this->assertEqual($result, 'tagOp:AND/tags:tag1,tag2');
    
    // disabled array default
    $result = $this->Search->serialize(false, false, false, array('defaults' => array('tags' => false)));
    $this->assertEqual($result, '');
  }

  function testGetUri() {
    $result = $this->Search->getUri();
    $this->assertEqual($result, '/explorer/query');

    $result = $this->Search->getUri(false, false, false, array('uriBase' => '/image/view/1'));
    $this->assertEqual($result, '/image/view/1');
  
    $result = $this->Search->getUri(array('tags' => array('tag1', 'tag2')));
    $this->assertEqual($result, '/explorer/query/tags:tag1,tag2');    
  
    $result = $this->Search->getUri(array('tags' => array('tag1', 'tag2')), false, false, array('uriBase' => '/image/view/1'));
    $this->assertEqual($result, '/image/view/1/tags:tag1,tag2');
  }
}
?>
