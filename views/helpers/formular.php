<?php
/*
 * phtagr.
 * 
 * social photo gallery for your community.
 * 
 * Copyright (C) 2006-2010 Sebastian Felis, sebastian@phtagr.org
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
class FormularHelper extends AppHelper
{
  var $helpers = array('form');

  function _getFieldName(&$params)
  {
    if (is_array($params))
      return $params['name'];
    else
    {
      $fieldName = $params;
      $params = null;
      return $fieldName;
    }
  }

  /** Returns the value from data by name
    @param name Name of the field. (e.g. 'User.username')
    @param data Model data
    @return returns the model data if set. Otherwise null */
  function _getValue($fieldName, $data)
  {
    // get fieldName from data
    $keys=explode('.', $fieldName);
    $value=$data[$keys[0]];
    array_shift($keys);
    while (count($keys)>0 && !empty($value))
    {
      $value=$value[$keys[0]];
      array_shift($keys);
    }
    return $value;
  }

  /** Returns the label from the name 
    @param fieldName Name of the field
    @param params Option array (optional)
    @return If the param contains a label it returns this entry. Otherwise it
returns the last key of the name (e.g. 'username' from 'User.username') */
  function _getLabel($fieldName, $params = null)
  {
    if (!empty($params['label']))
      return $params['label'];

    $keys = explode('.', $fieldName);
    $label = $keys[count($keys)-1];
    $label = strtoupper(substr($label, 0, 1)).substr($label, 1);
    return $label;
  }

  function _getInputItem($type, $fieldName, $value, $params)
  {
    if (is_numeric($type))
      $type = 'text';

    switch ($type)
    {
      case 'hidden':
        $o = $this->form->hidden($fieldName, array('value' => $value));
        break;
      case 'password':
        $o = $this->form->password($fieldName, array('value' => $value));
        break;
      case 'select':
        $params = am(array('attributes' => null, 'showEmpty' => null), $params);
        $o = $this->form->select($fieldName, $params['options'], $value, $params['attributes'], $params['showEmpty']);
        break;
      case 'text':
      default:
        $o = $this->form->text($fieldName, array('value' => $value));
    }
    return $o;
  }

  /** 
    @param frm Formular array
    @param data Data to the model */
  function formular($frm, $data, $options = null)
  {
    $o = "<fieldset>\n";
    if (!empty($options['legend']))
      $o .= "<legend>".$options['legend']."</legend>\n";

    // print hidden fields first
    foreach($frm as $type => $params)
    {
      if (is_numeric($type) || $type != 'hidden')
        continue;

      $fieldName = $this->_getFieldName(&$params);
      $value = $this->_getValue($fieldName, $data);

      $o .= $this->_getInputItem($type, $fieldName, $value, $params);
    }

    // print non hidden fields in ol list
    foreach ($frm as $type => $params)
    {
      if (!is_numeric($type) && $type == 'hidden')
        continue;

      $fieldName = $this->_getFieldName(&$params);
      $value = $this->_getValue($fieldName, $data);
      $label = $this->_getLabel($fieldName, $params);

      $o .= "<div class=\"input\"><label>$label</label>";
      $o .= $this->_getInputItem($type, $fieldName, $value, $params);
      $o .= "</div>\n";  
    }
    $o .= "</fieldset>\n";

    $o .= "<div class=\"submit\">";
    $o .= $this->form->submit(!empty($options['submit'])?$options['submit']:'Submit');
    $o .= "</div>";
    return $this->output($o);
  }
}
?>
