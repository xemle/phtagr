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
App::import('model', array('Media', 'MyFile'));
App::import('component', array('UpgradeSchema'));
App::import('File', 'Search', array('file' => APP.'logger.php'));

class UpgradeMediaSchemaShell extends Shell {

  var $uses = array('Media', 'MyFile');
  var $db = null;
  var $deletePolicy = false;

  function initialize() {
    $this->UpgradeSchema =& new UpgradeSchemaComponent();

    $this->out("Schema Upgrade Shell Script for Media Schema");
    $this->hr();
  }

  function startup() {
  }

  function main() {
    $this->help();
  }

  function _execute($sql) {
    Logger::debug($sql);
    $this->UpgradeSchema->db->execute($sql);
  }

  /** Returns the table name with table prefix */
  function _getTable($name) {
    return $this->UpgradeSchema->db->config['prefix'].$name;
  }

  /** Renames a table 
    @param old Old table name
    @param new New table name */
  function _renameTable($old, $new) {
    $sql = "ALTER TABLE `".$this->_getTable($old)."` RENAME `".$this->_getTable($new)."`";
    $this->_execute($sql);
  }

  /** Renames a column 
    @param old Old column name
    @param new New column name */
  function _renameColumn($table, $old, $new) {
    $colDef = $this->UpgradeSchema->schema->tables[$table][$new];
    $colDef['name'] = $new;
    $sql = "ALTER TABLE `".$this->_getTable($table)."` CHANGE `".$old."` ";
    $sql .= $this->UpgradeSchema->db->buildColumn($colDef);
    $this->_execute($sql);
  }

  /** Checks if the instance requires an upgrade
    @return True if an upgrade is required */
  function _requiresUpgrade() {
    $this->UpgradeSchema->initDataSource();
    $this->UpgradeSchema->loadSchema();

    if ($this->UpgradeSchema->hasTables(array('media', 'files'))) {
      return false;
    } else {
      return true;
    }
  }

  /** Prepares the upgrade by renaming tables and columns */
  function _prepareUpgrade() {
    // rename tables
    $this->_renameTable('images', 'media');
    $this->_renameTable('categories_images', 'categories_media');
    $this->_renameTable('images_locations', 'locations_media');
    $this->_renameTable('images_tags', 'media_tags');

    // rename columns
    $this->_renameColumn('comments', 'image_id', 'media_id');
    $this->_renameColumn('categories_media', 'image_id', 'media_id');
    $this->_renameColumn('locations_media', 'image_id', 'media_id');
    $this->_renameColumn('media_tags', 'image_id', 'media_id');
    
    // no rename for properties and locks to ensure data migration
  }

  /** Finds an external video thumb and adds it to the table 
    @param media Media model data
    @param file File model data */
  function _addVideoThumb($media, $file) {
    $name = $file['File']['file'];
    $pattern = substr($name, 0, strrpos($name, '.')+1)."[Tt][Hh][Mm]";
    $folder =& new Folder();
    $folder->cd($file['File']['path']);
    $found = $folder->find($pattern);
    if (!count($found)) {
      return;
    }
    $filename = $file['File']['path'].$found[0];
    $thumb = $this->MyFile->create($filename, $media['Media']['user_id']);
    $thumb['File']['media_id'] = $media['Media']['id'];
    $thumb['File']['readed'] = date("Y-m-d H:i:s", filemtime($filename));
    if (!$this->MyFile->save($thumb)) {
      Logger::warn("Could not add thumbnail to database");
    } else {
      Logger::verbose("Add thumb file of media {$media['Media']['id']}");
    }
  }

  /** Migrate a single media 
    @param media Media model data 
    @return True on success. False on errors */
  function _migrateMedia($media) {
    // create file model
    $filename = $media['Media']['path'].$media['Media']['file'];
    if (!file_exists($filename)) {
      Logger::err("Cannot find media {$media['Media']['id']}: $filename");
      if ($this->deletePolicy == "a") {
        Logger::warn("Delete not existing media file {$media['Media']['id']}: $filename");
        $this->out("Auto delete not existing media file {$media['Media']['id']}: $filename");
        $this->Media->delete($media['Media']['id']);
        return false;
      } 
      $a = false;
      while (!in_array($a, array("d", "s", "r", "a", "c"))) {
        $a = $this->in("Could not find media {$media['Media']['id']}: $filename!\n[d]elete, [s]kip, [r]etry, delete [a]ll, [c]ancel", array("d", "s", "r", "a", "c"), "d");
      }
      if ($a == "d" || $a == "a") {
        Logger::warn("Delete media {$media['Media']['id']}: $filename");
        $this->Media->delete($media['Media']['id']);
        if ($a == "a") {
          $this->deletePolicy = "a";
        }
        return true;
      } elseif ($a == "s") {
        Logger::warn("Skip media {$media['Media']['id']}: $filename");
        return true;
      } elseif ($a == "r") {
        clearstatcache();
        $media = $this->Media->findById($media['Media']['id']);
        return $this->_migrateMedia($media);
      } elseif ($a == "c") {
        $this->error("Missing media {$media['Media']['id']}", "User abort");
      }
    }
    $file = $this->MyFile->create($filename, $media['Media']['user_id']);
    $file['File']['media_id'] = $media['Media']['id'];
    $file['File']['readed'] = date("Y-m-d H:i:s", filemtime($filename));
    if (!$this->MyFile->save($file)) {
      Logger::debug("Cannot migrate data from media {$media['Media']['id']}");
      Logger::warn($file);
      return false;
    } 

    // migrate properties and locks
    $fileId = $this->MyFile->getLastInsertId();
    $file['File']['id'] = $fileId;
    $sql = "UPDATE ".$this->_getTable('properties')." SET file_id = $fileId WHERE image_id = {$media['Media']['id']}";
    $this->_execute($sql);
    $sql = "UPDATE ".$this->_getTable('locks')." SET file_id = $fileId WHERE image_id = {$media['Media']['id']}";
    $this->_execute($sql);

    if (($media['Media']['flag']) & 1 > 0) {
      // set media type
      $type = $file['File']['type'];
      switch ($type) {
        case FILE_TYPE_IMAGE:
          $this->Media->setType($media, MEDIA_TYPE_IMAGE);
          break;
        case FILE_TYPE_VIDEO:
          // if video search for thumb and add it
          $this->Media->setType($media, MEDIA_TYPE_VIDEO);
          $this->_addVideoThumb($media, $file);
          break;
        default:
          Logger::warn("Unhandled file type $type");
      }
      // Delete old flag IMAGE_FLAG_ACTIVE
      $this->Media->deleteFlag($media, 1);
    } else {
      // Inactive media will be deleted, data is stored in files
      $this->Media->delete($media['Media']['id']);
      Logger::debug("Deleted inactive media {$media['Media']['id']}");
    }
    return true;
  }

  /** Migrates the the old data to the new data schema. Creates file models for
    * each media */
  function _migrateData() {
    // init media and file model
    $this->Media =& new Media();
    $this->MyFile =& new MyFile();

    // fetch all media
    $this->Media->unbindAll();
    $media = $this->Media->findAll("1 = 1", array('Media.id', 'Media.path', 'Media.file', 'Media.user_id', 'Media.flag'));
    $this->out("Migrate ".count($media)." media...");
    Logger::verbose("Found ".count($media)." media to migrade ...");

    $errors = 0;
    foreach ($media as $m) {
      if (!$this->_migrateMedia($m)) {
        $errors++;
      }
    }
    if ($errors) {
      $this->out("Upgrade ".count($media)." media with $errors errors");
      Logger::err("Upgrade ".count($media)." media with $errors errors");
      return true;
    } else {
      Logger::info("Upgrade ".count($media)." media successfully");
      return false;
    }
  }

  function upgrade() {
    if (!$this->_requiresUpgrade()) {
      $this->out("Hey! No upgrade required ;-)");
      return;
    }
    $this->out("Prepare upgrade...");
    // upgrade database
    $this->_prepareUpgrade();

    // automatic create missing tables/columns
    $this->UpgradeSchema->deleteModelCache();
    $this->out("Upgrade schema...");
    $this->UpgradeSchema->upgrade(true);

    // migrate data
    $error = false;
    $this->UpgradeSchema->deleteModelCache();
    $error = $this->_migrateData();

    $this->out("Finalizing upgrade...");
    // delete model cache dir
    $this->UpgradeSchema->deleteModelCache();
    // downgrade database and drop not required tables/columns
    $this->UpgradeSchema->upgrade();

    if (!$error) {
      $this->out("All done. Enjoy!");
    } else {
      $this->out("Mmm. Something went wrong. Use your backup and look logs for details");
    }
  }

  function help() {
    $this->out("Help screen");
    $this->hr();
    $this->out("upgrade");
    $this->out("\tUpgrade schema to media schema");
    $this->hr();
    exit();
  }
}
?>
