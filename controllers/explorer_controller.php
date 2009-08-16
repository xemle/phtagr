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
  var $components = array('RequestHandler', 'FilterManager', 'Search', 'QueryBuilder');
  var $uses = array('Media', 'MyFile', 'Group', 'Tag', 'Category', 'Location');
  var $helpers = array('form', 'formular', 'html', 'javascript', 'ajax', 'imageData', 'time', 'explorerMenu', 'rss', 'search', 'navigator');

  function beforeFilter() {
    if ($this->action == 'points' && 
      Configure::read('Security.level') === 'high') {
      Configure::write('Security.level', 'medium');
    }

    parent::beforeFilter();
    
    // disable search parameter after role
    $role = $this->getUserRole();
    $disabled = array();
    switch ($role) {
      case ROLE_NOBODY:
        $disabled[] = 'group';
        $disabled[] = 'file';
      case ROLE_GUEST:
        $disabled[] = 'visibility';
      case ROLE_USER:
      case ROLE_SYSOP:
      case ROLE_ADMIN:
        break;
      default:
        Logger::warn("Unhandled role $role");
    }
    $this->Search->disabled = $disabled;
    $this->Search->parseArgs();
  }

  function beforeRender() {
    $data = $this->Search->paginate();
    // Set access rights
    if ($data) {
      $user = $this->getUser();
      // @note References in foreach loops does not work with PHP4
      foreach ($data as &$media) {
        $this->Media->setAccessFlags($media, $user);
      }
    }
    //Logger::debug($data);
    $this->data = $data;

    //$this->set('mainMenuExplorer', $this->Search->getMenu(&$data));
    if ($this->hasRole(ROLE_USER)) {
      $groups = $this->Group->findAll(array('Group.user_id' => $this->getUserId()), false, array('Group.name'));
      if ($groups) 
        $groups = Set::combine($groups, "{n}.Group.id", "{n}.Group.name");
      $groups[0] = '[Keep]';
      $groups[-1] = '[No Group]';
      $this->set('groups', $groups);
    }

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
  }

  function query() {
    if (!empty($this->data)) {
      /*
      $this->Search->addTags($this->data['Media']['tags']);
      $this->Search->setTagOp($this->data['Media']['tag_op']);
      $this->Search->addCategories($this->data['Media']['categories']);
      $this->Search->setCategoryOp($this->data['Media']['category_op']);
      $this->Search->addLocations($this->data['Media']['locations']);

      $this->Search->setDateFrom($this->data['Media']['date_from']);
      $this->Search->setDateTo($this->data['Media']['date_to']);

      $this->Search->setPageSize($this->data['Search']['show']);

      if ($this->hasRole(ROLE_GUEST)) {
        $this->Search->setFilename($this->data['Media']['filename']);
        $this->Search->setFiletype($this->data['Media']['file_type']);
        // Allow to search for my images
        if ($this->data['User']['username'] == $this->getUserId()) {
          $this->Search->setUser($this->data['User']['username']);
        }
      }

      if ($this->hasRole(ROLE_USER)) {
        $this->Search->setVisibility($this->data['Media']['visibility']);

        $this->Search->setUser($this->data['User']['username']);
        $this->Search->setGroupId($this->data['Group']['id']);
      } 
      */
    } 
    $this->render('index');
  }

  function search() {
    if ($this->hasRole(ROLE_USER)) {
      $groups = $this->Group->findAll(array('Group.user_id' => $this->getUserId()), false, array('Group.name'));
      if ($groups) 
        $groups = Set::combine($groups, "{n}.Group.id", "{n}.Group.name");
      $groups[-1] = '';
      $this->set('groups', $groups);
    }
    $this->set('userId', $this->Search->getUserId() == $this->getUserId() ? $this->getUserId() : false);
    $this->set('userRole', $this->getUserRole());
    $this->set('mainMenuExplorer', array());
  }

  function user($username, $param = false, $value = false) {
    $this->Search->setUser($username);
    if ($param && $value && in_array($param, array('tag', 'category', 'location'))) {
      Logger::debug("Add $param ($value)");
      $this->Search->addParam($param, explode(',', $value));
      Logger::debug($this->Search->getParams());
    }
    $this->render('index');
  }

  function group($id) {
    $this->Search->setGroupId($id);
    $this->render('index');
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
      $this->Search->setDateFrom($from);
      $this->Search->setDateTo($to-1);
      $this->Search->setOrder('-date');
    }
    $this->render('index');
  }

  function tag($tags) {
    $this->Search->addTags(preg_split('/\s*,\s*/', trim($tags)));
    $this->render('index');
  }

  function category($categories) {
    $this->Search->addCategories(preg_split('/\s*,\s*/', trim($categories)));
    $this->render('index');
  }

  function location($locations) {
    $this->Search->addLocations(preg_split('/\s*,\s*/', trim($locations)));
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

  function _editAcl(&$media, $groupId) {
    $changedAcl = false;
    // Backup old values
    $fieldsAcl = array('gacl', 'uacl', 'oacl', 'group_id');
    foreach ($fieldsAcl as $field) {
      $media['Media']['_'.$field] = $media['Media'][$field];
    }

    // Change access properties 
    if ($groupId!=0)
      $media['Media']['group_id'] = $groupId;

    // Higher grants first
    $this->Media->setAcl(&$media, ACL_WRITE_META, ACL_WRITE_MASK, $this->data['acl']['write']['meta']);
    $this->Media->setAcl(&$media, ACL_WRITE_TAG, ACL_WRITE_MASK, $this->data['acl']['write']['tag']);

    $this->Media->setAcl(&$media, ACL_READ_ORIGINAL, ACL_READ_MASK, $this->data['acl']['read']['original']);
    $this->Media->setAcl(&$media, ACL_READ_PREVIEW, ACL_READ_MASK, $this->data['acl']['read']['preview']);

    // Evaluate changes
    foreach ($fieldsAcl as $field) {
      if ($media['Media']['_'.$field] != $media['Media'][$field]) {
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
      if (!empty($this->data['Media']['date'])) {
        $time = strtotime($this->data['Media']['date']);
        if ($time !== false) {
          $date = date("Y-m-d H:i:s", $time);
        } else {
          Logger::warn("Could not convert time of '{$this->data['Media']['date']}'");
        }
      }

      $ids = split(',', $this->data['Media']['ids']);
      $ids = array_unique($ids);
      foreach ($ids as $id) {
        $id = intval($id);
        if ($id == 0)
          continue;

        $media = $this->Media->findById($id);
        if (!$media) {
          Logger::debug("Could not find Media with id $id");
          continue;
        }
        // primary access check
        if (!$this->Media->checkAccess(&$media, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK, &$members)) {
          Logger::warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change any metadata of image ".$id);
          continue;
        }

        $changedMeta = false;

        // Backup old associations
        $habtms = array_keys($this->Media->hasAndBelongsToMany);
        $oldHabtmIds = array();
        foreach ($habtms as $habtm) {
          $oldHabtmIds[$habtm] = Set::extract($media, "$habtm.{n}.id");
        }

        // Update metadata
        $this->_handleHabtm(&$media, 'Tag', $tags);
        if ($this->Media->checkAccess(&$media, &$user, ACL_WRITE_META, ACL_WRITE_MASK, &$members)) {
          if ($date) {
            $media['Media']['date'] = $date;
            $changedMeta = true;
          }
          $this->_handleHabtm(&$media, 'Category', $categories);
          $this->_removeLocation(&$media, &$delLocations);
          $this->_handleHabtm(&$media, 'Location', $locations);
        } else {
          Logger::warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change metadata of image ".$media['Media']['id']);
        }
      
        // Evaluate, if data changed and cleanup of unchanged HABTMs
        foreach ($habtms as $habtm) {
          if (isset($media[$habtm][$habtm]) && 
            (count($media[$habtm][$habtm]) != count($oldHabtmIds[$habtm]) ||
            count(array_diff($media[$habtm][$habtm], $oldHabtmIds[$habtm])))) {
            $changedMeta = true;
          } elseif (isset($media[$habtm])) {
            unset($media[$habtm]);
          }
        }

        $changedAcl = false;
        if (!empty($this->data['acl'])) {
          $this->Media->setAccessFlags(&$media, $user);

          if ($this->Media->checkAccess(&$media, &$user, 1, 0)) {
            $changedAcl = $this->_editAcl(&$media, $groupId);
          } else {
            Logger::warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change access rights of image ".$id);
          }
        }

        if ($changedMeta || $changedAcl) { 
          if ($changedMeta) {
            $media['Media']['flag'] |= MEDIA_FLAG_DIRTY;
          }
          $media['Media']['modified'] = null;
          if (!$this->Media->save($media)) {
            Logger::warn('Could not save new metadata/acl to image '.$id);
          } else {
            Logger::info('Updated metadata or acl of '.$id);
          }
        }
      }
      $this->data = array();
    }
    $this->render('index');
  }

  /** 
    * @todo Check for edit permissions
    * @todo Check and handle non-ajax request
    */
  function editmeta($id) {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      Logger::warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);
    $user = $this->getUser();
    $media = $this->Media->findById($id);
    $this->Media->setAccessFlags(&$media, $user);
    $this->set('data', $media);
    $this->layout='bare';
    if (!$this->Media->checkAccess(&$media, &$user, ACL_WRITE_META, ACL_WRITE_MASK)) {
      if ($this->Media->checkAccess(&$media, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK)) {
        $this->render('edittag');
      } else {
        Logger::warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change ACL of image ".$id);
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
      Logger::warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);
    $this->layout='bare';
    $user = $this->getUser();
    if (isset($this->data)) {
      $media = $this->Media->findById($id);

      if (!$this->Media->checkAccess(&$media, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK)) {
        Logger::warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change tags of image ".$id);
      } else {
        $ids = $this->Tag->createIdListFromText($this->data['Tags']['text'], 'name', true);
        $media['Tag']['Tag'] = $ids;

        if ($this->Media->checkAccess(&$media, &$user, ACL_WRITE_META, ACL_WRITE_MASK)) {
          $media['Media']['date'] = $this->data['Media']['date'];
          $ids = $this->Category->createIdListFromText($this->data['Categories']['text'], 'name', true);
          $media['Category']['Category'] = $ids;

          $locations = $this->Location->createLocationItems($this->data['Locations']);
          $locations = $this->Location->filterItems($locations);
          $ids = $this->Location->CreateIdList($locations, true);
          $media['Location']['Location'] = $ids;      
        } else {
          Logger::warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change meta data of image ".$id);
        }
        $media['Media']['modified'] = null;
        $media['Media']['flag'] |= MEDIA_FLAG_DIRTY;
        $this->Media->save($media);
      }
    }
    $media = $this->Media->findById($id);
    $this->Media->setAccessFlags(&$media, $user);
    $this->set('data', $media);
    Configure::write('debug', 0);
    $this->render('updatemeta');
  }

  /** 
   * @todo check for save permissions
   * @todo Check and handle non-ajax request 
   */
  function updatemeta($id) {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      Logger::warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);
    $media = $this->Media->findById($id);
    $this->Media->setAccessFlags(&$media, $this->getUser());
    $this->set('data', $media);
    $this->layout='bare';
    Configure::write('debug', 0);
  }

  function editacl($id) {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      Logger::warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);
    $user = $this->getUser();
    $media = $this->Media->findById($id);
    $this->Media->setAccessFlags(&$media, $user);
    $this->set('data', $media);
    $this->layout='bare';
    if ($this->Media->checkAccess(&$media, &$user, 1, 0)) {
      $groups = $this->Group->findAll(array('User.id' => $this->getUserId()));
      if (!empty($groups)) {
        $groups = Set::combine($groups, '{n}.Group.id', '{n}.Group.name');
      } else {
        $groups = array();
      }
      $groups[-1] = '[No Group]';
      $this->set('groups', $groups);
    } else {
      Logger::warn("User {$user['User']['username']} ({$user['User']['id']}) has no previleges to change ACL of image ".$id);
      $this->render('updatemeta');
    }
    //Configure::write('debug', 0);
  }

  function saveacl($id) {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      Logger::warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);
    $this->layout='bare';
    if (isset($this->data)) {
      // Call find() instead of read(). read() populates resultes to the model,
      // which causes problems at save()
      $media = $this->Media->findById($id);
      $user = $this->getUser();
      $userId = $user['User']['id'];
      if (!$this->Media->checkAccess(&$media, &$user, 1, 0)) {
        Logger::warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change ACL of image ".$id);
      } else {
        // check for existing group of user
        $groupId = $this->data['Group']['id'];
        if ($groupId > 0) {
          $group = $this->Group->find(array('and' => array('User.id' => $userId, 'Group.id' => $groupId)));
        } else {
          $group = null;
        }
        if ($group) {
          $media['Media']['group_id'] = $groupId;
        } else {
          $media['Media']['group_id'] = -1;
        }

        // higher grants first
        $this->Media->setAcl(&$media, ACL_WRITE_TAG, ACL_WRITE_MASK, $this->data['acl']['write']['tag']);
        $this->Media->setAcl(&$media, ACL_WRITE_META, ACL_WRITE_MASK, $this->data['acl']['write']['meta']);
        $this->Media->setAcl(&$media, ACL_READ_PREVIEW, ACL_READ_MASK, $this->data['acl']['read']['preview']);
        $this->Media->setAcl(&$media, ACL_READ_ORIGINAL, ACL_READ_MASK, $this->data['acl']['read']['original']);

        $media['Media']['modified'] = null;
        $this->Media->save($media['Media'], true, array('group_id', 'gacl', 'uacl', 'oacl'));
      }
    }
    $media = $this->Media->findById($id);
    $this->Media->setAccessFlags(&$media, $this->getUser());
    $this->set('data', $media);
    $this->layout='bare';
    $this->render('updatemeta');
    Configure::write('debug', 0);
  }

  function sync($id) {
    if (!$this->RequestHandler->isAjax() || !$this->RequestHandler->isPost()) {
      Logger::warn("Decline wrong ajax request");
      $this->redirect(null, '404');
    }
    $id = intval($id);

    $user = $this->getUser();
    $media = $this->Media->findById($id);
    if (!$media) {
      Logger::err("User '{$user['User']['username']}' ({$user['User']['id']}) requested non existing image id '$id'");
      $this->redirect(null, 401);
    }
    $this->Media->setAccessFlags(&$media, $user);
    if (!$media['Media']['isOwner']) {
      Logger::warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to sync image '$id'");
    } else {
      $this->FilterManager->write($media);
      $media =  $this->Media->findById($id);
      $this->Media->setAccessFlags(&$media, $user);
    }
    $this->set('data', $media);
    $this->layout='bare';
    $this->render('updatemeta');
    Configure::write('debug', 0);
  }

  function rss() {
    $this->layoutPath = 'rss';
    $this->Search->setPageSize(30);
    $this->Search->setOrder('newest');
    $this->set('data', $this->Search->paginate());

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
    $this->data = $this->Search->paginate();
    $this->layout = 'xml';
    if (Configure::read('debug') > 1) {
      Configure::write('debug', 1);
    }
  }

  function points($north, $south, $west, $east) {
    $this->Search->setParam('order', 'random');

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
        $this->Search->setParams(array(
          'north' => $lat+$stepLat, 'south' => $lat, 
          'west' => $lng, 'east' => $lng+$stepLng));
        $points = $this->Search->paginate();
        //Logger::trace("Found ".count($points)." points");
        $this->data = am($this->Search->paginate(), $this->data);
        $lng += $stepLng;
      }
      $lat += $stepLat;
    }

    $this->layout = 'xml';
    Logger::trace("Search points of N:$north, S:$south, W:$west, E:$east: Found ".count($this->data)." points");
    $this->Search->del(array('north', 'south', 'west', 'east'));
    if (Configure::read('debug') > 1) {
      Configure::write('debug', 1);
    }
  }

  function test() {
    
    $this->render('index');
  }
}
?>
