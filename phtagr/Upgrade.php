<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006,2007 Sebastian Felis, sebastian@phtagr.org
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

include_once("$phtagr_lib/Base.php");

/** @class Upgrade Upgrades the database */
class Upgrade extends Base
{

/** Old version */
var $_old;

function Upgrade()
{
  global $conf;
  $this->_old=$conf->get('db.version', -1);
}

/** @return Returns the version of phtagr befor the upgrage */
function get_old_version()
{
  return $this->_old;
}

/** @return Returns the current version */
function get_cur_version()
{
  global $conf;
  return $conf->get('db.version', -1);
}

/** @return True if an upgrade is needed. False otherwise */
function is_upgradable()
{
  if ($this->get_cur_version() < DB_VERSION)
    return true;
  return false;
}
/** Run the upgrade procedures. This operation requires admin rights */
function do_upgrade()
{
  global $user;
  global $conf;
  
  if (!$user->is_admin())
    return ERR_NOT_PERMITTED;

  $cur_version=$conf->get('db.version', -1);
  while ($cur_version<DB_VERSION)
  {
    switch ($cur_version)
    {
    case -1:
      $cur_version=$this->_upgrade_to_1(); break;
    case 1:
      // This should be done via external php script changedb.svn183.sh
      break;    
    case 2:
      $cur_version=$this->_upgrade_to_3(); break;
    case 3:
      $cur_version=$this->_upgrade_to_4(); break;
    case 4:
      // This should be done via external php script changedb.svn218.sh
      break;
    case 5:
      $cur_version=$this->_upgrade_to_6(); break;
    case 6:
      $cur_version=$this->_upgrade_to_7(); break;
    case 7:
      $cur_version=$this->_upgrade_to_8(); break;
    default: break;
    }
  }
}

/** Upgrade ACLs values, since the bit values changed */
function _upgrade_to_1()
{
  global $db;
  global $conf;

  $sql="ALTER TABLE $db->images
        CHANGE oacl macl TINYINT UNSIGNED";
  $db->query($sql);

  $sql="UPDATE $db->images
        SET gacl=32 WHERE gacl=16";
  $db->query($sql);

  $sql="UPDATE $db->images
        SET gacl=33 WHERE gacl!=32 AND gacl!=0";
  $db->query($sql);

  $sql="UPDATE $db->images
        SET macl=32 WHERE macl=16";
  $db->query($sql);

  $sql="UPDATE $db->images
        SET aacl=32 WHERE aacl=16";
  $db->query($sql);

  $sql="DELETE FROM $db->confs
        WHERE name='image.gacl'
          OR name='image.macl'
          OR name='image.aacl'";
  $db->query($sql);

  $conf->set_default('db.version', '1');
  return 1;
}

/** Add important index */
function _upgrade_to_3()
{
  global $db, $conf;

  $sql="ALTER TABLE $db->users ".
       "ADD INDEX(id)";
  $db->query($sql);

  $sql="ALTER TABLE $db->groups ".
       "ADD INDEX(id)";
  $db->query($sql);

  $sql="ALTER TABLE $db->usergroup ".
       "ADD INDEX(userid), ADD INDEX(groupid)";
  $db->query($sql);

  $sql="ALTER TABLE $db->images ".
       "ADD INDEX(id)";
  $db->query($sql);

  $sql="ALTER TABLE $db->imagetag ".
       "ADD INDEX(tagid)";
  $db->query($sql);

  $sql="ALTER TABLE $db->imageset ".
       "ADD INDEX(setid)";
  $db->query($sql);

  $sql="ALTER TABLE $db->imagelocation ".
       "ADD INDEX(locationid)";
  $db->query($sql);

  $sql="ALTER TABLE $db->logs ".
       "CHANGE image imageid INT DEFAULT NULL, ".
       "CHANGE user userid INT DEFAULT NULL";
  $db->query($sql);

  $sql="ALTER TABLE $db->logs ".
       "ADD INDEX(imageid), ADD INDEX(userid)";
  $db->query($sql);

  $conf->set_default('db.version', '3');
  return 3;
}

/** Split filename to path and file */
function _upgrade_to_4()
{
  global $db, $conf;

  // Table changes
  $sql="ALTER TABLE $db->images ".
       "CHANGE synced modified DATETIME, ".
       "CHANGE is_upload flag TINYINT UNSIGNED DEFAULT 0, ".
       "CHANGE aacl pacl TINYINT UNSIGNED DEFAULT 0, ".
       "ADD file VARCHAR(128) NOT NULL";
  $db->query($sql);

  // Set defaults
  $sql="ALTER TABLE $db->images ".
       "CHANGE gacl gacl TINYINT UNSIGNED DEFAULT 0, ".
       "CHANGE macl macl TINYINT UNSIGNED DEFAULT 0, ".
       "CHANGE filename path TEXT NOT NULL, ".
       "CHANGE width width INT UNSIGNED DEFAULT 0, ".
       "CHANGE height height INT UNSIGNED DEFAULT 0, ".
       "CHANGE orientation orientation TINYINT UNSIGNED DEFAULT 1, ".
       "CHANGE caption caption TEXT DEFAULT NULL, ".
       "CHANGE longitude longitude FLOAT DEFAULT NULL, ".
       "CHANGE latitude latitude FLOAT DEFAULT NULL, ".
       "CHANGE hue hue FLOAT DEFAULT NULL, ".
       "CHANGE saturation saturation FLOAT DEFAULT NULL, ".
       "CHANGE luminosity luminosity FLOAT DEFAULT NULL";
  $db->query($sql);

  // Imported files
  $sql="UPDATE $db->images ".
       "SET flag=128 ".
       "WHERE flag=0";
  $db->query_update($sql);
  
  // Uploaded and imported files
  $sql="UPDATE $db->images ".
       "SET flag=192 ".
       "WHERE flag=1";
  $db->query_update($sql);

  // Copy file part (basename)
  $sql="UPDATE $db->images ".
       " SET file=SUBSTRING_INDEX(path, '/', -1)";
  $db->query_update($sql);

  // Skip file part (only slashed path)
  $sql="UPDATE $db->images ".
       " SET path=LEFT(path, LENGTH(path)-LENGTH(SUBSTRING_INDEX(path, '/', -1)))";
  $db->query_update($sql);

  // Change index aacl to pacl
  $sql="ALTER TABLE $db->images ".
       "ADD INDEX(pacl), ".
       "DROP INDEX aacl ";
  $db->query($sql);

  $conf->set_default('db.version', '4');
  return 4;
}

function _upgrade_to_6() {
  global $db, $conf;

  // Add index column to configs
  $sql="ALTER TABLE $db->configs ".
       "ADD id INT NOT NULL AUTO_INCREMENT,".
       "ADD INDEX(id)";
  $db->query($sql);

  // Alter roles
  $sql="UPDATE $db->users ".
       "SET role=role+10";
  $db->query_update($sql);
  // Reset admins
  $sql="UPDATE $db->users ".
       "SET role=".USER_ADMIN.' '.
       "WHERE role=11";
  $db->query_update($sql);
  // Reset members
  $sql="UPDATE $db->users ".
       "SET role=".USER_MEMBER.' '.
       "WHERE role=12";
  $db->query_update($sql);
  // Reset guests
  $sql="UPDATE $db->users ".
       "SET role=".USER_GUEST.' '.
       "WHERE role=13";
  $db->query_update($sql);

  // Alter image flags
  $sql="Update $db->images ".
       "SET flag=(flag>>7)+(((flag&64)>>5)^2)";
  $db->query($sql);

  $conf->set_default('db.version', '6');
  return 6;
}

function _upgrade_to_7() {
  global $db, $conf;

  // Rename creator to creator_id
  $sql="ALTER TABLE $db->users ".
       "CHANGE creator creator_id INT UNSIGNED DEFAULT 0, ".
       "CHANGE expire expires DATETIME DEFAULT NULL";
  $db->query($sql);

  $conf->set_default('db.version', '7');
  return 7;
}

function _upgrade_to_8() {
  global $db, $conf, $log;

  $sql="ALTER TABLE $db->images ".
       "ADD filetime DATETIME NOT NULL";
  $db->query($sql);
  $log->warn("Alter table images!");

  $sql="SELECT id,path,file FROM $db->images ORDER BY id";
  $result = $db->query_table($sql);
  foreach ($result as $row) {
    $mtime = filemtime($row['path'].$row['file']);
    $sql = "UPDATE $db->images SET filetime='".date("Y-m-d H:i:s", $mtime)."' WHERE id={$row['id']}";
    $db->query($sql);
  }
  $log->warn("Set filetime for ".count($result)." images");

  $log->warn("Set new DB version to 8!");
  $conf->set_default('db.version', '8');
  return 8;
}
}
?>
