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
var markers = null;

function newLonLat(lon, lat) {
    return new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection('EPSG:4326'), new OpenLayers.Projection('EPSG:900913'));
}

function getMarkerURL() {
    /* left, bottom, right, top */
    var extents = map.getExtent().toArray();
    var c1 = new OpenLayers.LonLat(extents[0], extents[1]).transform(new OpenLayers.Projection('EPSG:900913'), new OpenLayers.Projection('EPSG:4326'));
    var c2 = new OpenLayers.LonLat(extents[2], extents[3]).transform(new OpenLayers.Projection('EPSG:900913'), new OpenLayers.Projection('EPSG:4326'));
    extents = [ c1.lat, c1.lon, c2.lat, c2.lon ];

    return '$url/' + extents[2] + '/' + extents[0] + '/' + extents[1] + '/' + extents[3];
}

/* name, icon, description are optional */
function addMarker(lon, lat, id, name, icon, description) {
    if (icon === null) {
        var size = new OpenLayers.Size(21, 25);
        var offset = new OpenLayers.Pixel(-10, -12);
        icon = new OpenLayers.Icon('".Router::url('/img/OpenLayers/marker-gold.png')."', size, offset);
    } else {
        icon = new OpenLayers.Icon(icon, new OpenLayers.Size(40, 40), new OpenLayers.Pixel(-20, -20));
    }

    var location = newLonLat(lon, lat);
    var marker = new OpenLayers.Marker(location, icon);
    var popup = new OpenLayers.Popup(id, location, new OpenLayers.Size(200,200),
        description, true /*, callback for closebox */);
    map.addPopup(popup);
    popup.hide();

    marker.events.register('mousedown', popup, function() {
        this.toggle();
    });

    markers.addMarker(marker);
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

    markers = new OpenLayers.Layer.Markers('Picture Locations');
    map.addLayer(markers);

    map.addControl(new OpenLayers.Control.LayerSwitcher());

    addMarker(lon, lat, id, null, null, null);

    OpenLayers.Request.GET({
        url: getMarkerURL(),
	success: function(data) {
	    var xml = data.responseXML;
	    var markerData = xml.getElementsByTagName('marker');
	    for (var i = 0; i < markerData.length; i++) {
	        var curMarker = markerData[i];
	        var id = parseInt(curMarker.getAttribute('id'));

		/* TODO: skip existing markers */

		addMarker(parseFloat(curMarker.getAttribute('lng')),
		    parseFloat(curMarker.getAttribute('lat')),
		    id,
		    curMarker.getElementsByTagName('name')[0].childNodes[0].nodeValue,
		    curMarker.getElementsByTagName('icon')[0].childNodes[0].nodeValue,
		    curMarker.getElementsByTagName('description')[0].childNodes[0].nodeValue);
	    }
	}
    });
};";
    $out .= $this->Html->scriptBlock($code, array('inline' => false));
    return $this->output($out);
  }
}

?>
