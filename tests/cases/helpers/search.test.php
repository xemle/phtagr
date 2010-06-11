<?php

App::import('Core', array('Helper', 'AppHelper', 'Controller', 'View'));
App::import('Helper', array('Search'));
App::import('File', 'Logger', array('file' => APP.'logger.php'));
 
class SearchHelperTest extends CakeTestCase {

  function setUp() {
    $this->Search = new SearchHelper();
    $this->Search->params['search']['data'] = array();
    $this->Search->params['search']['baseUri'] = '/explorer/query';
    $this->Search->params['search']['afterUri'] = false;
    $this->Search->params['search']['defaults'] = array(
      'show' => 12,
      'page' => 1,
      'pos' => false,
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
      'baseUri' => '/explorer/query',
      'afterUri' => false,
      'defaults' => array(
        'show' => 12,
        'page' => 1,
        'pos' => false,
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
    $result = $this->Search->serialize(false, array('pos' => 2));
    $this->assertEqual($result, 'tags:tag1,tag2');
 
    $result = $this->Search->serialize(array('pos' => 2));
    $this->assertEqual($result, '');
    
    // enable disable default value
    $result = $this->Search->serialize(false, array('pos' => 2), false, array('defaults' => array('pos' => true)));
    $this->assertEqual($result, 'pos:2/tags:tag1,tag2');
 
    $result = $this->Search->serialize(false, array('show' => 12), false, array('defaults' => array('show' => true)));
    $this->assertEqual($result, 'show:12/tags:tag1,tag2');
 
    // array default
    $result = $this->Search->serialize(false, false, false, array('defaults' => array('tags' => 'tag1')));
    $this->assertEqual($result, 'tags:tag2');

    $data = array('tags' => array('tag2'), 'page' => 12, 'pos' => 1);
    $result = $this->Search->serialize($data, false, false, array('defaults' => array('page' => '12', 'pos' => 1)));
    $this->assertEqual($result, 'tags:tag2');
    
    $data = array('tagOp' => 'AND', 'tags' => array('tag1', 'tag2'));
    $result = $this->Search->serialize($data, false, false, array('defaults' => array('tagOp' => 'OR')));
    $this->assertEqual($result, 'tagOp:AND/tags:tag1,tag2');
    
    // disabled array default
    $data = array('tags' => array('tag1', 'tag2'));
    $result = $this->Search->serialize($data, false, false, array('defaults' => array('tags' => false)));
    $this->assertEqual($result, '');

    $result = $this->Search->serialize(false, false, false, array('defaults' => array('tags' => false)));
    $this->assertEqual($result, '');
  }

  function testGetUri() {
    $result = $this->Search->getUri();
    $this->assertEqual($result, '/explorer/query');

    $result = $this->Search->getUri(false, false, false, array('baseUri' => '/image/view/1'));
    $this->assertEqual($result, '/image/view/1');
  
    $result = $this->Search->getUri(false, array('pos' => 2), false, array('baseUri' => '/image/view/1', 'defaults' => array('pos' => true)));
    $this->assertEqual($result, '/image/view/1/pos:2');
  
    $result = $this->Search->getUri(array('tags' => array('tag1', 'tag2')));
    $this->assertEqual($result, '/explorer/query/tags:tag1,tag2');    
  
    $result = $this->Search->getUri(array('tags' => array('tag1', 'tag2')), false, false, array('baseUri' => '/image/view/1'));
    $this->assertEqual($result, '/image/view/1/tags:tag1,tag2');

    $this->Search->setPos(13);
    $this->Search->setPage(2);
    $this->Search->config['defaults']['pos'] = true;
    // pos as singular test, overwrite
    $result = $this->Search->getUri(false, array('pos' => 14));
    $this->assertEqual($result, '/explorer/query/page:2/pos:14');

    // delete
    $result = $this->Search->getUri(false, false, 'pos');
    $this->assertEqual($result, '/explorer/query/page:2');

    // delete has higher priority as add
    $result = $this->Search->getUri(false, array('show' => 24), array('show'));
    $this->assertEqual($result, '/explorer/query/page:2/pos:13');

    // reset search
    $this->Search->delPos();
    $this->Search->delPage();
    $this->Search->config['defaults']['pos'] = false;
   
    // afterUri tests
    $result = $this->Search->getUri(false, false, false, array('afterUri' => '/media.rss'));
    $this->assertEqual($result, '/explorer/query/media.rss');

    // default afterUri
    $this->Search->config['afterUri'] = '/media.rss';
    $result = $this->Search->getUri();
    $this->assertEqual($result, '/explorer/query/media.rss');

    // overwrite default afterUri
    $result = $this->Search->getUri(false, false, false, array('afterUri' => false));
    $this->assertEqual($result, '/explorer/query');

    // overwrite default afterUri
    $result = $this->Search->getUri(false, false, false, array('afterUri' => '/rss.rss'));
    $this->assertEqual($result, '/explorer/query/rss.rss');

    // reset default afterUri
    $this->Search->config['afterUri'] = false;

  }
}
?>
