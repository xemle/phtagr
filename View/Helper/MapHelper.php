<?php
/**
 * PHP versions 5
 *
 * phTagr : Organize, Browse, and Share Your Photos.
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

  var $googleMapApiUrl = '//maps.google.com/maps/api/js?v=3.2&amp;sensor=false';

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
        <label for="mapSearch">'. __("Goto address:").'</label>
        <input type="text" id="mapSearch" size="32" onkeydown="if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) { map.geolocate(this.value); return false; } else { return true; }"/>
      </div>
    </div>
    </div>
    ');
  }

  function script() {
    $out = $this->Html->script(array($this->googleMapApiUrl, 'OpenLayers', 'maps'), array('inline' => false));
    $out .= $this->Html->css('OpenLayers/style');

    $url = Router::url('/explorer/points/' . $this->Search->serialize(), true);
    $url = preg_replace("/'/", "\\'", $url);
    $messages = array(
        'noAddressFound' => __("No address found"),
        'pictureLocations' => __("Picture Locations"),
        'currentLocation' => __("Location:")
      );
    foreach ($messages as $key => $message) {
      $i18nMessages[] = $key . ": '" . preg_replace("/'/", "\\'", $message) . "'";
    }

    $code = "
OpenLayers.ImgPath = '".Router::url('/img/OpenLayers/')."';
var mapOptions = {
    explorerPointsURL: '$url/',
    iconSize: 30,
    i18n: { " . join(', ', $i18nMessages) ." },
};";
    $out .= $this->Html->scriptBlock($code, array('inline' => false));
    return $this->output($out);
  }
}

?>
