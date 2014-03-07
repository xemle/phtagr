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
 * @since         phTagr 2.6
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

App::uses('View', 'View');
App::uses('MenuHelper', 'View/Helper');

class MenuHelperTest extends CakeTestCase {
  public function setUp() {
    parent::setUp();

    $this->View = new View(null);
    $this->Menu = new MenuHelper($this->View);
  }

  function testConfigureWrite() {
    // Test write array value than scalar value
    Configure::write('menu.test', array('options' => array('url' => '/options', 'title' => 'My Options')));
    Configure::write('menu.test.options.enabled', true);
    
    $result = Configure::read('menu.test');
    $expected = array('options' => array('url' => '/options', 'title' => 'My Options', 'enabled' => true));
    $this->assertEqual($result, $expected);

    // Test write scalar value than array value => Scalar will be overwritten
    Configure::write('menu.test.options.enabled', true);
    Configure::write('menu.test', array('options' => array('url' => '/options', 'title' => 'My Options')));
    
    $result = Configure::read('menu.test');
    $expected = array('options' => array('url' => '/options', 'title' => 'My Options'));
    $this->assertEqual($result, $expected);

    // Test merge
    Configure::write('menu.test.options.enabled', true);
    Configure::write('menu.test', Hash::merge(array('options' => array('url' => '/options', 'title' => 'My Options')), (array) Configure::read('menu.test')));
    
    $result = Configure::read('menu.test');
    $expected = array('options' => array('url' => '/options', 'title' => 'My Options', 'enabled' => true));
    $this->assertEqual($result, $expected);
  }
  
  function testMinimal() {
    Configure::write('menu.test.myTitle', '/test');
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/test">myTitle</a></li>';
    $this->assertEqual($output, $expected);
  }
  
  function testTitleName() {
    Configure::write('menu.test.myTitle.url', '/test');
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/test">myTitle</a></li>';
    $this->assertEqual($output, $expected);
  }

  function testTitleParam() {
    Configure::write('menu.test.myTitle', array('url' => '/test', 'title' => 'Another Title'));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/test">Another Title</a></li>';
    $this->assertEqual($output, $expected);
  }

  function testControllerParam() {
    Configure::write('menu.test.myTitle', array('controller' => 'test'));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/test">myTitle</a></li>';
    $this->assertEqual($output, $expected);
  }

  function testControllerActionParam() {
    Configure::write('menu.test.myTitle', array('controller' => 'tests', 'action' => 'edit'));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/tests/edit">myTitle</a></li>';
    $this->assertEqual($output, $expected);
  }

  function testActiveParam() {
    Configure::write('menu.test.myTitle', array('url' => '/test', 'active' => true));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/test" class="active">myTitle</a></li>';
    $this->assertEqual($output, $expected);
  }

  function testAdminParam() {
    Configure::write('menu.test.myTitle', array('controller' => 'tests', 'admin' => true));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/admin/tests">myTitle</a></li>';
    $this->assertEqual($output, $expected);
  }

  function testPluginParam() {
    Configure::write('menu.test.myTitle', array('controller' => 'tests', 'plugin' => 'plugin'));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/plugin/tests">myTitle</a></li>';
    $this->assertEqual($output, $expected);
  }

  function testUrlParam() {
    Configure::write('menu.test.myTitle', array('url' => array('action' => 'tests', 5)));

    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/tests/5">myTitle</a></li>';
    $this->assertEqual($output, $expected);
  }

  function testUrlParamBeforeControllerUrl() {
    Configure::write('menu.test.myTitle', array('url' => array('action' => 'tests'), 'controller' => 'another'));

    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/tests">myTitle</a></li>';
    $this->assertEqual($output, $expected);
  }

  function testDisabledParam() {
    Configure::write('menu.test.myTitle', array('url' => '/test', 'disabled' => true));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/test" class="disabled">myTitle</a></li>';
    $this->assertEqual($output, $expected);
  }

  function testDeactivatedParam() {
    Configure::write('menu.test.myTitle', array('url' => '/test', 'deactivated' => true));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '';
    $this->assertEqual($output, $expected);
  }

  function testNoLink() {
    Configure::write('menu.test.title1', array());
    Configure::write('menu.test.title2', array('title' => 'Another Title'));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li>title1</li><li>Another Title</li>';
    $this->assertEqual($output, $expected);
  }

  function testPriorityParam() {
    Configure::write('menu.test.Title1', array('url' => '/test1', 'priority' => 2));
    Configure::write('menu.test.Title2', array('url' => '/test2', 'priority' => 1));
    Configure::write('menu.test.Title3', array('url' => '/test3', 'priority' => 3));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/test2">Title2</a></li>';
    $expected .= '<li><a href="/test1">Title1</a></li>';
    $expected .= '<li><a href="/test3">Title3</a></li>';
    $this->assertEqual($output, $expected);
  }
 
  function testSubMenu() {
    Configure::write('menu.test.Title', array('url' => '/url'));
    Configure::write('menu.test.SubTitle', array('url' => '/sub', 'parent' => 'Title'));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/url">Title</a>';
    $expected .= '<ul><li><a href="/sub">SubTitle</a></li></ul>';
    $expected .= '</li>';
    $this->assertEqual($output, $expected);
  }
  
  function testSubSubMenu() {
    Configure::write('menu.test.Title', array('url' => '/url'));
    Configure::write('menu.test.SubTitle', array('url' => '/sub', 'parent' => 'Title'));
    Configure::write('menu.test.SubSubTitle', array('url' => '/sub/sub', 'parent' => 'SubTitle'));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/url">Title</a>';
    $expected .= '<ul><li><a href="/sub">SubTitle</a>';
    $expected .=   '<ul><li><a href="/sub/sub">SubSubTitle</a></li></ul>';
    $expected .= '</li></ul>';
    $expected .= '</li>';
    $this->assertEqual($output, $expected);
  }
  
  function testSubMenuDeactivated() {
    Configure::write('menu.test.Title', array('url' => '/url', 'deactivated' => true));
    Configure::write('menu.test.SubTitle', array('url' => '/sub', 'parent' => 'Title'));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '';
    $this->assertEqual($output, $expected);
  }
  
  function testSubMenuWithIdParam() {
    Configure::write('menu.test.Title', array('url' => '/url', 'id' => 'myId'));
    Configure::write('menu.test.SubTitle', array('url' => '/sub', 'parent' => 'myId'));
    
    $output = $this->Menu->renderMenu('test');
    $expected = '<li><a href="/url">Title</a>';
    $expected .= '<ul><li><a href="/sub">SubTitle</a></li></ul>';
    $expected .= '</li>';
    $this->assertEqual($output, $expected);
  }
  
}
