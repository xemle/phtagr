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

class DigestAuthComponent extends Object
{
  var $_hdrs = null;
  var $realm = '';
  var $controller = null;
  
  function initialize(&$controller) {
    $this->controller = $controller;
  }

  /** Returns the authorization header. It tryies to fetch the HTTP
   * authorization header from the apache header, from
   * _SERVER[HTTP_AUTHORIZATION] variable or from _SERVER[PHP_AUTH_DIGEST]. If no
   * header information is available, it returns false
    @return HTTP authorization header. False if no header was found */
  function __getAuthHeader() {
    $hdr = false;
    if (function_exists('apache_request_headers')) {
      $arh=apache_request_headers();
      if (isset($arh['Authorization']))
        $hdr=$arh['Authorization'];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
      $hdr=$_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
      $hdr=$_SERVER['PHP_AUTH_DIGEST'];
    }
    if ($hdr === false)
      $this->controller->Logger->warn("Could not find any authentication header");
    return $hdr;
  }
  
  /** Parse the http authorization header and checks for all required fields. 
    */
  function __checkAuthHeader() {
    $authHdr = $this->__getAuthHeader();
    if (!$authHdr) {
      $this->controller->Logger->info("Request authentication header");
      $this->__addAuthHeader();
      $this->controller->redirect(null, 401, true);
    }
    
    // protect against missing data
    $requiredParts=array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1, 'opaque'=>1);
    $data=array();
  
    preg_match_all('/(\w+)=([\'"]?)([a-zA-Z0-9=%.\/\\\\_\-~\@]+)\2/', $authHdr, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
      $data[$m[1]]=$m[3];
      unset($requiredParts[$m[1]]);
    }
  
    if ($requiredParts) {
      $this->controller->Logger->warn("Missing authorization part(s): ".implode(", ", array_keys($requiredParts)));
      $this->controller->Logger->info("Authorization header is: ".$authHdr);
      $this->__addAuthHeader();
      $this->controller->redirect(null, 401, true);
    }
  
    // convert nc from hex to decimal
    $this->_hdrs = $data;
    $this->opaque = $data['opaque'];
  }
  
  /** Add authentication header to the response. The session keeps a login
   * counter. If more than 3 logins where done, it denies the access by omitting
   * the authentication header */
  function __addAuthHeader() {
    // Use opaque value as session id
    if (!$this->controller->Session->started()) {
      $this->controller->Session->renew();
      $this->controller->Session->write('auth.nc', 0);
      $this->controller->Session->write('auth.logins', 0);
    }
    $opaque=$this->controller->Session->id();
    $counter=$this->controller->Session->read('auth.logins');
  
    if ($counter>3) {
      $this->controller->Logger->err('login countes exceeded');
      $this->controller->redirect(null, 401, true);
    }
    $this->controller->Session->write('auth.logins', $counter+1);
    
    header('WWW-Authenticate: Digest realm="'.$this->realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.$opaque.'",algorithm="MD5"');
  }

  function __checkUri() {
    if ($this->_hdrs['uri']!==$_SERVER['REQUEST_URI']) {
      $this->controller->Logger->err("Uri missmatch: Have ".$_SERVER['REQUEST_URI']." but auth has ".$this->_hdrs['uri']);
      $this->__addAuthHeader();
      $this->controller->redirect(null, 401, true);
    }
  }

  /** Checks the session to be active and validates the request counter. If the
   * session is not alive or the request counter was already used, an
   * unauthorized response is thrown
   */
  function __checkSession() {
    $sid = $this->_hdrs['opaque'];
    if ($this->controller->Session->started())
      $this->controller->Logger->warn("Session already started!");
    $this->controller->Session->id($sid);
    $this->controller->Session->start();

    if (!$this->controller->Session->check('auth.nc')) {
      $this->controller->Logger->err("Authorization failed: Unknown or died session ($sid)");
      $this->controller->Logger->trace($_SESSION);
      $this->controller->Session->write('auth.nc', 0);
      $this->controller->redirect(null, 403, true);
    }
  
    $snc=$this->controller->Session->read('auth.nc');
    $nc=hexdec($this->_hdrs['nc']);
    if ($snc==$nc)
      $this->controller->Logger->info("Same request counter $snc is used!");
  
    // Check request counter
    if ($snc>$nc) {
      $this->controller->Logger->err("Authorization failed: Reused request counter! Current is {$snc}. Request counter is $nc");
      $this->controller->redirect(null, 403, true);
    }
    // Update request counter to the session
    $this->controller->Session->write('auth.nc', $nc);
  }

  function __checkUser() {
    // Windows client syntax "<domain>\<username>"
    if (preg_match('/^(.*)\\\\(.*)$/', $this->_hdrs['username'], $matches)) {
      $this->_hdrs['domain']=$matches[1];
      $this->_hdrs['winusername']=$matches[2];
      $username=$this->_hdrs['winusername'];
    } else { 
      $username=$this->_hdrs['username'];
    } 
  
    $user = $this->controller->User->findByUsername($username);
    if ($user === false) {
      $this->controller->Logger->err("Unknown username '$username'");
      $this->controller->redirect(null, 403, true);
    }

    // Todo read A1 from database
    $A1=md5($this->_hdrs['username'].':'.$this->realm.':'.$user['User']['password']);
    $A2=md5($_SERVER['REQUEST_METHOD'].':'.$this->_hdrs['uri']);
    $validResponse=md5($A1.':'.$this->_hdrs['nonce'].':'.$this->_hdrs['nc'].':'.$this->_hdrs['cnonce'].':'.$this->_hdrs['qop'].':'.$A2);
    if ($this->_hdrs['response']!=$validResponse) {
      $this->controller->Logger->err("Invalid response: Got ".$this->_hdrs['response']." but expected $validResponse");
      $this->controller->redirect(null, 403, true);
    }

    if ($this->controller->User->isExpired($user)) {
      $this->controller->Logger->warn("User account of '{$user['User']['username']}' (id {$user['User']['id']}) is expired!");
      $this->controller->reidrect(null, 403, true);
    } else {
      $this->controller->Logger->info("User '{$user['User']['username']}' (id {$user['User']['id']}) authenticated");
      if (!$this->controller->Session->check('User.id') || $this->controller->Session->read('User.id') != $user['User']['id']) {
        $this->controller->Logger->info("Start new session for '{$user['User']['username']}' (id {$user['User']['id']})");
        $this->controller->Session->write('User.id', $user['User']['id']);
        $this->controller->Session->write('User.username', $user['User']['username']);
        $this->controller->Session->write('User.role', $user['User']['role']);
      }
    }
  }

  function check() {
    $this->__checkAuthHeader();
    $this->__checkUri();
    $this->__checkSession();
    $this->__checkUser();
    // @todo add check for client agent

    return true;
  }

}

?>
