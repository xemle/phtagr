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

App::import('vendor', "WebdavServer", true, array(), "webdav".DS."WebdavServer.php");

class WebdavController extends AppController
{
  var $components=array('RequestHandler', 'DigestAuth', 'FileCache');

  var $uses = array('User', 'Image', 'Property', 'Lock');
  // Important to set the davroot in the Webdav Server
  var $name = 'webdav';

  /** @todo Remove configuration of debug */
  function beforeFilter() {
    // dont't call parent::beforeFilter(). Cookies (and sessions) are not
    // supported for WebDAV..

    Configure::write('debug', 0);
    if ($this->RequestHandler->isSSL()) {
      // If the connection is encrypted we can allow the basic authentication
      // schema. E.g. Anyclient 1.4 sends requests unasked with basic schema
      $this->DigestAuth->validSchemas = array('digest', 'basic');
    }
    $this->DigestAuth->authenticate();

    // Bind Properties and Locks to images persistently (only webdav is using it)
    $this->Image->bind('Property', array('type' => 'hasMany'));
    $this->Image->bind('Lock', array('type' => 'hasMany'));
  }

  /** @todo Set webdav root to creator's root if user is guest */
  function index() {
    $this->requireRole(ROLE_GUEST);
    $this->layout = 'webdav';

    $webdav = new WebdavServer();
    $webdav->setController(&$this);
    
    $user = $this->getUser();
    if (!$this->User->allowWebdav($user)) {
      $this->Logger->err("WebDAV is not allowed to user '{$user['User']['username']}' (id {$user['User']['id']})");
      $this->redirect(null, 403);
    }

    $root = false;
    if ($user['User']['role'] == ROLE_GUEST) {
      $creator = $this->User->findById($user['User']['creator_id']);
      $root = $this->User->getRootDir($creator);
    } elseif ($user['User']['role'] >= ROLE_GUEST) {
      $root = $this->User->getRootDir($user);
    }
    if (!$root || !$webdav->setFsRoot($root)) {
      $this->Logger->err("Could not set fsroot: $root");
      $this->redirect(null, 401, true);
    }

    // start buffering
    ob_start();
    $webdav->ServeRequest($_SERVER['REQUEST_URI']);
    while (@ob_end_flush());
    die();
  }
}
?>
