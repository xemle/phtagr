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

class FlowplayerHelper extends AppHelper
{
  var $helpers = array('Html', 'ImageData');

  /** Loads the required script of flowplayer for scripts_for_layout variable
   */
  function importPlayer() {
    $this->Html->script('/flowplayer/flowplayer-3.1.4.min.js', array('inline' => false));
    return '';
  }

  /** Creates the link container for the flowplayer */
  function link($media) {
    list($width, $height) = $this->ImageData->getimagesize($this->data, OUTPUT_SIZE_VIDEO);
    $height += 24;
    $id = $media['Media']['id'];

    $out = $this->Html->tag('a', false, array(
      'href' => Router::url("/media/video/$id/$id.flv", true),
      'style' => "display:block; width: {$width}px; height: {$height}px;",
      'id' => 'player'
      ));
    return $out;
  }

  /** Creates the start script for the flowplayer */
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
