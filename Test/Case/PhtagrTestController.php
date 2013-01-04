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
 * @since         phTagr 2.3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

App::uses('AppController', 'Controller');

/**
 * Phtagr test controller with mocked user
 */
class PhtagrTestController extends AppController {
  var $mockUser;

  public function &getUser() {
    if ($this->mockUser) {
      return $this->mockUser;
    } else {
      $user = $this->User->find('first');
      if ($user) {
        $this->mockUser = $user;
      } else {
        $user = $this->User->getNobody();
        $this->mockUser = $user;
      }
      return $user;
    }
  }

}
