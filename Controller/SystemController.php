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
class SystemController extends AppController {

  var $name = 'System';
  var $helpers = array('Form', 'Number');
  var $uses = array('Media', 'Option');
  var $components = array('Exiftool');
  var $subMenu = array();

  public function beforeFilter() {
    parent::beforeFilter();
    $this->requireRole(ROLE_SYSOP, array('redirect' => '/'));

    $this->subMenu = array(
      'index' => __("General"),
      'register' => __("User Registration"),
      'external' => __("External Programs"),
      'map' => __("Map Settings"),
      'upgrade' => __("Database Upgrade"),
      'deleteUnusedMetaData' => __("Delete Unused Metadata"),
      'view' => __("Overview")
      );
  }

  public function beforeRender() {
    $this->layout = 'backend';
    parent::beforeRender();
  }

  private function _setOption($userId, $path, $data) {
    $value = Set::extract($data, $path);
    $this->Option->setValue($path, $value, $userId);
  }

  public function __fromReadableSize($readable) {
    if (is_float($readable) || is_numeric($readable)) {
      return $readable;
    } elseif (preg_match_all('/^\s*(0|[1-9][0-9]*)(\.[0-9]+)?\s*([KkMmGg][Bb]?)?\s*$/', $readable, $matches, PREG_SET_ORDER)) {
      $matches = $matches[0];
      $size = (float)$matches[1];
      if (is_numeric($matches[2])) {
        $size += $matches[2];
      }
      if (is_string($matches[3])) {
        switch ($matches[3][0]) {
          case 'k':
          case 'K':
            $size = $size * 1024;
            break;
          case 'm':
          case 'M':
            $size = $size * 1024 * 1024;
            break;
          case 'g':
          case 'G':
            $size = $size * 1024 * 1024 * 1024;
            break;
          case 'T':
            $size = $size * 1024 * 1024 * 1024 * 1024;
            break;
          default:
            Logger::err("Unknown unit {$matches[3]}");
        }
      }
      if ($size < 0) {
        Logger::err("Size is negtive: $size");
        return 0;
      }
      return $size;
    } else {
      return 0;
    }
  }

  public function index() {
    if (!empty($this->request->data)) {
      $this->_setOption(0, 'general.title', $this->request->data);
      $this->_setOption(0, 'general.subtitle', $this->request->data);
      $this->Session->setFlash(__("Titles were updated"));
    }
    $this->request->data = $this->Option->getTree(0);
  }

  public function register() {
    if (!empty($this->request->data)) {
      if ($this->request->data['user']['register']['enable']) {
        $this->Option->setValue('user.register.enable', 1, 0);
      } else {
        $this->Option->setValue('user.register.enable', 0, 0);
      }
      $quota = $this->__fromReadableSize($this->request->data['user']['register']['quota']);
      $this->Option->setValue('user.register.quota', $quota, 0);

      if ($this->request->data['user']['logging']['enable']) {
        $this->Option->setValue('user.logging.enable', 1, 0);
      } else {
        $this->Option->setValue('user.logging.enable', 0, 0);
      }

      $this->Session->setFlash(__("Options saved!"));
    }
    $this->request->data = $this->Option->getTree($this->getUserId());

    // add default values
    if (!isset($this->request->data['user']['register']['enable'])) {
      $this->request->data['user']['register']['enable'] = 0;
    }
    if (!isset($this->request->data['user']['register']['quota'])) {
      $this->request->data['user']['register']['quota'] = (float)100*1024*1024;
    }
    if (!isset($this->request->data['user']['logging']['enable'])) {
      $this->request->data['user']['logging']['enable'] = 0;
    }

  }

  public function external() {
    if (!empty($this->request->data)) {
      $this->_setOption(0, 'bin.exiftool', $this->request->data);
      $this->_setOption(0, 'bin.convert', $this->request->data);
      $this->_setOption(0, 'bin.ffmpeg', $this->request->data);
      $this->_setOption(0, 'bin.flvtool2', $this->request->data);
      $this->_setOption(0, $this->Exiftool->stayOpenOption, $this->request->data);

      $this->Session->setFlash("Settings saved");
    }
    $tree = $this->Option->getTree(0);
    $this->request->data = $tree;
  }

  public function map() {
    if (!empty($this->request->data)) {
      $this->_setOption(0, 'google.map.key', $this->request->data);
      // debug
      $this->set('commit', $this->request->data);
      $this->Session->setFlash("Settings saved");
    }
    $tree = $this->Option->getTree(0);
    $this->request->data = $tree;
  }

  /** Database upgrade via the Migraions plugin */
  public function upgrade($action = '') {
    CakePlugin::load('Migrations');
    App::import('Lib', 'Migrations.MigrationVersion');
    $Migration = new MigrationVersion(array('connection' => 'default'));
    if (empty($Migration)) {
      Logger::err("Could not load class Migrations.MigrationVersion");
      $this->redirect(false, 505);
    }
    $currentVersion = $Migration->getVersion('app');
    // Fallback of older databases: Add fist version if not exists
    if ($currentVersion == 0) {
      $Migration->setVersion(1, 'app');
    }
    $migrationVersion = max(array_keys($Migration->getMapping('app')));
    if ($action == 'run' && $currentVersion < $migrationVersion) {
      @ini_set('max_execution_time', 600);
      if (!$Migration->run(array('type' => 'app', 'direction' => 'up'))) {
        $this->Session->setFlash(__("Database migration failed. Please see the log files for errors."));
        Logger::error("Could not run migration");
      } else {
        Logger::info("Upgraded database from version $currentVersion to " . max(array_keys($Migration->getMapping('app'))));
        $this->Session->setFlash(__("The database migration was successful."));
      }
    }
    $this->set('currentVersion', $Migration->getVersion('app'));
    $mappings = $Migration->getMapping('app');
    $this->set('maxVersion', max(array_keys($mappings)));

    $newMappingNames = array();
    foreach ($mappings as $version => $mapping) {
      if ($version > $currentVersion) {
        $newMappingNames[] = $mapping['name'];
      }
    }
    $this->set('newMappingNames', $newMappingNames);
  }

  public function deleteUnusedMetaData($delete = '') {
    $this->Media->Field->bindModel(array('hasAndBelongsToMany' => array('Media')), false);
    $this->Media->Field->Behaviors->attach('DeleteUnused', array('relatedHabtm' => 'Media'));

    if ($delete == 'delete') {
      $this->Media->Field->deleteAllUnused();
      $this->Session->setFlash(__("All unused meta data are deleted"));
    }

    $unusedFieldCount = count($this->Media->Field->findAllUnused());

    $this->request->data = compact('unusedFieldCount');
  }

  public function view() {
    $data = array();
    $data['users'] = $this->User->find('count', array('conditions' => array('User.role >=' => ROLE_USER)));
    $data['guests'] = $this->User->find('count', array('conditions' => array('User.role =' => ROLE_GUEST)));
    $data['groups'] = $this->User->Group->find('count');
    $data['files'] = $this->Media->File->find('count');
    $data['files.external'] = $this->Media->File->find('count', array('conditions' => array('File.flag & ' . FILE_FLAG_EXTERNAL. ' > 0')));
    $bytes = $this->Media->File->find('all', array(
      'fields' => 'SUM(File.size) AS Bytes'));
    $data['file.size'] = floatval($bytes[0][0]['Bytes']);
    $bytes = $this->Media->File->find('all', array(
      'conditions' => array("File.flag & ".FILE_FLAG_EXTERNAL." > 0"),
      'fields' => 'SUM(File.size) AS Bytes'));
    $data['file.size.external'] = floatval($bytes[0][0]['Bytes']);
    $bytes = $this->Media->File->find('all', array(
      'conditions' => array("File.flag & ".FILE_FLAG_EXTERNAL." = 0"),
      'fields' => 'SUM(File.size) AS Bytes'));
    $data['file.size.internal'] = floatval($bytes[0][0]['Bytes']);
    $data['media'] = $this->Media->find('count');
    $data['media.images'] = $this->Media->find('count', array(
      'conditions' => array('Media.type & ' . MEDIA_TYPE_IMAGE . ' > 0')));
    $data['media.videos'] = $this->Media->find('count', array(
      'conditions' => array('Media.type & ' . MEDIA_TYPE_VIDEO . ' > 0')));
    $result = $this->Media->find('all', array(
      'conditions' => array('Media.type & ' . MEDIA_TYPE_VIDEO . ' > 0', 'Media.duration > 0'),
      'fields' => 'SUM(Media.duration) AS Duration'));
    Logger::debug($result);
    $data['media.video.length'] = floatval($result[0][0]['Duration']);
    $data['comments'] = $this->Media->Comment->find('count');
    $allFields = $this->Media->Field->find('all');
    $data['tags'] = count(Set::extract('/Field[name=keyword]/data', $allFields));
    $data['categories'] = count(Set::extract('/Field[name=category]/data', $allFields));
    $data['locations'] = count(Set::extract('/Field[name=/(sublocation|city|state|country)/]/data', $allFields));
    $this->set('data', $data);
  }
}
?>
