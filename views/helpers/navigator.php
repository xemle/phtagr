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

  function hasPages() {
    return (isset($this->params['search']['pageCount']) &&
      $this->params['search']['pageCount'] > 1);
  }

  function hasPrev() {
    return (isset($this->params['search']['prevPage']) &&
      $this->params['search']['prevPage']);
  }

  function getPrevUrl() {
    if (!$this->hasPrev()) {
      return false;
    }
    
    return $this->Search->getUri(false, array('page' => $this->params['search']['current'] - 1));
  }

  function prev() {
    $prevUrl = $this->getPrevUrl();
    if (!$prevUrl) {
      return false;
    }
    return $this->Html->link('prev', $prevUrl, array('class' => 'prev'));
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
          $output .= ' '.$this->Html->link($i, $this->Search->getUri(false, array('page' => $i)));
        }
        else if ($i == $count-2 || $i == 3) {
          $output .= " ... ";
        }
      }
    }
    return $output;
  }

  function hasNext() {
    return (isset($this->params['search']['nextPage']) &&
      $this->params['search']['nextPage']);
  }

  function getNextUrl($base = null) {
    if (!$this->hasNext()) {
      return false;
    }
    return $this->Search->getUri(false, array($this->params['search']['count'] + 1));
  }

  function next() {
    $nextUrl = $this->getNextUrl();
    if (!$nextUrl) {
      return false;
    }
    return $this->Html->link('next', $nextUrl, array('class' => 'next'));
  }

  function prevMedia() {
    if (!isset($this->params['search']))
      return;
    $query = $this->params['search'];
    if (isset($query['prevMedia'])) {
      $query['pos']--;
      $query['page'] = ceil($query['pos'] / $query['show']);
      return $this->Html->link('prev', '/images/view/'.$query['prevMedia'].'/'.$this->getParams($query, $this->_excludeMedia), array('class' => 'prev'));
    }
  }

  function up() {
    if (!isset($this->params['search']))
      return;
    $query = $this->params['search'];
    $query['page'] = ceil($query['pos'] / $query['show']);
    $exclude = am($this->_excludeMedia, array('image' => true, 'pos' => true));
    return $this->Html->link('up', $this->Search->getUri($query, $exclude).'#media-'.$query['media'], array('class' => 'up'));
  }

  function nextMedia() {
    if (!isset($this->params['search']))
      return;
    $query = $this->params['search'];
    if (isset($query['nextMedia'])) {
      $query['pos']++;
      $query['page'] = ceil($query['pos'] / $query['show']);
      return $this->Html->link('next', '/images/view/'.$query['nextMedia'].'/'.$this->getParams($query, $this->_excludeMedia), array('class' => 'next'));
    }
  }
}
