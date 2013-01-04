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

class ImageDataHelper extends AppHelper {

  var $helpers = array('Session', 'Html', 'Form', 'Search', 'Option', 'Breadcrumb');

  function beforeRender($viewFile) {
    $this->Search->initialize();
    $this->Option->beforeRender($viewFile);
  }

  /**
   * Returns the rotated size of the media
   *
   * @return Array of rotated width and height
   */
  function rotate($width, $height, $orientation) {
    // Rotate the image according to the orientation
    $orientation = $orientation ? $orientation : 1;
    if ($orientation >= 5 && $orientation <= 8) {
      return array($height, $width);
    } else {
      return array($width, $height);
    }
  }

  /**
   * Get resized size with maximum size
   *
   * @return array of width and height
   */
  function resize($width, $height, $size) {
    if ($width >= $height && $width > $size) {
      $height = floor($height * $size / $width);
      $width = $size;
    } else if ($height > $width && $height > $size) {
      $width = floor($width * $size / $height);
      $height = $size;
    }
    return array($width, $height);
  }

  /**
   * Get squared width and height with maximum size
   *
   * @return array of width and height
   */
  function square($width, $height, $size) {
    if (min($width, $height) < $size) {
      list($width, $height) = $this->resize($width, $height, $size);
    } else {
      $width = $size;
      $height = $size;
    }
    return array($width, $height);
  }

  /**
   * Get resized size with maximum width
   *
   * @return array of width and height
   */
  function resizeWidth($width, $height, $size) {
    if ($width > $size) {
      $height = floor($height * $size / $width);
      $width = $size;
    }
    return array($width, $height);
  }

  /**
   * Returns the image size of the given media. This function considers the
   * orientaion of the media.
   *
   * @param media Media model data
   * @param options New size given as single value. Numeric value of maximum
   * width or height. Possible text values: 'mini', 'thumb', 'preview',
   * 'original'.  If false than it returns the media size. If options is an
   * array following options are supported
   *   - height - Maximum height
   *   - width - Maximum width
   *   - square - Square the sizes
   *   - size - size
   * @return array of sizes. array(height, width, false, html size)
   */
  function getimagesize($media, $options = false) {
    if (!isset($media['Media']['width']) ||
      !isset($media['Media']['height'])) {
      return array(0 => 0, 1 => 0, 2 => false, 3 => '');
    }
    if (!is_array($options)) {
      $options = array('size' => $options);
    }
    $options = am(array('size' => false, 'width' => false, 'height' => false, 'square' => false), $options);
    $size = $options['size'];

    if ($size && !is_numeric($size) && !in_array($size, array('mini', 'thumb', 'preview', 'original'))) {
      Logger::err("Wrong media size $size");
      return;
    }

    $map = array(
      'mini' => OUTPUT_SIZE_MINI,
      'thumb' => OUTPUT_SIZE_THUMB,
      'preview' => OUTPUT_SIZE_PREVIEW,
      'original' => false
      );
    $resize = false;
    $square = false;
    if ($size) {
      if (isset($map[$size])) {
        $resize = $map[$size];
      } else if (is_numeric($size)) {
        $resize = $size;
      }
      if ($size == 'mini') {
        $square = true;
      }
    }

    list($width, $height) = $this->rotate($media['Media']['width'], $media['Media']['height'], $media['Media']['orientation']);

    if ($options['width'] && $width > $options['width']) {
      list($width, $height) = $this->resizeWidth($width, $height, $options['width']);
    } elseif ($options['height'] && $height > $options['height']) {
      list($height, $width) = $this->resizeWidth($height, $width, $options['height']);
    } elseif ($resize) {
      if ($square) {
        list($width, $height) = $this->square($width, $height, $resize);
      } else {
        list($width, $height) = $this->resize($width, $height, $resize);
      }
    }
    $result = array(0 => $width, 1 => $height, 2 => false, 3 => "width=\"$width\" height=\"$height\"");

    return $result;
  }

  /**
   * Creates an media source as HTML img tag
   *
   * @param media Media model data
   * @param options Media size or option array. Options are
   *   type - Type of the media. String values: 'mini', 'thumb', 'preview', 'original'
   *   size - Size of the media - optional.
   *   param - Extra url parameter
   * @return HTML link with media image
   */
  function mediaImage($media, $options) {
    if (!isset($media['Media']['id'])) {
      Logger::err("Media id is not set");
      return false;
    }
    if (!is_array($options)) {
      $options = array('type' => $options);
    }
    $mediaOptions = am(array('type' => 'thumb', 'size' => false, 'width' => false, 'height' => false), $options);

    if (!in_array($mediaOptions['type'], array('mini', 'thumb', 'preview', 'original'))) {
      Logger::err("Wrong media type {$options['type']}");
      return false;
    }
    if (!$mediaOptions['size'] && !$mediaOptions['width'] && !$mediaOptions['height']) {
      $mediaOptions['size'] = $mediaOptions['type'];
    }

    $imgSrc = Router::url("/media/{$options['type']}/{$media['Media']['id']}");
    $size = $this->getimagesize($media, $mediaOptions);
    if (!$size) {
      Logger::err("Could not fetch media size of type {$mediaOptions['type']} or size {$mediaOptions['size']}");
      return false;
    }
    $alt = $media['Media']['name'];

    $attrs = array('src' => $imgSrc, 'width' => $size[0], 'height' => $size[1], 'alt' => $alt, 'title' => $alt, 'class' => 'media-link');
    foreach (array('class', 'id') as $name) {
      if (isset($options[$name])) {
        $attrs[$name] = $options[$name];
      }
    }
    $out = $this->Html->tag('img', false, $attrs);
    return $this->output($out);
  }

  /**
   * Creates an media image with the link
   *
   * @param media Media model data
   * @param options Media size or option array. Options are
   *   type - Type of the media. String values: 'mini', 'thumb', 'preview', 'original'
   *   size - Size of the media - optional.
   *   params - Extra url parameter
   *   div - Wrapped div arround the link with the class given in the div options
   *   before - (Optional) Text before image link
   *   after - (Optional) Text after image link
   * @return HTML link with media image
   */
  function mediaLink($media, $options = array()) {
    if (!isset($media['Media']['id'])) {
      Logger::err("Media id is not set");
      return false;
    }

    $img = $this->mediaImage($media, $options);

    $options = am(array('div' => false, 'params' => false, 'before' => '', 'after' => ''), $options);

    $link = "/images/view/{$media['Media']['id']}";
    if ($options['params']) {
      $link .= $options['params'];
    }

    $out = $this->Html->link($img, $link, array('escape' => false));
    $out = $options['before'] . $out . $options['after'];

    if ($options['div']) {
      $out = '<div class="'.$options['div'].'">'.$out.'</div>';
    }
    return $out;
  }

  /**
   * Returns a link of the media date with different options
   *
   * @param media Media model data
   * @param option
   *   - from: All media after given media
   *   - to: All media before given media
   *   - offset: given in hours (3h), days (3.5d) or months (6m)
   *   - interval: Set interval where
   * @return Link of the date search
   */
  function getDateLink(&$media, $option = false) {
    $date = $media['Media']['date'];
    $extra = array('show' => $this->Search->getShow());
    $crumbs = array();
    $from = $to = $sort = $offset = false;
    if ($this->Search->getUser()) {
      $crumbs[] = 'user:' . $this->Search->getUser();
    }
    if ($option == 'from') {
      $from = $date;
      $sort = '-date';
    } elseif ($option == 'to') {
      $to = $date;
    } elseif ($option == 'addTo' && $this->Search->getFrom()) {
      $from = $this->Search->getFrom();
      $to = $date;
    } elseif ($option == 'addFrom' && $this->Search->getTo()) {
      $from = $date;
      $to = $this->Search->getTo();
    } elseif (preg_match('/^(\d+(.\d+)?)([hdm])$/', $option, $matches)) {
      $offset = $matches[1].$matches[2];
      switch ($matches[3]) {
        case 'h':
          $offset *= 60*60;
          break;
        case 'd':
          $offset *= 24*60*60;
          break;
        case 'm':
          $offset *= 30*24*60*60;
          break;
        default:
          Logger::err("Unknown date offset {$matches[3]}");
      }
    } else {
      $offset = (integer)$option;
    }

    if ($offset) {
      $from = date('Y-m-d H:i:s', strtotime($date) - $offset);
      $to = date('Y-m-d H:i:s', strtotime($date) + $offset);
    }

    if ($from) {
      $crumbs[] = 'from:' . $from;
    }
    if ($to) {
      $crumbs[] = 'to:' . $to;
    }
    if ($sort) {
      $crumbs[] = 'sort:' . $sort;
    }
    return $this->Breadcrumb->crumbUrl($crumbs);
    //return $this->Search->getUri(array('from' => $from, 'to' => $to), $extra);
  }

  /**
   * Returns an single icon of a acl
   */
  function _acl2icon($acl, $titlePrefix = '') {
    $t='';
    // Write access
    if (($acl & ACL_WRITE_MASK) == ACL_WRITE_META) {
      $t .= $this->Html->image('icons/tag.png', array('alt' => 'm', 'title' => __('%s can edit the meta data',  $titlePrefix)));
    } elseif (($acl & ACL_WRITE_MASK) == ACL_WRITE_TAG) {
      $t .= $this->Html->image('icons/tag_blue.png', array('alt' => 't', 'title' => __('%s can edit the tags',  $titlePrefix)));
    }

    // Read access
    if (($acl & ACL_READ_MASK) == ACL_READ_ORIGINAL) {
      $t .= $this->Html->image('icons/disk.png', array('alt' => 'o', 'title' => __('%s can download this media',  $titlePrefix)));
    } elseif (($acl & ACL_READ_MASK) == ACL_READ_PREVIEW) {
      $t .= $this->Html->image('icons/picture.png', array('alt' => 'v', 'title' => __('%s an view this media',  $titlePrefix)));
    }
    if ($t == '') {
      $t='-';
    }
    return $t;
  }

  /**
   * Returns an text repesentation of the acl
   */
  function _acl2text($data) {
    $output = $this->_acl2icon($data['Media']['gacl'], __('Group members')).' ';

    $output .= $this->Html->tag('span', __('users:', true), array('title' => __('Access for users')));
    $output .= $this->_acl2icon($data['Media']['uacl'], __('Users')).' ';

    $output .= $this->Html->tag('span', __('public:', true), array('title' => __('Access for the public')));
    $output .= $this->_acl2icon($data['Media']['oacl'], __('The public'));
    return $output;
  }

  function _metaDate($data) {
    $id = "date-".$data['Media']['id'];
    $output = '<span onmouseover="toggleVisibility(\''.$id.'\', \'inline\');"';
    $output .= ' onmouseout="toggleVisibility(\''.$id.'\', \'inline\');">';

    $output .= $this->Html->link($data['Media']['date'], $this->getDateLink($data, '3h'));
    $output .= ' ';

    $output .= '<div style="display: none;" class="actionlist" id="'.$id.'">';
    $icon = $this->Html->image('icons/date_previous.png', array('alt' => '<', 'title' => __("View media of previous dates")));
    $output .= $this->Html->link($icon, $this->getDateLink($data, 'to'), array('escape' => false));

    if ($this->Search->getFrom() && !$this->Search->getTo()) {
      $icon = $this->Html->image('icons/date_interval.png', array('alt' => '<>', 'title' => __("View media of interval")));
      $output .= $this->Html->link($icon, $this->getDateLink($data, 'addTo'), array('escape' => false));
    }

    if ($this->Search->getFrom() && $this->Search->getTo()) {
      $icon = $this->Html->image('icons/date_interval_add_prev.png', array('alt' => '<>', 'title' => __("Set new end date of interval")));
      $output .= $this->Html->link($icon, $this->getDateLink($data, 'addTo'), array('escape' => false));
    }

    $icon = $this->Html->image('icons/calendar_view_day.png', array('alt' => 'd', 'title' => __("View media of this day")));
    $output .= $this->Html->link($icon, $this->getDateLink($data, '12h'), array('escape' => false));

    $icon = $this->Html->image('icons/calendar_view_week.png', array('alt' => 'w', 'title' => __("View media of this week")));
    $output .= $this->Html->link($icon, $this->getDateLink($data, '3.5d'), array('escape' => false));

    $icon = $this->Html->image('icons/calendar_view_month.png', array('alt' => 'm', 'title' => __("View media of this month")));
    $output .= $this->Html->link($icon, $this->getDateLink($data, '15d'), array('escape' => false));

    if ($this->Search->getTo() && !$this->Search->getFrom()) {
      $icon = $this->Html->image('icons/date_interval.png', array('alt' => '<>', 'title' => __("View media of interval")));
      $output .= $this->Html->link($icon, $this->getDateLink($data, 'addFrom'), array('escape' => false));
    }

    if ($this->Search->getTo() && $this->Search->getFrom()) {
      $icon = $this->Html->image('icons/date_interval_add_next.png', array('alt' => '<>', 'title' => __("Set new start date for interval")));
      $output .= $this->Html->link($icon, $this->getDateLink($data, 'addFrom'), array('escape' => false));
    }

    $icon = $this->Html->image('icons/date_next.png', array('alt' => '>', 'title' => __("View media of next dates")));
    $output .= $this->Html->link($icon, $this->getDateLink($data, 'from'), array('escape' => false));
    $output .= '</div></span>';

    return $output;
  }

  /**
   * Returns media fields as field name to field value array
   *
   * @param array $media Media model data
   * @return array nameToValue array
   */
  function getMediaFields(&$media) {
    $fields = array('keyword' => array(), 'category' => array(), 'location' => array());
    $locations = array('sublocation', 'city', 'state', 'country');
    if (!isset($media['Field'])) {
      return $fields;
    }
    foreach ($media['Field'] as $field) {
      if (!isset($fields[$field['name']])) {
        $fields[$field['name']] = array();
      }
      $fields[$field['name']][] = $field['data'];
      if (in_array($field['name'], $locations)) {
        $fields['location'][] = $field['data'];
      }
    }
    return $fields;
  }

  function _metaHabtm($data, $name, $values) {
    if (!count($values)) {
      return false;
    }

    $base = "/explorer";
    if ($this->action == 'user') {
      $base .= "/user/".$this->params['pass'][0];
    }
    $base .= "/".$name;

    $links = array();
    foreach ($values as $value) {
      $links[] = $this->Html->link($value, "$base/$value");
    }
    return implode(', ', $links);
  }

  function _metaAccessFull($data) {
    return $this->Html->tag('div', $this->_acl2text($data), array('class' => 'actionList'));
  }

  function _metaAccessGroup($data) {
    $output = '';
    if (count($data['Group'])) {
      $groupNames = Set::extract('/Group/name', $data);
      sort($groupNames);
      foreach ($groupNames as $name) {
        $output .= $this->Html->link($name, $this->Search->getUri(array(), array('groups' => $name)), array('title' => __("This media belongs to the group '%s'", $name)));
        $output .= ' ';
        $output .= '(' . $this->Html->link(__('View'), "/groups/view/{$name}") . ') ';
      }
    } else {
      $output .= __('No group assigned');
    }
    return $this->Html->tag('div', $output, array('class' => 'actionList'));
  }

  function geoLocation($data) {
    if (!isset($data['Media']['longitude']) || !isset($data['Media']['latitude'])) {
      return $false;
    }
    $long = $data['Media']['longitude'];
    $lat = $data['Media']['latitude'];
    $longSuffix = 'E';
    $latSuffix = 'N';
    if ($long < 0) {
      $longSuffix = 'W';
      $long *= -1;
    }
    if ($lat < 0) {
      $latSuffix = 'S';
      $lat *= -1;
    }
    return sprintf("%.2f%s/%.2f%s", $lat, $latSuffix, $long, $longSuffix);
  }

  /**
   * Creates a link list of names with urlBase and name
   *
   * @param urlBase Base URL which is combinded with each name item
   * @param names Names names of links
   */
  function linkList($urlBase, $names) {
    $links = array();
    foreach ($names as $name) {
      $links[] = $this->Html->link($name, $urlBase . '/' . $name);
    }
    return $links;
  }

  /**
   * Returns link for global search, user search, include and exclude
   */
  function getExtendSearchUrls($crumbs, $username, $name, $value) {
    $urls = array();
    $baseUrl = '/explorer';
    $urls['global'] = "$baseUrl/$name/$value";
    if ($username) {
      $urls['user'] = "$baseUrl/user/$username/$name/$value";
    }
    $addCrumb = $this->Breadcrumb->replace($crumbs, array($name, '-' . $value), $value);
    $delCrumb = $this->Breadcrumb->replace($crumbs, array($name, $value), '-' . $value);
    $urls['add'] = $this->Breadcrumb->crumbUrl($addCrumb);
    $urls['del'] = $this->Breadcrumb->crumbUrl($delCrumb);
    return $urls;
  }

  function getAllExtendSearchUrls($crumbs, $username, $name, $values) {
    $urls = array();
    foreach ($values as $value) {
      $urls[$value] = $this->getExtendSearchUrls($crumbs, $username, $name, $value);
    }
    return $urls;
  }

  function getExtendSearchLinks($urls, $value, $withExclude = true) {
    $output = '';
    $icons = array();
    if (isset($urls['user'])) {
      $output = $this->Html->link($value, $urls['user']);
      $icons[] = $this->Html->link($this->getIcon('world', false, __("Search global for %s", $value)), $urls['global'], array('escape' => false));
    } else {
      $output = $this->Html->link($value, $urls['global']);
    }
    $icons[] = $this->Html->link($this->getIcon('add', false, __("Include %s into search", $value)), $urls['add'], array('escape' => false));
    if ($withExclude) {
      $icons[] = $this->Html->link($this->getIcon('delete', false, __("Exclude %s from search", $value)), $urls['del'], array('escape' => false));
    }
    return "<span class=\"tooltip-anchor\">" . $output . '<span class="tooltip-actions"><span class="sub">' . implode($icons) . '</span></span></span> ';
  }

  function getIcon($name, $alt = false, $title = false) {
    if (!$alt) {
      $alt = $name;
    }
    if (!$title) {
      $title = $alt;
    }
    return $this->Html->image("icons/$name.png", array('alt' => $alt, 'title' => $title));
  }

  function metaTable($data, $withMap = false) {
    $cells= array();
    if (!$data)
      return $cells;

    $mediaId = $data['Media']['id'];

    //Logger::debug($this->Search->_data);
    //Logger::debug("HUHU");
    $userId = $this->Search->getUser();
    if ($userId) {
      $this->Search->setUser($userId);
    }
    $cells[] = array(__("Date"), $this->_metaDate($data));

    $fields = $this->getMediaFields($data);
    if (count($fields['keyword'])) {
      $cells[] = array(__('Tags'), $this->_metaHabtm($data, 'tag', $fields['keyword']));
    }
    if (count($fields['category'])) {
      $cells[] = array(__('Categories'), $this->_metaHabtm($data, 'category', $fields['category']));
    }

    $locations = array();
    if (count($fields['location'])) {
      $locations[] = $this->_metaHabtm($data, 'location', $fields['location']);
    }
    if (isset($data['Media']['longitude']) && isset($data['Media']['latitude'])) {
      $locations[] = $this->geoLocation($data);
    }
    if (count($locations)) {
      $cells[] = array(__('Locations'), implode(', ', $locations));
    }

    if ($data['Media']['isOwner'] || $data['Media']['canWriteAcl']) {
      $cells[] = array(__('Groups'), $this->_metaAccessGroup($data));
      $cells[] = array(__('Access'), $this->_metaAccessFull($data));
    }

    // Action list
    $output = '';
    if ($data['Media']['canWriteTag']) {
      $output = $this->Form->checkbox('selected][', array('value' => $mediaId, 'id' => 'select-'.$mediaId, 'onclick' => "selectMedia($mediaId);"));
    }

    return $cells;
  }

  function _getCurrentLevel($data, $flag, $mask) {
    $data = am(array('Media' => array('oacl' => 0, 'uacl' => 0, 'gacl' => 0)), $data);
    if (($data['Media']['oacl'] & $mask) >= $flag) {
      return ACL_LEVEL_OTHER;
    }
    if (($data['Media']['uacl'] & $mask) >= $flag) {
      return ACL_LEVEL_USER;
    }
    if (($data['Media']['gacl'] & $mask) >= $flag) {
      return ACL_LEVEL_GROUP;
    }
    return ACL_LEVEL_PRIVATE;
  }

  /**
   * Creates a ACL select element
   *
   * @param fieldName fieldname of the select
   * @param data If data is numeric, the value is handled as acl level. If data
   * is an array, the data is assumed to be a image model data array. The level
   * is extracted bz the flag and the mask.
   * @param flag Bit flag of the acl (used for image data array)
   * @param mask Bit mask of the acl (used for image data array)
   *
   * // 0=keep, 1=me only, 2=group, 3=user, 4=others
   */
  function acl2select($fieldName, $data, $flag=0, $mask=0, $options=null) {
    if (is_array($data)) {
      $level = $this->_getCurrentLevel($data, $flag, $mask);
    } elseif (is_numeric($data)) {
      $level = $data;
    } else {
      $level = ACL_LEVEL_PRIVATE;
    }

    // level check
    if ($level < ACL_LEVEL_KEEP|| $level > ACL_LEVEL_OTHER) {
      $level = ACL_LEVEL_PRIVATE;
    }

    //$this->log($data['Media']);
    //$this->log("level=$level, flag=$flag, mask=$mask");
    $acl = array(
      ACL_LEVEL_KEEP => __('Keep'),
      ACL_LEVEL_PRIVATE => __('Me only'),
      ACL_LEVEL_GROUP => __('Group members'),
      ACL_LEVEL_USER => __('Users'),
      ACL_LEVEL_OTHER => __('Everyone'));
    $options = am($options, array('type' => 'select', 'options' => $acl, 'selected' => $level));
    //$this->log($options);
    return $this->Form->input($fieldName, $options);
  }

  /**
   * Returns the visibility icon for the own media
   *
   * @params media Media model data
   * @return Html output for the icon or false
   */
  function getVisibilityIcon(&$media) {
    $icon = false;
    if (isset($media['Media']['isOwner']) && $media['Media']['isOwner']) {
      switch ($media['Media']['visibility']) {
        case ACL_LEVEL_OTHER:
          $icon = $this->Html->image('icons/world.png', array('title' => __('This media is public visible')));
          break;
        case ACL_LEVEL_USER:
          $icon = $this->Html->image('icons/group.png', array('title' => __('This media is visible for users')));
          break;
        case ACL_LEVEL_GROUP:
          $icon = $this->Html->image('icons/user.png', array('title' => __('This media is visible for group members')));
          break;
        default:
          $icon = $this->Html->image('icons/stop.png', array('title' => __('This media is private')));
          break;
      }
    }
    return $icon;
  }

  function niceShutter($value) {
    if ($value >= 1) {
      return sprintf("%.1fs", $value);
    } else {
      $invert = round(1 / $value);
      return sprintf("1/%ds", $invert);
    }
  }

  function getPathLink($file) {
    if (isset($file['File'])) {
      $file = $file['File'];
    }
    $path = $file['path'];

    $fsRoots = $this->Option->get('path.fsroot', array());
    $fsRoots[] = USER_DIR.$file['user_id'].DS.'files'.DS;
    rsort($fsRoots);

    foreach ($fsRoots as $root) {
      if (strpos($path, $root) === 0) {
        $dirs = explode(DS, trim($root, DS));
        $postfix = substr($path, strlen($root));
        $rootDir = count($fsRoots) > 1 ? $dirs[count($dirs)-1] . "/" : "";
        return '/browser/index/'.$rootDir.$postfix;
      }
    }
    return false;
  }

  /**
   * Returns the first file of a media which has the dependend flag
   *
   * @param media Media model data
   * @return File model data or null
   */
  function _getFirstDependendFile($media) {
    if (!isset($media['File'])) {
      return null;
    }
    foreach ($media['File'] as $file) {
      if (isset($file['flag']) && ($file['flag'] & FILE_FLAG_DEPENDENT) > 0) {
        return $file;
      }
    }
    return null;
  }

  /**
   * Returns the upload folder of an internal file
   *
   * @param file
   * @return Relative upload folder or false on error
   */
  function _getUploadFolder($file) {
    if (!isset($file['user_id']) ||
      !isset($file['flag']) ||
      !isset($file['path'])) {
      Logger::err("Invalide input");
      Logger::trace($file);
      return false;
    }
    if ($file['flag'] & FILE_FLAG_EXTERNAL > 0) {
      Logger::trace("External files are not supported");
      return false;
    }
    $userRoot = USER_DIR . $file['user_id'] . DS . 'files' . DS;
    if (strpos($file['path'], $userRoot) !== 0) {
      Logger::trace("Invalid upload path {$file['path']}");
      return false;
    }
    $folder = substr($file['path'], strlen($userRoot));
    if (DS == '\\') {
      $folder = implode('/', explode(DS, $folder));
    }
    return trim($folder, '/');
  }

  /**
   * Returns the folder link of the media
   *
   * @param media Media model data
   * @return Link string for user's folder of the media for the explorer
   */
  function getFolderLinks($media) {
    $file = $this->_getFirstDependendFile($media);
    $folder = $this->_getUploadFolder($file);
    if (!$folder) {
      return false;
    }
    $paths = explode('/', $folder);
    $links = array();
    $base = '/explorer/user/'.$media['User']['username'].'/folder';
    $links[] = $this->Html->link('root', $base);
    foreach ($paths as $path) {
      $base .= '/' . $path;
      $links[] = $this->Html->link($path, $base);
    }
    return $links;
  }
}
?>
