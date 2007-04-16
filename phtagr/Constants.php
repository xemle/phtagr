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

define("DB_VERSION", 2);

define("USER_ADMIN",  0x01);
define("USER_MEMBER", 0x02);
define("USER_GUEST",  0x03);

// ACL constants
// Reading bits are the three highest bits
define("ACL_READ_MASK", 0xe0);
define("ACL_FULLSIZE", 0x60);
define("ACL_HIGHSOLUTION", 0x40);
define("ACL_PREVIEW", 0x20);

define("ACL_WRITE_MASK", 0x07); 
define("ACL_CAPTION", 0x03);
define("ACL_METADATA", 0x02);
define("ACL_EDIT", 0x01);

define("ACL_GROUP", 0x00);
define("ACL_MEMBER", 0x01);
define("ACL_ANY", 0x02);

define("LOCATION_UNDEFINED", 0x00);
define("LOCATION_CITY", 0x01);
define("LOCATION_SUBLOCATION", 0x02);
define("LOCATION_STATE", 0x03);
define("LOCATION_COUNTRY", 0x04);

define("VOTING_MAX", 0x05);

define("GUEST_MAX", 10);
define("GROUP_MAX", 10);
define("GROUP_MEMBER_MAX", 30);

define("ERR_DB_GENERAL",          -1);
define("ERR_DB_CONNECT",          -2);
define("ERR_DB_SELECT",           -3);
define("ERR_DB_INSERT",           -4);
define("ERR_DB_UPDATE",           -5);
define("ERR_USER_GERNERAL",       -6);
define("ERR_USER_ALREADY_EXISTS", -7);
define("ERR_USER_NAME_LEN",       -8);
define("ERR_USER_NAME_INVALID",   -9);
define("ERR_USER_PWD_LEN",       -10);
define("ERR_USER_PWD_INVALID",   -11);
define("ERR_FS_GENERAL",         -12);
define("ERR_FS_NOT_EXISTS",      -17);
define("ERR_GENERAL",            -13);
define("ERR_NOT_PERMITTED",      -14);
define("ERR_PASSWD_MISMATCH",    -15);
define("ERR_PARAM",              -16); // Functions parameters are wrong

?>
