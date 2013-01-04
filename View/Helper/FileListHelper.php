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

class FileListHelper extends AppHelper
{
  var $helpers = array('Html', 'Number', 'Form');

  /**
   * Returns an icon of a specific file type
   *
   * @param type File type
   * @return Html resource of the icon or false on error
   */
  function _icon($type) {
    $icon = false;
    switch ($type) {
      case FILE_TYPE_DIRECTORY: $icon = 'folder'; break;
      case FILE_TYPE_IMAGE: $icon = 'picture'; break;
      case FILE_TYPE_VIDEOTHUMB:
      case FILE_TYPE_VIDEO: $icon = 'film'; break;
      case FILE_TYPE_GPS: $icon = 'map'; break;
      case FILE_TYPE_TEXT: break;
      case FILE_TYPE_SIDECAR: $icon = 'chain'; break;
      default:
        Logger::warn("Unhanded file type $type");
        return false;
    }
    if ($icon) {
      return $this->Html->image("icons/$icon.png");
    }
    return false;
  }

  function _cmpFile($a, $b, $field = 'file') {
    if ($a[$field] < $b[$field]) {
      return -1;
    } elseif ($a[$field] > $b[$field]) {
      return 1;
    } elseif ($field == 'file') {
      return FileListHelper::_cmpFile($a, $b, 'path');
    }
    return 0;
  }

  function _dirRow($path, $dir, $options = array()) {
    if (!is_array($dir)) {
      $name = $dir;
      $value = '.';
    } else {
      $name = basename($dir['path']);
      $value = $name;
      $path .= $name;
    }
    $row = array();
    if ($options['checkBox']) {
      $cb = $this->Form->input('import][', array('type' => 'checkbox', 'value' => "$value", 'label' => false, 'div' => false, 'onclick' => 'javascript:selectTableRow(this)')).' ';
    } else {
      $cb = '';
    }
    $row[] = $cb.' '.$this->_icon(FILE_TYPE_DIRECTORY);
    $row[] = $this->Html->link($name, "index/$path");
    $row[] = '';
    $row[] = '';
    $actions = array();
    if ($options['isInternal']) {
      $actions[] = $this->Html->link(__('delete'), "delete/$path", array('style' => 'color: red', 'onclick' => "return confirm('" . __('Delete folder %s?', $path) . "')"));
    }
    $row[] = implode('', $actions);
    return $row;
  }

  function _fileRow($path, $file, $options) {
    $row = array();
    $cb = $this->Form->input('import][', array('type' => 'checkbox', 'value' => $file['file'], 'label' => false, 'div' => false, 'onclick' => 'javascript:selectTableRow(this)'));
    $row[] = $cb.' '.$this->_icon($file['type']);
    $row[] = $file['file'];
    if (isset($file['media_id'])) {
      $mediaLink = $this->Html->link($file['media_id'], '/images/view/'.$file['media_id']);
      $unlink = $this->Html->link(__('unlink'), "unlink/$path/{$file['file']}", array('style' => 'color: red', 'title' => __('Unlink media from this file')));
      $row[] = $mediaLink.' '.$unlink;
    } else {
      $row[] = '';
    }
    $row[] = $this->Number->toReadableSize($file['size']);
    $actions = array();

    // Download link for internal files and imported external files
    if ($options['isInternal'] || $file['media_id'] > 0) {
      $icon = $this->Html->image('icons/disk.png', array('alt' => 'download', 'title' => __('Download %s', $file['file'])));
      $actions[] = $this->Html->link($icon, "index/$path/{$file['file']}", array('escape' => false));
    }

    // Delete link for internal files
    if ($options['isInternal']) {
      $actions[] = $this->Html->link(__('delete'), "delete/$path/{$file['file']}", array('style' => 'color: red'));
    }
    $row[] = implode(' ', $actions);
    return $row;
  }

  function table($path, $dirs, $files, $options = array()) {
    $options = am(array('isInternal' => false, 'checkBox' => true), $options);
    $out = "";
    $cells = array();
    if ($path != '/') {
      $parentPath = dirname($path);
      $cells[] = $this->_dirRow($parentPath, __('(parent folder)'), array('checkBox' => false, 'isInternal' => false));
    }
    $cells[] = $this->_dirRow($path, __('(this folder)'), $options);
    usort($dirs, array("FileListHelper", "_cmpFile"));
    usort($files, array("FileListHelper", "_cmpFile"));
    foreach($dirs as $dir) {
      $cells[] = $this->_dirRow($path, $dir, $options);
    }
    foreach($files as $file) {
      $cells[] = $this->_fileRow($path, $file, $options);
    }
    $out .= "<table class=\"default\">\n";
    $out .= "<thead>\n";
    $out .= $this->Html->tableHeaders(
      array('', __('Name'), __('Media'), __('Size'), __('Actions'))
      );
    $out .= "</thead>\n";
    $out .= "<tbody>\n";
    $out .= $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
    $out .= "</tbody>\n";
    $out .= "</table>\n";
    return $out;
  }

  function location($path, $linkPrefix = 'index') {
    $out = $this->Html->link('root', $linkPrefix)." / ";
    if ($path != "/") {
      $paths = explode('/', trim($path, '/'));
      $cur = "";
      foreach ($paths as $p) {
        $cur .= '/'.$p;

        $out .= $this->Html->link($p, $linkPrefix.$cur)." / ";
      }
    }
    return $out;
  }
}
?>
