<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2009 Sebastian Felis, sebastian@phtagr.org
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
  var $helpers = array('Html', 'Search'); 

  function beforeRender() {
    $this->Search->initialize();
  }

  function hasPages() {
    return (isset($this->params['search']) && $this->params['search']['pageCount'] > 1);
  }

  function prev() {
    if (!isset($this->params['search']) || 
      !$this->params['search']['prevPage']) {
      return false;
    }
    $current = $this->params['search']['page'];
    $link = $this->Search->getUri(false, array('page' => $current - 1));
    return $this->Html->link('prev', $link, array('class' => 'prev'));
  }

  function numbers() {
    if (!isset($this->params['search'])) {
      return;
    }

    $params = $this->params['search'];
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
          $link = $this->Search->getUri(false, array('page' => $i));
          $output .= ' '.$this->Html->link($i, $link);
        }
        else if ($i == $count-2 || $i == 3) {
          $output .= " ... ";
        }
      }
    }

    return $output;
  }

  function next() {
    if (!isset($this->params['search']) || 
      !$this->params['search']['nextPage']) {
      return false;
    }
    $current = $this->params['search']['page'];
    $link = $this->Search->getUri(false, array('page' => $current + 1));
    return $this->Html->link('next', $link, array('class' => 'next'));
  }

  function prevMedia() {
    if (!isset($this->params['search']) ||
      !$this->params['search']['prevMedia']) {
      return;
    }
    $params = $this->params['search'];
    $pos = $this->Search->getPos() - 1;
    $page = ceil($pos / $this->Search->getShow());
    $baseUri = '/images/view/'.$params['prevMedia'];
    $link = $this->Search->getUri(false, array('pos' => $pos, 'page' => $page), false, array('baseUri' => $baseUri, 'defaults' => array('pos' => 1)));
    return $this->Html->link('prev', $link, array('class' => 'prev'));
  }

  function up() {
    if (!isset($this->params['search'])) {
      return;
    }
    $params = $this->params['search'];
    $pos = $this->Search->getPos();
    $link = $this->Search->getUri(false, false, array('pos' => $pos)).'#media-'.$params['current'];
    return $this->Html->link('up', $link, array('class' => 'up'));
  }

  function nextMedia() {
    if (!isset($this->params['search']) || 
      !$this->params['search']['nextMedia']) {
      return;
    }
    $params = $this->params['search'];
    $pos = $this->Search->getPos() + 1;
    $page = ceil($pos / $this->Search->getShow());
    $baseUri = '/images/view/'.$params['nextMedia'];
    $link = $this->Search->getUri(false, array('pos' => $pos, 'page' => $page), false, array('baseUri' => $baseUri, 'defaults' => array('pos' => 1)));
    return $this->Html->link('next', $link, array('class' => 'next'));
  }
}
?>
