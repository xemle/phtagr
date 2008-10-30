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

class ImagesController extends AppController
{
  var $components = array('RequestHandler', 'Query', 'ImageFilter', 'VideoFilter');
  var $uses = array('Image', 'Group', 'Tag', 'Category', 'Location');
  var $helpers = array('form', 'formular', 'html', 'javascript', 'ajax', 'imageData', 'time', 'query', 'explorerMenu', 'rss');

  function beforeFilter() {
    parent::beforeFilter();

    $this->Query->parseArgs();
  }

  function beforeRender() {
    $this->params['query'] = $this->Query->getParams();
    $this->set('feeds', '/explorer/rss');
  }

  function view($id) {
    $this->Query->setImageId($id);
    $this->data = $this->Query->paginateImage();
    if (!$this->data) {
      $this->render('notfound');
    } else {
      $this->set('mainMenuExplorer', $this->Query->getMenu(&$this->data));
      $role = $this->getUserRole();
      if ($role >= ROLE_USER) {
        $commentAuth = COMMENT_AUTH_NONE;
      } elseif ($role >= ROLE_GUEST) {
        $commentAuth = $this->getOption('comment.auth', COMMENT_AUTH_NONE);
      } else {
        $commentAuth = (COMMENT_AUTH_NAME | COMMENT_AUTH_CAPTCHA);
      }
      $this->set('userRole', $this->getUserRole());
      $this->set('userId', $this->getUserId());
      $this->set('commentAuth', $commentAuth);
      $this->set('mapKey', $this->getOption('google.map.key', false));

      if ($this->Session->check('Comment.data')) {
        $comment = $this->Session->read('Comment.data');
        $this->Comment->validationErrors = $this->Session->read('Comment.validationErrors');
        $this->data['Comment'] = am($comment['Comment'], $this->data['Comment']);
        //$this->data = am($this->Session->read('Comment.data'), $this->data);
        $this->Session->del('Comment.data');
      }
      if ($this->Image->isVideo($this->data)) {
        $this->render('video');
      }
    }
  }
}
?>
