<?php

include_once("$phtagr_lib/Base.php");

/** @class Upgrade Upgrades the database 
  This class handles the access control list (ACL) operations. It proceeds the
  HTML requests and sets the acl levels. */
class Acl extends Base
{

/** Old version */
var $_acl;

function Acl($gacl, $macl, $aacl)
{
  $this->_acl=array();
  $this->_acl[ACL_GROUP]=$gacl;
  $this->_acl[ACL_MEMBER]=$macl;
  $this->_acl[ACL_ANY]=$aacl;
}

/* Permit a new flag. The ACL flag influence lower levels. E.g. if a member is
 * allowed to access some data, the group member is also allowed.
  @param level Level of flag. 0 for group, 1 for member, 2 for all
  @param flag ACL flag */
function _add_acl($level, $flag, $mask)
{
  switch ($level) {
  case ACL_GROUP:
    $this->_acl[ACL_GROUP]=($this->_acl[ACL_GROUP] & (~$mask)) | $flag;
    break;
  case ACL_MEMBER:
    $this->_acl[ACL_GROUP]=($this->_acl[ACL_GROUP] & (~$mask)) | $flag;
    $this->_acl[ACL_MEMBER]=($this->_acl[ACL_MEMBER] & (~$mask)) | $flag;
    break;
  case ACL_ANY:
    $this->_acl[ACL_GROUP]=($this->_acl[ACL_GROUP] & (~$mask)) | $flag;
    $this->_acl[ACL_MEMBER]=($this->_acl[ACL_MEMBER] & (~$mask)) | $flag;
    $this->_acl[ACL_ANY]=($this->_acl[ACL_ANY] & ~$mask) | $flag;
    break;
  default:
  }
}

/* Deny a resource. The ACL flag influence higher levels. E.g. if a member is
 * denied to access some data, a non-member is also denied.
  @param level Level of flag. 0 for group, 1 for member, 2 for all
  @param mask Mask to deny higher data. */
function _del_acl($level, $mask)
{
  switch ($level) {
  case ACL_GROUP:
    $this->_acl[ACL_GROUP]&=~$mask;
    $this->_acl[ACL_MEMBER]&=~$mask;
    $this->_acl[ACL_ANY]&=~$mask;
    break;
  case ACL_MEMBER:
    $this->_acl[ACL_MEMBER]&=~$mask;
    $this->_acl[ACL_ANY]&=~$mask;
    break;
  case ACL_ANY:
    $this->_acl[ACL_ANY]&=~$mask;
    break;
  default:
  }
}

/** 
  @param op Operant. Possible values are strings of 'add', 'del', 'keep' or
  null. If op is null, the operant is handled as 'del' and will remove the ACL.
  The operand 'keep' changes nothing.
  @param flag Permit bit of the current ACL
  @param mask Deny mask of current ACL */
function _set_acl($op, $level, $mask)
{
  if ($op=='preview')
    $this->_add_acl($level, ACL_PREVIEW, $mask);
  else if ($op=='edit')
    $this->_add_acl($level, ACL_EDIT, $mask);
  else if ($op=='deny' || $op==null)
    $this->_del_acl($level, $mask);
}

function handle_request($prefix='')
{
  $this->_set_acl($_REQUEST[$prefix.'aacl_write'], ACL_ANY, ACL_WRITE_MASK);
  $this->_set_acl($_REQUEST[$prefix.'macl_write'], ACL_MEMBER, ACL_WRITE_MASK);
  $this->_set_acl($_REQUEST[$prefix.'gacl_write'], ACL_GROUP, ACL_WRITE_MASK);
    
  $this->_set_acl($_REQUEST[$prefix.'aacl_read'], ACL_ANY, ACL_READ_MASK);
  $this->_set_acl($_REQUEST[$prefix.'macl_read'], ACL_MEMBER, ACL_READ_MASK);
  $this->_set_acl($_REQUEST[$prefix.'gacl_read'], ACL_GROUP, ACL_READ_MASK);
}

function get_values()
{
  return array(
    $this->_acl[ACL_GROUP] & 0xff, 
    $this->_acl[ACL_MEMBER] & 0xff, 
    $this->_acl[ACL_ANY]   & 0xff);
}

function get_gacl()
{
  return $this->_acl[ACL_GROUP] & 0xff;
}

function get_macl()
{
  return $this->_acl[ACL_MEMBER] & 0xff;
}

function get_aacl()
{
  return $this->_acl[ACL_ANY] & 0xff;
}

}
?>
