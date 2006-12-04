<?php

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
      $this->_upgrade_to_1(); break;
    default: break;
    }
    $cur_version=$conf->get('db.version', -1);
  }
}

/** Upgrade ACLs values, since the bit values changed */
function _upgrade_to_1()
{
  global $db;
  global $conf;

  $sql="ALTER TABLE $db->image
        CHANGE oacl macl TINYINT UNSIGNED";
  $db->query($sql);

  $sql="UPDATE $db->image
        SET gacl=32 WHERE gacl=16";
  $db->query($sql);

  $sql="UPDATE $db->image
        SET gacl=33 WHERE gacl!=32 AND gacl!=0";
  $db->query($sql);

  $sql="UPDATE $db->image
        SET macl=32 WHERE macl=16";
  $db->query($sql);

  $sql="UPDATE $db->image
        SET aacl=32 WHERE aacl=16";
  $db->query($sql);

  $sql="DELETE FROM $db->conf
        WHERE name='image.gacl'
          OR name='image.macl'
          OR name='image.aacl'";
  $db->query($sql);

  $conf->set_default('db.version', '1');
  $this->success(_("Your phtagr instance is upgraded to version 1"));
}

}
?>
