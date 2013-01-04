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
class HomeController extends AppController
{
  var $name = 'home';

  var $components = array('Search', 'FastFileResponder');
  var $helpers = array('Html', 'Time', 'Text', 'Cloud', 'ImageData', 'Search');
  /** Don't load models for setup check */
  var $uses = null;

  /** Check database configuration and connection. If missing redirect to
   * the setup. */
  public function beforeFilter() {
    if (!file_exists(CONFIGS . 'database.php')) {
      $this->redirect('/setup');
    }

    App::uses('ConnectionManager', 'Model');
    $db = ConnectionManager::getDataSource('default');
    if (!$db->enabled()) {
      $this->redirect('/setup');
    }

    // Database connection is OK. Load components and models
    $this->uses = array('Media', 'Tag', 'Category', 'Comment');
    $this->constructClasses();
    $this->pageTitle = __("Home");

    parent::beforeFilter();
    $this->logUser();
  }

  /** @todo improve the randomized newest media */
  public function index() {
    $this->Search->setSort('newest');
    $this->Search->setShow(50);
    $newest = $this->Search->paginate();
    // generate tossed index to variy the media
    srand(time());
    while (count($newest) > 32) {
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
    $this->set('cloudTags', $this->Media->cloud($user, array('conditions' => array('Field.name' => 'keyword'), 'count' => 50)));
    $this->set('cloudCategories', $this->Media->cloud($user, array('conditions' => array('Field.name' => 'category'), 'count' => 50)));

    $this->Comment->currentUser = $this->getUser();
    $comments = $this->Comment->paginate(array(), array(), 'Comment.date DESC', 4);
    $this->FastFileResponder->addAll($comments, 'mini');
    $this->set('comments', $comments);
  }
  
  public function cloud() {
    $user = $this->getUser();

    $this->set('cloudTags', $this->Media->cloud($user, array('conditions' => array('Field.name' => 'keyword'), 'count' => 400)));
    $this->set('cloudCategories', $this->Media->cloud($user, array('conditions' => array('Field.name' => 'category'), 'count' => 100)));
  }
}
?>
