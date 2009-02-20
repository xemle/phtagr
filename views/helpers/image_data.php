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

class ImageDataHelper extends AppHelper {
  var $helpers = array('time', 'ajax', 'html', 'form', 'query');

  function getimagesize($data, $size, $square=false) {
    if (!isset($data['Medium']['width']) ||
      !isset($data['Medium']['height']) ||
      !isset($data['Medium']['orientation'])) {
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
      $width=$data['Medium']['width'];
      $height=$data['Medium']['height'];
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
    $orientation = $data['Medium']['orientation'];
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
    if (!isset($data['Medium']['date']))
      return -1;

    $sec=$this->time->toUnix($data['Medium']['date']);
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
    elseif (($acl & ACL_READ_MASK) == ACL_READ_HIGH) $t.='h';
    elseif (($acl & ACL_READ_MASK) == ACL_READ_PREVIEW) $t.='v';

    if ($t=='') $t='-';
    return $t;
  }

  /** Returns an text repesentation of the acl */
  function _acl2text($data) {
    $output = $this->_acl2icon($data['Medium']['gacl']).',';
    $output .= $this->_acl2icon($data['Medium']['uacl']).',';
    $output .= $this->_acl2icon($data['Medium']['oacl']);
    return $output;
  }

  function _metaDate($data) {
    $base = $this->query->getQuery();

    $this->query->set('from', $this->toUnix(&$data, -3*60*60));
    $this->query->set('to', $this->toUnix(&$data, 3*60*60));
    $output = $this->html->link($data['Medium']['date'], $this->query->getUri());
    $output .= ' [';

    $this->query->setQuery($base);
    $this->query->set('to', $this->toUnix(&$data));
    $this->query->set('sort', 'date');
    $output .= $this->html->link('<', $this->query->getUri());

    $this->query->setQuery($base);
    $this->query->set('from', $this->toUnix(&$data, -12*60*60));
    $this->query->set('to', $this->toUnix(&$data, 12*60*60));
    $output .= $this->html->link('d', $this->query->getUri());

    $this->query->set('from', $this->toUnix(&$data, -3.5*24*60*60));
    $this->query->set('to', $this->toUnix(&$data, 3.5*24*60*60));
    $output .= $this->html->link('w', $this->query->getUri());

    $this->query->set('from', $this->toUnix(&$data, -15*24*60*60));
    $this->query->set('to', $this->toUnix(&$data, 15*24*60*60));
    $output .= $this->html->link('m', $this->query->getUri());

    $this->query->setQuery($base);
    $this->query->set('from', $this->toUnix(&$data));
    $this->query->set('sort', '-date');
    $output .= $this->html->link('>', $this->query->getUri());
    $output .= ']';

    $this->query->setQuery($base);
    return $output;
  }

  function _metaHabtm($data, $habtm) {
    if (!count($data[$habtm])) 
      return false;

    $base = $this->query->getQuery();
    $field = strtolower(Inflector::pluralize($habtm));
    $links = array();
    foreach ($data[$habtm] as $assoc) {
      $this->query->set($field, $assoc['name']);
      $links[] = $this->html->link($assoc['name'], $this->query->getUri());
    }
    $this->query->setQuery($base);
    return implode(', ', $links);
  }

  function _metaAccess($data) {
    $base = $this->query->getQuery();
    $output = '';
    if (isset($data['Group']['name'])) {
      $this->query->set('group', $data['Group']['id']);
      $output .= $this->html->link($data['Group']['name'], $this->query->getUri()).', ';
    }
    $output .= $this->_acl2text($data);
    $this->query->setQuery($base);

    return $output;
  }

  function metaTable($data, $withMap = false) {
    $cells= array();
    if (!$data) 
      return $cells;

    $imageId = $data['Medium']['id'];

    $this->query->initialize();
    $tmpQuery = $this->query->getQuery();

    $userId = $this->query->get('user');
    $this->query->clear();
    if ($userId)
      $this->query->set('user', $userId);
    $base = $this->query->getQuery();

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

    if ($data['Medium']['isOwner']) {
      $cells[] = array('Access:', $this->_metaAccess($data));
    }
    
    // Action list 
    $output = '';
    if ($data['Medium']['canWriteTag']) {
      $output = $this->form->checkbox('selected][', array('value' => $imageId, 'id' => 'select-'.$imageId, 'onclick' => "selectMedium($imageId);"));
    }

    if ($data['Medium']['canWriteTag'])
      $output .= ' '.$this->ajax->link(
        $this->html->image('icons/tag_blue_edit.png', array('alt' => 'Edit tags', 'title' => 'Edit tags')), 
        '/explorer/editmeta/'.$imageId, 
        array('update' => 'meta-'.$imageId), null, false);
    if ($data['Medium']['canReadOriginal'])
      $output .= ' '.$this->html->link(
        $this->html->image('icons/disk.png', array('alt' => 'Save image', 'title' => 'Save image')), 
        '/media/original/'.$imageId.'/'.$data['Medium']['name'], null, null, false);

    if ($withMap && isset($data['Medium']['latitude']) && isset($data['Medium']['longitude'])) {
      $output .= ' '.$this->html->link(
          $this->html->image('icons/map.png',
            array('alt' => 'Show location in a map', 'title' => 'Show location in a map')),
          '#',
          array('onclick' => sprintf('showMap(%d, %f,%f);return false;', $data['Medium']['id'], $data['Medium']['latitude'],$data['Medium']['longitude'])),
          null, false);
    }
    
    if ($data['Medium']['isOwner']) {
      $output .= ' '.$this->ajax->link(
        $this->html->image('icons/key.png', 
          array('alt' => 'Edit ACL', 'title' => 'Edit access rights')), 
        '/explorer/editacl/'.$imageId, 
        array('update' => 'meta-'.$imageId), null, false);
      if ($data['Medium']['isDirty'])
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

    $this->query->setQuery($tmpQuery);
    return $cells;
  }

  function _getCurrentLevel($data, $flag, $mask) {
    $data = am(array('Medium' => array('oacl' => 0, 'uacl' => 0, 'gacl' => 0)), $data);
    if (($data['Medium']['oacl'] & $mask) >= $flag)
      return ACL_LEVEL_OTHER;
    if (($data['Medium']['uacl'] & $mask) >= $flag)
      return ACL_LEVEL_USER;
    if (($data['Medium']['gacl'] & $mask) >= $flag)
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

  // 0=keep, 1=me only, 2=group, 3=user, 4=others
  */
  function acl2select($fieldName, $data, $flag=0, $mask=0, $options=null) {
    if (is_array($data))
      $level = $this->_getCurrentLevel(&$data, &$flag, &$mask);
    elseif (is_numeric($data))
      $level = $data;
    else
      $level = ACL_LEVEL_PRIVATE;

    // level check
    if ($level < ACL_LEVEL_KEEP|| $level > ACL_LEVEL_OTHER)
      $level = ACL_LEVEL_PRIVATE;

    //$this->log($data['Medium']);
    //$this->log("level=$level, flag=$flag, mask=$mask");
    $acl = array(
      ACL_LEVEL_KEEP => 'Keep',
      ACL_LEVEL_PRIVATE => 'Me only',
      ACL_LEVEL_GROUP => 'Group members',
      ACL_LEVEL_USER => 'Users',
      ACL_LEVEL_OTHER => 'Everyone');
    $options = am($options, array('type' => 'select', 'options' => $acl, 'selected' => $level));
    //$this->log($options);
    return $this->form->input($fieldName, $options);
  }  

}
