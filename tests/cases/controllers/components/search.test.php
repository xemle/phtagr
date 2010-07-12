<?php
App::import('Core', array('Controller'));
App::import('Component', array('Search'));
App::import('File', 'Logger', array('file' => APP.'logger.php'));

Mock::generatePartial('SearchComponent', 'NoStopSearch', array('_stop'));

class SearchTestController extends Controller {
  var $uses = null;

  function __construct($params = array()) {
    foreach ($params as $key => $val) {
      $this->{$key} = $val;
    }
    parent::__construct();
  }

  function destination() {
    $this->viewPath = 'posts';
    $this->render('index');
  }
}

class SearchComponentTest extends CakeTestCase {
  var $Controller;
  var $Search;

  function setUp() {
    $this->_init();
  }

  function tearDown() {
    unset($this->Search);
    unset($this->Controller);
    if (!headers_sent()) {
      header('Content-type: text/html'); //reset content type.
    }
  }

  function _init() {
    $this->Controller = new SearchTestController(array('components' => array('Search')));
    $this->Controller->constructClasses();
    $this->Search =& $this->Controller->Search;
    $this->Search->initialize($this->Controller);

    $this->Search->validate = array(
      'page' => 'numeric',
      'show' => array('rule' => array('inList', array(12, 24, 64))),
      'tags' => array(
        'wordRule' => array('rule' => array('custom', '/^[-]?\w+$/')),
        'minRule' => array('rule' => array('minLength', 3))
        ),
      'user' => 'alphaNumeric', // disabled 
      'visibility', // no validation
      'world' // no validation but disabled
      );
    $this->Search->disabled = array('user', 'world');
    $this->Search->defaults = array();
    $this->Search->clear();
  }

  function testValidation() {
    $this->_init();

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

}
?>
