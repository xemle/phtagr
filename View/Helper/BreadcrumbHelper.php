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

class BreadcrumbHelper extends AppHelper
{
  var $helpers = array('Html', 'Form', 'Autocomplete', 'Search');

  /**
   * Return breadcrumb params for building urls
   *
   * @param crumbs Current breadcrumb stack
   * @param crumb Optional additional crumb
   * @param filter Optional filter crumb. Single type or array of types to filter
   */
  function params($crumbs, $crumb = false, $filter = array()) {
    if ($crumb) {
      $crumbs[] = $crumb;
    }
    $filter = (array)$filter;
    if (count($filter)) {
      $crumbs = $this->filterCrumbs($crumbs, $filter);
    }
    $escaped = array();
    foreach ($crumbs as $crumb) {
      if (!preg_match('/^(\w+):(.*)$/', $crumb, $matches)) {
        continue;
      }
      $escaped[] = $matches[1] . ":" . $this->Search->encode($matches[2]);
    }
    return implode('/', $escaped);
  }

  /**
   * Evaluates if crumbs contains a crumb with the given name
   *
   * @param crumbs Array of crumbs
   * @param name Crumb name to search
   * @return True if the crumb is found
   */
  function hasCrumb($crumbs, $name, $value = false, $exactMatch = false) {
    foreach ($crumbs as $crumb) {
      if (!preg_match('/^(\w+):((-).*)$/', $crumb, $matches)) {
        continue;
      }
      if ($matches[1] != $name) {
        continue;
      }
      Logger::debug($matches);
      if ($value === false ||
        $exactMatch && $value == $matches[3] ||
        !$exactMatch && $value == $matches[2]) {
        return true;
      }
    }
    return false;
  }

  /**
   * Return breadcrumb url
   *
   * @param crumbs Current breadcrumb stack
   * @param crumb Optional additional crumb
   * @param filter Optional filter crumb. Single type or array of types to
   * filter
   */
  function crumbUrl($crumbs, $crumb = false, $filter = array()) {
    return '/explorer/view/' . $this->params($crumbs, $crumb, $filter);
  }

  /**
   * Replace a breadcrumb value
   *
   * @param crumbs Current breadcrumb stack
   * @param needle Crumb to find. String of crumb type or array of crumb type
   * and crumb value. E.g. 'location' or array('tag', 'flower')
   * @param value to replace
   * @param Optional addEmpty. Adds the crumb anyway if this is set to true and
   * the stack does not have such breadcumb type. Default is true.
   * @return Replaced breadcrumb stack
   */
  function replace($crumbs, $needle, $value, $addEmpty = true) {
    $replace = array();
    $isReplaced = false;
    $isFound = false;
    $needleValue = false;
    if (is_array($needle)) {
      $needleValue = $needle[1];
      $needle = $needle[0];
    }
    foreach ($crumbs as $crumb) {
      $crumb = trim($crumb);
      if (!$crumb) {
        continue;
      }
      if (!preg_match('/^(\w+):(.+$)/', $crumb, $match)) {
        Logger::warn("Invalid crumb: $crumb");
        continue;
      }
      if ($match[1] == $needle && (!$needleValue || $needleValue == $match[2])) {
        $replace[] = "$needle:$value";
        $isReplaced = true;
      } elseif ($match[1] == $needle && $value == $match[2]) {
        // target crumb already exists
      } else {
        $replace[] = $crumb;
      }
    }
    if (!$isReplaced && $addEmpty) {
      $replace[] = "$needle:$value";
    }
    return $replace;
  }

  /**
   * Filter crumbs by excluded types
   *
   * @param crumbs Current breadcrumb stack
   * @param exclude Array of excluded types. Default it excludes type of show, pos, and page
   * @return Filtered crumbs
   */
  function filterCrumbs($crumbs, $exclude = array('key', 'page', 'pos', 'show')) {
    $filter = array();
    foreach ($crumbs as $crumb) {
      if (!preg_match('/^(\w+):.+/', $crumb, $match)) {
        Logger::warn("Invalid crumb: $crumb");
        continue;
      }
      if (in_array($match[1], $exclude)) {
        continue;
      }
      $filter[] = $crumb;
    }
    return $filter;
  }

  /**
   * Create breadcrumb html list
   *
   * @param crumbs Current breadcrumb stack
   * @param exclude Array of excluded crumbs
   */
  function breadcrumb($crumbs, $exclude = array('key', 'page', 'pos')) {
    $links = array();
    $crumbs = $this->filterCrumbs($crumbs, $exclude);
    foreach ($crumbs as $key => $crumb) {
      if (!preg_match('/^(\w+):(.*)$/', $crumb, $match)) {
        Logger::warn("Invalid crumb: $crumb");
        continue;
      }
      $name = $match[1];
      $value = $match[2];
      if (in_array($name, array('to', 'from'))) {
        list($value) = split(' ', $value);
      }
      $remove = $crumbs;
      unset($remove[$key]);
      $options = array();
      if (substr($value, 0, 1) == '-' && $value != '-date') {
        $options['class'] = 'p-breadcrumb-exclude';
      }
      $removeOptions = array('class' => 'p-breadcrumb-remove', 'escape' => false);
      $links[] = $this->Html->tag('li',
        $this->Html->tag('span', "$name:", array('class' => 'p-breadcrumb-type'))
        .$this->Html->link($value, $this->crumbUrl(array_slice($crumbs, 0, $key + 1)), $options)
        .$this->Html->link($this->Html->tag('span', '[x]'), $this->crumbUrl($remove), $removeOptions),
        array('class' => 'p-breadcrumb-crumb'));
    }

    $form = $this->Form->create(null, array('action' => 'view'));
    $form .= "<div>";
    $form .= $this->Form->hidden('Breadcrumb.current', array('value' => implode('/', $crumbs), 'div' => false));
    $form .= $this->Form->input('Breadcrumb.input', array('div' => false, 'label' => false));
    $form .= $this->Autocomplete->autoComplete('Breadcrumb.input', 'autocomplete/crumb', array('submitOnEnter' => true));
    $form .= $this->Form->submit(__('Add'), array('div' => false));
    $form .= "</div>";
    $form .= $this->Form->end();

    return $this->Html->tag('ul',
      $this->Html->tag('li', $this->Html->tag('span', __('Filter')), array('class' => 'p-breadcrumb-header', 'escape' => false))
        .implode("\n", $links)
        .$this->Html->tag('li', $form, array('class' => 'p-breadcrumb-input'), array('escape' => false)),
      array('class' => 'p-breadcrumb', 'escape' => false));
  }
}
?>
