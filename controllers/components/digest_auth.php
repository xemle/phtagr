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
  var $_authHdr = null;
  var $_authData = null;
  /** Supported schemas. Implemented schemas are basic and digest */
  var $validSchemas = array('digest');
  /** Prefered schema */
  var $preferedSchema = 'digest';
  var $realm = 'phtagr/webdav';
  var $controller = null;
  var $components = array('Logger', 'Session');
  
  function initialize(&$controller) {
    $this->controller = $controller;
  }

  function __fixWindowsUsername() {
    if (!isset($this->_authData['username'])) {
      $this->Logger->err("Username is not set");
      return false;
    }

    // Windows client syntax "<domain>\<username>"
    if (preg_match('/^(.*)\\\\(.*)$/', $this->_authData['username'], $matches)) {
      $this->_authData['domain']=$matches[1];
      $this->_authData['winusername']=$matches[2];
      $username=$this->_authData['winusername'];
    } else { 
      $username=$this->_authData['username'];
    }
    return $username;
  }

  function __writeUserData($user) {
    $this->Logger->info("User '{$user['User']['username']}' (id {$user['User']['id']}) authenticated");
    if (!$this->Session->check('User.id') || $this->Session->read('User.id') != $user['User']['id']) {
      $this->Logger->info("Start new session for '{$user['User']['username']}' (id {$user['User']['id']})");
      $this->controller->User->writeSession($user, $this->Session);
    }
  }

  function __addBasicRequestHeader() {
    $this->Logger->trace("Add basic authentications header");
    header('WWW-Authenticate: Basic realm="'.$this->realm.'"');
  }
  
  /** Add authentication header to the response. The session keeps a login
   * counter. If more than 3 logins where done, it denies the access by omitting
   * the authentication header */
  function __addDigestRequestHeader() {
    // Use opaque value as session id
    if (!$this->Session->started()) {
      $this->Session->renew();
      $this->Session->write('auth.nc', 0);
      $this->Session->write('auth.logins', 0);
    }
    $opaque = $this->Session->id();
    $counter = $this->Session->read('auth.logins');
  
    if ($counter>3) {
      $this->Logger->err('login countes exceeded');
      $this->decline();
    }
    $this->Session->write('auth.logins', $counter+1);
    
    $this->Logger->trace("Add authentications header");
    header('WWW-Authenticate: Digest realm="'.$this->realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.$opaque.'",algorithm="MD5"');
  }

  /** Request the client for authentication. The given authentication schema
   * depends on the preferedSchema property */
  function requestAuthentication() {
    if ($this->preferedSchema == 'basic') {
      $this->__addBasicRequestHeader();
    } else {
      $this->__addDigestRequestHeader();
    }
    $this->controller->redirect(null, 401, true);
  }

  /** Decline the client connection */
  function decline() {
    $this->controller->redirect(null, 403, true);
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
      //$this->Logger->trace($arh);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
      $hdr=$_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
      $hdr=$_SERVER['PHP_AUTH_DIGEST'];
    }
    if ($hdr === false) {
      $this->Logger->info("Could not find any authentication header");
    }
    //$this->Logger->trace($hdr);
    $this->_authHdr = $hdr;
    return $hdr;
  }
  
  function __getAuthSchema() {
    $words = preg_split("/[\s]+/", $this->_authHdr);
    if (!$words) {
      $this->Logger->warn("Could not split authentication header");
      $this->requestAuthentication();
    }

    if (count($words) < 2) {
      $this->Logger->warn("Authentication header a to less parameter");
      $this->requestAuthentication();
    }

    $schema = strtolower($words[0]);
    if ($schema != 'digest' && $schema != 'basic') {
      $this->Logger->err("Unsupported authentication schema: $schema");
      $this->requestAuthentication();
    }

    return $schema;
  }

  function __checkBasicHeader() {
    $words = preg_split("/[\s]+/", $this->_authHdr);
    if (count($words) != 2 || strtolower($words[0]) != 'basic') {
      $this->Logger->err("Wrong basic authentication header");
      $this->Logger->trace($this->authHdr);
      $this->requestAuthentication();
    }

    $decode = base64_decode($words[1]);
    $data = preg_split('/:/', $decode, 2);
    if (count($data) != 2) {
      $this->Logger->err("Authentication data is invalid");
      $this->Logger->trace("Basic authentication string is: {$word[1]} (decoded: $decode)");
      $this->requestAuthentication();
    }

    $this->_authData['username'] = $data[0];
    $this->_authData['password'] = $data[1];
    return $data;
  }

  function __checkBasicUser() {
    if (!$this->_authData) {
      $this->Logger->err("Aauthentication data is not set");
      $this->requestAuthentication();
    }

    // Check valid user
    $username = $this->__fixWindowsUsername();
    $user = $this->controller->User->findByUsername($username);
    if (!$user) {
      $this->Logger->err("User '$username' not found");
      $this->requestAuthentication();
    }
    if ($this->controller->User->isExpired($user)) {
      $this->Logger->warn("User account of '{$user['User']['username']}' (id {$user['User']['id']}) is expired!");
      $this->decline();
    }

    // check credentials
    $user = $this->controller->User->decrypt(&$user);
    if ($user['User']['password'] != $this->_authData['password']) {
      $this->Logger->err("Password missmatch");
      $this->requestAuthentication();
    }

    $this->__writeUserData($user);
  }

  /** Parse the http authorization header and checks for all required fields. 
    */
  function __checkDigestHeader() {
    // protect against missing data
    $requiredParts=array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1, 'opaque'=>1);
    $data=array();
  
    preg_match_all('/(\w+)=(([\'"])([^"\']+)\3|(\w+))/', $this->_authHdr, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
      $name = strtolower($match[1]);
      $value = (isset($match[5]) ? $match[5] : $match[4]);
      // TODO Check syntax of values
      $data[$name]=$value;
      unset($requiredParts[$name]);
    }
  
    if ($requiredParts) {
      $this->Logger->warn("Missing authorization part(s): ".implode(", ", array_keys($requiredParts)));
      $this->Logger->info("Authorization header is: ".$this->_authHdr);
      $this->requestAuthentication();
    }
  
    $this->_authData = $data;
  }

  function __checkUri() {
    $requestUri = $_SERVER['REQUEST_URI'];
    $uri = $this->_authData['uri'];

    // Slashify Uri for better client compatibility
    $lru = strlen($requestUri);
    if ($lru > 0 && $requestUri[$lru-1] != '/') {
      $requestUri .= '/';
    }
    $lu = strlen($uri);
    if ($lu > 0 && $uri[$lu-1] != '/') {
      $uri .= '/';
    }

    if ($uri !== $requestUri) {
      $this->Logger->err("Uri missmatch: Request is '$requestUri' but auth header has '$uri'");
      $this->requestAuthentication();
    }
  }

  /** Checks the session to be active and validates the request counter. If the
   * session is not alive or the request counter was already used, an
   * unauthorized response is thrown
   */
  function __checkSession() {
    $sid = $this->_authData['opaque'];
    if ($this->Session->started()) {
      $this->Logger->warn("Session already started!");
    }
    $this->Session->id($sid);
    $this->Session->start();

    if (!$this->Session->check('auth.nc')) {
      $this->Session->renew();
      $this->Session->write('auth.logins', 0);
      $this->Session->write('auth.nc', 0);
      $this->Logger->warn("Unknown or died session ($sid).");
      //$this->Logger->trace($_SESSION);
      $this->requestAuthentication();
    }
  
    $snc=$this->Session->read('auth.nc');
    $nc=hexdec($this->_authData['nc']);
    if ($snc==$nc)
      $this->Logger->warn("Same request counter $snc is used!");
  
    // Check request counter
    if ($snc>$nc) {
      $this->Logger->err("Reused request counter. Current count is {$snc}. Request counter is $nc");
      $this->decline();
    }

    // Update request counter to the session
    $this->Session->write('auth.nc', $nc);
  }

  function __checkDigestUser() {
    $username = $this->__fixWindowsUsername();  
    $user = $this->controller->User->findByUsername($username);
    if ($user === false) {
      $this->Logger->err("Unknown username '$username'");
      $this->requestAuthentication();
    }

    $user = $this->controller->User->decrypt(&$user);
    $A1=md5($this->_authData['username'].':'.$this->realm.':'.$user['User']['password']);
    $A2=md5($_SERVER['REQUEST_METHOD'].':'.$this->_authData['uri']);
    $validResponse=md5($A1.':'.$this->_authData['nonce'].':'.$this->_authData['nc'].':'.$this->_authData['cnonce'].':'.$this->_authData['qop'].':'.$A2);
    if ($this->_authData['response']!=$validResponse) {
      $this->Logger->err("Invalid authentication response: Got '".$this->_authData['response']."' but expected '$validResponse'");
      $this->decline();
    }

    if ($this->controller->User->isExpired($user)) {
      $this->Logger->warn("User account of '{$user['User']['username']}' (id {$user['User']['id']}) is expired!");
      $this->decline();
    } else {
      $this->__writeUserData($user);
    }
  }

  /** Authenticate a user by HTTP Authentication as described in RFC 2617
    @param required If true an authentication is required. If the
    authentication is optional, set this to false and the return value is true
    if the authentication was successful. . 
    @return True if the authentication was successful. 
    true, the authentication is force and will redirect the request with
    authentication information. In this case, the function will not return on
    unsuccessful authentication. */
  function authenticate($required = true) {
    if (!$this->__getAuthHeader()) {
      if ($required) {
        $this->requestAuthentication();
      } else {
        return false;
      }
    }

    $schema = $this->__getAuthSchema();
    if (!$schema) {
      $this->Logger->err("Drop request without valid authentication");
      $this->requestAuthentication();
    }

    if (!in_array($schema, $this->validSchemas) && $schema != $this->preferedSchema) {
      $this->Logger->err("Schema '$schema' is not allowed");
      $this->requestAuthentication();
    }

    switch ($schema) {
      case 'digest':
        $this->__checkDigestHeader();
        $this->__checkUri();
        $this->__checkSession();
        // @todo add check for client agent
        $this->__checkDigestUser();
        break;
      case 'basic':
        $this->__checkBasicHeader();
        $this->__checkBasicUser();
        break;
      default: 
        $this->Logger->err("Authentication schema '$schema' NIY");
        $this->decline();
        break;
    }

    return true;
  }

}

?>
