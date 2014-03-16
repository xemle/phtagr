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

class MenuComponent extends Component {

	var $components = array('Session');
  var $name = 'MenuComponent';

  var $controller;

  /** Menu data */
  var $menus = array();

  var $currentMenu = 'main';

  public function initialize(Controller $controller) {
    if ($this->controller) {
      return;
    }
    $this->controller = $controller;
    $this->setBackendMenu();
    $this->setCurrentMenu('main');
  }

  public function setBackendMenu() {
    $menu = array(
        'Options' => array('title' => __('Account Settings'), 'controller' => 'options', 'priority' => 1),
        'Groups' => array('title' => __('Groups'), 'controller' => 'groups', 'priority' => 2),
        'Users' => array('title' => __('Users'), 'controller' => 'users', 'priority' => 3),
        'Guests' => array('title' => __('Guests'), 'controller' => 'guests', 'priority' => 4),
        'Browser' => array('title' => __('Media Files'), 'controller' => 'browser', 'priority' => 5),
        'System' => array('title' => __('System'), 'controller' => 'system', 'priority' => 6, 'requiredRole' => ROLE_SYSOP),
        );
    Configure::write('menu.backend', Hash::merge($menu, (array) Configure::read('menu.backend')));

    $headerMenu = array(
        'gallery' => array('title' => __("View Gallery"), 'url' => '/'),
        );
    Configure::write('menu.backend-header', Hash::merge($headerMenu, (array) Configure::read('menu.backend-header')));

    $topMenu = array(
        'login' => array('title' => __("Login"), 'controller' => 'users', 'action' => 'login', 'roles' => ROLE_NOBODY),
        'register' => array('title' => __("Sign Up"), 'controller' => 'users', 'action' => 'register', 'roles' => ROLE_NOBODY),
        'dashboard' => array('title' => __("Dashboard"), 'controller' => 'options', 'requiredRole' => ROLE_USER),
        'logout' => array('title' => __("Logout"), 'controller' => 'users', 'action' => 'logout', 'requiredRole' => ROLE_GUEST)
    );
    Configure::write('menu.top-menu', Hash::merge($topMenu, (array) Configure::read('menu.top-menu')));

    $mainMenu = array(
        'home' => array('title' => __("Home"), 'url' => '/'),
        'explorer' => array('title' => __("Explorer"), 'controller' => 'explorer'),
        'upload' => array('title' => __("Upload"), 'controller' => 'browser', 'action' => 'quickupload', 'requiredRole' => ROLE_USER, 'priority' => 15)
    );
    Configure::write('menu.main-menu', Hash::merge($mainMenu, (array) Configure::read('menu.main-menu')));
  }

  /**
   * Create a menu ID from controller name and action
   *
   * @param string $controllerName
   * @param string $action
   * @return string
   */
  public function createMenuId($controllerName, $action) {
    return $controllerName . Inflector::camelize($action);
  }

  /**
   * Create a submenu of current controller
   *
   * @param array $actionToTitle Mapping from action to menu item title
   * @param string $menu Menu name. Default is backend
   */
  public function createSubMenu($actionToTitle, $menu = 'backend') {
    $controllerName = $this->controller->name;
    $currentAction = $this->controller->action;

    $subMenuItems = array($controllerName => array('active' => true));

    $default = array('controller' => $controllerName, 'parent' => $controllerName);
    foreach ($actionToTitle as $action => $title) {
      if (is_array($title)) {
        $options = $title;
        if (!isset($options['action']) && isset($options['url'])) {
          $options['action'] = $action;
        }
      } else {
        $options = array('title' => $title, 'action' => $action);
      }
      if ($action == $currentAction) {
        $options['active'] = true;
      }
      $id = $this->createMenuId($controllerName, $action);
      $subMenuItems[$id] = am($default, $options);
    }

    Configure::write('menu.'.$menu, Hash::merge($subMenuItems, (array) Configure::read('menu.'.$menu)));
  }

  public function beforeRender(Controller $controller) {
    $this->setCurrentMenu('main');
    $menu =& $this->menus[$this->currentMenu];

    if (isset($this->controller->subMenu) && $this->controller->subMenu) {
      $name = strtolower($this->controller->name);
      $parentId = 'item-' . $name;
      foreach ($this->controller->subMenu as $action => $title) {
        $defaults = array('parent' => $parentId, 'active' => false, 'controller' => $name, 'action' => false, 'admin' => false);
        if (is_numeric($action)) {
          $item = am($defaults, $title);
        } else {
          $item = am($defaults, array('title' => $title, 'action' => $action));
        }
        if ($this->controller->action == $item['action']) {
          $item['active'] = true;
        }
        $this->insertItem($menu, $parentId, $item);
      }
    }
    $this->controller->params['menus'] = $this->menus;
    $this->controller->set('menus_for_layout', $this->menus);

    $user = $this->Session->read('user');
    $username = isset($user['User']['username']) ? $user['User']['username'] : 'John Doe';

    // Set login name
    $topMenu = array(
        'username' => array('title' => __("Howdy, %s!", $username), 'requiredRole' => ROLE_GUEST, 'priority' => 0)
    );
    Configure::write('menu.top-menu', Hash::merge($topMenu, (array) Configure::read('menu.top-menu')));

    $mainMenu = array(
        'myPhotos' => array('title' => __("My Photos"), 'url' => '/explorer/user/' . $username, 'requiredRole' => ROLE_GUEST, 'priority' => 11),
    );
    Configure::write('menu.main-menu', Hash::merge($mainMenu, (array) Configure::read('menu.main-menu')));
  }

  /**
   * Set the current menu
   *
   * @param name Menu name
   */
  public function setCurrentMenu($name) {
    $this->currentMenu = $name;
    if (!isset($this->menus[$name])) {
      $this->menus[$name] = array('options' => array());
    }
  }

  /**
   * Insert given item into menu tree below given parent Id
   *
   * @param type $tree Menu tree
   * @param type $parentId Parent id
   * @param type $item Submenu item
   * @return boolean True if item was inserted
   */
  private function insertItem(&$tree, $parentId, $item) {
    if (!is_array($tree)) {
      return false;
    }
    if (isset($tree['id']) && $tree['id'] == $parentId) {
      $tree[] = $item;
      return true;
    } else {
      foreach ($tree as &$subItem) {
        $found = $this->insertItem($subItem, $parentId, $item);
        if ($found) {
          return $found;
        }
      }
    }
    return false;
  }

  /**
   * Add menu item
   *
   * @param title Menu title
   * @param url Menu url
   * @param options e.g. html link class options
   */
  public function addItem($title, $url, $options = array()) {
    $item = am(array('title' => $title, 'url' => $url), $options);
    $menu =& $this->menus[$this->currentMenu];
    if (isset($options['parent'])) {
      $this->insertItem($menu, $options['parent'], $item);
    } else {
      $menu[] = $item;
    }
  }

  /**
   * Set menu option for current menu
   *
   * @param name Option name
   * @param value Option value
   */
  public function setOption($name, $value) {
    $this->menus[$this->currentMenu]['options'][$name] = $value;
  }
}
?>
