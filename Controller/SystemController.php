<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */
class SystemController extends AppController {

  var $name = 'System';
  var $helpers = array('Form');
  var $uses = array('Media', 'Option');
  var $subMenu = array();

  function beforeFilter() {
    parent::beforeFilter();
    $this->requireRole(ROLE_SYSOP, array('redirect' => '/'));

    $this->subMenu = array(
      'index' => __("General"),
      'external' => __("External Programs"),
      'map' => __("Map Settings"),
      'upgrade' => __("Database Upgrade"),
      'deleteUnusedMetaData' => __("Delete Unused Metadata")
      );
  }

  function beforeRender() {
    $this->layout = 'backend';
    parent::beforeRender();
  }

  function _set($userId, $path, $data) {
    $value = Set::extract($data, $path);
    $this->Option->setValue($path, $value, $userId);
  }

  function index() {
    if (isset($this->request->data)) {
      $this->_set(0, 'general.title', $this->request->data);
      $this->_set(0, 'general.subtitle', $this->request->data);
    }
    $this->request->data = $this->Option->getTree(0);
  }

  function external() {
    if (!empty($this->request->data)) {
      // TODO check valid acl
      $this->_set(0, 'bin.exiftool', $this->request->data);
      $this->_set(0, 'bin.convert', $this->request->data);
      $this->_set(0, 'bin.ffmpeg', $this->request->data);
      $this->_set(0, 'bin.flvtool2', $this->request->data);
      // debug
      $this->set('commit', $this->request->data);
      $this->Session->setFlash("Settings saved");
    }
    $tree = $this->Option->getTree(0);
    $this->request->data = $tree;
  }

  function map() {
    if (!empty($this->request->data)) {
      $this->_set(0, 'google.map.key', $this->request->data);
      // debug
      $this->set('commit', $this->request->data);
      $this->Session->setFlash("Settings saved");
    }
    $tree = $this->Option->getTree(0);
    $this->request->data = $tree;
  }
  
  /** Database upgrade via the Migraions plugin */
  function upgrade($action = '') {
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

  function deleteUnusedMetaData($delete = '') {
    $this->Media->Tag->bindModel(array('hasAndBelongsToMany' => array('Media')), false);
    $this->Media->Tag->Behaviors->attach('DeleteUnused', array('relatedHabtm' => 'Media'));

    $this->Media->Category->bindModel(array('hasAndBelongsToMany' => array('Media')), false);
    $this->Media->Category->Behaviors->attach('DeleteUnused', array('relatedHabtm' => 'Media'));

    $this->Media->Location->bindModel(array('hasAndBelongsToMany' => array('Media')), false);
    $this->Media->Location->Behaviors->attach('DeleteUnused', array('relatedHabtm' => 'Media'));

    if ($delete == 'delete') {
      $this->Media->Tag->deleteAllUnused();
      $this->Media->Location->deleteAllUnused();
      $this->Media->Category->deleteAllUnused();
      $this->Session->setFlash(__("All unused meta data are deleted"));
    }

    $unusedTagCount = count($this->Media->Tag->findAllUnused());
    $unusedCategoryCount = count($this->Media->Category->findAllUnused());
    $unusedLocationCount = count($this->Media->Location->findAllUnused());

    $this->request->data = compact('unusedTagCount', 'unusedCategoryCount', 'unusedLocationCount');
  }
}
?>
