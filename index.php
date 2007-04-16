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

// This file includes the real content over $phtagr_prefix/main.php

if (file_exists ('config.php'))
  include 'config.php';

$cwd=getcwd();

if (!isset($phtagr_prefix))
  $phtagr_prefix='.';

$phtagr_lib=$phtagr_prefix.DIRECTORY_SEPARATOR.'phtagr';

if (!isset($phtagr_data))
  $phtagr_data=$cwd.DIRECTORY_SEPARATOR."data";

if (!isset($phtagr_htdocs))
{
  $phtagr_htdocs=dirname($_SERVER['PHP_SELF']);
  $len=strlen($phtagr_htdocs);
  if ($phtagr_htdocs{$len-1}=='/')
  {
    $phtagr_htdocs=substr($phtagr_htdocs, 0, $len-1);
  }
}

include "$phtagr_lib/main.php";
?>
