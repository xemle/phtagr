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
class GroupsController extends AppController {
  var $name = 'Groups';
  var $uses = array('Group', 'User', 'Media');
  var $components = array('RequestHandler', 'Security', 'Email', 'Search');
  var $helpers = array('Form', 'ImageData', 'Text');
  var $subMenu = false;

  function beforeFilter() {
    parent::beforeFilter();
    $this->subMenu = array(
      'index' => __("List Group"),
      'create' => __("Create Group"),
      );
    $this->requireRole(ROLE_USER);
    $this->Security->blackHoleCallback = 'fail';
    $this->Security->requirePost = array('addMember');
    if ($this->action == 'addMember') {
      $this->Security->validatePost = false;
    }
  }

  function beforeRender() {
    $this->layout = 'backend';
    parent::beforeRender();
  }

  function fail() {
    Logger::err("The security component denied the form input");
    Logger::debug($this->request->data);
    $this->redirect('index');
  }

  function index() {
    $userId = $this->getUserId();
    if ($this->hasRole(ROLE_ADMIN)) {
      $this->request->data = $this->Group->find('all', array('order' => 'Group.name'));
    } else {
      $this->request->data = $this->Group->find('all', array('conditions' => (array('OR' => array('User.id' => $userId, 'Group.is_hidden' => false))), 'order' => 'Group.name'));
    }
  }

  function view($name) {
    $this->request->data = $this->Group->findByName($name);
    if (!$this->request->data) {
      $this->Session->setFlash(__("%s not found", true, __("Group")));
      $this->redirect('index');
    }
    $this->Group->setAdmin(&$this->request->data, $this->getUser());
    $this->set('mediaCount', $this->Media->countByGroupId($this->request->data['Group']['id']));

    $this->Search->addGroup($name);
    $this->Search->setShow(6);
    $this->set('media', $this->Search->paginate());   
  }

  function create() {
    if (!empty($this->request->data)) {
      $user = $this->getUser();
      $this->request->data['Group']['user_id'] = $user['User']['id'];
      if (!$this->Group->isNameUnique($this->request->data)) {
        $this->Session->setFlash(__("%s already exists", true, __('Group')));
      } elseif ($this->Group->save($this->request->data)) {
        $groupId = $this->Group->getLastInsertID();
        $group = $this->Group->findById($groupId);
        $user = $this->getUser();
        Logger::info("User '{$user['User']['username']}' ({$user['User']['id']}) created group '{$group['Group']['name']}' ({$group['Group']['id']})");
        $this->Session->setFlash(__("Add successfully group '%s'", $this->request->data['Group']['name']));
        $this->redirect("view/{$group['Group']['name']}");
      } else {
        $this->Session->setFlash(__("Could not create group '%s'", $this->request->data['Group']['name']));
      }
    }
  }

  function _sendSubscribtionRequest($group) {
    $this->Email->to = sprintf("%s <%s>", $group['User']['username'], $group['User']['email']);
    $user = $this->getUser();

    $this->Email->subject = "Group {$group['Group']['name']}: Subscription request for user {$user['User']['username']}";

    $this->Email->template = 'group_subscribtion_request';
    $this->set('group', $group);
    $this->set('user', $user);

    if (!$this->Email->send()) {
      Logger::err(sprintf("Could not send group subscription request to {$group['User']['username']} <{$group['User']['email']}>"));
      if ($this->Email->smtpError) {
        Logger::err($this->Email->smtpError);
      }
      $this->Session->setFlash(__('Mail could not be sent'));
      return false;
    }
    Logger::info("Sent group subscribe request of user {$user['User']['username']} for group {$group['Group']['name']} to {$group['User']['username']}");
    $this->Session->setFlash(__("Group subscription request was sent to the group owner"));
    return true;
  }

  function _sendConfirmation($group, $user) {
    $this->Email->to = sprintf("%s <%s>", $user['User']['username'], $user['User']['email']);
    $this->Email->subject = "Group {$group['Group']['name']}: Your subscription was accepted";
    $this->Email->template = 'group_confirmation';

    $this->set('group', $group);
    $this->set('user', $user);

    if (!$this->Email->send()) {
      Logger::err(sprintf("Could not send group confirmation to {$user['User']['username']} <{$user['User']['email']}>"));
      if ($this->Email->smtpError) {
        Logger::err($this->Email->smtpError);
      }
      return false;
    }
    Logger::info("Sent group confirmation to user {$user['User']['username']} for group {$group['Group']['name']}");
    return true;
  }

  function _sendSubscribtion($group) {
    $this->Email->to = sprintf("%s <%s>", $group['User']['username'], $group['User']['email']);
    $user = $this->getUser();

    $this->Email->subject = "Group {$group['Group']['name']}: Subscription request for user {$user['User']['username']}";

    $this->Email->template = 'group_subscribtion';
    $this->set('group', $group);
    $this->set('user', $user);

    if (!$this->Email->send()) {
      Logger::err(sprintf("Could not send new group subscription to {$group['User']['username']} <{$group['User']['email']}>"));
      return false;
    }
    Logger::info("Sent new group subscribtion of user {$user['User']['username']} for group {$group['Group']['name']} to {$group['User']['username']}");
    return true;
  }

  function _sendUnsubscribtion($group) {
    $this->Email->to = sprintf("%s <%s>", $group['User']['username'], $group['User']['email']);
    $user = $this->getUser();

    $this->Email->subject = "Group {$group['Group']['name']}: Subscription request for user {$user['User']['username']}";

    $this->Email->template = 'group_unsubscribtion';
    $this->set('group', $group);
    $this->set('user', $user);

    if (!$this->Email->send()) {
      Logger::err(sprintf("Could not send new group subscription to {$group['User']['username']} <{$group['User']['email']}>"));
      return false;
    }
    Logger::info("Sent new group subscribtion of user {$user['User']['username']} for group {$group['Group']['name']} to {$group['User']['username']}");
    return true;
  }

  function subscribe($name) {
    $group = $this->Group->findByName($name);
    if (!$group) {
      $this->Session->setFlash(__("%s not found", true, __("Group")));
      $this->redirect('index');
    }
    if ($group['Group']['is_moderated']) {
      $this->_sendSubscribtionRequest($group);
      $this->redirect("view/$name");
    } else {
      $result = $this->Group->subscribe($group, $this->getUserId());
      $this->Session->setFlash($result['message']);
      if ($result['code'] >= 400 && $result['code'] < 500) {
        $this->redirect("index");
      } else {
        if ($result['code'] == 201) {
          $this->_sendSubscribtion($group);
        }
        $this->redirect("view/$name");
      }
    }
  }

  function confirm($groupName, $userName) {
    $conditions = array('Group.name' => $groupName);
    if ($this->getUserRole() < ROLE_ADMIN) {
      $conditions['Group.user_id'] = $this->getUserId();
    }
    $group = $this->Group->find('all', array('conditions' => $conditions));
    $user = $this->User->findByUsername($userName);
    $userId = ($user) ? $user['User']['id'] : false;
    $result = $this->Group->subscribe($group, $userId);
    $this->Session->setFlash($result['message']);
    if ($result['code'] >= 400 && $result['code'] < 500) {
      $this->redirect("index");
    } else {
      $this->_sendConfirmation($group, $user);
      $this->Session->setFlash("Confirmed subscription of {$user['User']['username']}");
      $this->redirect("view/$groupName");
    }
  }

  function unsubscribe($name) {
    $group = $this->Group->findByName($name);
    $result = $this->Group->unsubscribe($group, $this->getUserId());
    $this->Session->setFlash($result['message']);
    if ($result['code'] >= 400 && $result['code'] < 500) {
      $this->redirect("index");
    } else {
      if ($result['code'] == 201) {
        $this->_sendUnsubscribtion($group);
      }
      $this->redirect("view/$name");
    }
  }

  function addMember($id) {
    $group = $this->Group->findById($id);
    if (!$this->Group->isAdmin(&$group, $this->getUser())) {
      $this->Session->setFlash(__("You are not authorized to perform this action"));
      $this->redirect("view/{$group['Group']['name']}");
    }
    $user = $this->User->findByUsername($this->request->data['Member']['new']);
    if (!$user) {
      $this->Session->setFlash(__("%s not found", true), __("User"));
      $this->redirect("view/{$group['Group']['name']}");
    }
    $result = $this->Group->subscribe($group, $user['User']['id']);
    if ($result['code'] >= 400 && $result['code'] < 500) {
      $this->redirect("index");
    } elseif ($result['code'] == 201) {
      $this->Session->setFlash(__("User %s is now subscribe to this group", $this->request->data['Member']['new']));
    }
    $this->redirect("view/{$group['Group']['name']}");
  }

  function deleteMember($groupName, $userName) {
    $group = $this->Group->findByName($groupName);
    if (!$this->Group->isAdmin(&$group, $this->getUser())) {
      $this->Session->setFlash(__("You are not authorized to perform this action"));
      $this->redirect("view/{$group['Group']['name']}");
    }
    $user = $this->User->findByUsername($userName);
    if (!$user) {
      $this->Session->setFlash(__("%s not found", true), __("User"));
      $this->redirect("view/{$group['Group']['name']}");
    }
    $result = $this->Group->unsubscribe($group, $user['User']['id']);
    if ($result['code'] >= 400 && $result['code'] < 500) {
      $this->redirect("index");
    } elseif ($result['code'] == 201) {
      $this->Session->setFlash(__("User %s is now unsubscribe from this group", $userName));
    }
    $this->redirect("view/{$group['Group']['name']}");
  }

  function edit($groupName) {
    if (!empty($this->request->data)) {
      if ($this->request->data['Group']['name'] != $groupName && !$this->Group->isNameUnique($this->request->data)) {
        $this->Session->setFlash(__("%s already exists", true, __('Group')));
      } elseif (!$this->Group->save($this->request->data)) {
        $this->Session->setFlash(__("Could not save %s", true, __('Group')));
      } else {
        $this->Session->setFlash(__("%s updated", true, __('Group')));
        if ($groupName != $this->request->data['Group']['name']) {
          $this->redirect("edit/{$this->request->data['Group']['name']}");
        }
      }
    }
    $conditions = array('Group.name' => $groupName);
    if ($this->getUserRole() < ROLE_ADMIN) {
      $conditions['Group.user_id'] = $this->getUserId();
    }
    $this->request->data = $this->Group->find('all', array('conditions' => $conditions));
    if (!$this->request->data) {
      $this->Session->setFlash(__("Could not find group"));
      $this->redirect("index");
    }
  }

  /**
    @todo Reset all group information of image 
    @todo Check for permission! */
  function delete($groupId) {
    $userId = $this->getUserId();
    $group = $this->Group->find('first', array('conditions' => array('Group.id' => $groupId, 'Group.user_id' => $userId)));
    if ($group) {
      $this->Group->delete($groupId);
      $user = $this->getUser();
      Logger::info("User '{$user['User']['username']}' ({$user['User']['id']}) deleted group '{$group['Group']['name']}' ({$group['Group']['id']})");
      $this->Session->setFlash(__("Successfully deleted group '%s'", $group['Group']['name']));
    } else {
      $this->Session->setFlash(__("Could not find group"));
    }
    $this->redirect("index");
  }

}
?>
