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

App::uses('Folder', 'Utility');
App::uses('CakeEmail', 'Network/Email');

class UsersController extends AppController
{
  var $components = array('RequestHandler', 'Cookie', 'Captcha', 'Search');
  var $uses = array('Option', 'Media', 'MyFile');
  var $helpers = array('Form', 'Number', 'Time', 'Text', 'ImageData');
  var $paginate = array('limit' => 10, 'order' => array('User.username' => 'asc'));
  var $subMenu = false;

  public function beforeFilter() {
    parent::beforeFilter();
    $this->subMenu = array(
      'index' => __("List User"),
      );
    if ($this->hasRole(ROLE_SYSOP)) {
      $this->subMenu = am($this->subMenu, array(
        array('action' => 'add', 'title' => __("Add User"), 'admin' => true),
        ));
    }
    $this->layout = 'backend';
  }

  public function beforeRender() {
    parent::beforeRender();
  }

  public function __fromReadableSize($readable) {
    if (is_float($readable) || is_numeric($readable)) {
      return $readable;
    } elseif (preg_match_all('/^\s*(0|[1-9][0-9]*)(\.[0-9]+)?\s*([KkMmGg][Bb]?)?\s*$/', $readable, $matches, PREG_SET_ORDER)) {
      $matches = $matches[0];
      $size = (float)$matches[1];
      if (is_numeric($matches[2])) {
        $size += $matches[2];
      }
      if (is_string($matches[3])) {
        switch ($matches[3][0]) {
          case 'k':
          case 'K':
            $size = $size * 1024;
            break;
          case 'm':
          case 'M':
            $size = $size * 1024 * 1024;
            break;
          case 'g':
          case 'G':
            $size = $size * 1024 * 1024 * 1024;
            break;
          case 'T':
            $size = $size * 1024 * 1024 * 1024 * 1024;
            break;
          default:
            Logger::err("Unknown unit {$matches[3]}");
        }
      }
      if ($size < 0) {
        Logger::err("Size is negtive: $size");
        return 0;
      }
      return $size;
    } else {
      return 0;
    }
  }

  public function index() {
    $this->set('isAdmin', $this->hasRole(ROLE_SYSOP));
    $this->request->data = $this->User->findVisibleUsers($this->getUser());
  }

  public function view($name) {
    $this->request->data = $this->User->findVisibleUsers($this->getUser(), $name);
    if (!$this->request->data) {
      $this->redirect('index');
    }
    $userId = $this->request->data['User']['id'];
    $this->request->data['Media']['count'] = $this->Media->find('count', array('conditions' => array('Media.user_id' => $userId), 'recursive' => -1));
    $this->request->data['File']['count'] = $this->MyFile->find('count', array('conditions' => array('File.user_id' => $userId), 'recursive' => -1));
    $bytes = $this->MyFile->find('all', array('conditions' => array("File.user_id" => $userId), 'recursive' => -1, 'fields' => 'SUM(File.size) AS Bytes'));
    $this->request->data['File']['bytes'] = max(0, $bytes[0][0]['Bytes']);

    $groupUserIds = Set::extract('/Group/user_id');
    $this->set('users', $this->User->find('all', array('condition' => array('User.id' => $groupUserIds), 'recursive' => -1)));

    $this->Search->setUser($this->request->data['User']['username']);
    $this->Search->setShow(6);
    $this->set('media', $this->Search->paginate());
  }

  /** Checks the login of the user. If the session variable 'loginRedirect' is
   * set the user is forwarded to this given address on successful login. */
  public function login() {
    $failedText = __("Sorry. Wrong password or unknown username!");
    if (!empty($this->request->data) && !$this->RequestHandler->isPost()) {
      Logger::warn("Authentication failed: Request was not HTTP POST");
      $this->Session->setFlash($failedText);
      $this->request->data = null;
    }
    if (empty($this->request->data['User']['username']) xor empty($this->request->data['User']['password'])) {
      Logger::warn("Authentication failed: Username or password are not set");
      $this->Session->setFlash(__("Please enter username and password!"));
      $this->request->data = null;
    }

    if (!empty($this->request->data)) {
      $user = $this->User->findByUsername($this->request->data['User']['username']);

      if (!$user) {
        Logger::warn("Authentication failed: Unknown username '{$this->request->data['User']['username']}'!");
        $this->Session->setFlash($failedText);
      } elseif ($this->User->isExpired($user)) {
        Logger::warn("User account of '{$user['User']['username']}' (id {$user['User']['id']}) is expired!");
        $this->Session->setFlash(__("Sorry. Your account is expired!"));
      } else {
        $user = $this->User->decrypt($user);
        if ($user['User']['password'] == $this->request->data['User']['password']) {
          $this->Session->renew();
          if (!$this->Session->check('User.id') || $this->Session->read('User.id') != $user['User']['id']) {
            Logger::info("Start new session for '{$user['User']['username']}' (id {$user['User']['id']})");
            $this->User->writeSession($user, $this->Session);

            // Save Cookie for 3 months
            $this->Cookie->write('user', $user['User']['id'], true, 92*24*3600);
            Logger::debug("Write authentication cookie for user '{$user['User']['username']}' (id {$user['User']['id']})");

            Logger::info("Successfull login of user '{$user['User']['username']}' (id {$user['User']['id']})");
            if ($this->Session->check('loginRedirect')) {
              $this->redirect($this->Session->read('loginRedirect'));
              $this->Session->delete('loginRedirect');
            } else {
              $this->redirect('/');
            }
          } else {
            Logger::err("Could not write session information of user '{$user['User']['username']}' ({$user['User']['id']})");
            $this->Session->setFlash(__("Sorry. Internal login procedure failed!"));
          }
        } else {
          Logger::warn("Authentication failed: Incorrect password of username '{$this->request->data['User']['username']}'!");
          $this->Session->setFlash($failedText);
        }
      }
      unset($this->request->data['User']['password']);
    }
    $this->set('register', $this->Option->getValue($this->getUser(), 'user.register.enable', 0));
    $this->layout = 'default';
  }

  public function logout() {
    $user = $this->getUser();
    Logger::info("Delete session for user id {$user['User']['id']}");

    $this->Session->destroy();

    $this->Cookie->name = 'phTagr';
    $this->Cookie->destroy();

    $this->redirect('/');
  }

  public function admin_index() {
  $userId = $this->getUserId();

    $this->requireRole(ROLE_SYSOP, array('loginRedirect' => '/admin/users'));

    $this->request->data = $this->paginate('User', array('User.role>='.ROLE_USER));

  foreach($this->request->data as $user):
 
    $userId = $user['User']['id'];
     
    $this->request->data['calc'][$userId]['MediaCount'] = $this->Media->find('count', array('conditions' => array('Media.user_id' => $userId), 'recursive' => -1));
    $this->request->data['calc'][$userId]['FileCount'] = $this->MyFile->find('count', array('conditions' => array('File.user_id' => $userId), 'recursive' => -1));

    $this->request->data['calc'][$userId]['files.external'] = $this->Media->File->find('count', array('conditions' => array('File.flag & ' . FILE_FLAG_EXTERNAL. ' > 0', 'File.user_id' => $userId)));

    $bytes = $this->MyFile->find('all', array('conditions' => array("File.user_id" => $userId), 'recursive' => -1, 'fields' => 'SUM(File.size) AS Bytes'));
    $this->request->data['calc'][$userId]['FileBytes'] = max(0, floatval($bytes[0][0]['Bytes']));


    $bytes = $this->Media->File->find('all', array('conditions' => array("File.flag & ".FILE_FLAG_EXTERNAL." > 0", 'Media.user_id' => $userId), 'fields' => 'SUM(File.size) AS Bytes'));
    $this->request->data['calc'][$userId]['file.size.external'] = floatval($bytes[0][0]['Bytes']);

    $bytes = $this->Media->File->find('all', array('conditions' => array("File.flag & ".FILE_FLAG_EXTERNAL." = 0", 'Media.user_id' => $userId), 'fields' => 'SUM(File.size) AS Bytes'));
    $this->request->data['calc'][$userId]['file.size.internal'] = floatval($bytes[0][0]['Bytes']);

  endforeach;
  }

  /** Ensure at least one admin exists
    @param id Current user
    @return True if at least one system operator exists */
  public function _lastAdminCheck($id) {
    $userId = $this->getUserId();
    $userRole = $this->getUserRole();
    if ($userId == $id && $userRole == ROLE_ADMIN && $this->request->data['User']['role'] < ROLE_ADMIN) {
      $count = $this->User->find('count', array('conditions' => array('User.role >= '.ROLE_ADMIN)));
      if ($count == 1) {
        Logger::warn('Can not degrade last admin');
        $this->Session->setFlash(__('Can not degrade last admin'));
        return false;
      }
    }
    return true;
  }

  /** Add 3rd level menu for user edit for admin */
  public function _addAdminEditMenu($userId) {
    $subActions = array(
      'password' => __("Password"),
      'path' => __("Local Paths"));
    $subItems = array('url' => array('admin' => true, 'action' => 'edit', $userId), 'title' => __('Edit'), 'active' => true);
    foreach ($subActions as $action => $title) {
      $subItems[] = array('url' => array('admin' => true, 'action' => $action, $userId), 'title' => $title, 'active' => ('admin_'.$action == $this->action));
    }
    Logger::debug($this->action);
    $this->subMenu[] = $subItems;
  }

  public function admin_edit($id) {
    $this->requireRole(ROLE_SYSOP, array('loginRedirect' => '/admin/users'));

    $id = intval($id);
    if (!empty($this->request->data) && $this->_lastAdminCheck($id)) {
      $this->request->data['User']['id'] = $id;

      if ($this->User->save($this->request->data, true, array('email', 'expires', 'quota', 'firstname', 'lastname', 'role'))) {
        Logger::debug("Data of user {$this->request->data['User']['id']} was updated");
        $this->Session->setFlash(__('User data was updated'));
      } else {
        Logger::err("Could not save user data");
        Logger::debug($this->User->validationErrors);
        $this->Session->setFlash(__('Could not be updated'));
      }
    }

    $this->request->data = $this->User->findById($id);
    $this->set('allowAdminRole', ($this->getUserRole() == ROLE_ADMIN) ? true : false);

    $this->_addAdminEditMenu($id);
  }

  public function admin_password($id) {
    $this->requireRole(ROLE_SYSOP, array('loginRedirect' => '/admin/users'));

    $id = intval($id);
    if (!empty($this->request->data)) {
      $this->request->data['User']['id'] = $id;

      if ($this->User->save($this->request->data, true, array('password'))) {
        Logger::debug("Data of user {$this->request->data['User']['id']} was updated");
        $this->Session->setFlash(__('User data was updated'));
      } else {
        Logger::err("Could not save user data");
        Logger::debug($this->User->validationErrors);
        $this->Session->setFlash(__('Could not be updated'));
      }
    }

    $this->request->data = $this->User->findById($id);
    unset($this->request->data['User']['password']);

    $this->_addAdminEditMenu($id);
  }

  public function admin_path($id) {
    $this->requireRole(ROLE_SYSOP, array('loginRedirect' => '/admin/users'));

    $id = intval($id);
    if (!empty($this->request->data)) {
      $this->request->data['User']['id'] = $id;

      $this->User->set($this->request->data);

      if (!empty($this->request->data['Option']['path']['fspath'])) {
        $fsroot = $this->request->data['Option']['path']['fspath'];
        $fsroot = Folder::slashTerm($fsroot);

        if (is_dir($fsroot) && is_readable($fsroot)) {
          $this->Option->addValue('path.fsroot[]', $fsroot, $id);
          $this->Session->setFlash(__("Directory '%s' was added", $fsroot));
          Logger::info("Add external directory '$fsroot' to user $id");
        } else {
          $this->Session->setFlash(__("Directory '%s' could not be read", $fsroot));
          Logger::err("Directory '$fsroot' could not be read");
        }
      }
    }

    $this->request->data = $this->User->findById($id);
    unset($this->request->data['User']['password']);

    $this->set('fsroots', $this->Option->buildTree($this->request->data, 'path.fsroot'));
    $this->_addAdminEditMenu($id);
  }

  public function admin_add() {
    $this->requireRole(ROLE_SYSOP, array('loginRedirect' => '/admin/users'));

    if (!empty($this->request->data)) {
      if ($this->User->hasAny(array('User.username' => $this->request->data['User']['username']))) {
        $this->Session->setFlash(__('Username already exists, please choose a different name!'));
      } else {
        $this->request->data['User']['role'] = ROLE_USER;
        if ($this->User->save($this->request->data, true, array('username', 'password', 'role', 'email'))) {
          Logger::info("New user {$this->request->data['User']['username']} was created");
          $this->Session->setFlash(__('User was created'));
          $this->redirect('/admin/users/edit/'.$this->User->id);
        } else {
          Logger::warn("Creation of user {$this->request->data['User']['username']} failed");
          $this->Session->setFlash(__('Could not create user!'));
        }
      }
    }
  }

  public function admin_del($id) {
    $this->requireRole(ROLE_SYSOP, array('loginRedirect' => '/admin/users'));

    $id = intval($id);
    $user = $this->User->findById($id);
    if (!$user) {
      $this->Session->setFlash(__("Could not delete user: user not found!"));
      $this->redirect('index');
    } else {
      $this->User->delete($id);
      Logger::notice("All data of user '{$user['User']['username']}' ($id) deleted");
      $this->Session->setFlash(__("User %s was deleted", $user['User']['username']));
      $this->redirect('index');
    }
  }

  public function admin_delpath($id) {
    $this->requireRole(ROLE_SYSOP);

    $id = intval($id);
    $dirs = $this->request->params['pass'];
    unset($dirs[0]);
    $fsroot = implode(DS, $dirs);
    if (DS == '/') {
      $fsroot = '/'.$fsroot;
    }
    $fsroot = Folder::slashTerm($fsroot);

    $this->Option->delValue('path.fsroot[]', $fsroot, $id);
    Logger::info("Deleted external directory '$fsroot' from user $id");
    $this->Session->setFlash(__("Deleted external directory '%s'", $fsroot));
    $this->redirect("path/$id");
  }

  public function _createEmail() {
    $Email = new CakeEmail('default');
    $Email->helpers('Html');
    return $Email;
  }

  /**
   * Password recovery
   */
  public function password() {
    if (!empty($this->request->data)) {
      $user = $this->User->find('first', array('conditions' => array(
          'username' => $this->request->data['User']['username'],
          'email' => $this->request->data['User']['email'])));
      if (empty($user)) {
        $this->Session->setFlash(__('No user with this email was found'));
        Logger::warn(sprintf("No user '%s' with email %s was found",
            $this->request->data['User']['username'], $this->request->data['User']['email']));
      } else {
        $user = $this->User->decrypt($user);

        $email = $this->_createEmail();
        $email->template('password')
          ->to(array($user['User']['email'] => $user['User']['username']))
          ->subject(__('[phtagr] Password Request'))
          ->viewVars(array('user' => $user));

        try {
          $email->send();
          Logger::info(sprintf("Sent password mail to user '%s' (id %d) with address %s",
            $user['User']['username'],
            $user['User']['id'],
            $user['User']['email']));
          $this->Session->setFlash(__('Mail was sent!'));
        } catch (Exception $e) {
          Logger::err(sprintf("Could not send password mail to user '%s' (id %d) with address '%s'",
            $user['User']['username'],
            $user['User']['id'],
            $user['User']['email']));
          $this->Session->setFlash(__('Mail could not be sent: unknown error!'));
        }
      }
    }
    $this->layout = 'default';
  }

  public function register() {
    if ($this->getUserRole() != ROLE_NOBODY) {
      $this->redirect('/');
    } elseif (!$this->getOption('user.register.enable', 0)) {
      Logger::verbose("User registration is disabled");
      $this->redirect('login');
    }

    if (!empty($this->request->data)) {
      if ($this->request->data['Captcha']['verification'] != $this->Session->read('user.register.captcha')) {
        $this->Session->setFlash(__('Captcha verification failed'));
        Logger::verbose("Captcha verification failed");
      } elseif ($this->User->hasAny(array('User.username' => $this->request->data['User']['username']))) {
        $this->Session->setFlash(__('Username already exists, please choose different name!'));
        Logger::info("Username already exists: {$this->request->data['User']['username']}");
      } else {
        $user = $this->User->create($this->request->data);
        if ($this->User->save($user['User'], true, array('username', 'password', 'email'))) {
          Logger::info("New user {$this->request->data['User']['username']} was created");
          $this->_initRegisteredUser($this->User->getLastInsertID());
        } else {
          Logger::err("Creation of user {$this->request->data['User']['username']} failed");
          $this->Session->setFlash(__('Could not create user'));
        }
      }
    }
    unset($this->request->data['User']['password']);
    unset($this->request->data['User']['confirm']);
    unset($this->request->data['Captcha']['verification']);
    $this->layout = 'default';
  }

  public function captcha() {
    if (!$this->getOption('user.register.enable', 0)) {
      $this->redirect(null, 404);
    }

    $this->Captcha->render('user.register.captcha');
  }

  public function _initRegisteredUser($newUserId) {
    $user = $this->User->findById($newUserId);
    if (!$user) {
      Logger::err("Could not find user with ID $newUserId");
      return false;
    }
    $options = $this->Option->getTree(0);
    $user['User']['role'] = ROLE_USER;
    $user['User']['expires'] = date("Y-m-d H:i:s", time() - 3600);
    $user['User']['quota'] = $this->Option->getValue($options, 'user.register.quota', 0);
    if (!$this->User->save($user['User'], true, array('role', 'expires', 'quota'))) {
      Logger::err("Could not init user $newUserId");
      return false;
    }

    // set confirmation key
    $key = md5($newUserId.':'.$user['User']['username'].':'.$user['User']['password'].':'.$user['User']['expires']);
    $this->Option->setValue('user.register.key', $key, $newUserId);
    // send confimation email
    if (!$this->_sendConfirmationEmail($user, $key)) {
      $this->Session->setFlash(__("Could not send the confirmation email. Please contact the admin."));
      if (!$this->User->delete($user['User']['id'])) {
        Logger::err("Could not delete user {$user['User']['id']} due email error");
      } else {
        Logger::info("Delete user {$user['User']['id']} due email error");
      }
      return false;
    }
    $this->Session->setFlash(__("A confirmation email was sent to your email address"));
    $this->redirect("/users/confirm");
  }

  public function confirm($key = false) {
    if ($this->getUserRole() != ROLE_NOBODY) {
      $this->redirect('/');
    } elseif (!$this->getOption('user.register.enable', 0)) {
      Logger::verbose("User registration is disabled");
      $this->redirect(null, 404);
    }

    if (!$key && !empty($this->request->data)) {
      // check user input
      if (empty($this->request->data['User']['key'])) {
        $this->Session->setFlash(__("Please enter the confirmation key"));
      } else {
        $key = $this->request->data['User']['key'];
      }
    }

    if ($key) {
      $this->_checkConfirmation($key);
    }
  }

  /**
   * Verifies the confirmation key and activates the new user account
   * @param key Account confirmation key
   */
  public function _checkConfirmation($key) {
    // check key. Option [belongsTo] User: The user is bound to option
    $user = $this->Option->find('first', array('conditions' => array("Option.value" => $key)));
    if (!$user) {
      Logger::trace("Could not find confirmation key");
      $this->Session->setFlash(__("Could not find confirmation key"));
      return false;
    }

    if (!isset($user['User']['id'])) {
      Logger::err("Could not find the user for register confirmation");
      $this->Session->setFlash(__("Internal error occured"));
      return false;
    }

    // check expiration (14 days+1h). After this time, the account will be
    // deleted
    $now = time();
    $expires = strtotime($user['User']['expires']);
    if ($now - $expires > (14 * 24 * 3600 + 3600)) {
      $this->Session->setFlash(__("Could not find confirmation key"));
      Logger::err("Registration confirmation is expired.");
      $this->User->delete($user['User']['id']);
      Logger::info("Deleted user from expired registration");
      return false;
    }

    // activate user account (disabling the expire date)
    $user['User']['expires'] = null;
    if (!$this->User->save($user['User'], true, array('expires'))) {
      Logger::err("Could not update expires of user {$user['User']['id']}");
      return false;
    }

    // send email to user and notify the sysops
    $this->_sendNewAccountEmail($user);
    $this->_sendNewAccountNotifiactionEmail($user);

    // delete confirmation key
    $this->Option->delete($user['Option']['id']);

    // login the user automatically
    $this->User->writeSession($user, $this->Session);
    $this->redirect('/options/profile');
  }

  /**
   * Send the confirmation email to the new user
   * @param user User model data
   * @param key Confirmation key to activate the account
   */
  public function _sendConfirmationEmail($user, $key) {
    $email = $this->_createEmail();
    $email->template('new_account_confirmation', 'default')
      ->to(array($user['User']['email'] => $user['User']['username']))
      ->subject(__('[phtagr] Account confirmation: %s', $user['User']['username']))
      ->viewVars(array('user' => $user, 'key' => $key));

    try {
      $email->send();
      Logger::info("Account confirmation mail send to {$user['User']['email']} for new user {$user['User']['username']}");
      return true;
    } catch (Exception $e) {
      Logger::err("Could not send account confirmation mail to {$user['User']['email']} for new user {$user['User']['username']}");
      return false;
    }
  }

  /**
   * Send an email for the new account to the new user
   * @param user User model data
   */
  public function _sendNewAccountEmail($user) {
    $email = $this->_createEmail();
    $email->template('new_account')
      ->to($user['User']['email'])
      ->subject(__('[phtagr] Welcome %s', $user['User']['username']))
      ->viewVars(array('user' => $user));

    try {
      $email->send();
      Logger::info("New account mail send to {$user['User']['email']} for new user {$user['User']['username']}");
      return true;
    } catch (Exception $e) {
      Logger::err("Could not send new account mail to {$user['User']['email']} for new user {$user['User']['username']}");
      return false;
    }
  }

  /**
   * Send a notification email to all system operators (and admins) of the new
   * account
   * @param user User model data (of the new user)
   */
  public function _sendNewAccountNotifiactionEmail($user) {
    $sysOps = $this->User->find('all', array('conditions' => "User.role >= ".ROLE_SYSOP));
    if (!$sysOps) {
      Logger::err("Could not find system operators");
      return false;
    }
    $sysOpsNames = Set::extract('/User/username', $sysOps);
    $first = array_pop($sysOps);
    $to = array($first['User']['email'] => $first['User']['username']);

    $email = $this->_createEmail();
    $email->template('new_account_notification')
      ->to($to)
      ->subject(__('[phtagr] New account notification: %s', $user['User']['username']))
      ->viewVars(array('user' => $user));
    foreach ($sysOps as $sysOp) {
      $email->cc($sysOp['User']['email'], $sysOp['User']['username']);
    }

    try {
      $email->send();
      Logger::info("New account notification email send to system operators " . implode(', ', $sysOpsNames));
      return true;
    } catch (Exception $e) {
      Logger::err("Could not send new account notification email to system operators ".implode(', ', $sysOpsNames));
      return false;
    }
  }

}
?>
