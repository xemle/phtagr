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

uses('file', 'model' . DS . 'schema');

class UpgradeSchemaComponent extends Object{

  var $controller = null;
  var $dbConfig = null; 
  var $db = null;
  var $cakeSchema = null;
  var $schema = null;
  var $modelMapping = array('files' => 'MyFile', 'media' => 'Media');

  function startup(&$controller) {
    $this->controller = $controller;
  }

  /** Initialize the database schema and data source
    @return True if the database source could be loaded */
  function initDataSource($options = array()) {
    if (!empty($this->db)) {
      return true;
    }

    App::import('Core', 'CakeSchema');
    $options = am(array('path' => CONFIGS.'sql'.DS, 'name' => 'Phtagr'), $options);
    $this->cakeSchema =& new CakeSchema($options);
    if (!$this->cakeSchema) {
      Logger::err("Could not create database schema");
      return false;
    }

    App::import('Core', 'ConnectionManager');
    $this->db =& ConnectionManager::getDataSource($this->cakeSchema->connection);
    if (!$this->db) {
      Logger::err("Could not create database source");
      return false;
    }
    $this->db->cacheSources = false;
    return true;
  }

  function loadSchema($options = array()) {
    $options = am(array('path' => CONFIGS.'sql'.DS, 'name' => 'Phtagr'), $options);
    $schema = $this->cakeSchema->load($options);
    if (!$schema) {
      Logger::err("Could not load schema!");
    }
    $this->schema = $schema;
    return $schema;
  }


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

  /** Checks the current database for missing tables. If tables are missing, it
   * returns an array of creation statements 
    @param Schema Current Schema
    @param Create statements or false if all required tables are in the
    database */
  function _getMissingTables() {
    // Reset sources for refetching
    $sources = $this->db->listSources();
    $requiredTables = array();
    $missingTables = array();
    foreach ($this->schema->tables as $table => $fields) {
      $tableName = $this->db->fullTableName($table, false);
      $requiredTables[] = $tableName;
      if (!in_array($tableName, $sources)) {
        $missingTables[$table] = $this->db->createSchema($this->schema, $table);
      }
    }
    // set tables sources only to the required tables. This overwrites current
    // list and hides not required tables
    $this->db->_sources = $requiredTables;

    if (!count($missingTables)) {
      return false;
    }
    return $missingTables;
  }
  
  /** Create tables according to create statements
    @param Schema Current table schema
    @param Array of creation statements
    @return On success it returns false. If error occurs, it returns the
    creation statements */
  function _createTables($newTables) {
    if (empty($this->schema) || !$newTables) {
      return false;
    }

    $errors = array();
    foreach ($newTables as $table => $sql) {
      $tableName = $this->db->fullTableName($table, false);
      if (!$this->db->_execute($sql)) {
        $errors[$table] = $sql;
        Logger::err("Could not create table '$tableName'");
        Logger::debug($sql);
      } else {
        Logger::info("Created new table '$tableName'");
      }
    }
    if (!count($errors)) {
      return false;
    }
    return $errors;
  }

  function _getAlteredColumns($noDrop = false) {
    // Reset sources for refetching
    $this->db->_sources = null;
    $Old = $this->cakeSchema->read();
    $compare = $this->cakeSchema->compare($Old, $this->schema);
    $models = Configure::listObjects('model');

    // remove column drops if required
    if ($noDrop) {
      foreach ($compare as $table => $changes) {
        if (isset($compare[$table]['drop'])) {
          unset($compare[$table]['drop']);
        }
      }
    }

    // Check changes
    $columns = array();
    $sources = $this->db->listSources();
    foreach ($compare as $table => $changes) {
      // Check for existing table
      $tableName = $this->db->fullTableName($table, false);
      if (!in_array($tableName, $sources)) {
        Logger::warn("Skip table changes of not existing table '$tableName'");
        continue;
      }
      // Check for existing model
      if (!isset($this->modelMapping[$table])) {
        $modelName = Inflector::classify($table);
      } else {
        $modelName = $this->modelMapping[$table];
      }
      if (!in_array($modelName, $models)) {
        Logger::warn("Model '$modelName' does not exists");
      }

      $columns[$table] = $this->db->alterSchema(array($table => $changes), $table);
    }
    
    if (!count($columns)) {
      return false;
    }
    return $columns;
  }

  function _alterColumns($columns) {
    if (empty($this->schema) || !$columns)  {
      return false;
    }

    $errors = array();
    foreach ($columns as $table => $sql) {
      $tableName = $this->db->fullTableName($table, false);
      if (!$this->db->_execute($sql)) {
        $errors[$table] = $sql;
        Logger::err("Could not update table '$tableName'");
        Logger::debug($sql);
      } else {
        Logger::info("Upgraded table '$tableName'");
        Logger::trace($sql);
      }
    }
    if (!count($errors)) {
      return false;
    }
    return $errors;
  }

  function requireUpgrade() {
    $missingTables = $this->_getMissingTables($this->schema);
    if ($missingTables) {
      Logger::info("Missing table(s): ".implode(", ", array_keys($missingTables)));
      return true;
    }
    $alterColumns = $this->_getAlteredColumns($this->schema);
    if ($alterColumns) {
      Logger::info("Table change(s): ".implode(", ", array_keys($alterColumns)));
      return true;
    }
    return false;
  }

  /** @todo Drop not required tables */
  function _createMissingTables() {
    $missingTables = $this->_getMissingTables();
    return $this->_createTables($missingTables);
  }

  function _upgradeTables($noDrop = false) {
    $alterColumns = $this->_getAlteredColumns($noDrop);
    return $this->_alterColumns($alterColumns);
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
