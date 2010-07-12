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

class FeedComponent extends Object {
  
  var $name = 'FeedComponent';

  var $controller = null;

  var $_feeds = array();

  /** Set true for test cases. This flag will disable the function
   * is_uploaded_file() and move_uploaded_file(). Otherwise the upload test
   * needs a special web test with ugly post data handling */
  var $_testRun = false;

  function initialize(&$controller) {
    $this->controller = $controller;
  }
  
  /** Set feeds output for layout */
  function beforeRender() {
    App::import('Helper', 'Html');
    $html = new HtmlHelper();

    $output = '';
    foreach($this->_feeds as $url => $options) {
      if (!empty($options['type'])) {
        $type = $options['type'];
        unset($options['type']);
      } else {
        $type = 'rss';
      }
      if (!empty($options['url'])) {
        $url = Router::url($options['url']);
        unset($options['url']);
      }
      $output .= $html->meta($type, $url, $options);
    }
    $this->controller->set('feeds_for_layout', $output);
  }

  /** Clears all feeds */
  function clear() {
    $this->_feeds = array();
  }

  /** Add a feed
  @param url Url of the feed. String or url array
  @param options Option arrya
    - title
  @param type Optional feed type. Default is 'rss' */
  function add($url, $options, $type = 'rss') {
    if (is_array($url)) {
      $this->_feeds[] = am(array('type' => $type, 'url' => $url), $options);
    } else {
      $this->_feeds[$url] = am(array('type' => $type), $options);
    } 
  }
}
?>
