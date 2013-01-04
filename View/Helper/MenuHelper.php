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
class MenuHelper extends AppHelper
{
  var $helpers = array('Html');

  var $_url = false;

/**
  items
    text: Link text
    link: Link
    type: menu types: current, active, text, multi, or false
    onmouseover, onmouseout: add event
    submenu: subitem, array of items

  Example
  array(
    'id' => 'list id',
    'title' => 'Title text',
    'items' => array(
      [0] => array(
        'text' => 'TEXT'
        'type' => 'active'
        ),
      [1] => array(
        'text' => 'Submenu Text',
        'type' => 'text',
        'submenu' => array(
          'items' => array(
            [0] => array(
              'text' => 'Submenu Text',
              'link' => '#submenulink'
              )
            )
          )
        ),
      [2] => array(
        'text' => 'Menu Text'
        'link' => '#link'
        ),
      [3] => array(
        'text' => '#link1 #link2'
        'type' => 'multi'
        'onmouseover' => 'toggleItem('item-3');'
        )
      )
    )
*/
  function _getMenu($data) {
    $data = am(array('id' => false, 'title' => false, 'items' => null, 'toggleid' => false), $data);
    if ($data['id'])
      $out = "<ul id=\"{$data['id']}\">\n";
    else
      $out = "<ul>\n";

    if ($data['title'])
      $out .= "<li id=\"title\">{$data['title']}</li>\n";

    if (empty($data['items']))
      return $out."</ul>\n";

    foreach ($data['items'] as $item) {
      $item = am(array('type' => false, 'text' => false, 'link' => false), $item);
      if (!$item['text']) {
        // Trigger error
        continue;
      }

      if ($item['link'] == 'false' && $item['type'] == false) {
        $item['type'] == 'text';
      }

      $out .= "<li";
      $attrs = array();
      if ($item['type'] == 'current') {
        $attrs['id'] = 'current';
        //$item['link'] = false;
      } elseif ($item['type'] == 'active' ||
        ($item['link'] && Router::url($item['link']) == $this->_url)) {
        $attrs['id'] = 'active';
        $item['link'] = false;
      } elseif ($item['type'] == 'multi') {
        $attrs['class'] = 'multi';
        $item['link'] = false;
      }
      foreach (array('onmouseover', 'onmouseout') as $event) {
        if (isset($item[$event]))
          $attrs[$event] = $item[$event];
      }
      foreach ($attrs as $name => $value) {
        $out .= " $name=\"".htmlspecialchars($value)."\"";
      }
      $out .= ">";

      if ($item['link']) {
        $out .= $this->Html->link($item['text'], $item['link']);
      } elseif ($item['type'] == 'text') {
        $out .= "<span>".$item['text']."</span>";
      } else {
        $out .= $item['text'];
      }

      if(isset($item['submenu']))
        $out .= $this->_getMenu($item['submenu']);

      $out .= "</li>\n";
    }
    $out .= "</ul>\n";
    return $out;
  }

  function getMainMenu($data) {
    $data = am(array('id' => 'submenu', 'title' => __('Main Menu'), 'active' => false), $data);

    // Get current url with action. If the default action 'index' is missing,
    // its adds it to the url
    $this->_url = $this->here;
    if (strpos($this->_url, 'index') === false &&
      strpos($this->action, 'index') !== false) {
      if ($this->_url[strlen($this->_url)-1] != '/')
        $this->_url .= '/';
      $this->_url .= 'index';
    }

    return $this->output($this->_getMenu($data));
  }

  function _excludeKeys($data, $excluds = array('title', 'url', 'parent')) {
    $filtered = array();
    foreach ($data as $key => $value) {
      if (is_numeric($key) || in_array($key, $excluds)) {
        continue;
      }
      $filtered[$key] = $value;
    }
    return $filtered;
  }

  function _getSubMenu($menu, $options) {
    if (!is_array($menu)) {
      return false;
    }
    $items = array();
    foreach ($menu as $key => $item) {
      if (!is_numeric($key)) {
        continue;
      }
      $attrs = $this->_excludeKeys($item);
      $submenu = $this->_getSubMenu($item, $options);
      $linkOptions = array();
      if (isset($attrs['active']) && $attrs['active']) {
        $linkOptions['class'] = 'active';
      }
      if (isset($item['url']) && $item['url'] === false) {
        $item = $item['title'];
      } else {
        if (!isset($item['url'])) {
          $item['url'] = array('controller' => $item['controller'], 'action' => $item['action'], 'admin' => $item['admin']);
        }
        $item = $this->Html->link($item['title'], $item['url'], $linkOptions);
      }
      $items[] = $this->Html->tag('li', $item . $submenu, $attrs);
    }
    if (count($items)) {
      return $this->Html->tag('ul', implode("\n", $items), $options);
    }
    return false;
  }

  function menu($name, $options = array()) {
    if (!isset($this->params['menus'][$name])) {
      return false;
    }
    $menu = $this->params['menus'][$name];
    return $this->_getSubMenu($menu, am($options, array('id' => $name), $menu['options']));
  }
}
?>