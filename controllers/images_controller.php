<?php
/*
 * phtagr.
 * 
 * social photo gallery for your community.
 * 
 * Copyright (C) 2006-2010 Sebastian Felis, sebastian@phtagr.org
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

class ImagesController extends AppController
{
  var $components = array('RequestHandler', 'Search', 'FastFileResponder');
  var $uses = array('Media', 'Group', 'Tag', 'Category', 'Location');
  var $helpers = array('Form', 'Html', 'Javascript', 'Ajax', 'ImageData', 'Time', 'Search', 'ExplorerMenu', 'Rss', 'Map', 'Navigator', 'Flowplayer', 'Tab', 'Number', 'Option');

  function beforeFilter() {
    parent::beforeFilter();

    $this->Search->parseArgs();
  }

  function beforeRender() {
    $this->set('feeds', '/explorer/rss');
    parent::beforeRender();
  }

  /** Simple crawler detection
    @todo Verifiy and improve crawler detection */
  function _isCrawler() {
    return (preg_match('/(agent|bot|crawl|search|spider|walker)/i', env('HTTP_USER_AGENT')) == 1);
  }

  /** Update the rating and clicks of a media. The rated media will be stored
   * in the session to avoid multiple rating per session. */
  function _updateRating() {
    if (!$this->data || !isset($this->data['Media']['id'])) {
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
    $id = $this->data['Media']['id'];
    $ranked = array();
    if ($this->Session->check('Media.ranked')) {
      $ranked = $this->Session->read('Media.ranked');
    }
    if (in_array($id, $ranked)) {
      Logger::trace("Skip ranking for already rated media $id");
      return;
    }

    $this->Media->updateRanking($this->data);

    // update rated media ids
    $ranked[] = $id;
    $this->Session->write('Media.ranked', $ranked);
  }

  function view($id) {
    $this->data = $this->Search->paginateMedia($id);
    if (!$this->data) {
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
        $this->data['Comment'] = am($comment['Comment'], $this->data['Comment']);
        //$this->data = am($this->Session->read('Comment.data'), $this->data);
        $this->Session->delete('Comment.data');
      }
      $this->FastFileResponder->add($this->data, 'preview');
    }
  }
}
?>
