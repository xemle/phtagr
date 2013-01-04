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

class FeedComponent extends Component {

  var $name = 'FeedComponent';

  var $controller = null;

  var $_feeds = array();

  /** Set true for test cases. This flag will disable the function
   * is_uploaded_file() and move_uploaded_file(). Otherwise the upload test
   * needs a special web test with ugly post data handling */
  var $_testRun = false;

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  /** Set feeds output for layout */
  public function beforeRender(Controller $controller) {
    App::uses('HtmlHelper', 'View/Helper');
    App::uses('View', 'View');
    $View = new View($this->controller, false);
    $Html = new HtmlHelper($View);
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
      $output .= $Html->meta($type, $url, $options);
    }
    $this->controller->set('feeds_for_layout', $output);
  }

  /** Clears all feeds */
  public function clear() {
    $this->_feeds = array();
  }

  /** Add a feed
  @param url Url of the feed. String or url array
  @param options Option arrya
    - title
  @param type Optional feed type. Default is 'rss' */
  public function add($url, $options, $type = 'rss') {
    if (is_array($url)) {
      $this->_feeds[] = am(array('type' => $type, 'url' => $url), $options);
    } else {
      $this->_feeds[$url] = am(array('type' => $type), $options);
    }
  }
}
?>
