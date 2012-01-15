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

class UpgradeSchemaComponent extends Component {

  var $controller = null;
  var $dbConfig = null; 
  var $db = null;
  var $cakeSchema = null;
  var $schema = null;
  var $modelMapping = array('files' => 'MyFile', 'media' => 'Media');

  function initialize(&$controller) {
    $this->controller = $controller;
  }

  /** Initialize the database schema and data source
    @return True if the database source could be loaded */
  function initDataSource($options = array()) {
    if (!empty($this->db)) {
      return true;
    }

    App::uses('ConnectionManager', 'Model');
    $this->db =& ConnectionManager::getDataSource('default');
    if (!$this->db) {
      Logger::err("Could not create database source");
      return false;
    }
    $this->db->cacheSources = false;
    return true;
  }

  /*
  function loadSchema($options = array()) {
    $options = am(array('path' => CONFIGS.'schema'.DS, 'name' => 'Phtagr'), $options);
    $schema = $this->cakeSchema->load($options);
    if (!$schema) {
      Logger::err("Could not load schema!");
    }
    $this->schema = $schema;
    return $schema;
  }
  */

  function isConnected() {
    if (!empty($this->db) && $this->db->connected) {
      return true;
    } else {
      return false;
    }
  }

  /** Checks for existing tables
    @param tables. Array of tables names. Default array('users')
    @return True if all given tables exists */
  function hasTables($tables = array()) {
    if (!$this->isConnected()) {
      return false;
    }

    if (!is_array($tables)) {
      $tables = array($tables);
    }
    Logger::debug("Check for required tables: ".implode($tables, ', '));

    $sources = $this->db->listSources();
    foreach ($tables as $table) {
      $tableName = $this->db->fullTableName($table, false);
      if (!in_array($tableName, $sources)) {
        Logger::warn("Missing table $tableName (from $table)");
        return false;
      }
    }
    return true;
  }
  
  function requireUpgrade() {
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

  function upgrade($noDrop = false) {
    $errorTables = $this->_createMissingTables();
    $errorColumns = $this->_upgradeTables($noDrop);
    if ($errorTables || $errorColumns) {
      return array('tables' => $errorTables, 'columns' => $errorColumns);
    } else {
      return false;
    }
  }

  function deleteModelCache() {
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
