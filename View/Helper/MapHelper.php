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

    $url = Router::url('/explorer/points/' . $this->Search->serialize(), true);
    $url = preg_replace("/'/", "\\'", $url);
    $code = "
var map = null;
var loadMap = function(id, latitude, longitude) {
  if (undefined == map) {

    var options = {
      url: '$url'
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