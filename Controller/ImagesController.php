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

class ImagesController extends AppController
{
  var $name = 'Images';
  var $components = array('RequestHandler', 'Search', 'FastFileResponder');
  var $uses = array('Media', 'Group');
  var $helpers = array('Form', 'Html', 'ImageData', 'Time', 'Search', 'ExplorerMenu', 'Rss', 'Map', 'Navigator', 'Flowplayer', 'Tab', 'Number', 'Option', 'Autocomplete');
  var $crumbs = array();

  public function beforeFilter() {
    parent::beforeFilter();
    $this->logUser();

    if ($this->hasRole(ROLE_USER)) {
      $groups = $this->Group->getGroupsForMedia($this->getUser());
      $groupSelect = Set::combine($groups, '{n}.Group.id', '{n}.Group.name');
      asort($groupSelect);
      $groupSelect[0] = __('[Keep]');
      $groupSelect[-1] = __('[No Group]');
      $this->set('groups', $groupSelect);
    } else {
      $this->set('groups', array());
    }

    $parts = split('/', urldecode($this->request->url));
    $encoded = array_splice($parts, 3);
    foreach ($encoded as $crumb) {
      $this->crumbs[] = $this->Search->decode($crumb);
    }
  }

  public function beforeRender() {
    parent::beforeRender();
    $this->set('crumbs', $this->crumbs);
    $this->request->params['crumbs'] = $this->crumbs;
  }

  /** Simple crawler detection
    @todo Verifiy and improve crawler detection */
  public function _isCrawler() {
    return (preg_match('/(agent|bot|crawl|search|spider|walker)/i', env('HTTP_USER_AGENT')) == 1);
  }

  /** Update the rating and clicks of a media. The rated media will be stored
   * in the session to avoid multiple rating per session. */
  public function _updateRating() {
    if (!$this->request->data || !isset($this->request->data['Media']['id'])) {
      Logger::warn("Precondition failed");
      return;
    }
    if (!$this->Session->check('Session.requestCount') ||
      $this->Session->read('Session.requestCount') <= 1) {
      Logger::verbose("No session found or request counter to low");
      return;
    } elseif ($this->_isCrawler()) {
      Logger::verbose("Deny ranking for crawler: ".env('HTTP_USER_AGENT'));
      return;
    }

    // Check for media rating
    $id = $this->request->data['Media']['id'];
    $ranked = array();
    if ($this->Session->check('Media.ranked')) {
      $ranked = $this->Session->read('Media.ranked');
    }
    if (in_array($id, $ranked)) {
      Logger::trace("Skip ranking for already rated media $id");
      return;
    }

    $this->Media->updateRanking($this->request->data);

    // update rated media ids
    $ranked[] = $id;
    $this->Session->write('Media.ranked', $ranked);
  }

  public function view($id) {
    $this->request->data = $this->Search->paginateMediaByCrumb($id, $this->crumbs);
    if (!$this->request->data) {
      $this->render('notfound');
    } else {
      $role = $this->getUserRole();
      if ($role >= ROLE_USER) {
        $commentAuth = COMMENT_AUTH_NONE;
      } elseif ($role >= ROLE_GUEST) {
        $commentAuth = $this->getOption('comment.auth', COMMENT_AUTH_NONE);
      } else {
        $commentAuth = (COMMENT_AUTH_NAME | COMMENT_AUTH_CAPTCHA);
      }
      $this->_updateRating();
      $this->set('userRole', $this->getUserRole());
      $this->set('userId', $this->getUserId());
      $this->set('commentAuth', $commentAuth);
      $this->set('mapKey', $this->getOption('google.map.key', false));

      if ($this->Session->check('Comment.data')) {
        $comment = $this->Session->read('Comment.data');
        $this->Comment->validationErrors = $this->Session->read('Comment.validationErrors');
        $this->request->data['Comment'] = am($comment['Comment'], $this->request->data['Comment']);
        //$this->request->data = am($this->Session->read('Comment.data'), $this->request->data);
        $this->Session->delete('Comment.data');
      }
      $this->FastFileResponder->add($this->request->data, 'preview');
    }
  }

  public function update($id) {
    if (!empty($this->request->data)) {
      $user = $this->getUser();
      $media = $this->Media->findById($id);
      if (!$media) {
        Logger::warn("Invalid media id: $id");
        $this->redirect(null, '404');
      } elseif (!$this->Media->canWrite($media, $user)) {
        Logger::warn("User '{$username}' ({$user['User']['id']}) has no previleges to change tags of image ".$id);
      } else {
        $this->Media->setAccessFlags($media, $user);
        $tmp = $this->Media->editSingle($media, $this->request->data, $user);
        if (!$this->Media->save($tmp)) {
          Logger::warn("Could not save media");
          Logger::debug($tmp);
        } else {
          Logger::info("Updated meta of media {$tmp['Media']['id']}");
        }
        if (isset($tmp['Media']['orientation'])) {
          $this->FileCache->delete($tmp);
          $this->FastFileResponder->excludeMedia($tmp);
          Logger::debug("Deleted previews of media {$tmp['Media']['id']}");
        }
      }
    }
    $url = 'view/' . $id;
    if (count($this->crumbs)) {
      $url .= '/' . join('/', $this->crumbs);
    }
    $this->redirect($url);
  }

  public function updateAcl($id) {
    if (!empty($this->request->data)) {
      $user = $this->getUser();
      $media = $this->Media->findById($id);
      if (!$media) {
        Logger::warn("Invalid media id: $id");
        $this->redirect(null, '404');
      } elseif (!$this->Media->canWriteAcl($media, $user)) {
        Logger::warn("User '{$username}' ({$user['User']['id']}) has no previleges to change tags of image ".$id);
      } else {
        $this->Media->setAccessFlags($media, $user);
        $tmp = $this->Media->editSingle($media, $this->request->data, $user);
        if ($tmp) {
          if ($this->Media->save($tmp, true)) {
            Logger::info("Changed acl of media $id");
          } else {
            Logger::err("Could not update acl of media {$media['Media']['id']}");
            Logger::debug($tmp);
          }
        }
      }
    }

    $url = 'view/' . $id;
    if (count($this->crumbs)) {
      $url .= '/' . join('/', $this->crumbs);
    }
    $this->redirect($url);
  }
}
?>
