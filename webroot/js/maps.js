function phMap(options) {
  this.explorerPointsURL = options.explorerPointsURL;
  this.iconSize = options.iconSize;
  this.i18n = options.i18n;
  this.map = new OpenLayers.Map('map', {
    projection: 'EPSG:4326',
    layers: [
    new OpenLayers.Layer.Google(
      'Google Streets', {
        numZoomLevels: 20
      }
      ),
    new OpenLayers.Layer.Google(
      'Google Hybrid', {
        type: google.maps.MapTypeId.HYBRID,
        numZoomLevels: 20
      }
      ),
    new OpenLayers.Layer.Google(
      'Google Satellite', {
        type: google.maps.MapTypeId.SATELLITE,
        numZoomLevels: 22
      }
      ),
    new OpenLayers.Layer.Google(
      'Google Physical', {
        type: google.maps.MapTypeId.TERRAIN
        }
      )
    ]
  });

  this.markerIDs = {};
  this.markers = new OpenLayers.Layer.Markers(this.i18n.pictureLocations);
  this.map.addLayer(this.markers);

  this.map.addControl(new OpenLayers.Control.LayerSwitcher());

  this.newLonLat = function(lon, lat) {
    return new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection('EPSG:4326'), new OpenLayers.Projection('EPSG:900913'));
  }

  this.getMarkerURL = function() {
    /* left, bottom, right, top */
    var extents = this.map.getExtent().toArray();
    var c1 = new OpenLayers.LonLat(extents[0], extents[1]).transform(new OpenLayers.Projection('EPSG:900913'), new OpenLayers.Projection('EPSG:4326'));
    var c2 = new OpenLayers.LonLat(extents[2], extents[3]).transform(new OpenLayers.Projection('EPSG:900913'), new OpenLayers.Projection('EPSG:4326'));
    extents = [ c1.lat, c1.lon, c2.lat, c2.lon ];

    return this.explorerPointsURL + extents[2] + '/' + extents[0] + '/' + extents[1] + '/' + extents[3];
  }

  this.geolocate = function(queryString) {
    new OpenLayers.Protocol.Script({
      scope: this,
      url: '//nominatim.openstreetmap.org/search',
      params: {
        q: queryString,
        format: 'json',
        limit: 1
      },
      callback: function(loc) {
        if (loc && loc.data && loc.data.length > 0) {
          var foundPosition = this.newLonLat(loc.data[0].lon, loc.data[0].lat);
          this.map.setCenter(foundPosition, 16);
          $('#mapInfo').html('');
        } else {
          $('#mapInfo').html(this.i18n.noAddressFound);
        }
      },
      callbackKey: 'json_callback'
    }).read();
  }

  /* name, icon, description are optional */
  this.addMarker = function(lon, lat, id, name, icon, description) {
    /* does marker exist already? */
    if (this.markerIDs[id] === true) {
      return;
    }

    if (icon === null) {
      var size = new OpenLayers.Size(21, 25);
      var offset = new OpenLayers.Pixel(-10, -12);
      icon = new OpenLayers.Icon(OpenLayers.ImgPath + 'marker-gold.png', size, offset);
    } else {
      icon = new OpenLayers.Icon(icon, new OpenLayers.Size(this.iconSize, this.iconSize), new OpenLayers.Pixel(-20, -20));
    }

    var location = this.newLonLat(lon, lat);
    var marker = new OpenLayers.Marker(location, icon);
    if (description !== null) {
      var popup = new OpenLayers.Popup(id, location, new OpenLayers.Size(200,200),
        description, true);
      this.map.addPopup(popup);
      popup.updateSize();
      popup.hide();

      marker.events.register('mousedown', popup, function() {
        this.toggle();
      });
    }

    this.markers.addMarker(marker);
    this.markerIDs[id] = true;
  }

  this.fetchMarkers = function() {
    OpenLayers.Request.GET({
      scope: this,
      url: this.getMarkerURL(),
      success: function(data) {
        var xml = data.responseXML;
        var markerData = xml.getElementsByTagName('marker');
        for (var i = 0; i < markerData.length; i++) {
          var curMarker = markerData[i];
          var id = parseInt(curMarker.getAttribute('id'));

          this.addMarker(parseFloat(curMarker.getAttribute('lng')),
            parseFloat(curMarker.getAttribute('lat')),
            id,
            curMarker.getElementsByTagName('name')[0].childNodes[0].nodeValue,
            curMarker.getElementsByTagName('icon')[0].childNodes[0].nodeValue,
            curMarker.getElementsByTagName('description')[0].childNodes[0].nodeValue);
        }
      }
    });
  }

  this.center = function(lon, lat) {
    this.map.setCenter(this.newLonLat(lon, lat), 15);
  }

  this.updateInfo = function() {
    var lonLat = this.map.getCenter().transform(new OpenLayers.Projection('EPSG:900913'), new OpenLayers.Projection('EPSG:4326'));
    var text = this.i18n.currentLocation + ' ' +  lonLat.lat.toFixed(5) + ', ' + lonLat.lon.toFixed(5);
    $('#mapInfo').html(text);
  }
  if (options.center !== null) {
    this.center(options.center.lon, options.center.lat);
  } else {
    /* necessary so fetchMarkers has an extent to query */
    this.center(0, 0);
  }

  this.map.events.register('moveend', this, this.fetchMarkers);
  this.map.events.register('zoomend', this, this.fetchMarkers);
  this.map.events.register('moveend', this, this.updateInfo);
  this.map.events.register('zoomend', this, this.updateInfo);

  this.fetchMarkers();
  this.updateInfo();
}
