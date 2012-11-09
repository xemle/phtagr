<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */
class OptionsController extends AppController {

  var $name = 'Options';
  var $helpers = array('Form');
  var $uses = array('Option', 'Group');
  var $subMenu = false;

  public function beforeFilter() {
    $this->subMenu = array(
      'profile' => __("Profile"),
      'password' => __("Password"),
      'import' => __("Import Options"),
      'rss' => __("RSS Feeds"),
      );
    parent::beforeFilter();

    $this->requireRole(ROLE_GUEST, array('redirect' => '/'));
  }

  public function beforeRender() {
    $this->requireRole(ROLE_USER);
    $this->layout = 'backend';
    parent::beforeRender();
  }

  private function _setOption($userId, $path, $data) {
    $value = Set::extract($data, $path);
    $this->Option->setValue($path, $value, $userId);
  }

  public function index() {
    // dummy
  }

  public function profile() {
    $userId = $this->getUserId();
    if (!empty($this->request->data)) {
      $this->User->id = $userId;
      if (!$this->User->save($this->request->data['User'], true, array('username', 'firstname', 'lastname', 'email', 'visible_level', 'notify_interval'))) {
        Logger::err("Could not update user profile");
        $this->Session->setFlash(__("Could not save profile!"));
      } else {
        Logger::info("User $userId profile updated");
        $this->Session->setFlash(__("Profile saved"));
      }
    }
    $this->request->data = $this->User->findById($userId);
  }

  public function password() {
    $userId = $this->getUserId();
    if (!empty($this->request->data)) {
      $this->User->id = $userId;
      if (!$this->User->save($this->request->data['User'], true, array('password'))) {
        Logger::err("Could not update user profile");
        $this->Session->setFlash(__("Could not save profile!"));
      } else {
        Logger::info("User $userId profile updated");
        $this->Session->setFlash(__("Profile saved"));
      }
    }
    $this->request->data = $this->User->findById($userId);
    unset($this->request->data['User']['password']);
  }

  public function rss($action = null) {
    $userId = $this->getUserId();
    $user = $this->User->findById($userId);
    if ($action == 'renew' || empty($user['User']['key'])) {
      $tmp = array('User' => array('id' => $userId));
      $tmp = $this->User->generateKey($tmp);
      if (!$this->User->save($tmp, false, array('key'))) {
        Logger::err("Could not save user data");
        Logger::debug($this->User->validationErrors);
      }
    }
    $this->request->data = $this->User->findById($userId);
  }

  public function import() {
    $userId = $this->getUserId();
    if (!empty($this->request->data)) {
      $this->_setOption($userId, 'acl.group', $this->request->data);

      // check values
      if ($this->request->data['acl']['write']['meta'] > $this->request->data['acl']['write']['tag']) {
        $this->request->data['acl']['write']['meta'] = $this->request->data['acl']['write']['tag'];
      }
      if ($this->request->data['acl']['read']['original'] > $this->request->data['acl']['read']['preview']) {
        $this->request->data['acl']['read']['original'] = $this->request->data['acl']['read']['preview'];
      }

      $this->_setOption($userId, 'acl.write.tag', $this->request->data);
      $this->_setOption($userId, 'acl.write.meta', $this->request->data);

      $this->_setOption($userId, 'acl.read.original', $this->request->data);
      $this->_setOption($userId, 'acl.read.preview', $this->request->data);

      $offset = intval(Set::extract('filter.gps.offset', $this->request->data));
      $offset = min(720, max(-720, $offset));
      $this->Option->setValue('filter.gps.offset', $offset, $userId);

      $check = Set::extract('filter.gps.overwrite', $this->request->data);
      $check = $check ? 1 : 0;
      $this->Option->setValue('filter.gps.overwrite', $check, $userId);

      $range = intval(Set::extract('filter.gps.range', $this->request->data));
      $range = max(0, min(60, $range));
      $this->Option->setValue('filter.gps.range', $range, $userId);

      $this->Session->setFlash(__("Settings saved"));
    }
    $tree = $this->Option->getTree($userId);
    $this->Option->addDefaultAclTree($tree);
    $this->request->data = $tree;

    $this->set('userId', $userId);
    $groups = $this->Group->find('all', array('conditions' => "Group.user_id = $userId", 'order' => array('Group.name' => 'ASC')));
    $user = $this->User->findById($userId);
    $groups = $this->Group->getGroupsForMedia($user);
    if ($groups) {
      $groups = Set::combine($groups, '{n}.Group.id', '{n}.Group.name');
    } else {
      $groups = array();
    }
    sort($groups);
    $groups[-1] = __('[No Group]');
    $this->set('groups', $groups);
  }
}
?>
