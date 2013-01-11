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

  var $googleMapApiUrl = 'http://maps.google.com/maps/api/js?v=3.2&amp;sensor=false';

  function initialize() {
    $this->Search->initialize();
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
    $out = $this->Html->script(array($this->googleMapApiUrl, 'OpenLayers'), array('inline' => false));
    $out .= $this->Html->css('OpenLayers/style');

    $url = Router::url('/explorer/points/' . $this->Search->serialize(), true);
    $url = preg_replace("/'/", "\\'", $url);
    $code = "
var map = null;

function newLonLat(lon, lat) {
    return new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection('EPSG:4326'), new OpenLayers.Projection('EPSG:900913'));
}

function loadMap(id, lat, lon) {
    OpenLayers.ImgPath = '".Router::url('/img/OpenLayers/')."';
    map = new OpenLayers.Map('map', {
        projection: 'EPSG:4326',
	zoom: 15,
        center: newLonLat(lon, lat),
        layers: [
            new OpenLayers.Layer.Google(
                'Google Physical',
                {type: google.maps.MapTypeId.TERRAIN}
            ),
            new OpenLayers.Layer.Google(
                'Google Streets',
                {numZoomLevels: 20}
            ),
            new OpenLayers.Layer.Google(
                'Google Hybrid',
                {type: google.maps.MapTypeId.HYBRID, numZoomLevels: 20}
            ),
            new OpenLayers.Layer.Google(
                'Google Satellite',
                {type: google.maps.MapTypeId.SATELLITE, numZoomLevels: 22}
            )
        ]
    });

    var markers = new OpenLayers.Layer.Markers('Picture Locations');
    map.addLayer(markers);


    var size = new OpenLayers.Size(21, 25);
    var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
    var icon = new OpenLayers.Icon('".Router::url('/img/OpenLayers/marker-gold.png')."', size, offset);
    markers.addMarker(new OpenLayers.Marker(newLonLat(lon, lat), icon));

    map.addControl(new OpenLayers.Control.LayerSwitcher());
};";
    $out .= $this->Html->scriptBlock($code, array('inline' => false));
    return $this->output($out);
  }
}

?>
