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

App::uses('Sanitize', 'Utility');

class GuestsController extends AppController {
  var $name = 'Guests';
  var $uses = array('Group', 'User', 'Guest');
  var $components = array('RequestHandler');
  var $helpers = array('Form', 'Autocomplete');
  var $subMenu = false;

  public function beforeFilter() {
    parent::beforeFilter();
    $this->layout = 'backend';
    $this->requireRole(ROLE_USER);
    $this->subMenu = array(
      'create' => __('New Guest')
      );
  }

  /**
   * Add sub menu entry for guest
   */
  private function _addSubmenu(&$guest) {
    $this->subMenu[] = array(
      'url' => array('action' => $this->action, $guest['Guest']['id']),
      'title' => __("Edit %s", $guest['Guest']['username']), 'active' => true,
      array(
        'url' => array('action' => 'links', $guest['Guest']['id']), 'title' => __("Links")),
      );
  }

  public function beforeRender() {
    parent::beforeRender();
  }

  public function index() {
    $userId = $this->getUserId();
    $this->request->data = $this->Guest->find('all', array('conditions' => array('Guest.creator_id' => $userId)));
  }

  public function autocomplete() {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      $this->redirect(null, '404');
    }
    $userId = $this->getUserId();
    $escName = Sanitize::escape($this->request->data['Group']['name']);
    $groups = $this->Group->find('all', array('conditions' => "Group.user_id = $userId AND Group.name LIKE '%$escName%'"));
    $this->request->data = $groups;
    $this->layout = "bare";
  }

  public function create() {
    if (!empty($this->request->data)) {
      $userId = $this->getUserId();
      $this->request->data['Guest']['creator_id'] = $userId;
      $this->request->data['Guest']['role'] = ROLE_GUEST;
      if ($this->Guest->hasAny(array("Guest.username" => $this->request->data['Guest']['username']))) {
        $this->Session->setFlash(__("Sorry. Username is already taken"));
      } elseif ($this->Guest->save($this->request->data, true, array('username', 'password', 'role', 'creator_id', 'email', 'quota'))) {
        $guestId = $this->Guest->getLastInsertID();
        $guest = $this->Guest->findById($guestId);
        $user = $this->getUser();
        Logger::info("User '{$user['User']['username']}' ({$user['User']['id']}) created a guest account '{$guest['Guest']['username']}' ({$guest['Guest']['id']})");
        $this->Session->setFlash(__("Guest account '%s' was successfully created", $this->request->data['Guest']['username']));
        $this->redirect("edit/$guestId");
      } else {
        $this->Session->setFlash(__("Sorry. Guest account could not created"));
      }
    }
  }

  public function edit($guestId) {
    $guestId = intval($guestId);
    $userId = $this->getUserId();

    if (!$this->Guest->hasAny(array('id' => $guestId, 'creator_id' => $userId))) {
      $this->Session->setFlash(__("Sorry. Could not find requested guest"));
      Logger::debug("Sorry. Could not find requested guest '$guestId' of user '$userId'");
      $this->redirect("index");
    }

    if (!empty($this->request->data)) {
      $this->Guest->id = $guestId;
      $this->Guest->set($this->request->data);
      if ($this->Guest->save(null, true, array('username', 'password', 'email', 'expires', 'quota', 'notify_interval'))) {
        $this->Session->setFlash(__("Guest data were saved"));
        $auth = max(0, min(3, $this->request->data['Comment']['auth']));
        $this->Option->setValue('comment.auth', $auth, $guestId);
      } else {
        Logger::err("Could not save guest");
        Logger::trace($this->Guest->validationErrors);
        $this->Session->setFlash(__("Updates could not be saved!"));
      }
    }
    $guest = $this->Guest->findById($guestId);
    unset($guest['Guest']['password']);
    $this->_addSubMenu($guest);
    $this->request->data = $guest;
    $this->request->data['Comment']['auth'] = $this->Option->getValue($guest, 'comment.auth', COMMENT_AUTH_NONE);
    $this->set('userId', $userId);
  }

  /**
    @todo Reset all group information of image */
  public function delete($guestId) {
    $userId = $this->getUserId();
    $guest = $this->Guest->find('first', array('conditions' => array('Guest.id' => $guestId, 'Creator.id' => $userId)));
    if (!$guest) {
      $this->Session->setFlash(__("Could not find requested guest"));
    } else {
      $user = $this->getUser();
      Logger::info("User '{$user['User']['username']}' ({$user['User']['id']}) deleted guest account '{$guest['Guest']['username']}' ({$guest['Guest']['id']})");
      $this->Session->setFlash(__("Guest account '%s' deleted!", $guest['Guest']['username']));
      $this->Guest->delete($guestId);
    }
    $this->redirect("index");
  }

  public function addGroup($groupId) {
    if (!empty($this->request->data)) {
      $userId = $this->getUserId();
      $group = $this->Group->find('first', array('conditions' => array('Group.name' => $this->request->data['Group']['name'], 'Group.user_id' => $userId)));
      $guest = $this->Guest->find('first', array('conditions' => array('Guest.id' => $groupId, 'Creator.id' => $userId)));

      if (!$guest) {
        $this->Session->setFlash("The given user with id '$groupId' could not be found!");
        $this->redirect("index");
      } elseif (!$group) {
        $this->Session->setFlash("The group '{$this->request->data['Group']['name']}' does not exists!");
      } else {
        $list = Set::extract($guest, "Member.{n}.id");
        $list[] = $group['Group']['id'];
        $guest['Member']['Member'] = array_unique($list);
        unset($guest['Guest']['password']);
        $this->Guest->set($guest);
        if ($this->Guest->save()) {
          Logger::info("Added group '{$group['Group']['name']}' ({$group['Group']['id']}) to guest '{$guest['Guest']['username']}' ({$guest['Guest']['id']})");
          $this->Session->setFlash("The group '{$this->request->data['Group']['name']}' was added to your guest '{$guest['Guest']['username']}'");
        } else {
          $this->Session->setFlash("The group '{$this->request->data['Group']['name']}' could not be added to your guest '{$guest['Guest']['username']}'");
        }
      }
      $this->redirect("edit/$groupId");
    }
  }

  public function deleteGroup($guestId, $groupId) {
    $guestId = intval($guestId);
    $groupId = intval($groupId);
    $userId = $this->getUserId();

    $guest = $this->Guest->find('first', array('conditions' => array('Guest.id' => $guestId, 'Creator.id' => $userId)));
    if (!$guest) {
      $this->Session->setFlash("Could not find guest!");
      $this->redirect("index");
    } else {
      $list = Set::extract($guest, "Member.{n}.id");
      $key = array_search($groupId, $list);
      if ($key === false) {
        $this->Session->setFlash("Could not find group of guest '{$guest['Guest']['username']}'");
      } else {
        unset($list[$key]);
        $guest['Member']['Member'] = array_unique($list);
        unset($guest['Guest']['password']);
        $this->Guest->id = $guestId;
        $this->Guest->set($guest);
        if (!$this->Guest->save()) {
          Logger::err("Could not save guest");
          Logger::trace($this->Guest->validationErrors);
          $this->Session->setFlash("Could not save guest");
        } else {
          $group = $this->Group->findById($groupId);
          Logger::info("Deleted group '{$group['Group']['name']}' ({$group['Group']['id']}) from guest '{$guest['Guest']['username']}' ({$guest['Guest']['id']})");
          $this->Session->setFlash("Group '{$group['Group']['name']}' was successfully deleted from guest '{$guest['Guest']['username']}'");
        }
      }
      $this->redirect("edit/$guestId");
    }
  }

  public function links($guestId, $action = null) {
    $this->requireRole(ROLE_USER);

    $userId = $this->getUserId();
    $guest = $this->Guest->find('first', array('conditions' => array('Guest.id' => $guestId, 'Creator.id' => $userId)));
    if (!$guest) {
      $this->Session->setFlash("Could not find guest!");
      Logger::err("Could not find guest $guestId for user $userId");
      $this->redirect("index");
    }

    if ($action == 'renew' || empty($guest['Guest']['key'])) {
      // reuse function of User model
      $tmp = array('Guest' => array('id' => $guestId));
      $this->User->generateKey($tmp);
      $tmp['Guest']['key'] = $tmp['User']['key'];
      unset($tmp['User']['key']);
      if (!$this->Guest->save($tmp, false, array('key'))) {
        Logger::err("Could not save user data");
        Logger::debug($this->Guest->validationErrors);
      }
    }
    $guest = $this->Guest->findById($guestId);
    $this->_addSubMenu($guest);
    $this->request->data = $guest;
  }
}
?>
