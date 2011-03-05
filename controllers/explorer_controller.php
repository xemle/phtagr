<?PHP
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

class ExplorerController extends AppController
{
  var $components = array('RequestHandler', 'FilterManager', 'Search', 'QueryBuilder', 'FastFileResponder');
  var $uses = array('Media', 'MyFile', 'Group', 'Tag', 'Category', 'Location');
  var $helpers = array('Form', 'Html', 'Ajax', 'ImageData', 'Time', 'ExplorerMenu', 'Rss', 'Search', 'Navigator', 'Tab', 'Breadcrumb', 'Autocomplete');

  var $crumbs = array();

  function beforeFilter() {
    if ($this->action == 'points' && 
      Configure::read('Security.level') === 'high') {
      Configure::write('Security.level', 'medium');
    }

    parent::beforeFilter();
    $this->crumbs = $this->Search->urlToCrumbs($this->params['url']['url'], 2);
  }

  function beforeRender() {
    $paginateActions = array('category', 'date', 'edit', 'group', 'index', 'location', 'query', 'tag', 'user', 'view');
    if (in_array($this->action, $paginateActions)) {
      $this->data = $this->Search->paginateByCrumbs($this->crumbs);
      $this->FastFileResponder->addAll($this->data, 'thumb');

      if ($this->hasRole(ROLE_USER)) {
        $groups = $this->Group->getGroupsForMedia($this->getUser());
        $groupSelect = Set::combine($groups, '{n}.Group.id', '{n}.Group.name');
        asort($groupSelect);
        $groupSelect[0] = __('[Keep]', true);
        $groupSelect[-1] = __('[No Group]', true);
        $this->set('groups', $groupSelect);
      } else {
        $this->set('groups', array());
      }
    }
    $this->set('crumbs', $this->crumbs);
    $this->params['crumbs'] = $this->crumbs;
    parent::beforeRender();
  }

  function index() {
    //$this->render('table');
  }

  function view() {
    if (!empty($this->data)) {
      $crumbs = split('/', $this->data['Breadcrumb']['current']);
      $crumbs[] = $this->data['Breadcrumb']['input'];
      $this->crumbs = $crumbs;
    }
    $this->render('index');
  }

  function autocomplete($type) {
    if (in_array($type, array('tag', 'category', 'city', 'sublocation', 'state', 'country'))) {
      if ($type == 'tag' || $type == 'category') {
        $field = Inflector::camelize(Inflector::pluralize($type));
        $value = $this->data[$field]['text'];
      } else {
        $value = $this->data['Locations'][$type];
      }
      $this->data = $this->_getAssociation($type, $value);
    } elseif ($type == 'crumb') {
      $queryMap = array(
        'category' => '_getAssociation', 
        'category_op' => array('OR', 'AND'),
        'from' => 'true',
        'group' => '_getAssociation', 
        'location' => '_getAssociation', 
        'location_op' => array('OR', 'AND'), 
        'operand' => array('OR', 'AND'), 
        'show' => array(2, 6, 12, 24, 60, 120, 240),
        'sort' => array('date', '-date', 'newest', 'random'), 
        'tag' => '_getAssociation', 
        'tag_op' => array('OR', 'AND'), 
        'type' => array('image', 'video'),
        'to' => 'true',
        'user' => '_getAssociation'
      );
      $queryTypes = array_keys($queryMap);
      $input = trim($this->data['breadcrumb']['input']);
      // cut input to maximum of 64 chars
      if (strlen($input) > 64) {
        $input = substr($input, 0, 64);
      }
      $this->data = array();
      if (strpos($input, ':') === false) {
        // Search for crumb type
        // collect all if input is empty or starts with the input
        $this->_findGenericCrumb($input, $queryMap);
        foreach ($queryTypes as $types) {
          if ($input == '' || strpos($types, $input) === 0) {
            $this->data[] = $types . ':';
          }
        }
      } else {
        // Search for crumb value
        preg_match('/(\w+):(-)?(.*)/', $input, $matches);
        $crumbType = $matches[1];
        $input = $matches[3];
        $exclude = $matches[2];

        if (!in_array($crumbType, $queryTypes)) {
          Logger::debug("Invalid crumb type: $crumbType");
          $this->redirect(404);
        }
        if (is_array($queryMap[$crumbType])) {
          $data = $queryMap[$crumbType];
          $exclude = '';
        } elseif ($queryMap[$crumbType] === true) {
          $data = '';
        } else {
          $method = $queryMap[$crumbType];
          $data = $this->{$method}($crumbType, $input);
        }
        foreach ($data as $value) {
          $this->data[] = "$crumbType:$exclude$value";
        }
      }
    } else {
      Logger::warn("Invalid autocomlete type: $type");
      $this->redirect(404);
    }
    $this->layout = 'xml';
    if (Configure::read('debug') > 1) {
      Configure::write('debug', 1);
    }
  }

  /** Find needle in tags, categories, locations, or users */
  function _findGenericCrumb($needle, $queryMap) {
    $prefix = '';
    if (substr($needle, 0, 1) == '-') {
      $prefix = '-';
      $needle = substr($needle, 1);
    }
    if (strlen($needle) < 2) {
      return;
    }
    App::import('Sanitize');
    $sanitize = new Sanitize();
    $sqlNeedle = $sanitize->escape($needle) . '%';

    $tags = Set::extract('/Tag/name', $this->Media->Tag->find(
      'all', array('conditions' => array("Tag.name like" => $sqlNeedle), 'recursive' => 0, 'limit' => 10
      )));
    foreach ($tags as $tag) {
      $this->data[] = 'tag:' . $prefix . $tag;
    }
    $categories = Set::extract('/Category/name', $this->Media->Category->find(
      'all', array('conditions' => array("Category.name like" => $sqlNeedle), 'recursive' => 0, 'limit' => 10
      )));
    foreach ($categories as $category) {
      $this->data[] = 'category:' . $prefix . $category;
    }
    $locations = array_unique(Set::extract('/Location/name', $this->Media->Location->find(
      'all', array('conditions' => array("Location.name like" => $sqlNeedle), 'recursive' => 0, 'limit' => 10
      ))));
    foreach ($locations as $location) {
      $this->data[] = 'location:' . $prefix . $location;
    }
    $groups = Set::extract('/Group/name', $this->Media->Group->find(
      'all', array('conditions' => array("Group.name like" => $sqlNeedle), 'recursive' => 0, 'limit' => 10
      )));
    foreach ($groups as $group) {
      $this->data[] = 'group:' . $prefix . $group;
    }
    $users = Set::extract('/User/username', $this->Media->User->find(
      'all', array('conditions' => array("User.username like" => $sqlNeedle), 'recursive' => 0, 'limit' => 10
      )));
    foreach ($users as $user) {
      // TODO excluding of users are currently not supported
      $this->data[] = 'user:' . $user;
    }

    $needle = strtolower($needle);
    $len = strlen($needle);
    foreach ($queryMap as $type => $values) {
      if (!is_array($values)) {
        continue;
      }
      foreach ($values as $value) {
        if (substr(strtolower($value), 0, $len) == $needle) {
          $this->data[] = $type . ':' . $value;
        }
      }
    }
  }

  function _getDate($type, $value) {
  }

  function _getAssociation($type, $value) {
    $result = array();
    switch ($type) {
      case 'tag':
        $data = $this->Media->Tag->find('all', array(
          'conditions' => array('name LIKE' => $value.'%'), 
          'limit' => 10
          ));
        $result = Set::extract('/Tag/name', $data);
        break;
      case 'category':
        $data = $this->Media->Category->find('all', array(
          'conditions' => array('name LIKE' => $value.'%'),
          'limit' => 10
          ));
        $result = Set::extract('/Category/name', $data);
        break;
      case 'location':
        $data = $this->Media->Location->find('all', array(
          'conditions' => array('name LIKE' => $value.'%'),
          'limit' => 10
          ));
        $result = Set::extract('/Location/name', $data);
        break;
      case 'city':
        $data = $this->Media->Location->find('all', array(
          'conditions' => array('name LIKE' => $value.'%', 'type' => LOCATION_CITY),
          'limit' => 10
          ));
        $result = Set::extract('/Location/name', $data);
        break;
      case 'sublocation':
        $data = $this->Media->Location->find('all', array(
          'conditions' => array('name LIKE' => $value.'%', 'type' => LOCATION_SUBLOCATION),
          'limit' => 10
          ));
        $result = Set::extract('/Location/name', $data);
        break;
      case 'state':
        $data = $this->Media->Location->find('all', array(
          'conditions' => array('name LIKE' => $value.'%', 'type' => LOCATION_STATE),
          'limit' => 10
          ));
        $result = Set::extract('/Location/name', $data);
        break;
      case 'country':
        $data = $this->Media->Location->find('all', array(
          'conditions' => array('name LIKE' => $value.'%', 'type' => LOCATION_COUNTRY),
          'limit' => 10
          ));
        $result = Set::extract('/Location/name', $data);
        break;
      case 'group':
        $data = $this->Media->Group->find('all', array(
          'conditions' => array('name LIKE' => $value.'%'),
          'limit' => 10
          ));
        $result = Set::extract('/Group/name', $data);
        break;
      case 'user':
        $data = $this->Media->User->find('all', array(
          'conditions' => array('username LIKE' => $value.'%'),
          'limit' => 10
          ));
        $result = Set::extract('/User/username', $data);
        break;
      default:
        Logger::err("Unknown type $type");
        $this->redirect(404);
        break;
    }
    return $result;
  }

  function quicksearch($quicksearch = false) {
    if (!empty($this->data) && isset($this->data['Media']['quicksearch'])) {
      $quicksearch = $this->data['Media']['quicksearch'];
    } 

    if ($quicksearch) {
      $this->data = $this->Search->quicksearch($quicksearch, 6);
    }
    $this->set('quicksearch', $quicksearch);
  }

  function query() {
    if (!empty($this->data)) {
      $this->Search->addTags(preg_split('/\s*,\s*/', trim($this->data['Media']['tags'])));
      $this->Search->setTagOp($this->data['Media']['tag_op']);

      $this->Search->addCategories(preg_split('/\s*,\s*/', trim($this->data['Media']['categories'])));
      $this->Search->setCategoryOp($this->data['Media']['category_op']);

      $this->Search->addLocations(preg_split('/\s*,\s*/', trim($this->data['Media']['locations'])));
      $this->Search->setLocationOp($this->data['Media']['location_op']);
      $this->Search->setOperand($this->data['Media']['operand']);

      $this->Search->setFrom($this->data['Media']['from']);
      $this->Search->setTo($this->data['Media']['to']);

      $this->Search->setShow($this->data['Option']['show']);
      $this->Search->setSort($this->data['Option']['sort']);
      if ($this->hasRole(ROLE_GUEST)) {
        $this->Search->setName($this->data['Media']['name']);
        $this->Search->setType($this->data['Media']['type']);
        // Allow to search for my images
        if ($this->data['User']['username'] == $this->getUserId()) {
          $this->Search->setUser($this->data['User']['username']);
        }
      }

      if ($this->hasRole(ROLE_USER)) {
        $this->Search->setVisibility($this->data['Media']['visibility']);

        $this->Search->setUser($this->data['User']['username']);
        $this->Search->addGroup($this->data['Group']['name']);
      } 
      $this->crumbs = $this->Search->convertToCrumbs();
    } 
    $this->render('index');
  }

  function search() {
    if ($this->hasRole(ROLE_USER)) {
      $groups = $this->Group->find('all', array('conditions' => array('Group.user_id' => $this->getUserId()), 'order' => 'Group.name'));
      if ($groups) {
        $groups = Set::combine($groups, "{n}.Group.id", "{n}.Group.name");
      }
      $groups[-1] = 'Any';
      $this->set('groups', $groups);
    }
    $this->set('userId', $this->Search->getUserId() == $this->getUserId() ? $this->getUserId() : false);
    $this->set('userRole', $this->getUserRole());
  }

  function user($username, $param = false, $value = false) {
    $user = $this->User->find('first', array('conditions' => array('User.username' => $username, 'User.role' >= ROLE_USER), 'recursive' => 0));
    if (!$user) {
      Logger::verbose(sprintf("User not found %s", $username));
      $this->render('index');
      return;
    }
    $crumbs = array("user:$username");
    if ($param && $value && in_array($param, array('tag', 'category', 'location'))) {
      $values = preg_split('/\s*,\s*/', trim($value));
      foreach ($values as $value) {
        $crumbs[] = "$param:$value";
      }
      $crumbs = am($crumbs, $this->Search->urlToCrumbs($this->params['url']['url'], 5));
    } elseif ($param == 'folder') {
      $folder = implode('/', array_slice($this->params['pass'], 2));
      $fsRoot = $this->User->getRootDir($user);
      $fsFolder = implode(DS, array_slice($this->params['pass'], 2));
      $fsFolder = Folder::slashTerm(Folder::addPathElement($fsRoot, $fsFolder));
      if (is_dir($fsRoot) && is_dir($fsFolder)) {
        $crumbs[] = "folder:$folder";
        $crumbs[] = "sort:name";
      } else {
        Logger::info(sprintf("Invalid root %s or folder %s", $fsRoot, $fsFolder));
        $this->Session->setFlash(sprintf(__("Invalid folder: %s", true), $folder));
      }
    } else {
      $crumbs = am($crumbs, $this->Search->urlToCrumbs($this->params['url']['url'], 3));
    }
    $this->crumbs = $crumbs;
    $this->render('index');
  }

  function group($name) {
    $this->crumbs = am(array('group:' . $name), $this->Search->urlToCrumbs($this->params['url']['url'], 3));
    $this->render('index');
  }

  function date($year = null, $month = null, $day = null) {
    $this->crumbs = array();
    if ($year && $year > 1950 && $year < 2050) {
      $year = intval($year);
      if ($month && $month > 0 && $month < 13) {
        $y = $year;
        $month = intval($month);
        $m = $month + 1;
        if ($day && $day > 0 && $day < 32) {
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
      $this->crumbs[] = 'from:' . date('Y-m-d H:i:s', $from);
      $this->crumbs[] = 'to:' . date('Y-m-d H:i:s', $to);
      $this->crumbs[] = 'sort:-date';
    } elseif ($year) {
      $from = strtotime($year);
      $sort = 'sort:date';
      if ($from) {
        $this->crumbs[] = 'from:' . date('Y-m-d H:i:s', $from);
        $sort = 'sort:-date';
      }
      if ($month) {
        $to = strtotime($month);
        if ($to) {
          $this->crumbs[] = 'to:' . date('Y-m-d H:i:s', $to);
        }
      }
      $this->crumbs[] = $sort;
    }
    $this->render('index');
  }

  function tag($tags) {
    $tags = preg_split('/\s*,\s*/', trim($tags));
    $crumbs = array();
    foreach($tags as $tag) {
      $crumbs[] = 'tag:' . $tag;
    }
    $this->crumbs = am($crumbs, $this->Search->urlToCrumbs($this->params['url']['url'], 3));
    $this->render('index');
  }

  function category($categories) {
    $categories = preg_split('/\s*,\s*/', trim($categories));
    $crumbs = array();
    foreach($categories as $category) {
      $crumbs[] = 'category:' . $category;
    }
    $this->crumbs = am($crumbs, $this->Search->urlToCrumbs($this->params['url']['url'], 3));
    $this->render('index');
  }

  function location($locations) {
    $locations = preg_split('/\s*,\s*/', trim($locations));
    $crumbs = array();
    foreach($locations as $location) {
      $crumbs[] = 'location:' . $location;
    }
    $this->crumbs = am($crumbs, $this->Search->urlToCrumbs($this->params['url']['url'], 3));
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
    if ($groupId != 0) {
      $media['Media']['group_id'] = $groupId;
    }

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
      $groupIds = Set::extract('/Group/id', $this->Group->getGroupsForMedia($this->getUser()));
      $groupIds[] = -1; // no group
      if (isset($this->data['Group']['id']) &&
        in_array($this->data['Group']['id'], $groupIds)) {
        $groupId = intval($this->data['Group']['id']);
      } else {
        $groupId = 0;
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
      $geoData = false;
      if (!empty($this->data['Media']['geo'])) {
        if ($this->data['Media']['geo'] == '-' || $this->data['Media']['geo'] == '-,-') {
          $geoData = array('latitude' => null, 'longitude' => null);
        } elseif (preg_match('/^\s*([+\-]?[0-9]+(\.[0-9]+)?)\s*,\s*([+\-]?[0-9]+(\.[0-9]+)?)\s*$/', $this->data['Media']['geo'], $m)) {
          $geoData = array('latitude' => $m[1], 'longitude' => $m[3]);
        }
      }

      $ids = split(',', $this->data['Media']['ids']);
      $ids = array_unique($ids);
      foreach ($ids as $id) {
        $id = intval($id);
        if ($id == 0) {
          continue;
        }

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
          if ($geoData) {
            foreach ($geoData as $geo => $value) {
              if ($media['Media'][$geo] !== $value) {
                $media['Media'][$geo] = $value;
                $changedMeta = true;
              }
            }      
          }
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
    $this->redirect('view/' . implode('/', $this->Search->encodeCrumbs($this->crumbs)));
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
          if (!empty($this->data['Media']['geo'])) {
            if ($this->data['Media']['geo'] == '-' || $this->data['Media']['geo'] == '-,-') {
              $media['Media']['latitude'] = null;
              $media['Media']['longitude'] = null;
            } elseif (preg_match('/^\s*([+\-]?[0-9]+(\.[0-9]+)?)\s*,\s*([+\-]?[0-9]+(\.[0-9]+)?)\s*$/', $this->data['Media']['geo'], $m)) {
              $geoData = array('latitude' => $m[1], 'longitude' => $m[3]);
              $media['Media']['latitude'] = $m[1];
              $media['Media']['longitude'] = $m[3];
            }
          }
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
      $groups = $this->Group->getGroupsForMedia($user);
      $groups = Set::combine($groups, '{n}.Group.id', '{n}.Group.name');
      asort($groups);
      $groups[-1] = __('[No Group]', true);
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
        $groupIds = Set::extract('/Group/id', $this->Group->getGroupsForMedia($user));
        $groupIds[] = -1; // no group
        if (isset($this->data['Group']['id']) && in_array($this->data['Group']['id'], $groupIds)) {
          $groupId = $this->data['Group']['id'];
        } else {
          $groupId = 0;
        }
        if ($groupId != 0) {
          $media['Media']['group_id'] = $groupId;
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
    $this->Search->setShow(30);
    $this->Search->setSort('newest');

    if (Configure::read('debug') > 1) {
      Configure::write('debug', 1);
    }
    $this->set(
        'channel', array('title' => "New Images",
        'link' => "/explorer/rss",
        'description' => "Recently Published Images" )
      );
    $this->data = $this->Search->paginateByCrumbs($this->crumbs);
  }

  function media() {
    $this->layout = 'xml';
    if (Configure::read('debug') > 1) {
      Configure::write('debug', 1);
    }
    $this->data = $this->Search->paginateByCrumbs($this->crumbs);
  }

  function points($north, $south, $west, $east) {
    $this->Search->setSort('random');

    $this->data = array();

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
        $this->Search->setNorth($lat + $stepLat);
        $this->Search->setSouth($lat);
        $this->Search->setWest($lng);
        $this->Search->setEast($lng + $stepLng);
        $points = $this->Search->paginate();
        //Logger::trace("Found ".count($points)." points");
        if ($points) {
          $this->data = am($points, $this->data);
        }
        $lng += $stepLng;
      }
      $lat += $stepLat;
    }

    $this->layout = 'xml';
    Logger::trace("Search points of N:$north, S:$south, W:$west, E:$east: Found ".count($this->data)." points");
    $this->FastFileResponder->addAll($this->data, 'mini');
    if (Configure::read('debug') > 1) {
      Configure::write('debug', 1);
    }
  }

}
?>
