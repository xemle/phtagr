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

class NavigatorHelper extends AppHelper {
  var $helpers = array('Html', 'Search', 'Breadcrumb'); 

  function beforeRender() {
    $this->Search->initialize();
  }

  function getPageCount() {
    if (isset($this->params['search'])) {
      return $this->params['search']['pageCount'];
    }
    return 0;
  }

  function getCurrentPage() {
    if (isset($this->params['search']['page'])) {
      return $this->params['search']['page'];
    }
    return 0;
  }

  function hasPages() {
    return (isset($this->params['search']) && $this->params['search']['pageCount'] > 1);
  }

  function hasPrev() {
    if (isset($this->params['search']) && 
      $this->params['search']['prevPage']) {
      return true;
    }
    return false;
  }

  function prev() {
    if (!isset($this->params['search']) || 
      !$this->params['search']['prevPage']) {
      return false;
    }
    $prev = $this->params['search']['page'] - 1;
    $crumbs = $this->params['crumbs'];
    $link = $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'page', $prev));
    return $this->Html->link(__('prev', true), $link, array('class' => 'prev'));
  }

  function numbers() {
    if (!isset($this->params['search'])) {
      return;
    }

    $params = $this->params['search'];
    $crumbs = $this->params['crumbs'];
    $output = '';
    
    if ($params['pageCount'] > 1) {
      $count = $params['pageCount'];
      $current = $params['page'];
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
    if (isset($this->params['search']) && 
      $this->params['search']['nextPage']) {
      return true;
    }
    return false;
  }

  function next() {
    if (!isset($this->params['search']) || 
      !$this->params['search']['nextPage']) {
      return false;
    }
    $next = $this->params['search']['page'] + 1;
    $crumbs = $this->params['crumbs'];
    $link = $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'page', $next));
    return $this->Html->link(__('next', true), $link, array('class' => 'next'));
  }

  function hasPrevMedia() {
    return !empty($this->params['search']['prevMedia']);
  }

  function prevMedia() {
    if (!isset($this->params['search']) ||
      !$this->params['search']['prevMedia']) {
      return;
    }
    $params = $this->params['search'];
    $crumbs = $this->params['crumbs'];
    $pos = $this->Search->getPos(1) - 1;
    $page = ceil($pos / $this->Search->getShow());
    $baseUri = '/images/view/'.$params['prevMedia'] . '/';
    $crumbs = $this->params['crumbs'];
    $crumbParams = $this->Breadcrumb->params($this->Breadcrumb->replace($this->Breadcrumb->replace($crumbs, 'page', $page), 'pos', $pos));
    $link = $baseUri . $crumbParams;
    return $this->Html->link(__('prev', true), $link, array('class' => 'prev'));
  }

  function up() {
    if (!isset($this->params['search'])) {
      return;
    }
    $params = $this->params['search'];
    $link = $this->Breadcrumb->crumbUrl($this->params['crumbs'], false, array('pos'));
    $link .= '#media-'.$params['current'];
    return $this->Html->link(__('overview', true), $link, array('class' => 'up'));
  }

  function hasNextMedia() {
    return !empty($this->params['search']['nextMedia']);
  }

  function getNextMediaUrl() {
    $params = $this->params['search'];
    $pos = $this->Search->getPos(1) + 1;
    $page = ceil($pos / $this->Search->getShow());
    $baseUri = '/images/view/'.$params['nextMedia'] . '/';
    $crumbs = $this->params['crumbs'];
    $crumbParams = $this->Breadcrumb->params($this->Breadcrumb->replace($this->Breadcrumb->replace($crumbs, 'page', $page), 'pos', $pos));
    $link = $baseUri . $crumbParams;
    return $link;
  }

  function nextMedia() {
    if (!isset($this->params['search']) || 
      !$this->params['search']['nextMedia']) {
      return;
    }
    return $this->Html->link(__('next', true), $this->getNextMediaUrl(), array('class' => 'next'));
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
?>
