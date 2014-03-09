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
class MenuHelper extends AppHelper {

  var $helpers = array('Html', 'Session');

  /**
   * create item defaults
   *
   * @param array $items
   * @return array of items with default values
   */
  function createItemDefaults(&$items) {
    $result = array();
    foreach ($items as $name => $item) {
      if (!is_array($item)) {
        $item = array('url' => $item);
      }
      $item = am(array(
          'id' => $name,
          'parent' => false,
          'title' => $name,
          'url' => false,
          'plugin' => false,
          'controller' => false,
          'action' => false,
          'admin' => false,
          'class' => array(),
          'active' => false,
          'disabled' => false,
          'deactivated' => false,
          'priority' => 10,
          'requiredRole' => false,
          'roles' => false,
          'children' => array(),
          )
          , $item);
      $result[] = $item;
    }
    return $result;
  }

  /**
   * Create tree items
   *
   * @param array $items
   * @return array menu items in tree structure
   */
  public function createTreeMenu($items) {
    $parentList = array();
    foreach ($items as $item){
        $parentList[$item['parent']][] = $item;
    }
    return $this->buildTree($parentList, $parentList[0]);
  }

  private function buildTree(&$list, $parents){
    $tree = array();
    foreach ($parents as $parent){
      if(isset($list[$parent['id']])){
        $parent['children'] = $this->buildTree($list, $list[$parent['id']]);
      }
      $tree[] = $parent;
    }
    return $tree;
  }

  private function isValidMenuItem(&$item) {
    if ($item['deactivated']) {
      return false;
    }
    if ($item['roles'] !== false) {
      $roles = (array) $item['roles'];
      $user = $this->Session->read('user');
      if (!$user || !isset($user['User']['role']) || !in_array($user['User']['role'], $roles)) {
        return false;
      }
    }
    if ($item['requiredRole'] !== false) {
      $requiredRole = (int) $item['requiredRole'];
      $user = $this->Session->read('user');
      if (!$user || !isset($user['User']['role']) || $user['User']['role'] < $requiredRole) {
        return false;
      }
    }
    return true;
  }

  /**
   * Create url from item
   *
   * @param array $item Menu item
   * @return mixed
   */
  private function createUrl(&$item) {
    $url = false;
    if ($item['url']) {
      $url = $item['url'];
    } elseif ($item['controller']) {
      $url = array('plugin' => $item['plugin'], 'controller' => $item['controller']);
      if ($item['action']) {
        $url['action'] = $item['action'];
      } else {
        $url['action'] = 'index';
      }
      if ($item['admin']) {
        $url['admin'] = $item['admin'];
      }
    }
    return $url;
  }

  /**
   * Get html tag options for the link
   *
   * @param array $item Menu item
   * @return array
   */
  private function getLinkOptions(&$item) {
    $class = $item['class'];
    if ($item['active']) {
      $class[] = 'active';
    } else if ($item['disabled']) {
      $class[] = 'disabled';
    }

    $linkOptions = array();
    if (count($class)) {
      $linkOptions['class'] = join(' ', $class);
    }
    return $linkOptions;
  }

  /**
   * Render tree items
   *
   * @param array $treeItems
   * @return string
   */
  function renderTree(&$treeItems) {
    $output = '';
    foreach ($treeItems as $item) {
      if (!$this->isValidMenuItem($item)) {
        continue;
      }

      $url = $this->createUrl($item);
      $linkOptions = $this->getLinkOptions($item);

      $link = array();
      if ($url) {
        $link[] = $this->Html->link($item['title'], $url, $linkOptions);
      } else {
        $link[] = $item['title'];
      }
      if (count($item['children'])) {
        $link[] = '<ul>' . $this->renderTree($item['children']) . '</ul>';
      }
      $output .= $this->Html->tag('li', join('', $link));
    }
    return $output;
  }

  /**
   * Render menu
   *
   * @param String $name Menu name
   * @return string output string
   */
  public function renderMenu($name) {
    $items = Configure::read("menu.$name");
    if (!count($items)) {
      return '';
    }
    $list = $this->createItemDefaults($items);
    $list = Hash::sort($list, '{n}.priority', 'asc');
    $tree = $this->createTreeMenu($list);
    return $this->renderTree($tree);
  }
}

