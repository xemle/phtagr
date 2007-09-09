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

/** @class Acl 
  handles the access control list (ACL) operations. It proceeds the
  HTML requests and sets the acl levels. */
class Acl extends Base
{

/** Old version */
var $_acl;
var $_old_acl;

function Acl($gacl, $macl, $pacl)
{
  $this->_acl=array();
  $this->_acl[ACL_LEVEL_GROUP]=$gacl;
  $this->_acl[ACL_LEVEL_MEMBER]=$macl;
  $this->_acl[ACL_LEVEL_PUBLIC]=$pacl;

  $this->_old_acl=array();
  $this->_old_acl[ACL_LEVEL_GROUP]=$gacl;
  $this->_old_acl[ACL_LEVEL_MEMBER]=$macl;
  $this->_old_acl[ACL_LEVEL_PUBLIC]=$pacl;
}

/** @return Returns true, if the ACL changed */
function has_changes()
{
  if ($this->_acl[ACL_LEVEL_GROUP] != $this->_old_acl[ACL_LEVEL_GROUP] ||
    $this->_acl[ACL_LEVEL_MEMBER] != $this->_old_acl[ACL_LEVEL_MEMBER] ||
    $this->_acl[ACL_LEVEL_PUBLIC] != $this->_old_acl[ACL_LEVEL_PUBLIC])
    return true;
  return false;
}

/** Returns an array of ACL values of (group, member, any) */
function get_values()
{
  return array(
    $this->_acl[ACL_LEVEL_GROUP] & 0xff, 
    $this->_acl[ACL_LEVEL_MEMBER] & 0xff, 
    $this->_acl[ACL_LEVEL_PUBLIC] & 0xff);
}

function get_gacl()
{
  return $this->_acl[ACL_LEVEL_GROUP] & 0xff;
}

function get_macl()
{
  return $this->_acl[ACL_LEVEL_MEMBER] & 0xff;
}

function get_pacl()
{
  return $this->_acl[ACL_LEVEL_PUBLIC] & 0xff;
}

/** Converts an ACL string to ACL level integer value
  @param value ACL string
  @return Integer value of ACL level */
function _str_to_level($value)
{
  switch ($value)
  {
    case 'keep': return ACL_LEVEL_KEEP;
    case 'private': return ACL_LEVEL_PRIVATE;
    case 'group': return ACL_LEVEL_GROUP;
    case 'member': return ACL_LEVEL_MEMBER;
    case 'any': return ACL_LEVEL_PUBLIC;
    default: return ACL_UNKNOWN;
  }
}

/** Increase the ACL level. It checks the current flag and increases the ACL
 * level of lower ACL levels (first level is ACL_LEVEL_GROUP, second level is
 * ACL_LEVEL_MEMBER and the third level is ACL_LEVEL_PUBLIC).
  @param level Highes ACL level which should be increased
  @param flag Threshold flag which indicates the upper inclusive bound
  @param mask Bit mask of flag */
function _increase_acl($level, $flag, $mask)
{
  if ($level>ACL_LEVEL_PUBLIC)
    return;

  for ($l=ACL_LEVEL_GROUP ; $l<=$level; $l++)
  {
    if (($this->_acl[$l]&($mask))<$flag) 
      $this->_acl[$l]=($this->_acl[$l]&(~$mask))|$flag;
  }
}

/** Decrease the ACL level. Decreases the ACL level of higher ACL levels
 * according to the current flag (first level is ACL_LEVEL_GROUP, second level
 * is ACL_LEVEL_MEMBER and the third level is ACL_LEVEL_PUBLIC). The decreased ACL
 * value is the ACL value of the higher levels which is less than the current
 * threshold or it is zero if no lower ACL value is available. 
  @param level Lower ACL level which should be downgraded
  @param flag Threshold flag which indicates the upper exlusive bound
  @param mask Bit mask of flag */
function _decrease_acl($level, $flag, $mask)
{
  if ($level<ACL_LEVEL_GROUP)
    return;

  for ($l=ACL_LEVEL_PUBLIC ; $l>=$level; $l--)
  {
    // Evaluate the available ACL value which is lower than the threshold
    $lower=($l==ACL_LEVEL_PUBLIC)?0:($this->_acl[$l+1]&($mask));
    $lower=($lower<$flag)?$lower:0;
    if (($this->_acl[$l]&($mask))>=$flag)
      $this->_acl[$l]=($this->_acl[$l]&(~$mask))|$lower;
  }
}

/** Sets the acl levels to the current values 
  @param level Current ACL level
  @param flag ACL flag which should be set
  @param mask Bit mask of the ACL flag 
  @return True if the ACL could be set */
function _set_acl_levels($level, $flag, $mask)
{
  if ($level<ACL_LEVEL_KEEP || $level>ACL_LEVEL_PUBLIC)
    return false;

  if ($level==ACL_LEVEL_KEEP)
    return true;

  if ($level>=ACL_LEVEL_GROUP)
    $this->_increase_acl($level, $flag, $mask);

  if ($level<ACL_LEVEL_PUBLIC)
    $this->_decrease_acl($level+1, $flag, $mask);

  return true;
}

function handle_request($prefix='')
{
  // read level
  if (isset($_REQUEST[$prefix.'acl_original']))
  {
    $level=$this->_str_to_level($_REQUEST[$prefix.'acl_original']);
    $this->_set_acl_levels($level, ACL_READ_ORIGINAL, ACL_READ_MASK);
  }  
  if (isset($_REQUEST[$prefix.'acl_preview']))
  {
    $level=$this->_str_to_level($_REQUEST[$prefix.'acl_preview']);
    $this->_set_acl_levels($level, ACL_READ_PREVIEW, ACL_READ_MASK);
  }  

  // write level
  if (isset($_REQUEST[$prefix.'acl_meta']))
  {
    $level=$this->_str_to_level($_REQUEST[$prefix.'acl_meta']);
    $this->_set_acl_levels($level, ACL_WRITE_META, ACL_WRITE_MASK);
  }  
  if (isset($_REQUEST[$prefix.'acl_tag']))
  {
    $level=$this->_str_to_level($_REQUEST[$prefix.'acl_tag']);
    $this->_set_acl_levels($level, ACL_WRITE_TAG, ACL_WRITE_MASK);
  }  
}

}
?>
