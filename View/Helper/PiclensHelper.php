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

class PiclensHelper extends AppHelper
{
  var $helpers = array('Html', 'Search');

  function initialize() {
    $this->Search->initialize();
  }

  function slideshow() {
    $this->Html->script('/piclenslite/piclens_optimized', array('inline' => false));
    $swf = Router::url("/piclenslite/PicLensLite.swf");
    $feed = Router::url($this->Search->getUri(false, false, false, array('baseUri' => '/explorer/media')));
    $code = "PicLensLite.setLiteURLs({swf:'$swf'});
var startSlideshow = function(quality) {
  var feed = '$feed';
  if (quality == 'high') {
    feed += '/quality:high';
  }
  PicLensLite.start({feedUrl:feed});
}";
    return $this->Html->scriptBlock($code);
  }
}
?>