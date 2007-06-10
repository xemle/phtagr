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

include_once("$phtagr_lib/SectionBase.php");

class SectionAcl extends SectionBase
{

var $_acl;

function SectionAcl($gacl=0, $macl=0, $pacl=0)
{
  $this->name="acl";
  $this->_acl=array(ACL_LEVEL_GROUP => $gacl, 
                ACL_LEVEL_MEMBER => $macl, 
                ACL_LEVEL_PUBLIC => $pacl);
}

function _get_acl_level($flag, $mask)
{
  for ($l=ACL_LEVEL_PUBLIC; $l>=ACL_LEVEL_GROUP; $l--)
  {
    if (($this->_acl[$l]&($mask))>=$flag)
      return $l;
  }
  return ACL_LEVEL_PRIVATE;
}

/** 
  @param keep If true print also 'keep' option */
function print_table($keep=true)
{
  $prefix="";

  echo "<li>";
  $this->label(_("Who can edit image tags?"));
  echo "<select size=\"1\" name=\"acl_tag\">\n";
  if ($keep)
  {
    $this->option(_("Keep settings"), "keep");
    $level=ACL_LEVEL_KEEP;
  }
  else
  {
    $level=$this->_get_acl_level(ACL_WRITE_TAG, ACL_WRITE_MASK);
  }

  $this->option(_("Me only"), "private", ($level==ACL_LEVEL_PRIVATE));
  $this->option(_("Group members"), "group", ($level==ACL_LEVEL_GROUP));
  $this->option(_("All members"), "member", ($level==ACL_LEVEL_MEMBER));
  $this->option(_("Everyone"), "any", ($level==ACL_LEVEL_PUBLIC));
  echo "</select>";
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Who can edit the meta data?"));
  echo "<select size=\"1\" name=\"acl_meta\">\n";
  if ($keep)
  {
    $this->option(_("Keep settings"), "keep");
    $level=ACL_LEVEL_KEEP;
  }
  else
  {
    $level=$this->_get_acl_level(ACL_WRITE_META, ACL_WRITE_MASK);
  }

  $this->option(_("Me only"), "private", ($level==ACL_LEVEL_PRIVATE));
  $this->option(_("Group members"), "group", ($level==ACL_LEVEL_GROUP));
  $this->option(_("All members"), "member", ($level==ACL_LEVEL_MEMBER));
  $this->option(_("Everyone"), "any", ($level==ACL_LEVEL_PUBLIC));
  echo "</select>";
  echo "</li>\n";

  echo "<li>";
  $this->label(_("Who can preview the images?"));
  echo "<select size=\"1\" name=\"acl_preview\">\n";
  if ($keep)
  {
    $this->option(_("Keep settings"), "keep");
    $level=ACL_LEVEL_KEEP;
  }
  else
  {
    $level=$this->_get_acl_level(ACL_READ_PREVIEW, ACL_READ_MASK);
  }
  $this->option(_("Me only"), "private", ($level==ACL_LEVEL_PRIVATE));
  $this->option(_("Group members"), "group", ($level==ACL_LEVEL_GROUP));
  $this->option(_("All members"), "member", ($level==ACL_LEVEL_MEMBER));
  $this->option(_("Everyone"), "any", ($level==ACL_LEVEL_PUBLIC));
  echo "</select>";
  echo "</li>";

  echo "<li>";
  $this->label(_("Who can view the origial images?"));
  echo "<select size=\"1\" name=\"acl_original\">\n";
  if ($keep)
  {
    $this->option(_("Keep settings"), "keep");
    $level=ACL_LEVEL_KEEP;
  }
  else
  {
    $level=$this->_get_acl_level(ACL_READ_ORIGINAL, ACL_READ_MASK);
  }
  $this->option(_("Me only"), "private", ($level==ACL_LEVEL_PRIVATE));
  $this->option(_("Group members"), "group", ($level==ACL_LEVEL_GROUP));
  $this->option(_("All members"), "member", ($level==ACL_LEVEL_MEMBER));
  $this->option(_("Everyone"), "any", ($level==ACL_LEVEL_PUBLIC));
  echo "</select>";
  echo "</li>";
}

}
?>
