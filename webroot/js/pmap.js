/*
 * phtagr.
 *
 * Multi-user image gallery.
 *
 * Copyright (C) 2006-2009 Sebastian Felis, sebastian@phtagr.org
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

/** phTagr map object 
  @param id DOM id of map
  @param latitude Latitude of center
  @param longitude Longitude of center 
  @param options Options. 
    - domId: DOM ID of the map. 
    - zoom: Inition zoom level of the map
    - url: Query url for markers
  map. */
function PMap(latitude, longitude, options) {
  this.ICON_SIZE = 24;
  this.gmap = null;   /**< Google map */
  this.markers = new Hash(); /**< Hash of markser id -> Marker */
  this.currentId = null; /**< Current image marker's id */
  this.url = null;    /**< base URL for background queries */
  this.isLoaded = false;
  this.contextMenu = null;
  this._updatingMarkers = false;
  this._continueUpdateMarkers = false;

  options = options || {};
  var domId = 'map';
  if (undefined != options.domId) {
    domId = options.domId;
  }
  var zoom = 14;
  if (typeof options.zoom == "number") {
    zoom = options.zoom;
  }
  if (undefined != options.url) {
    this.url = options.url;
  }

  var e = document.getElementById(domId);
  if (e && GBrowserIsCompatible()) {
    this.gmap = new GMap2(e);
    this.gmap.addControl(new GMapTypeControl());
    this.gmap.addControl(new GSmallMapControl());
    this.gmap.addControl(new GScaleControl());
    this.gmap.enableScrollWheelZoom();

    this.gmap.setCenter(new GLatLng(latitude, longitude), zoom);
    this.initBaseIcon();
    // Hock events
    GEvent.bind(this.gmap, "moveend", this, this.onMoveEnd);
    GEvent.bind(this.gmap, "zoomend", this, this.onZoomEnd);
    this.isLoaded = true;
    this.contextMenu = new PContextMenu(this.gmap);
  }
}

PMap.prototype.initBaseIcon = function() {
  this.baseIcon = new GIcon(G_DEFAULT_ICON);
  this.baseIcon.iconSize = new GSize(this.ICON_SIZE, this.ICON_SIZE);
  this.baseIcon.shadow = null;
  this.baseIcon.iconAnchor = new GPoint(this.ICON_SIZE/2, this.ICON_SIZE/2);
  this.baseIcon.infoWindowAnchor = new GPoint(this.ICON_SIZE, 2);
}

PMap.prototype.setCenter = function(latitude, longitude){
  if (!this.isLoaded) {
    return false;
  }
  var center = new GLatLng(latitude, longitude);
  this.gmap.setCenter(center, 14);
  return true;
}

PMap.prototype.setMediaMarker = function(id, latitude, longitude){
  if (!this.isLoaded) {
    return false;
  }
  var point = new GLatLng(latitude, longitude);
  var marker = new GMarker(point);
  this.gmap.addOverlay(marker);
  this.currentId = id;
  this.markers.set(id, marker);
  return true;
}

/** Request for markers within the current map bounds */
PMap.prototype.updateMarkers = function() {
  if (!this.isLoaded || undefined == this.url) {
    return false;
  }

  /** Wait until the current update if finished */
  if (this._updatingMarkers == true) {
    this._continueUpdateMarkers = true;
    return true;
  }
  this._updatingMarkers = true;

  var bounds = this.gmap.getBounds();
  var sw = bounds.getSouthWest();
  var ne = bounds.getNorthEast();

  var p = '/' + ne.lat() + '/' + sw.lat();
  p = p + '/' + sw.lng() + '/' + ne.lng();

  var query = this.url + p;
  var req = new Ajax.Request(query, {
    method: 'get',
    onSuccess: this.readMarkers.bind(this)
  });

}

/** Read markers from xml document 
  @param data XML response */
PMap.prototype.readMarkers = function(data) {
  var xml = data.responseXML;
  var markers = xml.getElementsByTagName("marker");
  for (var i = 0; i < markers.length; i++) {
    this.readMarker(markers[i]);
  }

  this._updatingMarkers = false;
  /** trigger next update */
  if (this._continueUpdateMarkers == true) {
    this._continueUpdateMarkers = false;
    this.updateMarkers();
  }
}

/** Read marker from xml element
  @param e XML element of XML markers document */
PMap.prototype.readMarker = function(e){
  var id = parseInt(e.getAttribute("id"));

  // skip existion markers
  var marker = this.markers.get(id);
  if (undefined != marker) {
    return true;
  }
  
  var marker = this.createMarker(e);
  this.gmap.addOverlay(marker);
  return true;
}

/** Create marker from XML element 
  @param e XML element of XML marker */
PMap.prototype.createMarker = function(e) {
  var id = parseInt(e.getAttribute("id"));

  var markerIcon = new GIcon(this.baseIcon);
  markerIcon.image = e.getElementsByTagName("icon")[0].childNodes[0].nodeValue;
  var markerOptions = { icon: markerIcon };

  var point = new GLatLng(parseFloat(e.getAttribute("lat")), parseFloat(e.getAttribute("lng")));
  var marker = new GMarker(point, markerOptions);

  GEvent.addListener(marker, "click", function() {
    marker.openInfoWindowHtml(e.getElementsByTagName("description")[0].childNodes[0].nodeValue);
  });

  this.markers.set(id, marker);
  return marker;
}

PMap.prototype.setQueryUrl = function(url){
  this.url = url;
}

PMap.prototype.onMoveEnd = function() {
  this.updateInfo();
  this.updateMarkers();
}

PMap.prototype.onZoomEnd = function(oldLevel, newLevel) {
  this.updateMarkers();
}

PMap.prototype.updateInfo = function() {
  var e = document.getElementById("mapInfo");
  if (!e) {
    return false;
  }
  var text = "Center of map: ";
  text = text + this.gmap.getCenter().lat().toFixed(4);
  text = text + "," + this.gmap.getCenter().lng().toFixed(4);
  text = text + " ";
  e.firstChild.nodeValue = text;
}

/** PContextMenu bases on http://www.ajaxlines.com/ajax/stuff/article/context_menu_in_google_maps_api.php */
function PContextMenu(gMap) {
  this.initialize(gMap);
}

/** Add a link to the context menu
  @param text Link entry name
  @param fnc Function which will be executed on clicking the entry */
PContextMenu.prototype.addLink = function(text, fnc) {
  var that = this;

  var a = document.createElement("a");

  var aHref = document.createAttribute("href");
  aHref.nodeValue = "javascript:void(0);";
  a.setAttributeNode(aHref);

  GEvent.addDomListener(a, "click", fnc);

  // handling of context menu
  GEvent.addDomListener(a, "click", function() {
    that.hide();
    // unfocus link
    a.blur(); // most browsers
    if (a.hideFocus) { // ie
      a.hideFocus = false;
    }
    a.style.outline = 'none'; // mozilla
  });

  a.appendChild(document.createTextNode(text));

  var li = document.createElement("li");
  li.appendChild(a);
  this._ulContainer.appendChild(li);    
}

PContextMenu.prototype.show = function() {
  this.contextmenu.style.display = ""; 
}

PContextMenu.prototype.hide = function() {
  this.contextmenu.style.display = "none"; 
}

PContextMenu.prototype.getHeight = function() {
  return this._ulContainer.childNodes.length * 20;
}

PContextMenu.prototype.getWidth = function() {
  return 120;
}

//The object constructor
PContextMenu.prototype.initialize = function(gMap){
  var that = this;
  this._map = gMap;
 
  this.contextmenu = document.createElement("div");
  this.contextmenu.className = "mapContextMenu";
  this._ulContainer = document.createElement("ul");
  this._ulContainer.id = "contextMenuContainer";
  this.contextmenu.appendChild(this._ulContainer);   
  this.addLink("Zoom out", function() {
    that._map.zoomOut();
  });
  this.addLink("Zoom in", function() {
    that._map.zoomIn();
  });
  this.addLink("Zoom in here", function() {
    var point = that._map.fromContainerPixelToLatLng(that.clickedPixel);
    that._map.zoomIn(point,true);
  });
  this.addLink("Center here", function() {
    var point = that._map.fromContainerPixelToLatLng(that.clickedPixel);
    that._map.panTo(point);
  });
  this.hide();
  this._map.getContainer().appendChild(this.contextmenu);   

  //Event listeners that will interact with our context menu
  GEvent.addListener(gMap, "singlerightclick", function(pixel, tile, obj) {
    that.clickedPixel = pixel;
    var x = pixel.x;
    var y = pixel.y;
    //Prevents the menu to go out of the map margins, in this case the expected
    //menu size is 150x110
    if (x > that._map.getSize().width - that.getWidth()) { 
      x = that._map.getSize().width - that.getWidth();
    }
    if (y > that._map.getSize().height - that.getHeight()) { 
      y = that._map.getSize().height - that.getHeight();
    }
    var pos = new GControlPosition(G_ANCHOR_TOP_LEFT, new GSize(x,y)); 
    pos.apply(that.contextmenu);

    // Open context menu only on non-markers
    if (obj == null) {
      that.show();
    }
  });   
  GEvent.addListener(gMap, "move", function() {
    that.hide();
  });
  GEvent.addListener(gMap, "click", function(overlay, point) {
    that.hide();
  });   
}
