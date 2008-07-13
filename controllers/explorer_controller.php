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

class ExplorerController extends AppController
{
  var $components = array('RequestHandler', 'Query', 'ImageFilter', 'VideoFilter');
  var $uses = array('Image', 'Group', 'Tag', 'Category', 'Location');
  var $helpers = array('form', 'formular', 'html', 'javascript', 'ajax', 'imageData', 'time', 'query', 'explorerMenu', 'rss');

  function beforeFilter() {
    parent::beforeFilter();

    $this->Query->controller =& $this;
    $this->Query->parseArgs();
  }

  function beforeRender() {
    $this->params['query'] = $this->Query->getParams();
    $this->set('feeds', '/explorer/rss');
  }

  function index() {
    $this->_setDataAndRender();
  }

  function query() {
    if (!empty($this->data)) {
      $this->Query->addTags($this->data['Image']['tags']);
      $this->Query->setTagOp($this->data['Image']['tag_op']);
      $this->Query->addCategories($this->data['Image']['categories']);
      $this->Query->setCategoryOp($this->data['Image']['category_op']);
      $this->Query->addLocations($this->data['Image']['locations']);

      $this->Query->setDateFrom($this->data['Image']['date_from']);
      $this->Query->setDateTo($this->data['Image']['date_to']);

      $this->Query->setPageSize($this->data['Query']['show']);

      if ($this->hasRole(ROLE_GUEST)) {
        $this->Query->setFilename($this->data['Image']['filename']);
        $this->Query->setFiletype($this->data['Image']['file_type']);
        // Allow to search for my images
        if ($this->data['User']['username'] == $this->getUserId()) {
          $this->Query->setUser($this->data['User']['username']);
        }
      }

      if ($this->hasRole(ROLE_USER)) {
        $this->Query->setVisibility($this->data['Image']['visibility']);

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

  function image($id) {
    $this->Query->setImageId($id);
    $data = $this->Query->paginateImage();
    $this->set('data', $data);
    $this->_countMeta(array($data));
    $this->set('mapKey', $this->getPreferenceValue('google.map.key', false));
    if ($this->Image->isVideo($data))
      $this->render('video');
    else
      $this->render('image');
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

  /** Counts values of a specific array key
    @param counter Pointer to the merged array
    @param data Hash array to count
    @param key Key of the hash entry. Default is 'name' */
  function _arrayCountMerge(&$counter, $data, $key = 'name')
  {
    if (!count($data))
      return;
    foreach ($data as $item) {
      $name = $item[$key];
      if (!isset($counter[$name]))
        $counter[$name] = 1;
      else
        $counter[$name]++;
    }
  }

  function _countMeta($data) {
    $tags = array();
    $categories = array();
    $locations = array();
    foreach ($data as $image) {
      $this->_arrayCountMerge(&$tags, &$image['Tag']);
      $this->_arrayCountMerge(&$categories, &$image['Category']);
      $this->_arrayCountMerge(&$locations, &$image['Location']);
    }
    arsort($tags);
    arsort($categories);
    arsort($locations);
    
    $this->set('tags', $tags);
    $this->set('categories', $categories);
    $this->set('locations', $locations);
    
    $menu = array();
    $menu['tags'] = $tags;
    $menu['categories'] = $categories;
    $menu['locations'] = $locations;
    
    $this->set('mainMenuExplorer', &$menu);
  }

  function _setDataAndRender() {
    $data = $this->Query->paginate();
    $this->_countMeta(&$data);
    $this->set('data', &$data);
    if ($this->hasRole(ROLE_USER)) {
      $groups = $this->Group->findAll(array('Group.user_id' => $this->getUserId()), false, array('Group.name'));
      if ($groups) 
        $groups = Set::combine($groups, "{n}.Group.id", "{n}.Group.name");
      $groups[0] = '[Keep]';
      $groups[-1] = '[No Group]';
      $this->set('groups', $groups);
    }
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

  function _editAcl(&$image, $groupId) {
    $changedAcl = false;
    // Backup old values
    $fieldsAcl = array('gacl', 'uacl', 'oacl', 'group_id');
    foreach ($fieldsAcl as $field) {
      $image['Image']['_'.$field] = $image['Image'][$field];
    }

    // Change access properties 
    if ($groupId!=0)
      $image['Image']['group_id'] = $groupId;

    $this->Image->setAcl(&$image, ACL_WRITE_TAG, ACL_WRITE_MASK, $this->data['acl']['write']['tag']);
    $this->Image->setAcl(&$image, ACL_WRITE_META, ACL_WRITE_MASK, $this->data['acl']['write']['meta']);
    $this->Image->setAcl(&$image, ACL_READ_PREVIEW, ACL_READ_MASK, $this->data['acl']['read']['preview']);
    $this->Image->setAcl(&$image, ACL_READ_ORIGINAL, ACL_READ_MASK, $this->data['acl']['read']['original']);

    // Evaluate changes
    foreach ($fieldsAcl as $field) {
      if ($image['Image']['_'.$field] != $image['Image'][$field]) {
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
      if (!empty($this->data['Image']['date'])) {
        $time = strtotime($this->data['Image']['date']);
        if ($time !== false) {
          $date = date("Y-m-d H:i:s", $time);
        } else {
          $this->Logger->warn("Could not convert time of '{$this->data['Image']['date']}'");
        }
      }

      $ids = split(',', $this->data['Image']['ids']);
      $ids = array_unique($ids);
      foreach ($ids as $id) {
        $id = intval($id);
        if ($id == 0)
          continue;

        $image = $this->Image->findById($id);
        if (!$image) {
          $this->Logger->debug("Could not find Image with id $id");
          continue;
        }
        // primary access check
        if (!$this->Image->checkAccess(&$image, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK, &$members)) {
          $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change any metadata of image ".$id);
          continue;
        }

        $changedMeta = false;

        // Backup old associations
        $habtms = array_keys($this->Image->hasAndBelongsToMany);
        $oldHabtmIds = array();
        foreach ($habtms as $habtm) {
          $oldHabtmIds[$habtm] = Set::extract($image, "$habtm.{n}.id");
        }

        // Update metadata
        $this->_handleHabtm(&$image, 'Tag', $tags);
        if ($this->Image->checkAccess(&$image, &$user, ACL_WRITE_META, ACL_WRITE_MASK, &$members)) {
          if ($date) {
            $image['Image']['date'] = $date;
            $changedMeta = true;
          }
          $this->_handleHabtm(&$image, 'Category', $categories);
          $this->_removeLocation(&$image, &$delLocations);
          $this->_handleHabtm(&$image, 'Location', $locations);
        } else {
          $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change metadata of image ".$image['Image']['id']);
        }
      
        // Evaluate, if data changed and cleanup of unchanged HABTMs
        foreach ($habtms as $habtm) {
          if (isset($image[$habtm][$habtm]) && 
            (count($image[$habtm][$habtm]) != count($oldHabtmIds[$habtm]) ||
            count(array_diff($image[$habtm][$habtm], $oldHabtmIds[$habtm])))) {
            $changedMeta = true;
          } elseif (isset($image[$habtm])) {
            unset($image[$habtm]);
          }
        }

        $changedAcl = false;
        if (!empty($this->data['acl'])) {
          $this->Image->setAccessFlags(&$image, $user);

          if ($this->Image->checkAccess(&$image, &$user, 1, 0)) {
            $changedAcl = $this->_editAcl(&$image, $groupId);
          } else {
            $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change access rights of image ".$id);
          }
        }

        if ($changedMeta || $changedAcl) { 
          if ($changedMeta) {
            $image['Image']['flag'] |= IMAGE_FLAG_DIRTY;
          }
          $image['Image']['modified'] = null;
          if (!$this->Image->save($image)) {
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
    $image = $this->Image->findById($id);
    $this->Image->setAccessFlags(&$image, $user);
    $this->set('data', $image);
    $this->layout='bare';
    if (!$this->Image->checkAccess(&$image, &$user, ACL_WRITE_META, ACL_WRITE_MASK)) {
      if ($this->Image->checkAccess(&$image, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK)) {
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
      $image = $this->Image->findById($id);

      if (!$this->Image->checkAccess(&$image, &$user, ACL_WRITE_TAG, ACL_WRITE_MASK)) {
        $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change tags of image ".$id);
      } else {
        $ids = $this->Tag->createIdListFromText($this->data['Tags']['text'], 'name', true);
        $image['Tag']['Tag'] = $ids;

        if ($this->Image->checkAccess(&$image, &$user, ACL_WRITE_META, ACL_WRITE_MASK)) {
          $image['Image']['date'] = $this->data['Image']['date'];
          $ids = $this->Category->createIdListFromText($this->data['Categories']['text'], 'name', true);
          $image['Category']['Category'] = $ids;

          $locations = $this->Location->createLocationItems($this->data['Locations']);
          $locations = $this->Location->filterItems($locations);
          $ids = $this->Location->CreateIdList($locations, true);
          $image['Location']['Location'] = $ids;      
        } else {
          $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change meta data of image ".$id);
        }
        $image['Image']['modified'] = null;
        $image['Image']['flag'] |= IMAGE_FLAG_DIRTY;
        $this->Image->save($image);
      }
    }
    $image = $this->Image->findById($id);
    $this->Image->setAccessFlags(&$image, $user);
    $this->set('data', $image);
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
    $image = $this->Image->findById($id);
    $this->Image->setAccessFlags(&$image, $this->getUser());
    $this->set('data', $image);
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
    $image = $this->Image->findById($id);
    $this->Image->setAccessFlags(&$image, $user);
    $this->set('data', $image);
    $this->layout='bare';
    if ($this->Image->checkAccess(&$image, &$user, 1, 0)) {
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
      $image = $this->Image->findById($id);
      $user = $this->getUser();
      $userId = $user['User']['id'];
      if (!$this->Image->checkAccess(&$image, &$user, 1, 0)) {
        $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to change ACL of image ".$id);
      } else {
        // check for existing group of user
        $groupId = $this->data['Group']['id'];
        if ($groupId>0) 
          $group = $this->Group->find(array('and' => array('User.id' => $userId, 'Group.id' => $groupId)));
        else
          $group = null;
        if ($group)
          $image['Image']['group_id'] = $groupId;
        else
          $image['Image']['group_id'] = -1;

        $this->Image->setAcl(&$image, ACL_WRITE_TAG, ACL_WRITE_MASK, $this->data['acl']['write']['tag']);
        $this->Image->setAcl(&$image, ACL_WRITE_META, ACL_WRITE_MASK, $this->data['acl']['write']['meta']);
        $this->Image->setAcl(&$image, ACL_READ_PREVIEW, ACL_READ_MASK, $this->data['acl']['read']['preview']);
        $this->Image->setAcl(&$image, ACL_READ_ORIGINAL, ACL_READ_MASK, $this->data['acl']['read']['original']);

        $image['Image']['modified'] = null;
        $this->Image->save($image['Image'], true, array('group_id', 'gacl', 'uacl', 'oacl'));
      }
    }
    $image = $this->Image->findById($id);
    $this->Image->setAccessFlags(&$image, $this->getUser());
    $this->set('data', $image);
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

    $image = $this->Image->findById($id);
    $user = $this->getUser();
    if ($image)
      $this->Image->setAccessFlags(&$image, $user);
    if (!$image) {
      $this->Logger->err("User '{$user['User']['username']}' ({$user['User']['id']}) requested non existing image id '$id'");
    } elseif (!$image['Image']['isOwner']) {
      $this->Logger->warn("User '{$user['User']['username']}' ({$user['User']['id']}) has no previleges to sync image '$id'");
    } else {
      @clearstatcache();
      if ($this->Image->isVideo($image)) {
        $thumbFilename = $this->VideoFilter->getVideoPreviewFilename(&$image);
      } else {
        $thumbFilename = $this->Image->getFilename(&$image);
      }
      if (!$thumbFilename || !$this->ImageFilter->writeFile(&$image, $thumbFilename)) {
        $this->Logger->err("Count not write file '".$this->Image->getFilename($image)."'");
      } else {
        $this->Logger->info("Synced file '".$this->Image->getFilename($image)."' ({$image['Image']['id']})");
        // reread image
        $image = $this->Image->findById($id);
        $this->Image->setAccessFlags(&$image, $user);
      }
    }
    $this->set('data', $image);
    $this->layout='bare';
    $this->render('updatemeta');
    Configure::write('debug', 0);
  }

  function rss() {
    $this->layoutPath = 'rss';
    $this->Query->setPageSize(30);
    $this->Query->setOrder('newest');
    $this->set('data', $this->Query->paginate());

    Configure::write('debug', 0);
    $this->set(
        'channel', array('title' => "New Images",
        'link' => "/explorer/rss",
        'description' => "Recently Published Images" )
      );
  }
}
?>
