<?php
/**
 * PHP versions 5
 *
 * phTagr : Organize, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.4
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

App::uses('CakeEventListener', 'Event');

/**
 * Resets session data of current user if dependend model was saved
 */
class UserSessionListener implements CakeEventListener {

    var $resetModels = array('User', 'Option', 'Group');
    var $userKey = 'User';

    public function implementedEvents() {
        return array(
            'Model.afterSave' => 'afterSave',
        );
    }

    public function afterSave($event) {
      $model = $event->subject();
      if (in_array($model->alias, $this->resetModels)) {
        CakeSession::delete('user');
      }
    }
}
