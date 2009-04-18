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
App::import('model', array('Media', 'MyFile'));
App::import('component', array('Logger', 'UpgradeSchema'));

class UpgradeMediaSchemaShell extends Shell {

  var $uses = array('Media', 'MyFile');
  var $db = null;
  var $Logger = null;
  var $_dryRun = false;

  function initialize() {
    $this->Logger =& new LoggerComponent();
    $this->UpgradeSchema =& new UpgradeSchemaComponent();
    $this->UpgradeSchema->Logger =& $this->Logger;

    $this->out("Schema Upgrade Shell Script for Media Schema");
    $this->hr();
  }

  function startup() {
  }

  function main() {
    $this->help();
  }

  function _execute($sql) {
    $this->Logger->debug($sql);
    if (!$this->_dryRun) {
      $this->UpgradeSchema->db->execute($sql);
    }
  }

  function _getTable($name) {
    return $this->UpgradeSchema->db->config['prefix'].$name;
  }

  function _renameTable($from, $to) {
    $sql = "ALTER TABLE `".$this->_getTable($from)."` RENAME `".$this->_getTable($to)."`";
    $this->_execute($sql);
  }

  function _renameColumn($table, $from, $to) {
    $colDef = $this->UpgradeSchema->schema->tables[$table][$to];
    $colDef['name'] = $to;
    $sql = "ALTER TABLE `".$this->_getTable($table)."` CHANGE `".$from."` ";
    $sql .= $this->UpgradeSchema->db->buildColumn($colDef);
    $this->_execute($sql);
  }

  function _prepareUpgrade() {
    $this->UpgradeSchema->initDataSource();
    $this->UpgradeSchema->loadSchema();
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

  function _addVideoThumb($media, $file) {
    $name = $file['File']['file'];
    $pattern = substr($name, 0, strrpos($name, '.')+1)."[Tt][Hh][Mm]";
    $this->Logger->debug($pattern);
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
      $this->Logger->warn("Could not add thumbnail to database");
    } else {
      $this->Logger->verbose("Add thumb file of media {$media['Media']['id']}");
    }
  }

  function _migrateData() {
    // init media and file model
    $this->Media =& new Media();
    $this->MyFile =& new MyFile();
    // foreach media
    $this->Media->unbindAll();
    $media = $this->Media->findAll("1 = 1", array('Media.id', 'Media.path', 'Media.file', 'Media.user_id', 'Media.flag'));
    $this->out("Migrate ".count($media)." media...");
    $this->Logger->verbose("Migrate ".count($media)." media");
    $errors = 0;
    foreach ($media as $media) {
      //  create file model
      $filename = $media['Media']['path'].$media['Media']['file'];
      $file = $this->MyFile->create($filename, $media['Media']['user_id']);
      $file['File']['media_id'] = $media['Media']['id'];
      $file['File']['readed'] = date("Y-m-d H:i:s", filemtime($filename));
      if (!$this->MyFile->save($file)) {
        $this->Logger->debug("Cannot migrate data from media {$media['Media']['id']}");
        $this->Logger->warn($file);
        $errors++;
        continue;
      } 
      // migrate properties and locks
      $fileId = $this->MyFile->getLastInsertId();
      $file['File']['id'] = $fileId;
      $sql = "UPDATE ".$this->_getTable('properties')." SET file_id = $fileId WHERE image_id = {$media['Media']['id']}";
      $this->_execute($sql);
      $sql = "UPDATE ".$this->_getTable('locks')." SET file_id = $fileId WHERE image_id = {$media['Media']['id']}";
      $this->_execute($sql);

      // set media type
      $type = $file['File']['type'];
      switch ($type) {
        case FILE_TYPE_IMAGE:
          $this->Media->setType($media, MEDIUM_TYPE_IMAGE);
          break;
        case FILE_TYPE_VIDEO:
          // if video search for thumb and add it
          $this->Media->setType($media, MEDIUM_TYPE_VIDEO);
          $this->_addVideoThumb($media, $file);
          break;
        default:
          $this->Logger->warn("Unhandled file type $type");
      }
    }
    if ($errors) {
      $this->out("Upgrade ".count($media)." media with $errors errors");
      $this->Logger->err("Upgrade ".count($media)." media with $errors errors");
    } else {
      $this->Logger->info("Upgrade ".count($media)." media without errors");
    }
  }

  function upgrade() {
    // @todo add parameters for base path, config or tmp directory
    // @todo check tables for upgrade
    $this->out("Prepare upgrade...");
    // upgrade database
    $this->_prepareUpgrade();
    //  automatic create missing tables/columns
    $this->UpgradeSchema->deleteModelCache();
    $this->out("Upgrade schema...");
    $this->UpgradeSchema->upgrade(true);
    // delete model cache dir
    $this->UpgradeSchema->deleteModelCache();
    $this->_migrateData();
    // downgrade database
    //  drop not required tables/columns
    // delete model cache dir
    $this->out("Finalize schema...");
    $this->UpgradeSchema->deleteModelCache();
    $this->UpgradeSchema->upgrade();
    $this->out("Done.");
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
