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

class UpgradeSchemaComponent extends Component {

  var $controller = null;
  var $dbConfig = null;
  var $db = null;
  var $cakeSchema = null;
  var $schema = null;
  var $modelMapping = array('files' => 'MyFile', 'media' => 'Media');

  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  /** Initialize the database schema and data source
    @return True if the database source could be loaded */
  public function initDataSource($options = array()) {
    if (!empty($this->db)) {
      return true;
    }

    App::uses('ConnectionManager', 'Model');
    try {
      $this->db = ConnectionManager::getDataSource('default');
    } catch (Exception $e) {
      $this->db = null;
      return false;
    }
    if (!$this->db) {
      Logger::err("Could not create database source");
      return false;
    }
    $this->db->cacheSources = false;
    return true;
  }

  /*
  public function loadSchema($options = array()) {
    $options = am(array('path' => CONFIGS.'schema'.DS, 'name' => 'Phtagr'), $options);
    $schema = $this->cakeSchema->load($options);
    if (!$schema) {
      Logger::err("Could not load schema!");
    }
    $this->schema = $schema;
    return $schema;
  }
  */

  public function isConnected() {
    if (!empty($this->db) && $this->db->enabled()) {
      return true;
    } else {
      return false;
    }
  }

  /** Checks for existing tables
    @param tables. Array of tables names. Default array('users')
    @return True if all given tables exists */
  public function hasTables($tables = array()) {
    if (!$this->isConnected()) {
      return false;
    }

    if (!is_array($tables)) {
      $tables = array($tables);
    }
    Logger::debug("Check for required tables: ".implode($tables, ', '));

    $sources = $this->db->listSources();
    foreach ($tables as $table) {
      $tableName = $this->db->fullTableName($table, false, false);
      if (!in_array($tableName, $sources)) {
        Logger::warn("Missing table $tableName (from $table)");
        return false;
      }
    }
    return true;
  }

  public function requireUpgrade() {
    $missingTables = $this->_getMissingTables($this->schema);
    if ($missingTables) {
      Logger::verbose("Missing table(s): ".implode(", ", array_keys($missingTables)));
      return true;
    }
    $alterColumns = $this->_getAlteredColumns(false);
    if ($alterColumns) {
      Logger::verbose("Table change(s): ".implode(", ", array_keys($alterColumns)));
      return true;
    }
    return false;
  }

  public function upgrade($noDrop = false) {
    @ini_set('max_execution_time', 600);
    $errorTables = $this->_createMissingTables();
    $errorColumns = $this->_upgradeTables($noDrop);
    if ($errorTables || $errorColumns) {
      return array('tables' => $errorTables, 'columns' => $errorColumns);
    } else {
      return false;
    }
  }

  public function deleteModelCache() {
    $modelCacheDir = TMP.'cache'.DS.'models'.DS;
    $folder = new Folder($modelCacheDir);
    $modelCacheFiles = $folder->find('cake_model_.*');
    foreach ($modelCacheFiles as $file) {
      if (!@unlink($folder->addPathElement($modelCacheDir, $file))) {
        Logger::err("Could not delete model cache file '$file' in '$modelCacheDir'");
      }
    }
  }

}
?>
