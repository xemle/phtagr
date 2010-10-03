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
class HomeController extends AppController
{
  var $name = 'home';

  var $components = array('Search', 'FastFileResponder');
  var $helpers = array('Html', 'Time', 'Text', 'Cloud', 'ImageData', 'Search');
  /** Don't load models for setup check */
  var $uses = null;

  /** Check database configuration and connection. If missing redirect to 
   * the setup. */
  function beforeFilter() {
    if (!file_exists(CONFIGS . 'database.php')) {
      $this->redirect('/setup');
    }

    App::import('Core', 'ConnectionManager');
    $db =& ConnectionManager::getDataSource('default');
    if (empty($db->connection)) {
      $this->redirect('/setup');
    }
    
    // Database connection is OK. Load components and models
    $this->uses = array('Media', 'Tag', 'Category', 'Comment');
    $this->constructClasses();
    $this->pageTitle = __("Home", true);

    parent::beforeFilter();
  }

  /** @todo improve the randomized newest media */
  function index() {
    $this->Search->setSort('newest');
    $this->Search->setShow(50);
    $newest = $this->Search->paginate();
    // generate tossed index to variy the media 
    srand(time());
    while (count($newest) > 12) {
      $rnd = rand(0, 50);
      if (isset($newest[$rnd])) {
        unset($newest[$rnd]);
      }
    }
    $this->FastFileResponder->addAll($newest, 'mini');
    $this->set('newMedia', $newest);

    $this->Search->setSort('random');
    $this->Search->setShow(1);
    $random = $this->Search->paginate();
    $this->FastFileResponder->addAll($random, 'preview');
    $this->set('randomMedia', $random);

    $user = $this->getUser();
    $this->set('cloudTags', $this->Media->cloud($user, 'Tag', 50));
    $this->set('cloudCategories', $this->Media->cloud($user, 'Category', 50));

    $conditions = $this->Media->buildAclConditions($this->getUser());
    $comments = $this->Comment->find('all', array('conditions' => $conditions, 'order' => 'Comment.date DESC', 'limit' => 4));
    $this->FastFileResponder->addAll($comments, 'mini');
    $this->set('comments', $comments);
  }
}
?>
