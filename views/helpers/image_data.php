<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2008 Sebastian Felis, sebastian@phtagr.org
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

class ImageDataHelper extends AppHelper {
  var $helpers = array('time', 'ajax', 'html', 'form', 'search');

  function getimagesize($data, $size, $square=false) {
    if (!isset($data['Image']['width']) ||
      !isset($data['Image']['height']) ||
      !isset($data['Image']['orientation'])) {
      $result = array();
      $result[0] = 0;
      $result[1] = 0;
      $result[3] = "";
      return $result;
    }
    if ($square) {
      $width=$size;
      $height=$size;
    } else {
      $width=$data['Image']['width'];
      $height=$data['Image']['height'];
      if ($width > $size && $width>=$height) {
        $height=intval($size*($height/$width));
        $width=$size;
      } elseif ($height > $size && $height > $width) {
        $width=intval($size*($width/$height));
        $height=$size;
      }
    }
    $result = array();

    // Rotate the image according to the orientation
    $orientation = $data['Image']['orientation'];
    if ($orientation >= 5 && $orientation <= 8) {
      $result[0] = $height;
      $result[1] = $width;
    } else {
      $result[0] = $width;
      $result[1] = $height;
    }

    $result[3] = "height=\"{$result[1]}\" width=\"{$result[0]}\"";

    return $result;
  }
  
  function toUnix($data, $offset=0) {
    if (!isset($data['Image']['date']))
      return -1;

    $sec=$this->time->toUnix($data['Image']['date']);
    return $sec+$offset;
  }

  /** Returns an single icon of a acl */
  function _acl2icon($acl)
  {
    $t='';
    // Write access
    if (($acl & ACL_WRITE_MASK) == ACL_WRITE_CAPTION) $t.='c';
    elseif (($acl & ACL_WRITE_MASK) == ACL_WRITE_META) $t.='m';
    elseif (($acl & ACL_WRITE_MASK) == ACL_WRITE_TAG) $t.='t';

    // Read access
    if (($acl & ACL_READ_MASK) == ACL_READ_ORIGINAL) $t.='o';
    elseif (($acl & ACL_READ_MASK) == ACL_READ_HIGHSOLUTION) $t.='h';
    elseif (($acl & ACL_READ_MASK) == ACL_READ_PREVIEW) $t.='v';

    if ($t=='') $t='-';
    return $t;
  }

  /** Returns an text repesentation of the acl */
  function _acl2text($data) {
    $output = $this->_acl2icon($data['Image']['gacl']).',';
    $output .= $this->_acl2icon($data['Image']['macl']).',';
    $output .= $this->_acl2icon($data['Image']['pacl']);
    return $output;
  }

  function _metaDate($data) {
    $base = $this->search->getSearch();

    $this->search->set('from', $this->toUnix(&$data, -3*60*60));
    $this->search->set('to', $this->toUnix(&$data, 3*60*60));
    $output = $this->html->link($data['Image']['date'], $this->search->getUri());
    $output .= ' [';

    $this->search->setSearch($base);
    $this->search->set('to', $this->toUnix(&$data));
    $this->search->set('sort', 'date');
    $output .= $this->html->link('<', $this->search->getUri());

    $this->search->setSearch($base);
    $this->search->set('from', $this->toUnix(&$data, -12*60*60));
    $this->search->set('to', $this->toUnix(&$data, 12*60*60));
    $output .= $this->html->link('d', $this->search->getUri());

    $this->search->set('from', $this->toUnix(&$data, -3.5*24*60*60));
    $this->search->set('to', $this->toUnix(&$data, 3.5*24*60*60));
    $output .= $this->html->link('w', $this->search->getUri());

    $this->search->set('from', $this->toUnix(&$data, -15*24*60*60));
    $this->search->set('to', $this->toUnix(&$data, 15*24*60*60));
    $output .= $this->html->link('m', $this->search->getUri());

    $this->search->setSearch($base);
    $this->search->set('from', $this->toUnix(&$data));
    $this->search->set('sort', '-date');
    $output .= $this->html->link('>', $this->search->getUri());
    $output .= ']';

    $this->search->setSearch($base);
    return $output;
  }

  function _metaHabtm($data, $habtm) {
    if (!count($data[$habtm])) 
      return false;

    $base = $this->search->getSearch();
    $field = strtolower(Inflector::pluralize($habtm));
    $links = array();
    foreach ($data[$habtm] as $assoc) {
      $this->search->set($field, $assoc['name']);
      $links[] = $this->html->link($assoc['name'], $this->search->getUri());
    }
    $this->search->setSearch($base);
    return implode(', ', $links);
  }

  function _metaAccess($data) {
    $base = $this->search->getSearch();
    $output = '';
    if (isset($data['Group']['name'])) {
      $this->search->clear();
      $this->search->set('group', $data['Group']['id']);
      $output .= $this->html->link($data['Group']['name'], $this->search->getUri()).', ';
    }
    $output .= $this->_acl2text($data);
    $this->search->setSearch($base);

    return $output;
  }

  function metaTable($data, $withMap = false) {
    $cells= array();
    if (!$data) 
      return $cells;

    $imageId = $data['Image']['id'];

    $this->search->initialize();
    $userId = $this->search->get('user');
    $this->search->clear();
    if ($userId)
      $this->search->set('user', $userId);
    $base = $this->search->getSearch();

    $cells[] = array("Date:", $this->_metaDate(&$data));

    if (count($data['Tag'])) {
      $cells[] = array('Tags:', $this->_metaHabtm(&$data, 'Tag'));
    }
    if (count($data['Category'])) {
      $cells[] = array('Categories:', $this->_metaHabtm(&$data, 'Category'));
    }
    if (count($data['Location'])) {
      $cells[] = array('Locations:', $this->_metaHabtm(&$data, 'Location'));
    }

    if ($data['Image']['isOwner']) {
      $cells[] = array('Access:', $this->_metaAccess($data));
    }
    
    // Action list 
    $output = '';
    if ($data['Image']['canWriteTag']) {
      $output = $this->form->checkbox('selected][', array('value' => $imageId, 'id' => 'select-'.$imageId, 'onclick' => "selectImage($imageId);"));
    }

    if ($data['Image']['canWriteTag'])
      $output .= ' '.$this->ajax->link(
        $this->html->image('icons/tag_blue_edit.png', array('alt' => 'Edit tags', 'title' => 'Edit tags')), 
        '/explorer/editmeta/'.$imageId, 
        array('update' => 'meta-'.$imageId), null, false);
    if ($data['Image']['canReadOriginal'])
      $output .= ' '.$this->html->link(
        $this->html->image('icons/disk.png', array('alt' => 'Save image', 'title' => 'Save image')), 
        '/files/original/'.$imageId, null, null, false);

    if ($withMap && isset($data['Image']['latitude']) && isset($data['Image']['longitude'])) {
      $output .= ' '.$this->html->link(
          $this->html->image('icons/map.png',
            array('alt' => 'Show location in a map', 'title' => 'Show location in a map')),
          '#',
          array('onclick' => sprintf('showMap(%f,%f);return false;', $data['Image']['latitude'],$data['Image']['longitude'])),
          null, false);
    }
    
    if ($data['Image']['isOwner']) {
      $output .= ' '.$this->ajax->link(
        $this->html->image('icons/key.png', 
          array('alt' => 'Edit ACL', 'title' => 'Edit access rights')), 
        '/explorer/editacl/'.$imageId, 
        array('update' => 'meta-'.$imageId), null, false);
      if ($data['Image']['isDirty'])
        $output .= ' '.$this->ajax->link(
          $this->html->image('icons/database_refresh.png', 
            array('alt' => 'Synchronize db with image', 'title' => 'Synchronize meta data with the image')), 
          '/explorer/sync/'.$imageId, 
          array('update' => 'meta-'.$imageId), null, false);
    }

    if ($output) {
      $output = "<div class=\"actionlist\">$output</div>\n";
      $cells[] = array("Actions:", $output);
    }
    return $cells;
  }

  function _getCurrentLevel($data, $flag, $mask) {
    $data = am(array('Image' => array('pacl' => 0, 'macl' => 0, 'gacl' => 0)), $data);
    if (($data['Image']['pacl'] & $mask) >= $flag)
      return ACL_LEVEL_PUBLIC;
    if (($data['Image']['macl'] & $mask) >= $flag)
      return ACL_LEVEL_MEMBER;
    if (($data['Image']['gacl'] & $mask) >= $flag)
      return ACL_LEVEL_GROUP;
    return ACL_LEVEL_PRIVATE;
  }

  /**
    Creates a ACL select element
    @param fieldName fieldname of the select
    @param data If data is numeric, the value is handled as acl level. If data
    is an array, the data is assumed to be a image model data array. The level
    is extracted bz the flag and the mask.
    @param flag Bit flag of the acl (used for image data array)
    @param mask Bit mask of the acl (used for image data array)

  // 0=keep, 1=me only, 2=group, 3=member, 4=public
  */
  function acl2select($fieldName, $data, $flag=0, $mask=0, $options=null) {
    if (is_array($data))
      $level = $this->_getCurrentLevel(&$data, &$flag, &$mask);
    elseif (is_numeric($data))
      $level = $data;
    else
      $level = ACL_LEVEL_PRIVATE;

    // level check
    if ($level < ACL_LEVEL_KEEP|| $level > ACL_LEVEL_PUBLIC)
      $level = ACL_LEVEL_PRIVATE;

    //$this->log($data['Image']);
    //$this->log("level=$level, flag=$flag, mask=$mask");
    $acl = array(
      ACL_LEVEL_KEEP => 'Keep',
      ACL_LEVEL_PRIVATE => 'Me only',
      ACL_LEVEL_GROUP => 'Group members',
      ACL_LEVEL_MEMBER => 'All members',
      ACL_LEVEL_PUBLIC => 'Everyone');
    $options = am($options, array('type' => 'select', 'options' => $acl, 'selected' => $level));
    $this->log($options);
    return $this->form->input($fieldName, $options);
  }  

}
