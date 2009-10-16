<?php
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
class MapHelper extends AppHelper
{
  var $helpers = array("Javascript");

  function loadScripts($mapKey) {
    $scripts = array(
      Router::url('/js/pmap.js'),
      'http://maps.google.com/maps?file=api&amp;v=2&amp;key='.htmlentities($mapKey));
    $out = '';
    foreach ($scripts as $script) {
      $out .= "<script src=\"$script\" type=\"text/javascript\"></script>\n";
    }
    return $this->output($out);
  }

  function container() {
    return $this->output('<div id="mapbox" style="display: none;">
    <div id="map"></div>
    <div id="mapInfo">
      <a href="#" onclick="toggleVisibility(\'mapbox\')">Close Map</a>
    </div>
    </div>
    ');
  }

  function script() {
    $out = "
var map = null;
var showMap = function(id, latitude, longitude) {
  toggleVisibility('mapbox');

  if (undefined == map) {
    var options = { url: '".Router::url('/explorer/points')."' };
    map = new PMap(latitude, longitude, options);
    map.setMediaMarker(id, latitude, longitude);
    map.updateInfo();
    map.updateMarkers();
  }
};";
    return $this->output($this->Javascript->codeBlock($out));
  }
}

?>
