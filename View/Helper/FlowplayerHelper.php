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

class FlowplayerHelper extends AppHelper
{
  var $helpers = array('Html', 'ImageData');

  /**
   * Loads the required script of flowplayer for scripts_for_layout variable
   */
  function importPlayer() {
    $this->Html->script('/flowplayer/flowplayer-3.1.4.min.js', array('inline' => false));
    return '';
  }

  /**
   * Creates the link container for the flowplayer
   */
  function link($media) {
    list($width, $height) = $this->ImageData->getimagesize($this->request->data, OUTPUT_SIZE_VIDEO);
    $height += 24;
    $id = $media['Media']['id'];

    $out = $this->Html->tag('a', false, array(
      'href' => Router::url("/media/video/$id/$id.flv", true),
      'style' => "display:block; width: {$width}px; height: {$height}px;",
      'id' => 'player'
      ));
    return $out;
  }

  /**
   * Creates the start script for the flowplayer
   */
  function player($media) {
    $id = $media['Media']['id'];
    return $this->Html->scriptBlock("flowplayer('player', '".Router::url("/flowplayer/flowplayer-3.1.5.swf", true)."', {
playlist: [
  {
    url: '".Router::url("/media/preview/$id/$id.jpg", true)."',
    scaling: 'fit'
  },
  {
    url: '".Router::url("/media/video/$id/$id.flv", true)."',
    autoPlay: false,
    autoBuffering: false
  }
]});\n");
  }

  function video($media) {
    $out = $this->importPlayer().$this->link($media).$this->player($media);
    return $out;
  }
}
?>