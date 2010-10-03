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

/** This class enables a fast file response without the framework stack of
 * CakePHP. It checks the session and the URL and returns a valid file */
class FastFileResponder {
  /** Should be same as in app/config/core.php Session.cookie */
  var $sessionCookie = 'CAKEPHP'; 
  var $sessionKey = 'fastFile';
  var $items = array();

  function __construct() {
    $this->startSession();
  }

  /** Starts the session if the session sessionCookie is set */
  function startSession() {
    if (!isset($_COOKIE[$this->sessionCookie])) {
      return;
    }
    session_id($_COOKIE[$this->sessionCookie]);
    session_start();
    if (isset($_SESSION[$this->sessionKey])) {
      $this->items = (array) $_SESSION[$this->sessionKey]['items'];
      $this->deleteExpiredItems();
    }
  }

  /** Deletes expired itemes from the session list */
  function deleteExpiredItems() {
    if (!count($this->items)) {
      return;
    }
    $now = time();
    foreach ($this->items as $key => $item) {
      if ($item['expires'] < $now) {
        unset($_SESSION[$this->sessionKey][$key]);
      }
    }
  }
  
  /** Simple log function for debug purpos */
  function log($msg) {
    $h = @fopen(dirname(__FILE__) . DS . 'fast_file_responder.log', 'a');
    @fwrite($h, sprintf("%s %s\n", date('Y-M-d h:i:s', time()), $msg));
    @fclose($h);
  }

  /** Extracts the item key from the url and returns it. Returns false if no
   * key could be found */
  function getItemKey() {
    if (!isset($_GET['url'])) {
      return false;
    }
    $url = $_GET['url'];
    if (!preg_match('/media\/(\w+)\/(\d+)/', $url, $matches)) {
      return false;
    }
    return $matches[1] . '-' . $matches[2];
  }

  /** Returns the file of the media request */
  function getFilename() {
    $key = $this->getItemKey();
    if (!$key || !isset($this->items[$key])) {
      return false;
    }
    $item = $this->items[$key];
    if ($item['expires'] < time() || !is_readable($item['file'])) {
      return false;
    }
    return $item['file'];
  }

  /** Returns an array of request headers */
  function getRequestHeaders() {
    $headers = array();
    if (function_exists('apache_request_headers')) {
      $headers = apache_request_headers();
      foreach($headers as $h => $v) {
        $headers[strtolower($h)] = $v;
      }
    } else {
      $headers = array();
      foreach($_SERVER as $h => $v) {
        if(ereg('HTTP_(.+)', $h, $hp)) {
          $headers[strtolower($hp[1])] = $v;
        }
      }
    }
    return $headers;
  }

  /** Evaluates the client file cache and response if the client has still a
   * valid file
   * @param filename Current cache file */
  function checkClientCache($filename) {
    $cacheTime = filemtime($filename);
    $headers = $this->getRequestHeaders();
    if (isset($headers['if-modified-since']) &&
        (strtotime($headers['if-modified-since']) == $cacheTime)) {
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $cacheTime).' GMT', true, 304);
      // Allow further caching for 30 days
      header('Cache-Control: max-age=2592000, must-revalidate');
      exit;
    }
  }

  function sendResponseHeaders($file) {
    $fileSize = @filesize($file);
    header('Content-Type: image/jpg');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: max-age=2592000, must-revalidate');
    header('Pragma: cache');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)));
  }

  /** Evaluates if a valid cache file exists */
  function exists() {
    return $this->getFilename() != false;
  }
 
  /** Sends the cache file if it exists and exit. If it returns an error
    * occured */
  function send() {
    $filename = $this->getFilename();
    if (!$filename) {
      return false;
    }
    $this->checkClientCache($filename);
    $this->sendResponseHeaders($filename);
 
    $chunkSize = 1024;
    $buffer = '';
    $handle = fopen($filename, 'rb');
    while (!feof($handle)) {
      $buffer = fread($handle, $chunkSize);
      echo $buffer;
    }
    fclose($handle);
    //$this->log("File send: $filename");
    exit(0);
  }
 
  /** Closes the session */
  function close() {
    session_write_close();
  }
}
?>
