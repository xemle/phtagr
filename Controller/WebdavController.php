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

/**
 * Debug PUT with Netbeans and CURL:
 *
 * curl -c cookies.txt -b XDEBUG_SESSION=netbeans-xdebug --digest --user USER:PASS -T sample.jpg http://localhost/phtagr/webdav/sample.jpg
 */
class WebdavController extends AppController
{
  var $components=array('RequestHandler', 'DigestAuth', 'FileCache');

  var $uses = array('User', 'MyFile', 'Media', 'Property', 'Lock');
  // Important to set the davroot in the Webdav Server
  var $name = 'webdav';

  /** @todo Remove configuration of debug */
  public function beforeFilter() {
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
    $this->MyFile->bindModel(array(
      'hasMany' => array(
        'Property' => array('foreignKey' => 'file_id'),
        'Lock' => array('foreignKey' => 'file_id')
        )
      ));

    // Preload WebdavServer component which requires a running session
    $this->loadComponent('WebdavServer');
  }

  /** @todo Set webdav root to creator's root if user is guest */
  public function index() {
    $this->requireRole(ROLE_GUEST);
    $this->layout = 'webdav';

    $user = $this->getUser();
    if (!$this->User->allowWebdav($user)) {
      Logger::err("WebDAV is not allowed to user '{$user['User']['username']}' (id {$user['User']['id']})");
      $this->redirect(null, 403);
    }

    $root = false;
    if ($user['User']['role'] == ROLE_GUEST) {
      $creator = $this->User->findById($user['User']['creator_id']);
      $root = $this->User->getRootDir($creator);
    } elseif ($user['User']['role'] >= ROLE_GUEST) {
      $root = $this->User->getRootDir($user);
    }
    if (!$root || !$this->WebdavServer->setFsRoot($root)) {
      Logger::err("Could not set fsroot: $root");
      $this->redirect(null, 401, true);
    }

    // start buffering
    ob_start();
    $this->WebdavServer->ServeRequest($_SERVER['REQUEST_URI']);
    while (@ob_end_flush());
    die();
  }
}
