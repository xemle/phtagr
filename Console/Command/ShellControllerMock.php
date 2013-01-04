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

App::uses('Router', 'Routing');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Controller', 'Controller');
App::uses('AppController', 'Controller');

class ShellControllerMock extends AppController {
	var $uses = array();
	var $components = array();

  var $userMock = null;
	
	function getUser() {
		if (!$this->userMock) {
			$this->userMock = $this->User->getNobody();
      $this->userMock['User']['role'] = ROLE_ADMIN;		
		}
		return $this->userMock;
	}
	
	function mockUser($user) {
		$this->userMock = $user;
	}
	
}