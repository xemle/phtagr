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

class NavigatorHelper extends AppHelper {
  var $helpers = array('Html', 'Search', 'Breadcrumb');

  var $data = array();
  var $defaults = array('page' => 1, 'pageCount' => 1, 'prevPage' => false, 'nextPage' => false, 'current' => false, 'prevMedia' => false, 'nextMedia' => false);

  function beforeRender($viewFile) {
    $this->Search->initialize();
    if (isset($this->request->params['search'])) {
      $this->data = am($this->defaults, $this->request->params['search']);
      if (!isset($this->data['page'])) {
        // weired bug. 'page' parameter gets lost.
        $this->data['page'] = 1;
      }
    } else {
      $this->data = $this->defaults;
    }
  }

  function getCrumbs() {
    if (isset($this->request->params['crumbs'])) {
      return $this->request->params['crumbs'];
    }
    return array();
  }

  function getPageCount() {
    return $this->data['pageCount'];
  }

  function getCurrentPage() {
    return $this->data['page'];
  }

  function hasPages() {
    return $this->data['pageCount'] > 1;
  }

  function hasPrev() {
    return $this->data['prevPage'];
  }

  function prev() {
    if (!$this->hasPrev()) {
      return false;
    }
    $prev = $this->getCurrentPage() - 1;
    $crumbs = $this->getCrumbs();
    $link = $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'page', $prev));
    return $this->Html->link(__('prev'), $link, array('class' => 'prev'));
  }

  function numbers() {
    $crumbs = $this->getCrumbs();
    $output = '';

    if ($this->hasPages()) {
      $count = $this->getPageCount();
      $current = $this->getCurrentPage();
      for ($i = 1; $i <= $count; $i++) {
        if ($i == $current) {
          $output .= " <span class=\"current\">$i</span> ";
        }
        else if ($count <= 12 ||
            ($i < 3 || $i > $count-2 ||
            ($i-$current < 4 && $current-$i<4))) {
          $link = $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'page', $i));
          $output .= ' '.$this->Html->link($i, $link);
        }
        else if ($i == $count-2 || $i == 3) {
          $output .= " ... ";
        }
      }
    }

    return $output;
  }

  function hasNext() {
    return $this->data['nextPage'];
  }

  function next() {
    if (!$this->hasNext()) {
      return false;
    }
    $next = $this->getCurrentPage() + 1;
    $crumbs = $this->getCrumbs();
    $link = $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'page', $next));
    return $this->Html->link(__('next'), $link, array('class' => 'next'));
  }

  function getCurrentMedia() {
    return $this->data['current'];
  }

  function hasPrevMedia() {
    return $this->data['prevMedia'];
  }

  function prevMedia() {
    if (!$this->hasPrevMedia()) {
      return;
    }
    $pos = $this->Search->getPos(1) - 1;
    $page = ceil($pos / $this->Search->getShow($this->Search->getDefault('show')));
    $baseUri = '/images/view/'.$this->data['prevMedia'] . '/';
    $crumbs = $this->getCrumbs();
    $crumbParams = $this->Breadcrumb->params($this->Breadcrumb->replace($this->Breadcrumb->replace($crumbs, 'page', $page), 'pos', $pos));
    $link = $baseUri . $crumbParams;
    return $this->Html->link(__('prev'), $link, array('class' => 'prev'));
  }

  function up() {
    if (!$this->getCurrentMedia()) {
      return;
    }
    $crumbs = $this->getCrumbs();
    $link = $this->Breadcrumb->crumbUrl($crumbs, false, array('pos'));
    $link .= '#media-'.$this->getCurrentMedia();
    return $this->Html->link(__('Explorer'), $link, array('class' => 'up'));
  }

  function hasNextMedia() {
    return $this->data['nextMedia'];
  }

  function getNextMediaUrl() {
    $pos = $this->Search->getPos(1) + 1;
    $page = ceil($pos / $this->Search->getShow($this->Search->getDefault('show')));
    $baseUri = '/images/view/'.$this->data['nextMedia'] . '/';
    $crumbs = $this->request->params['crumbs'];
    $crumbParams = $this->Breadcrumb->params($this->Breadcrumb->replace($this->Breadcrumb->replace($crumbs, 'page', $page), 'pos', $pos));
    $link = $baseUri . $crumbParams;
    return $link;
  }

  function nextMedia() {
    if (!$this->data['nextMedia']) {
      return;
    }
    return $this->Html->link(__('next'), $this->getNextMediaUrl(), array('class' => 'next'));
  }

  function pages() {
    if (!$this->hasPages()) {
      return;
    }
    $out = $this->Html->tag('div',
      $this->Html->tag('div',
        $this->prev() . ' ' . $this->numbers() . ' ' . $this->next(),
        array('class' => 'sub', 'escape' => false)),
      array('class' => 'p-navigator-pages', 'escape' => false));
    return $out;
  }
}
