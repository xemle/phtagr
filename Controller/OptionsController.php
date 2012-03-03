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

  function beforeFilter() {
    $this->subMenu = array(
      'acl' => __("Default Rights"),
      'profile' => __("Profile"),
      'rss' => __("RSS Feeds"),
      );
    parent::beforeFilter();

    $this->requireRole(ROLE_GUEST, array('redirect' => '/'));
  }
  
  function beforeRender() {
    $this->layout = 'backend';
    parent::beforeRender();
  }

  function _set($userId, $path, $data) {
    $value = Set::extract($data, $path);
    $this->Option->setValue($path, $value, $userId);
  }

  function index() {
    // dummy
  }

  function acl() {
    $this->requireRole(ROLE_USER);

    $userId = $this->getUserId();
    if (!empty($this->request->data)) {
      // TODO check valid acl
      $this->_set($userId, 'acl.group', $this->request->data);

      // check values
      if ($this->request->data['acl']['write']['meta'] > $this->request->data['acl']['write']['tag']) {
        $this->request->data['acl']['write']['meta'] = $this->request->data['acl']['write']['tag'];
      }
      if ($this->request->data['acl']['read']['original'] > $this->request->data['acl']['read']['preview']) {
        $this->request->data['acl']['read']['original'] = $this->request->data['acl']['read']['preview'];
      }

      $this->_set($userId, 'acl.write.tag', $this->request->data);
      $this->_set($userId, 'acl.write.meta', $this->request->data);

      $this->_set($userId, 'acl.read.original', $this->request->data);
      $this->_set($userId, 'acl.read.preview', $this->request->data);

      $this->Session->setFlash(__("Settings saved"));
    }
    $tree = $this->Option->getTree($userId);
    $this->Option->addDefaultAclTree(&$tree);
    $this->request->data = $tree;

    $this->set('userId', $userId);
    $groups = $this->Group->find('all', array('conditions' => "Group.user_id = $userId", 'order' => array('Group.name' => 'ASC')));
    if ($groups) {
      $groups = Set::combine($groups, '{n}.Group.id', '{n}.Group.name');
    } else {
      $groups = array();
    }
    $groups[-1] = __('[No Group]');
    $this->set('groups', $groups);
  }

  function profile() {
    $this->requireRole(ROLE_USER);

    $userId = $this->getUserId();
    if (!empty($this->request->data)) {
      $this->User->id = $userId;
      if (!$this->User->save($this->request->data['User'], true, array('username', 'firstname', 'lastname', 'password', 'email', 'visible_level', 'notify_interval'))) {
        Logger::err("Could not update user profile");
        $this->Session->setFlash(__("Could not save profile!"));
      } else {
        Logger::info("User $userId profile updated");
        $this->Session->setFlash(__("Profile saved"));
      }
      $browser = Set::extract("/Option/user/browser/full", $this->request->data);
      $this->Option->setValue('user.browser.full', $browser[0], $userId);
    }
    $this->request->data = $this->User->findById($userId);
    $this->request->data['Option'] = $this->Option->getTree($userId);
    unset($this->request->data['User']['password']);
  }

  function rss($action = null) {
    $this->requireRole(ROLE_USER);

    $userId = $this->getUserId();
    $user = $this->User->findById($userId);
    if ($action == 'renew' || empty($user['User']['key'])) {
      $tmp = array('User' => array('id' => $userId));
      $this->User->generateKey(&$tmp);
      if (!$this->User->save($tmp, false, array('key'))) {
        Logger::err("Could not save user data");
        Logger::debug($this->User->validationErrors);
      }
    }
    $this->request->data = $this->User->findById($userId);
  }

}
?>
