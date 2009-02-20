<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2009 Sebastian Felis, sebastian@phtagr.org
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

class ExplorerController extends AppController
{
  var $components = array('RequestHandler', 'Query', 'FilterManager');
  var $uses = array('Medium', 'MyFile', 'Group', 'Tag', 'Category', 'Location');
  var $helpers = array('form', 'formular', 'html', 'javascript', 'ajax', 'imageData', 'time', 'query', 'explorerMenu', 'rss');

  function beforeFilter() {
    parent::beforeFilter();

    $this->Query->controller =& $this;
    $this->Query->parseArgs();
  }

  function beforeRender() {
    $this->params['query'] = $this->Query->getParams();
    $this->set('feeds', array(
      $this->_getMediaRss() => array('title' => 'Media RSS of current search', 'id' => 'gallery') 
      ));
  }

  function _getMediaRss() {
    $args = array();
    foreach ($this->passedArgs as $name => $value) {
      if (is_numeric($name)) {
        $name = $this->action;
        if (in_array($name, array('tag', 'category', 'location'))) {
          $name = Inflector::pluralize($name);
        }
      }
      $args[] = $name.':'.$value;
    }
    $args[] =  "media.rss";
    return '/explorer/media/'.implode('/', $args);
  }

  function index() {
    $this->_setDataAndRender();
  }

  function query() {
    if (!empty($this->data)) {
      $this->Query->addTags($this->data['Medium']['tags']);
      $this->Query->setTagOp($this->data['Medium']['tag_op']);
      $this->Query->addCategories($this->data['Medium']['categories']);
      $this->Query->setCategoryOp($this->data['Medium']['category_op']);
      $this->Query->addLocations($this->data['Medium']['locations']);

      $this->Query->setDateFrom($this->data['Medium']['date_from']);
      $this->Query->setDateTo($this->data['Medium']['date_to']);

      $this->Query->setPageSize($this->data['Query']['show']);

      if ($this->hasRole(ROLE_GUEST)) {
        $this->Query->setFilename($this->data['Medium']['filename']);
        $this->Query->setFiletype($this->data['Medium']['file_type']);
        // Allow to search for my images
        if ($this->data['User']['username'] == $this->getUserId()) {
          $this->Query->setUser($this->data['User']['username']);
        }
      }

      if ($this->hasRole(ROLE_USER)) {
        $this->Query->setVisibility($this->data['Medium']['visibility']);

        $this->Query->setUser($this->data['User']['username']);
        $this->Query->setGroupId($this->data['Group']['id']);
      } 
    } 
    $this->_setDataAndRender();
  }

  function search() {
    if ($this->hasRole(ROLE_USER)) {
      $groups = $this->Group->findAll(array('Group.user_id' => $this->getUserId()), false, array('Group.name'));
      if ($groups) 
        $groups = Set::combine($groups, "{n}.Group.id", "{n}.Group.name");
      $groups[-1] = '';
      $this->set('groups', $groups);
    }
    $this->set('userId', $this->Query->getUserId() == $this->getUserId() ? $this->getUserId() : false);
    $this->set('userRole', $this->getUserRole());
    $this->set('mainMenuExplorer', array());
  }

  function user($idOrName) {
    $this->Query->setUser($idOrName);
    $this->_setDataAndRender();
  }

  function group($id) {
    $this->Query->setGroupId($id);
    $this->_setDataAndRender();
  }

  function date($year = null, $month = null, $day = null) {
    if ($year && $year > 1950 && $year < 2050) {
      $year = intval($year);
      if ($month && $month > 0 && $month < 13) {
        $y = $year;
        $month = intval($month);
        $m = $month + 1;
        if ($day && $day > 0 && $day < 13) {
          $m = $month;
          $day = intval($day);
          $d = $day + 1;
        } else {
          $day = $d = 1;
          $m = $month + 1;
        }
      } else {
        $month = $m = $day = $d = 1;
        $y = $year + 1;
      }
      $from = mktime(0, 0, 0, $month, $day, $year);
      $to = mktime(0, 0, 0, $m, $d, $y);
      $this->Query->setDateFrom($from);
      $this->Query->setDateTo($to-1);
      $this->Query->setOrder('-date');
    }
    $this->_setDataAndRender();
  }

  function tag($tags) {
    $this->Query->addTags(preg_split('/\s*,\s*/', trim($tags)));
    $this->_setDataAndRender();
  }

  function category($categories) {
    $this->Query->addCategories(preg_split('/\s*,\s*/', trim($categories)));
    $this->_setDataAndRender();
  }

  function location($locations) {
    $this->Query->addLocations(preg_split('/\s*,\s*/', trim($locations)));
    $this->_setDataAndRender();
  }

  function _setDataAndRender() {
    $data = $this->Query->paginate();
    if (count($data) == 0) {
      $this->Session->setFlash("Sorry. No image or files found!");
    }
    $this->set('mainMenuExplorer', $this->Query->getMenu(&$data));
    $this->set('data', &$data);
    if ($this->hasRole(ROLE_USER)) {
      $groups = $this->Group->findAll(array('Group.user_id' => $this->getUserId()), false, array('Group.name'));
      if ($groups) 
        $groups = Set::combine($groups, "{n}.Group.id", "{n}.Group.name");
      $groups[0] = '[Keep]';
      $groups[-1] = '[No Group]';
      $this->set('groups', $groups);
    }
    $this->set('mediaRss', $this->_getMediaRss());
    $this->render('index');
  }

  /** Updates the ids lists of a given association. It adds and deletes items
    * to the habtm assoziation
    @param data Array of image data
    @param assoc Name of HABTM accosciation
    @param items List of items
    @return Updated array of image data */
  function _handleHabtm(&$data, $assoc, $items) {
    if (!count($items)) {
      return $data;
    }

    // Create id itemss of deletion and addition
    $add = $this->$assoc->filterItems($items);
    $del = $this->$assoc->filterItems($items, false);
   
    $addIds = $this->$assoc->createIdList($add, 'name', true);
    $delIds = $this->$assoc->createIdList($del, 'name', false);

    // Remove and add association
    $oldIds = Set::extract($data, "$assoc.{n}.id");
    $ids = array_diff($oldIds, $delIds);
    $ids = array_unique(am($ids, $addIds));

    if (count($ids) != count($oldIds) ||
      array_diff($oldIds, $ids))
      $data[$assoc][$assoc] = $ids;
    return $data;
  }

  /** 
    @param Array of Locations
    @return Array of location types, which will be overwritten */
  function _getNewLocationsTypes($locations) {
    $new = $this->Location->filterItems($locations);
    $types = Set::extract($new, "{n}.type");
    return $types;
  }

  /** Removes locations which will be overwritten
    @param data Image data array
    @param types Array of location types which will be overwritten */
  function _removeLocation(&$data, $types) {
    if (!count($data['Location']))
      return;
    foreach ($data['Location'] as $key => $location) {
      if (!is_numeric($key))
        continue;
      if (in_array($location['type'], $types)) {
        unset($data['Location'][$key]);
      }
    }
  }

  function _editAcl(&$medium, $groupId) {
    $changedAcl = false;
    // Backup old values
    $fieldsAcl = array('gacl', 'uacl', 'oacl', 'group_id');
    foreach ($fieldsAcl as $field) {
      $medium['Medium']['_'.$field] = $medium['Medium'][$field];
    }

    // Change access properties 
    if ($groupId!=0)
      $medium['Medium']['group_id'] = $groupId;

    // Higher grants first
    $this->Medium->setAcl(&$medium, ACL_WRITE_META, ACL_WRITE_MASK, $this->data['acl']['write']['meta']);
    $this->Medium->setAcl(&$medium, ACL_WRITE_TAG, ACL_WRITE_MASK, $this->data['acl']['write']['tag']);

    $this->Medium->setAcl(&$medium, ACL_READ_ORIGINAL, ACL_READ_MASK, $this->data['acl']['read']['original']);
    $this->Medium->setAcl(&$medium, ACL_READ_PREVIEW, ACL_READ_MASK, $this->data['acl']['read']['preview']);

    // Evaluate changes
    foreach ($fieldsAcl as $field) {
      if ($medium['Medium']['_'.$field] != $medium['Medium'][$field]) {
        $changedAcl = true;
        break;
      }
    }
    return $changedAcl;
  }

  function edit() {
    if (isset($this->data)) {
      // create item lists
      $tags = $this->Tag->createItems($this->data['Tags']['text']);
      if (isset($this->data['Categories']) && isset($this->data['Locations'])) {
        $categories = $this->Category->createItems($this->data['Categories']['text']);
        $locations = $this->Location->createLocationItems($this->data['Locations']);
        $delLocations = $this->_getNewLocationsTypes($locations);
      } else {
        $categories = array();
        $locations = array();
        $delLocations = array();
      }
      
      $user = $this->getUser();
      $userId = $user['User']['id'];
      $members = Set::extract($user, 'Member.{n}.id');

      $groupId = -1;
      if (isset($this->data['Group']['id'])) {
        $groupId = $this->data['Group']['id'];
        if ($groupId>0 && !$this->Group->hasAny("Group.user_id=$userId AND Group.id=$groupId"))
          $groupId = -1;
      }
    
      $date = false;
      if (!empty($this->data['Medium']['date'])) {
        $time = strtotime($this->data['Medium']['date']);
        if ($time !== false) {
          $date = date("Y-m-d H:i:s", $time);
        } else {
          $this->Logger->warn("Could not convert time of '{$this->data['Medium']['date']}'");
        }
      }

      $ids = split(',', $this->data['Medium']['ids']);
      $ids = array_unique($ids);
      foreach ($ids as $id) {
        $id = intval($id);
        if ($id == 0)
          continue;

        $medium = $this->Medium->findById($id);
        if (!$medium) {
          $this->Logger->debug("Could not find Medium with id $id");
          continue;
        }
        // primary access check
        if (!$this->Medium->checkAccess(&$medium, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK, &$members)) {
          $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change any metadata of image ".$id);
          continue;
        }

        $changedMeta = false;

        // Backup old associations
        $habtms = array_keys($this->Medium->hasAndBelongsToMany);
        $oldHabtmIds = array();
        foreach ($habtms as $habtm) {
          $oldHabtmIds[$habtm] = Set::extract($medium, "$habtm.{n}.id");
        }

        // Update metadata
        $this->_handleHabtm(&$medium, 'Tag', $tags);
        if ($this->Medium->checkAccess(&$medium, &$user, ACL_WRITE_META, ACL_WRITE_MASK, &$members)) {
          if ($date) {
            $medium['Medium']['date'] = $date;
            $changedMeta = true;
          }
          $this->_handleHabtm(&$medium, 'Category', $categories);
          $this->_removeLocation(&$medium, &$delLocations);
          $this->_handleHabtm(&$medium, 'Location', $locations);
        } else {
          $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change metadata of image ".$medium['Medium']['id']);
        }
      
        // Evaluate, if data changed and cleanup of unchanged HABTMs
        foreach ($habtms as $habtm) {
          if (isset($medium[$habtm][$habtm]) && 
            (count($medium[$habtm][$habtm]) != count($oldHabtmIds[$habtm]) ||
            count(array_diff($medium[$habtm][$habtm], $oldHabtmIds[$habtm])))) {
            $changedMeta = true;
          } elseif (isset($medium[$habtm])) {
            unset($medium[$habtm]);
          }
        }

        $changedAcl = false;
        if (!empty($this->data['acl'])) {
          $this->Medium->setAccessFlags(&$medium, $user);

          if ($this->Medium->checkAccess(&$medium, &$user, 1, 0)) {
            $changedAcl = $this->_editAcl(&$medium, $groupId);
          } else {
            $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change access rights of image ".$id);
          }
        }

        if ($changedMeta || $changedAcl) { 
          if ($changedMeta) {
            $medium['Medium']['flag'] |= MEDIUM_FLAG_DIRTY;
          }
          $medium['Medium']['modified'] = null;
          if (!$this->Medium->save($medium)) {
            $this->Logger->warn('Could not save new metadata/acl to image '.$id);
          } else {
            $this->Logger->info('Updated metadata or acl of '.$id);
          }
        }
      }
      $this->data = array();
    }
    $this->_setDataAndRender();
  }

  /** 
    * @todo Check for edit permissions
    * @todo Check and handle non-ajax request
    */
  function editmeta($id) {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      $this->Logger->warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);
    $user = $this->getUser();
    $medium = $this->Medium->findById($id);
    $this->Medium->setAccessFlags(&$medium, $user);
    $this->set('data', $medium);
    $this->layout='bare';
    if (!$this->Medium->checkAccess(&$medium, &$user, ACL_WRITE_META, ACL_WRITE_MASK)) {
      if ($this->Medium->checkAccess(&$medium, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK)) {
        $this->render('edittag');
      } else {
        $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change ACL of image ".$id);
        $this->render('updatemeta');
      }
    }
    //Configure::write('debug', 0);
  }

  /**
   * @todo Check and handle non-ajax request 
   */
  function savemeta($id) {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      $this->Logger->warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);
    $this->layout='bare';
    $user = $this->getUser();
    if (isset($this->data)) {
      $medium = $this->Medium->findById($id);

      if (!$this->Medium->checkAccess(&$medium, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK)) {
        $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change tags of image ".$id);
      } else {
        $ids = $this->Tag->createIdListFromText($this->data['Tags']['text'], 'name', true);
        $medium['Tag']['Tag'] = $ids;

        if ($this->Medium->checkAccess(&$medium, &$user, ACL_WRITE_META, ACL_WRITE_MASK)) {
          $medium['Medium']['date'] = $this->data['Medium']['date'];
          $ids = $this->Category->createIdListFromText($this->data['Categories']['text'], 'name', true);
          $medium['Category']['Category'] = $ids;

          $locations = $this->Location->createLocationItems($this->data['Locations']);
          $locations = $this->Location->filterItems($locations);
          $ids = $this->Location->CreateIdList($locations, true);
          $medium['Location']['Location'] = $ids;      
        } else {
          $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change meta data of image ".$id);
        }
        $medium['Medium']['modified'] = null;
        $medium['Medium']['flag'] |= MEDIUM_FLAG_DIRTY;
        $this->Medium->save($medium);
      }
    }
    $medium = $this->Medium->findById($id);
    $this->Medium->setAccessFlags(&$medium, $user);
    $this->set('data', $medium);
    Configure::write('debug', 0);
    $this->render('updatemeta');
  }

  /** 
   * @todo check for save permissions
   * @todo Check and handle non-ajax request 
   */
  function updatemeta($id) {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      $this->Logger->warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);
    $medium = $this->Medium->findById($id);
    $this->Medium->setAccessFlags(&$medium, $this->getUser());
    $this->set('data', $medium);
    $this->layout='bare';
    Configure::write('debug', 0);
  }

  function editacl($id) {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      $this->Logger->warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);
    $user = $this->getUser();
    $medium = $this->Medium->findById($id);
    $this->Medium->setAccessFlags(&$medium, $user);
    $this->set('data', $medium);
    $this->layout='bare';
    if ($this->Medium->checkAccess(&$medium, &$user, 1, 0)) {
      $groups = $this->Group->findAll(array('User.id' => $this->getUserId()));
      if (!empty($groups)) {
        $groups = Set::combine($groups, '{n}.Group.id', '{n}.Group.name');
      } else {
        $groups = array();
      }
      $groups[-1] = '[No Group]';
      $this->set('groups', $groups);
    } else {
      $this->Logger->warn("User {$user['User']['username']} ({$user['User']['id']}) has no previleges to change ACL of image ".$id);
      $this->render('updatemeta');
    }
    //Configure::write('debug', 0);
  }

  function saveacl($id) {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      $this->Logger->warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);
    $this->layout='bare';
    if (isset($this->data)) {
      // Call find() instead of read(). read() populates resultes to the model,
      // which causes problems at save()
      $medium = $this->Medium->findById($id);
      $user = $this->getUser();
      $userId = $user['User']['id'];
      if (!$this->Medium->checkAccess(&$medium, &$user, 1, 0)) {
        $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change ACL of image ".$id);
      } else {
        // check for existing group of user
        $groupId = $this->data['Group']['id'];
        if ($groupId>0) 
          $group = $this->Group->find(array('and' => array('User.id' => $userId, 'Group.id' => $groupId)));
        else
          $group = null;
        if ($group)
          $medium['Medium']['group_id'] = $groupId;
        else
          $medium['Medium']['group_id'] = -1;

        $this->Medium->setAcl(&$medium, ACL_WRITE_TAG, ACL_WRITE_MASK, $this->data['acl']['write']['tag']);
        $this->Medium->setAcl(&$medium, ACL_WRITE_META, ACL_WRITE_MASK, $this->data['acl']['write']['meta']);
        $this->Medium->setAcl(&$medium, ACL_READ_PREVIEW, ACL_READ_MASK, $this->data['acl']['read']['preview']);
        $this->Medium->setAcl(&$medium, ACL_READ_ORIGINAL, ACL_READ_MASK, $this->data['acl']['read']['original']);

        $medium['Medium']['modified'] = null;
        $this->Medium->save($medium['Medium'], true, array('group_id', 'gacl', 'uacl', 'oacl'));
      }
    }
    $medium = $this->Medium->findById($id);
    $this->Medium->setAccessFlags(&$medium, $this->getUser());
    $this->set('data', $medium);
    $this->layout='bare';
    $this->render('updatemeta');
    Configure::write('debug', 0);
  }

  function sync($id) {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      $this->Logger->warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);

    $user = $this->getUser();
    $medium = $this->Medium->findById($id);
    if (!$medium) {
      $this->Logger->err("User '{$user['User']['username']}' ({$user['User']['id']}) requested non existing image id '$id'");
      $this->redirect(null, 401);
    }
    $this->Medium->setAccessFlags(&$medium, $user);
    if (!$medium['Medium']['isOwner']) {
      $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to sync image '$id'");
    } else {
      $this->FilterManager->write($medium);
      $medium =  $this->Medium->findById($id);
      $this->Medium->setAccessFlags(&$medium, $user);
    }
    $this->set('data', $medium);
    $this->layout='bare';
    $this->render('updatemeta');
    Configure::write('debug', 0);
  }

  function rss() {
    $this->layoutPath = 'rss';
    $this->Query->setPageSize(30);
    $this->Query->setOrder('newest');
    $this->set('data', $this->Query->paginate());

    if (Configure::read('debug') > 1) {
      Configure::write('debug', 1);
    }
    $this->set(
        'channel', array('title' => "New Images",
        'link' => "/explorer/rss",
        'description' => "Recently Published Images" )
      );
  }

  function media() {
    $this->data = $this->Query->paginate();
    $this->layout = 'xml';
    if (Configure::read('debug') > 1) {
      Configure::write('debug', 1);
    }
  }

  function points($north, $south, $west, $east) {
    $this->Query->setParam('order', 'random');

    $north = floatval($north);
    $south = floatval($south);
    $west = floatval($west);
    $east = floatval($east);

    $stepLat = ($north - $south) / 3;
    $stepLng = ($east - $west) / 3;
    $lat = $south;
    while ($lat < $north) {
      $lng = $west;
      while ($lng < $east) {
        $this->Query->setParams(array(
          'north' => $lat+$stepLat, 'south' => $lat, 
          'west' => $lng, 'east' => $lng+$stepLng));
        $points = $this->Query->paginate();
        //$this->Logger->trace("Found ".count($points)." points");
        $this->data = am($this->Query->paginate(), $this->data);
        $lng += $stepLng;
      }
      $lat += $stepLat;
    }

    $this->layout = 'xml';
    $this->Logger->trace("Query points of N:$north, S:$south, W:$west, E:$east: Found ".count($this->data)." points");
    $this->Query->delParams(array('north', 'south', 'west', 'east'));
    if (Configure::read('debug') > 1) {
      Configure::write('debug', 1);
    }
  }

}
?>
