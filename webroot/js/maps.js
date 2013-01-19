var map = null;
var markers = null;
var markerIDs = {};

function newLonLat(lon, lat) {
    return new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection('EPSG:4326'), new OpenLayers.Projection('EPSG:900913'));
}

function getMarkerURL() {
    /* left, bottom, right, top */
    var extents = map.getExtent().toArray();
    var c1 = new OpenLayers.LonLat(extents[0], extents[1]).transform(new OpenLayers.Projection('EPSG:900913'), new OpenLayers.Projection('EPSG:4326'));
    var c2 = new OpenLayers.LonLat(extents[2], extents[3]).transform(new OpenLayers.Projection('EPSG:900913'), new OpenLayers.Projection('EPSG:4326'));
    extents = [ c1.lat, c1.lon, c2.lat, c2.lon ];

    return explorerPointsURL + extents[2] + '/' + extents[0] + '/' + extents[1] + '/' + extents[3];
}

function reportError(message) {
    document.getElementById('mapInfo').firstChild.nodeValue = message;
}

function geolocate(queryString) {
    OpenLayers.Request.POST({
        url: 'http://www.openrouteservice.org/php/OpenLSLUS_Geocode.php',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
	data: OpenLayers.Util.getParameterString({FreeFormAdress: queryString, MaxResponse: 1}),
        failure: function() {
             reportError('Could not communicate with geo-location service');
        },
        success: function(data) {
            var xml = data.responseXML;
            var format = new OpenLayers.Format.XLS();
            var output = format.read(xml);
            if ((output.responseLists.length > 0) &&
	        (output.responseLists[0].features.length > 0) &&
		output.responseLists[0]) {
                var geometry = output.responseLists[0].features[0].geometry;
                var foundPosition = newLonLat(geometry.x, geometry.y);
                map.setCenter(foundPosition, 16);
            } else {
                reportError('No address found');
            }
        }
    });
}

/* name, icon, description are optional */
function addMarker(lon, lat, id, name, icon, description) {
    /* does marker exist already? */
    if (markerIDs[id] === true) {
       return;
    }

    var doPopup = false;

    if (icon === null) {
        var size = new OpenLayers.Size(21, 25);
        var offset = new OpenLayers.Pixel(-10, -12);
        icon = new OpenLayers.Icon(OpenLayers.ImgPath + 'marker-gold.png', size, offset);
	doPupup = true;
    } else {
        icon = new OpenLayers.Icon(icon, new OpenLayers.Size(40, 40), new OpenLayers.Pixel(-20, -20));
    }

    var location = newLonLat(lon, lat);
    var marker = new OpenLayers.Marker(location, icon);
    if (doPopup) {
        var popup = new OpenLayers.Popup(id, location, new OpenLayers.Size(200,200),
            description, true);
        map.addPopup(popup);
        popup.updateSize();
        popup.hide();

        marker.events.register('mousedown', popup, function() {
            this.toggle();
        });
    }

    markers.addMarker(marker);
    markerIDs[id] = true;
}

function fetchMarkers() {
    OpenLayers.Request.GET({
        url: getMarkerURL(),
	success: function(data) {
	    var xml = data.responseXML;
	    var markerData = xml.getElementsByTagName('marker');
	    for (var i = 0; i < markerData.length; i++) {
	        var curMarker = markerData[i];
	        var id = parseInt(curMarker.getAttribute('id'));

		addMarker(parseFloat(curMarker.getAttribute('lng')),
		    parseFloat(curMarker.getAttribute('lat')),
		    id,
		    curMarker.getElementsByTagName('name')[0].childNodes[0].nodeValue,
		    curMarker.getElementsByTagName('icon')[0].childNodes[0].nodeValue,
		    curMarker.getElementsByTagName('description')[0].childNodes[0].nodeValue);
	    }
	}
    });
}

function loadMap(id, lat, lon) {
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

    fetchMarkers();

    map.events.register('moveend', map, fetchMarkers);
    map.events.register('zoomend', map, fetchMarkers);
};

