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
App::uses('CakeEmail', 'Network/Email');

class CommentsController extends AppController
{
  var $name = 'Comments';
  var $uses = array('Comment', 'Media');
  var $helpers = array('Html', 'Time', 'Text', 'Form', 'Rss', 'Paginator');
  var $components = array('Captcha');

  var $paginate = array (
    'fields' => array ('Comment.id', 'Comment.created', 'Comment.name', 'Comment.date', 'Comment.text', 'Media.id', 'Media.name' ),
    'limit' => 10,
    'order' => array (
      'Comment.date' => 'desc'
    )
  );
  var $namedArgs = '';

  public function beforeFilter() {
    parent::beforeFilter();

    $params = array();
    foreach ($this->request->params['named'] as $key => $value) {
      $params[] = $key.':'.$value;
    }
    $this->namedArgs = implode('/', $params);
  }

  public function index() {
    $this->Comment->currentUser = $this->getUser();
    $data = $this->paginate('Comment');
    $this->set('comments', $data);
  }

  public function view($id = null) {
    if (!$id) {
      $this->Session->setFlash(__('Invalid Comment.'));
      $this->redirect(array('action'=>'index'));
    }
    $this->set('comment', $this->Comment->read(null, $id));
  }

  public function add() {
    if (!empty($this->request->data) && isset($this->request->data['Media']['id'])) {
      $mediaId = intval($this->request->data['Media']['id']);
      $user = $this->getUser();
      $userId = $this->getUserId();
      $role = $this->getUserRole();

      // Fetch required authentication method
      if ($role < ROLE_GUEST) {
        $auth = (COMMENT_AUTH_NAME | COMMENT_AUTH_CAPTCHA);
      } elseif ($role <= ROLE_GUEST) {
        $auth = $this->getOption('comment.auth', COMMENT_AUTH_NONE);
      } else {
        $auth = COMMENT_AUTH_NONE;
      }

      // Check capatcha if required
      if (($auth & COMMENT_AUTH_CAPTCHA) > 0 && (!$this->Session->check('captcha') || $this->request->data['Captcha']['verification'] != $this->Session->read('captcha'))) {
        $this->Session->setFlash("Verification failed");
        Logger::warn("Captcha verification failed: ".$this->request->data['Captcha']['verification']." != ".$this->Session->read('captcha'));
        $this->Session->delete('captcha');
        $this->Session->write('Comment.data', $this->request->data);
        $this->Session->write('Comment.validationErrors', $this->Comment->validationErrors);
        $this->redirect("/images/view/$mediaId/{$this->namedArgs}");
      }
      $this->Session->delete('captcha');

      // Get media and check permissons
      $media = $this->Media->findById($mediaId);
      if (!$media) {
        $this->Session->setFlash("Media not found");
        Logger::info("Media $mediaId not found");
        $this->redirect("/explorer");
      }
      if (!$this->Media->canRead($media, $user)) {
        $this->Session->setFlash("Media not found");
        Logger::info("Comments denied to media $mediaId");
        $this->redirect("/explorer");
      }

      $this->Comment->create();
      $this->request->data['Comment']['media_id'] = $mediaId;
      $this->request->data['Comment']['date'] = date("Y-m-d H:i:s", time());
      $this->request->data['Comment']['text'] = Sanitize::html($this->request->data['Comment']['text']);
      if (($auth & COMMENT_AUTH_NAME) == 0) {
        $this->request->data['Comment']['user_id'] = $user['User']['id'];
        $this->request->data['Comment']['name'] = $user['User']['username'];
        $this->request->data['Comment']['email'] = $user['User']['email'];
      }
      if ($this->Comment->save($this->request->data)) {
        $commentId = $this->Comment->getLastInsertID();
        $this->Session->setFlash(__('The Comment has been saved'));
        Logger::info("New comment of media $mediaId");
        // Send email notification of other media owners
        if ($media['Media']['user_id'] != $userId) {
          $this->_sendEmail($commentId);
        }
        $this->_sendNotifies($mediaId, $commentId);
      } else {
        $this->Session->setFlash(__('The Comment could not be saved. Please, try again.'));
        Logger::err("Could not save comment to media $mediaId");
        Logger::trace($this->Comment->validationErrors);
        $this->Session->write('Comment.data', $this->request->data);
        $this->Session->write('Comment.validationErrors', $this->Comment->validationErrors);
      }
      $this->redirect("/images/view/$mediaId/{$this->namedArgs}");
    } else {
      $this->redirect("/explorer");
    }
  }

  public function _createEmail() {
    $Email = new CakeEmail('default');
    $Email->helpers('Html');
    return $Email;
  }

  public function _sendEmail($commentId) {
    $comment = $this->Comment->findById($commentId);
    if (!$comment) {
      Logger::err("Could not find comment $commentId");
      return;
    }
    $user = $this->User->findById($comment['Media']['user_id']);
    if (!$user) {
      Logger::err("Could not find user '{$comment['Media']['user_id']}'");
      return;
    }
    $email = $this->_createEmail();
    $email->template('comment')
      ->to(array($user['User']['email'] => $user['User']['firstname'] . ' ' . $user['User']['lastname']))
      ->subject(__('[phtagr] Comment: %s', $comment['Media']['name']))
      ->viewVars(array('user' => $user, 'data' => $comment));

    try {
      $email->send();
      Logger::info("Notification mail for new comment send to {$user['User']['email']}");
    } catch (Exception $e) {
      Logger::warn("Could not send notification mail for new comment");
    }
  }

  /**
   * Send email notifications to previous commentator which enables the mail
   * notification. It collects all emails of previous commentators who accepted
   * a notification mail
   *
   * @param mediaId Current media id
   * @param commentId Id of the new comment
   */
  public function _sendNotifies($mediaId, $commentId) {
    $this->Media->bindModel(array('hasMany' => array('Comment')));
    $media = $this->Media->findById($mediaId);
    if (!$media) {
      Logger::err("Could not find media $mediaId");
      return;
    }
    $comment = $this->Comment->findById($commentId);
    if (!$comment || $comment['Comment']['media_id'] != $mediaId) {
      Logger::err("Could not find comment $commentId");
      return;
    } elseif ($comment['Comment']['media_id'] != $mediaId) {
      Logger::err("Comment $commentId does not corrolate with media $mediaId");
      return;
    }

    $emails = array();
    foreach($media['Comment'] as $c) {
      // not media owner, disabled notify, current comment
      if ($c['user_id'] == $media['Media']['user_id'] ||
        !$c['notify'] ||
        $c['id'] == $commentId) {
        continue;
      }
      $emails[] = $c['email'];
    }
    if (!count($emails)) {
      Logger::debug("No user for comment update notifications found");
      return;
    }

    $emails = array_unique($emails);
    $to = array_pop($emails);

    $email = $this->_createEmail();
    $email->template('commentnotify')
      ->to($to)
      ->bcc($emails)
      ->subject(__('[phtagr] Comment notification: %s', $media['Media']['name']))
      ->viewVars(array('data' => $comment));

    Logger::debug($comment);
    try {
      $email->send();
      Logger::info("Send comment update notification to: $to, bbc to: " . implode(', ', $emails));
    } catch (Exception $e) {
      Logger::warn("Could not send comment update notification mail for new comment");
    }
  }

  public function delete($id = null) {
    if (!$id) {
      $this->Session->setFlash(__('Invalid id for Comment'));
      $this->redirect("/explorer");
    }
    $this->requireRole(ROLE_USER, array('loginRedirect' => '/comments/delete/'.$id));

    $comment = $this->Comment->findById($id);
    if (!$comment) {
      $this->redirect("/explorer");
    }
    $userId = $this->getUserId();

    // Allow only comment owner, media owner or admins
    if ((isset($comment['User']['id']) && $comment['User']['id'] == $userId) || ($comment['Media']['user_id'] == $userId) || ($this->getUserRole() == ROLE_ADMIN)) {
      if ($this->Comment->delete($id)) {
        $this->Session->setFlash(__('Comment deleted'));
        Logger::info("Delete comment {$comment['Comment']['id']} of media {$comment['Media']['id']}");
      }
    } else {
      $this->Session->setFlash("Deny deletion of comment");
      Logger::warn("Deny deletion of comment");
    }
    $this->redirect("/images/view/".$comment['Media']['id']."/{$this->namedArgs}");
  }

  public function captcha() {
    $this->Captcha->render();
  }

  public function rss() {
    $this->layoutPath = 'rss';
    $this->Comment->currentUser = $this->getUser();
    $data = $this->paginate('Comment');
    $this->set('data', $data);

    if (Configure::read('debug') > 1) {
      Configure::write('debug', 1);
    }
  }
}
?>
