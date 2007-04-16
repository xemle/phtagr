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

class SectionOptions extends SectionBase
{

function SectionOptions()
{
  $this->name="options";
}

function print_options($dir)
{
  global $sql;
  echo "Here you can change options";
  echo "<br>";
  echo "<br>";
  echo "Upload directory: ".$sql->upload_dir;
}

function print_content()
{
  echo "<h2>Options</h2>\n";
  $this->print_options($this->path);
}

}

?>
