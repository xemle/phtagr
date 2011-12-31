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
class MapHelper extends AppHelper
{
  var $helpers = array("Html", "Search", "Option");

  var $googleMapApiUrl = 'http://maps.google.com/maps?file=api&amp;v=2&amp;key=';

  function initialize() {
    $this->Search->initialize();
  }

  function hasApi() {
    return ($this->Option->get('google.map.key') != false);
  }

  function hasMediaGeo($media) {
    $data = $media;
    if (isset($media['Media'])) {
      $data = $media['Media'];
    }
    return !empty($data['latitude']) && !empty($data['longitude']);
  }

  function container() {
    return $this->output('<div id="mapbox" style="display: none;">
    <div id="map" style="width: 100%; height: 450px;"></div>
    <div id="mapStatusLine">
      <div id="mapInfo">
      </div>
      <div id="mapSearch">
        <label for="mapSearch">Goto:</label>
        <input type="text" id="mapSearch" size="32" onkeydown="if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) { map.showAddress(this.value); return false; } else { return true; }"/>
      </div>
    </div>
    </div>
    ');
  }

  function script() {
    if (!$this->hasApi()) {
      return $this->output();
    }

    $out = $this->Html->script(array($this->googleMapApiUrl . h($this->Option->get('google.map.key')), 'prototype', 'pmap'), array('inline' => false));

    $code = "
var map = null;
var loadMap = function(id, latitude, longitude) {
  if (undefined == map) {

    var options = { 
      url: '" . Router::url('/explorer/points/' . $this->Search->serialize(), true) . "' 
      };

    map = new PMap(latitude, longitude, options);
    map.setMediaMarker(id, latitude, longitude);
    map.updateInfo();
    map.updateMarkers();
  }
};";
    $out .= $this->Html->scriptBlock($code, array('inline' => false));
    return $this->output($out);
  }
}

?>
