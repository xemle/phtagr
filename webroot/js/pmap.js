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
 * @since         phTagr v 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
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
  this.resizeControl = null;
  this._updatingMarkers = false;
  this._continueUpdateMarkers = false;
  this.geocoder = false;

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
    this.gmap.addControl(new PResizeControl());
    //this.gmap.enableScrollWheelZoom();

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

PMap.prototype.showAddress = function(address) {
  var that = this;
  if (!this.geocoder) {
    this.geocoder = new GClientGeocoder();
  }
  this.geocoder.getLocations(
    address,
    function(response) {
      if (!response || response.Status.code != 200) {
        alert("\"" + address + "\" not found");
      } else {
        place = response.Placemark[0];
        point = new GLatLng(place.Point.coordinates[1],
                            place.Point.coordinates[0]);
        var accuracy = place.AddressDetails.Accuracy;
        var zoom = 1;
        if (accuracy <= 1) { zoom = 4; } // country
        else if (accuracy <= 3) { zoom = 6; } // sub-region
        else if (accuracy <= 4) { zoom = 12; } // town 
        else if (accuracy <= 5) { zoom = 14; } // postcode
        else { zoom = 16; } // intersection
        that.gmap.setCenter(point, zoom);
        that.updateMarkers();
      }
    }
  );
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

/** PResizeControl to resize google's map
  by xemle@phtagr.org
  based on http://www.wolfpil.de/map-in-a-box.html 
  by Wolfgang Pichler, wolfpil-at-gmail-com */
function PResizeControl() {};
PResizeControl.prototype = new GControl();
PResizeControl.RESIZE_BOTH = 0;
PResizeControl.RESIZE_WIDTH = 1;
PResizeControl.RESIZE_HEIGHT = 2;
PResizeControl.prototype.initialize = function(gMap) {
  var that = this;
  this._map = gMap;
  this.resizable = false;

  /** modes: 0 both, 1 only width, 2 only height */
  this.mode = PResizeControl.RESIZE_HEIGHT;

  this.minWidth = 150;
  this.minHeight = 150;
  this.maxWidth = 0;
  this.maxHeight = 0;

  this.diffX = 0;
  this.diffY = 0;

  var container = document.createElement("div");
  container.style.width = "20px";
  container.style.height = "20px";
  // embedded image does not work with IE < 8
  container.style.backgroundImage = "url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUBAMAAAB/pwA+AAAAAXNSR0IArs4c6QAAAA9QTFRFMBg0f39/0dDN7eri/v7+XsdLVAAAAAF0Uk5TAEDm2GYAAABNSURBVAjXRcpBDcAwDEPRKAymImghuCUw/qTWJI7nk/X0zXquZ+tH6E5df3TngPBA+ELY7UW2gWwDq02sNjHbwmwLoyVGS7ytbw62tA8zTA85AeAv2wAAAABJRU5ErkJggg%3D%3D)";

  gMap.getContainer().appendChild(container);

  GEvent.addDomListener(container, 'mousedown', function() { that.resizable = true; });
  GEvent.addDomListener(document, 'mouseup', function() { that.resizable = false; });
  GEvent.addDomListener(document, 'mousemove', function(e) { that.onmouseover(e); });

  /* Move the 'Terms of Use' 25px to the left to make sure that it's fully
   * readable */
  var terms = gMap.getContainer().childNodes[2];
  terms.style.marginRight = "25px";

  return container;
}

PResizeControl.prototype.getDefaultPosition = function() {
  return new GControlPosition(G_ANCHOR_BOTTOM_RIGHT,new GSize(0,0));
}

PResizeControl.prototype.onmouseover = function(e) {
  // Include possible scroll values
  var sx = window.scrollX || document.documentElement.scrollLeft || 0;
  var sy = window.scrollY || document.documentElement.scrollTop || 0;

  // IEs event definition
  if(!e) { 
    e = window.event;
  }

  mouseX = e.clientX + sx;
  mouseY = e.clientY + sy;

  /* Direction of mouse movement
  *  deltaX: -1 for left, 1 for right
  *  deltaY: -1 for up, 1 for down
  */
  var deltaX = mouseX - this.diffX;
  var deltaY = mouseY - this.diffY;
  // Store difference in object's variables
  this.diffX = mouseX;
  this.diffY = mouseY;

  // resize button is being held
  if (this.resizable) {
    this.changeMapSize(deltaX, deltaY);
  }

  return false;
}

// Resizes the map's width and height by the given increment
PResizeControl.prototype.changeMapSize = function(dx, dy) {
  var container = this._map.getContainer();
  var width = parseInt(container.style.width);
  var height = parseInt(container.style.height);

  width += dx;
  height += dy;

  if (this.minWidth) { width = Math.max(this.minWidth, width); }
  if (this.maxWidth) { width = Math.min(this.maxWidth, width); }
  if (this.minHeight) { height = Math.max(this.minHeight, height); }
  if (this.maxHeight) { height = Math.min(this.maxHeight, height); }

  if (this.mode != PResizeControl.RESIZE_HEIGHT) {
   container.style.width = width + "px";
  }
  if (this.mode != PResizeControl.RESIZE_WIDTH) {
    container.style.height = height + "px";
  }
  this._map.checkResize();
}
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
 * @since         phTagr v 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
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
  this.resizeControl = null;
  this._updatingMarkers = false;
  this._continueUpdateMarkers = false;
  this.geocoder = false;

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
    this.gmap.addControl(new PResizeControl());
    //this.gmap.enableScrollWheelZoom();

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

PMap.prototype.showAddress = function(address) {
  var that = this;
  if (!this.geocoder) {
    this.geocoder = new GClientGeocoder();
  }
  this.geocoder.getLocations(
    address,
    function(response) {
      if (!response || response.Status.code != 200) {
        alert("\"" + address + "\" not found");
      } else {
        place = response.Placemark[0];
        point = new GLatLng(place.Point.coordinates[1],
                            place.Point.coordinates[0]);
        var accuracy = place.AddressDetails.Accuracy;
        var zoom = 1;
        if (accuracy <= 1) { zoom = 4; } // country
        else if (accuracy <= 3) { zoom = 6; } // sub-region
        else if (accuracy <= 4) { zoom = 12; } // town 
        else if (accuracy <= 5) { zoom = 14; } // postcode
        else { zoom = 16; } // intersection
        that.gmap.setCenter(point, zoom);
        that.updateMarkers();
      }
    }
  );
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

/** PResizeControl to resize google's map
  by xemle@phtagr.org
  based on http://www.wolfpil.de/map-in-a-box.html 
  by Wolfgang Pichler, wolfpil-at-gmail-com */
function PResizeControl() {};
PResizeControl.prototype = new GControl();
PResizeControl.RESIZE_BOTH = 0;
PResizeControl.RESIZE_WIDTH = 1;
PResizeControl.RESIZE_HEIGHT = 2;
PResizeControl.prototype.initialize = function(gMap) {
  var that = this;
  this._map = gMap;
  this.resizable = false;

  /** modes: 0 both, 1 only width, 2 only height */
  this.mode = PResizeControl.RESIZE_HEIGHT;

  this.minWidth = 150;
  this.minHeight = 150;
  this.maxWidth = 0;
  this.maxHeight = 0;

  this.diffX = 0;
  this.diffY = 0;

  var container = document.createElement("div");
  container.style.width = "20px";
  container.style.height = "20px";
  // embedded image does not work with IE < 8
  container.style.backgroundImage = "url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUBAMAAAB/pwA+AAAAAXNSR0IArs4c6QAAAA9QTFRFMBg0f39/0dDN7eri/v7+XsdLVAAAAAF0Uk5TAEDm2GYAAABNSURBVAjXRcpBDcAwDEPRKAymImghuCUw/qTWJI7nk/X0zXquZ+tH6E5df3TngPBA+ELY7UW2gWwDq02sNjHbwmwLoyVGS7ytbw62tA8zTA85AeAv2wAAAABJRU5ErkJggg%3D%3D)";

  gMap.getContainer().appendChild(container);

  GEvent.addDomListener(container, 'mousedown', function() { that.resizable = true; });
  GEvent.addDomListener(document, 'mouseup', function() { that.resizable = false; });
  GEvent.addDomListener(document, 'mousemove', function(e) { that.onmouseover(e); });

  /* Move the 'Terms of Use' 25px to the left to make sure that it's fully
   * readable */
  var terms = gMap.getContainer().childNodes[2];
  terms.style.marginRight = "25px";

  return container;
}

PResizeControl.prototype.getDefaultPosition = function() {
  return new GControlPosition(G_ANCHOR_BOTTOM_RIGHT,new GSize(0,0));
}

PResizeControl.prototype.onmouseover = function(e) {
  // Include possible scroll values
  var sx = window.scrollX || document.documentElement.scrollLeft || 0;
  var sy = window.scrollY || document.documentElement.scrollTop || 0;

  // IEs event definition
  if(!e) { 
    e = window.event;
  }

  mouseX = e.clientX + sx;
  mouseY = e.clientY + sy;

  /* Direction of mouse movement
  *  deltaX: -1 for left, 1 for right
  *  deltaY: -1 for up, 1 for down
  */
  var deltaX = mouseX - this.diffX;
  var deltaY = mouseY - this.diffY;
  // Store difference in object's variables
  this.diffX = mouseX;
  this.diffY = mouseY;

  // resize button is being held
  if (this.resizable) {
    this.changeMapSize(deltaX, deltaY);
  }

  return false;
}

// Resizes the map's width and height by the given increment
PResizeControl.prototype.changeMapSize = function(dx, dy) {
  var container = this._map.getContainer();
  var width = parseInt(container.style.width);
  var height = parseInt(container.style.height);

  width += dx;
  height += dy;

  if (this.minWidth) { width = Math.max(this.minWidth, width); }
  if (this.maxWidth) { width = Math.min(this.maxWidth, width); }
  if (this.minHeight) { height = Math.max(this.minHeight, height); }
  if (this.maxHeight) { height = Math.min(this.maxHeight, height); }

  if (this.mode != PResizeControl.RESIZE_HEIGHT) {
   container.style.width = width + "px";
  }
  if (this.mode != PResizeControl.RESIZE_WIDTH) {
    container.style.height = height + "px";
  }
  this._map.checkResize();
}
