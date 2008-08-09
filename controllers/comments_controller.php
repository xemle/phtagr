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

class CommentsController extends AppController 
{
  var $name = 'Comments';
  var $uses = array('Comment', 'Image');
  var $helpers = array('Html', 'Form', 'Rss');
  var $components = array('Query', 'Captcha', 'Email');

  function beforeFilter() {
    parent::beforeFilter();

    $this->Query->parseArgs();
  }

  function view($id = null) {
    if (!$id) {
      $this->Session->setFlash(__('Invalid Comment.', true));
      $this->redirect(array('action'=>'index'));
    }
    $this->set('comment', $this->Comment->read(null, $id));
  }

  function add() {
    if (!empty($this->data) && isset($this->data['Image']['id'])) {
      $imageId = intval($this->data['Image']['id']);
      $url = $this->Query->getUrl();
      $user = $this->getUser();
      $userId = $this->getUserId();

      if ($userId <= 0 && (!$this->Session->check('captcha') || $this->data['Captcha']['verification'] != $this->Session->read('captcha'))) {
        $this->Session->setFlash("Verification failed");
        $this->Logger->warn("Captcha verification failed: ".$this->data['Captcha']['verification']." != ".$this->Session->read('captcha'));
        $this->Session->delete('captcha');
        $this->redirect("/images/view/$imageId/$url");        
      }
      $this->Session->delete('captcha');

      $image = $this->Image->findById($imageId);
      if (!$image) {
        $this->Session->setFlash("Image not found");
        $this->Logger->info("Image $imageId not found");
        $this->redirect("/explorer");
      }
      if (!$this->Image->checkAccess($image, $user, ACL_READ_PREVIEW, ACL_READ_MASK)) {
        $this->Session->setFlash("Image not found");
        $this->Logger->info("Comments denied to image $imageId");
        $this->redirect("/explorer");
      }

      $this->Comment->create();
      $this->data['Comment']['image_id'] = $imageId;
      $this->data['Comment']['date'] = date("Y-m-d H:i:s", time());
      uses('Sanitize');
      $this->data['Comment']['text'] = Sanitize::html($this->data['Comment']['text']);
      if ($userId > 0) {
        $this->data['Comment']['user_id'] = $user['User']['id'];
        $this->data['Comment']['name'] = $user['User']['username'];
        $this->data['Comment']['email'] = $user['User']['email'];
      }
      if ($this->Comment->save($this->data)) {
        $this->Session->setFlash(__('The Comment has been saved', true));
        $this->Logger->info("New comment of image $imageId");
        // Send email notification of other image owners
        if ($image['Image']['user_id'] != $userId) {
          $commentId = $this->Comment->getLastInsertID();
          $this->_sendEmail($commentId);
        }
        $this->_sendNotifies($imageId, $commentId);
      } else {
        $this->Session->setFlash(__('The Comment could not be saved. Please, try again.', true));
        $this->Logger->err("Could not save comment to image $imageId");
      }
      $this->redirect("/images/view/$imageId/$url");
    } else {
      $this->redirect("/explorer");
    }
  }

  function _sendEmail($commentId) {
    $comment = $this->Comment->findById($commentId);
    if (!$comment) {
      $this->Logger->err("Could not find comment $commentId");
      return;
    }
    $user = $this->User->findById($comment['Image']['user_id']);
    if (!$user) {
      $this->Logger->err("Could not find user '{$comment['Image']['user_id']}'");
      return;
    }
    $email = $user['User']['email'];
    
    $this->Email->to = sprintf("%s %s <%s>",
      $user['User']['firstname'],
      $user['User']['lastname'],
      $user['User']['email']);

    $this->Email->subject = 'New Comment of Image '.$comment['Image']['name'];
    $this->Email->replyTo = 'noreply@phtagr.org';
    $this->Email->from = 'phTagr <noreply@phtagr.org>';

    $this->Email->template = 'comment';
    $this->set('user', $user);
    $this->set('data', $comment);

    if (!$this->Email->send()) {
      $this->Logger->warn("Could not send notification mail for new comment");
    } else {
      $this->Logger->info("Notification mail for new comment send to {$user['User']['email']}");
    }
  }

  /** Send email notifications to previous commentator which enables the mail
   * notification. It collects all emails of previous commentators who accepted
   * a notification mail
    @param imageId Current image id
    @param commentId Id of the new comment */
  function _sendNotifies($imageId, $commentId) {
    $this->Image->bindModel(array('hasMany' => array('Comment')));
    $image = $this->Image->findById($imageId);
    if (!$image) {
      $this->Logger->err("Could not find image $imageId");
      return;
    }
    $comment = $this->Comment->findById($commentId); 
    if (!$comment || $comment['Comment']['image_id'] != $imageId) {
      $this->Logger->err("Could not find comment $commentId");
      return;
    } elseif ($comment['Comment']['image_id'] != $imageId) {
      $this->Logger->err("Comment $commentId does not corrolate with image $imageId");
      return;
    }

    $emails = array();
    foreach($image['Comment'] as $c) {
      // not image owner, disabled notify, current comment
      if ($c['user_id'] == $image['Image']['user_id'] || 
        !$c['notify'] ||
        $c['id'] == $commentId) {
        continue;
      }
      $emails[] = $c['email'];
    }
    if (!count($emails)) {
      $this->Logger->debug("No user for comment update notifications found");
      return;
    }

    $emails = array_unique($emails);
    $to = array_pop($emails);
    $this->Email->to = $to;
    $this->Email->bcc = $emails;

    $this->Email->subject = 'Comment notification of Image '.$image['Image']['name'];
    $this->Email->replyTo = 'noreply@phtagr.org';
    $this->Email->from = 'phTagr <noreply@phtagr.org>';

    $this->Email->template = 'commentnotify';
    $this->set('data', $comment);
    $this->Logger->debug($comment);
    if (!$this->Email->send()) {
      $this->Logger->warn("Could not send comment update notification mail for new comment");
    } else {
      $this->Logger->info("Send comment update notification to: $to, bbc to: ".implode(', ', $emails));
    }
  }

  function delete($id = null) {
    if (!$id) {
      $this->Session->setFlash(__('Invalid id for Comment', true));
      $this->redirect("/explorer");
    }
    $this->requireRole(ROLE_USER, array('loginRedirect' => '/comments/delete/'.$id));

    $comment = $this->Comment->findById($id);
    if (!$comment) {
      $this->redirect("/explorer");
    }
    $userId = $this->getUserId();

    // Allow only comment owner, image owner or admins
    if ((isset($comment['User']['id']) && $comment['User']['id'] == $userId) || ($comment['Image']['user_id'] == $userId) || ($this->getUserRole() == ROLE_ADMIN)) {
      if ($this->Comment->del($id)) {
        $this->Session->setFlash(__('Comment deleted', true));
        $this->Logger->info("Delete comment {$comment['Comment']['id']} of image {$comment['Image']['id']}");
      }
    } else {
      $this->Session->setFlash("Deny deletion of comment");
      $this->Logger->warn("Deny deletion of comment");
    }
    $url = $this->Query->getUrl();
    $this->redirect("/images/view/".$comment['Image']['id']."/$url");
  }

  function captcha() {
    $this->Captcha->render(); 
  }

  function rss() {
    $this->layoutPath = 'rss';
    $where = '1 = 1'.$this->Image->buildWhereAcl($this->getUser());
    $comments = $this->Comment->findAll($where, null, 'Comment.date', 20);
    $this->set('data', $comments);

    if (Configure::read('debug') > 1) {
      Configure::write('debug', 1);
    }
    $this->set(
        'channel', array(
          'title' => "New Comments",
          'link' => "/comments/rss",
          'description' => "Recently Published Comments" )
        );
  }
}
?>
