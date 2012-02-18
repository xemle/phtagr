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

App::uses('Email', 'Component');

class ImageEmailComponent extends EmailComponent {
  
  /** List of embedded images */
  var $images = array();

  function initialize(&$controller) {
    parent::initialize(&$controller);
  }

  function _createHeader() {
    // fake attachment to force MIME mixed
    $this->attachments[-1] = false;
    parent::_createHeader();
  }

  /** Add support of related content */
	function _render($content) {
		$viewClass = $this->Controller->view;

		if ($viewClass != 'View') {
			list($plugin, $viewClass) = pluginSplit($viewClass);
			$viewClass = $viewClass . 'View';
			App::import('View', $this->Controller->view);
		}

		$View = new $viewClass($this->Controller);
		$View->layout = $this->layout;
		$msg = array();

		$content = implode("\n", $content);

		if ($this->sendAs === 'both') {
			$htmlContent = $content;
			if (!empty($this->attachments)) {
				$msg[] = '--' . $this->__boundary;
				$msg[] = 'Content-Type: multipart/alternative; boundary="alt-' . $this->__boundary . '"';
				$msg[] = '';
			}
			$msg[] = '--alt-' . $this->__boundary;
			$msg[] = 'Content-Type: text/plain; charset=' . $this->charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$content = $View->element('email' . DS . 'text' . DS . $this->template, array('content' => $content), true);
			$View->layoutPath = 'email' . DS . 'text';
			$content = explode("\n", $this->textMessage = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($content)));

			$msg = array_merge($msg, $content);

			$msg[] = '';
			$msg[] = '--alt-' . $this->__boundary;
      if (!empty($this->images)) {
        $msg[] = 'Content-Type: multipart/related; boundary="rel-' . $this->__boundary . '"';
        $msg[] = '';
        $msg[] = '--rel-' . $this->__boundary;
      }
			$msg[] = 'Content-Type: text/html; charset=' . $this->charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$htmlContent = $View->element('email' . DS . 'html' . DS . $this->template, array('content' => $htmlContent), true);
			$View->layoutPath = 'email' . DS . 'html';
			$htmlContent = explode("\n", $this->htmlMessage = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($htmlContent)));
			$msg = array_merge($msg, $htmlContent);
      if (!empty($this->images)) {
        $images = $this->_attachImages();
        $msg = array_merge($msg, $images);
        $msg[] = '';
        $msg[] = '--rel-' . $this->__boundary . '--';
        $msg[] = '';
      }
			$msg[] = '';
			$msg[] = '--alt-' . $this->__boundary . '--';
			$msg[] = '';

			ClassRegistry::removeObject('view');
			return $msg;
		}

		if (!empty($this->attachments)) {
			if ($this->sendAs === 'html') {
				$msg[] = '';
				$msg[] = '--' . $this->__boundary;
				$msg[] = 'Content-Type: text/html; charset=' . $this->charset;
				$msg[] = 'Content-Transfer-Encoding: 7bit';
				$msg[] = '';
			} else {
				$msg[] = '--' . $this->__boundary;
				$msg[] = 'Content-Type: text/plain; charset=' . $this->charset;
				$msg[] = 'Content-Transfer-Encoding: 7bit';
				$msg[] = '';
			}
		}

		$content = $View->element('email' . DS . $this->sendAs . DS . $this->template, array('content' => $content), true);
		$View->layoutPath = 'email' . DS . $this->sendAs;
		$content = explode("\n", $rendered = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($content)));

		if ($this->sendAs === 'html') {
			$this->htmlMessage = $rendered;
		} else {
			$this->textMessage = $rendered;
		}

		$msg = array_merge($msg, $content);
		ClassRegistry::removeObject('view');

		return $msg;
	}

  /** Add support of Content-ID */
  function _attachImages() {
    $files = array();
    foreach ($this->images as $filename => $options) {
      if (!is_array($options)) {
        $options['file'] = $options;
      }
      $options = am(array('mime' => 'application/octet-stream', 'id' => false, 'file' => false), $options);
      $file = $this->_findFiles($options['file']);
      if (!empty($file)) {
        if (is_int($filename)) {
          $filename = basename($file);
        }
        $options['file'] = $file;
        $files[$filename] = $options;
      }
    }

    $msg = array();
    foreach ($files as $filename => $options) {
      $file = $options['file'];
      $handle = fopen($file, 'rb');
      $data = fread($handle, filesize($file));
      $data = chunk_split(base64_encode($data)) ;
      fclose($handle);

      $msg[] = '--rel-' . $this->__boundary;
      $msg[] = 'Content-Type: ' . $options['mime'];
      $msg[] = 'Content-Transfer-Encoding: base64';
      if ($options['id']) {
        $msg[] = 'Content-ID: <' . $options['id'] . '>';
      }
      $msg[] = 'Content-Disposition: attachment; filename="' . basename($filename) . '"';
      $msg[] = '';
      $msg = array_merge($msg, explode("\r\n", $data)); 
      $msg[] = '';
    }
    //Logger::debug($msg);
    return $msg;
  }
}
?>
