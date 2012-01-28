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
    if (isset($this->request->params['search'])) {
      return $this->request->params['search']['pageCount'];
    }
    return 0;
  }

  function getCurrentPage() {
    if (isset($this->request->params['search']['page'])) {
      return $this->request->params['search']['page'];
    }
    return 0;
  }

  function hasPages() {
    return (isset($this->request->params['search']) && $this->request->params['search']['pageCount'] > 1);
  }

  function hasPrev() {
    if (isset($this->request->params['search']) && 
      $this->request->params['search']['prevPage']) {
      return true;
    }
    return false;
  }

  function prev() {
    if (!isset($this->request->params['search']) || 
      !$this->request->params['search']['prevPage']) {
      return false;
    }
    $prev = $this->request->params['search']['page'] - 1;
    $crumbs = $this->request->params['crumbs'];
    $link = $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'page', $prev));
    return $this->Html->link(__('prev'), $link, array('class' => 'prev'));
  }

  function numbers() {
    if (!isset($this->request->params['search'])) {
      return;
    }

    $params = $this->request->params['search'];
    $crumbs = $this->request->params['crumbs'];
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
    if (isset($this->request->params['search']) && 
      $this->request->params['search']['nextPage']) {
      return true;
    }
    return false;
  }

  function next() {
    if (!isset($this->request->params['search']) || 
      !$this->request->params['search']['nextPage']) {
      return false;
    }
    $next = $this->request->params['search']['page'] + 1;
    $crumbs = $this->request->params['crumbs'];
    $link = $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'page', $next));
    return $this->Html->link(__('next'), $link, array('class' => 'next'));
  }

  function hasPrevMedia() {
    return !empty($this->request->params['search']['prevMedia']);
  }

  function prevMedia() {
    if (!isset($this->request->params['search']) ||
      !$this->request->params['search']['prevMedia']) {
      return;
    }
    $params = $this->request->params['search'];
    $crumbs = $this->request->params['crumbs'];
    $pos = $this->Search->getPos(1) - 1;
    $page = ceil($pos / $this->Search->getShow());
    $baseUri = '/images/view/'.$params['prevMedia'] . '/';
    $crumbs = $this->request->params['crumbs'];
    $crumbParams = $this->Breadcrumb->params($this->Breadcrumb->replace($this->Breadcrumb->replace($crumbs, 'page', $page), 'pos', $pos));
    $link = $baseUri . $crumbParams;
    return $this->Html->link(__('prev'), $link, array('class' => 'prev'));
  }

  function up() {
    if (!isset($this->request->params['search'])) {
      return;
    }
    $params = $this->request->params['search'];
    $link = $this->Breadcrumb->crumbUrl($this->request->params['crumbs'], false, array('pos'));
    $link .= '#media-'.$params['current'];
    return $this->Html->link(__('overview'), $link, array('class' => 'up'));
  }

  function hasNextMedia() {
    return !empty($this->request->params['search']['nextMedia']);
  }

  function getNextMediaUrl() {
    $params = $this->request->params['search'];
    $pos = $this->Search->getPos(1) + 1;
    $page = ceil($pos / $this->Search->getShow());
    $baseUri = '/images/view/'.$params['nextMedia'] . '/';
    $crumbs = $this->request->params['crumbs'];
    $crumbParams = $this->Breadcrumb->params($this->Breadcrumb->replace($this->Breadcrumb->replace($crumbs, 'page', $page), 'pos', $pos));
    $link = $baseUri . $crumbParams;
    return $link;
  }

  function nextMedia() {
    if (!isset($this->request->params['search']) || 
      !$this->request->params['search']['nextMedia']) {
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
?>
