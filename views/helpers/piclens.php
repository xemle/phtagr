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
