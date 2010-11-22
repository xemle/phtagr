<?php
/*
 * phtagr.
 * 
 * social photo gallery for your community.
 * 
 * Copyright (C) 2006-2010 Sebastian Felis, sebastian@phtagr.org
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the 
 * License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

class BreadcrumbHelper extends AppHelper
{
  var $helpers = array('Html', 'Form', 'Ajax', 'Search');
  
  /** Return breadcrumb params for building urls
    @param crumbs Current breadcrumb stack
    @param crumb Optional additional crumb
    @param filter Optional filter crumb. Single type or array of types to filter */
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

  /** Return breadcrumb url
    @param crumbs Current breadcrumb stack
    @param crumb Optional additional crumb
    @param filter Optional filter crumb. Single type or array of types to
    filter */
  function crumbUrl($crumbs, $crumb = false, $filter = array()) {
    return '/explorer/view/' . $this->params($crumbs, $crumb, $filter);
  }

  /** Replace a breadcrumb value
    @param crumbs Current breadcrumb stack
    @param needle Crumb to find. String of crumb type or array of crumb type
    and crumb value. E.g. 'location' or array('tag', 'flower')
    @param value to replace
    @param Optional addEmpty. Adds the crumb anyway if this is set to true and
    the stack does not have such breadcumb type. Default is true.
    @return Replaced breadcrumb stack */
  function replace($crumbs, $needle, $value, $addEmpty = true) {
    $replace = array();
    $isReplaced = false;
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
      } else {
        $replace[] = $crumb;
      }
    }
    if (!$isReplaced && $addEmpty) {
      $replace[] = "$needle:$value";
    }
    return $replace;
  }

  /** Filter crumbs by excluded types 
    @param crumbs Current breadcrumb stack
    @param exclude Array of excluded types. Default it excludes type of show, pos, and page
    @return Filtered crumbs */
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

  /** Create breadcrumb html list 
    @param crumbs Current breadcrumb stack */
  function breadcrumb($crumbs) {
    $links = array();
    $crumbs = $this->filterCrumbs($crumbs);
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
        "$name:" 
        .$this->Html->link($value, $this->crumbUrl(array_slice($crumbs, 0, $key + 1)), $options) 
        .$this->Html->link($this->Html->tag('span', '[x]'), $this->crumbUrl($remove), $removeOptions));
    }

    $form = $this->Form->create(null, array('action' => 'view'));
    $form .= $this->Form->hidden('breadcrumb.current', array('value' => implode('/', $crumbs), 'div' => false));
    $form .= $this->Ajax->autoComplete('breadcrumb.input', 'autocomplete/crumb'); 
    //$form .= $this->Form->input('breadcrumb.input', array('div' => false, 'label' => false));
    $form .= $this->Form->submit('add', array('div' => false));
    $form .= $this->Form->end();

    return $this->Html->tag('ul', 
      $this->Html->tag('li', __('Filter', true), array('class' => 'p-breadcrumb-header'))
        .$this->Html->tag('li', $this->Html->tag('ul', implode("\n", $links), array('class' => 'p-breadcrumb-list')))
        .$this->Html->tag('li', $form, array('class' => 'p-breadcrumb-input')),
      array('class' => 'p-breadcrumb'));
  }
}
?>
