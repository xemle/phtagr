<?php 
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006-2008 Sebastian Felis, sebastian@phtagr.org
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
/* SVN FILE: $Id$ */
/* Phtagr schema generated on: 2008-03-25 10:03:23 : 1206438263*/
class PhtagrSchema extends CakeSchema {
	var $name = 'Phtagr';

	function before($event = array()) {
		return true;
	}

	function after($event = array()) {
	}

	var $categories = array(
			'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
			'name' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 64, 'key' => 'index'),
			'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'name' => array('column' => 'name', 'unique' => 0))
		);
	var $categories_images = array(
			'image_id' => array('type'=>'integer', 'null' => false, 'default' => '0'),
			'category_id' => array('type'=>'integer', 'null' => false, 'default' => '0'),
			'indexes' => array('PRIMARY' => array('column' => array('image_id', 'category_id'), 'unique' => 1), 'setid' => array('column' => 'category_id', 'unique' => 0))
		);
	var $comments = array(
			'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
			'created' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'modified' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'image_id' => array('type'=>'integer', 'null' => true, 'default' => NULL),
			'user_id' => array('type'=>'integer', 'null' => true, 'default' => NULL),
			'name' => array('type'=>'string', 'null' => false, 'length' => 32),
			'email' => array('type'=>'string', 'null' => false, 'length' => 64),
			'url' => array('type'=>'string', 'null' => true, 'length' => 254),
			'date' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'text' => array('type'=>'text', 'null' => false),
			'reply' => array('type'=>'integer', 'null' => true, 'default' => '0'),
			'notify' => array('type'=>'integer', 'null' => true, 'default' => '0', 'length' => 3),
			'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
		);
	var $groups = array(
			'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
			'user_id' => array('type'=>'integer', 'null' => true, 'default' => NULL),
			'name' => array('type'=>'string', 'null' => false, 'length' => 32),
			'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'id' => array('column' => 'id', 'unique' => 0))
		);
	var $groups_users = array(
			'user_id' => array('type'=>'integer', 'null' => false, 'default' => '0'),
			'group_id' => array('type'=>'integer', 'null' => false, 'default' => '0'),
			'indexes' => array('PRIMARY' => array('column' => array('user_id', 'group_id'), 'unique' => 1), 'userid' => array('column' => 'user_id', 'unique' => 0), 'groupid' => array('column' => 'group_id', 'unique' => 0))
		);
	var $images = array(
			'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
			'created' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'modified' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'user_id' => array('type'=>'integer', 'null' => true, 'default' => NULL),
			'group_id' => array('type'=>'integer', 'null' => true, 'default' => NULL),
			'flag' => array('type'=>'integer', 'null' => true, 'default' => '0', 'length' => 3),
			'path' => array('type'=>'text', 'null' => false),
			'file' => array('type'=>'string', 'null' => false, 'length' => 254), /* should be 255. Bug: Schema::read() removes default lengths, while Schema::compare() doesn't which causes unnecessary changes */
			'bytes' => array('type'=>'integer', 'null' => false),
			'filetime' => array('type'=>'datetime', 'null' => false),
			'gacl' => array('type'=>'integer', 'null' => true, 'default' => '0', 'length' => 3),
			'uacl' => array('type'=>'integer', 'null' => true, 'default' => '0', 'length' => 3),
			'oacl' => array('type'=>'integer', 'null' => true, 'default' => '0', 'length' => 3, 'key' => 'index'),
			'date' => array('type'=>'datetime', 'null' => true, 'default' => NULL, 'key' => 'index'),
			'width' => array('type'=>'integer', 'null' => true, 'default' => NULL, 'length' => 10),
			'height' => array('type'=>'integer', 'null' => true, 'default' => NULL, 'length' => 10),
			'name' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 128),
			'orientation' => array('type'=>'integer', 'null' => true, 'default' => NULL, 'length' => 4),
			'aperture' => array('type'=>'float', 'null' => true, 'default' => NULL),
			'shutter' => array('type'=>'float', 'null' => true, 'default' => NULL),
			'model' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 128),
			'duration' => array('type'=>'integer', 'null' => true, 'default' => '-1'),
			'latitude' => array('type'=>'float', 'null' => true, 'default' => NULL),
			'longitude' => array('type'=>'float', 'null' => true, 'default' => NULL),
			'caption' => array('type'=>'text', 'null' => true, 'default' => NULL),
			'clicks' => array('type'=>'integer', 'null' => true, 'default' => '0'),
			'lastview' => array('type'=>'datetime', 'null' => false, 'default' => '2006-01-08 11:00:00'),
			'ranking' => array('type'=>'float', 'null' => true, 'default' => '0', 'key' => 'index'),
			'voting' => array('type'=>'float', 'null' => true, 'default' => '0'),
			'votes' => array('type'=>'integer', 'null' => true, 'default' => '0'),
			'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'date' => array('column' => 'date', 'unique' => 0), 'ranking' => array('column' => 'ranking', 'unique' => 0), 'id' => array('column' => 'id', 'unique' => 0), 'oacl' => array('column' => 'oacl', 'unique' => 0))
		);
	var $images_locations = array(
			'image_id' => array('type'=>'integer', 'null' => false, 'default' => '0'),
			'location_id' => array('type'=>'integer', 'null' => false, 'default' => '0'),
			'indexes' => array('PRIMARY' => array('column' => array('image_id', 'location_id'), 'unique' => 1), 'locationid' => array('column' => 'location_id', 'unique' => 0))
		);
	var $images_tags = array(
			'image_id' => array('type'=>'integer', 'null' => false, 'default' => '0'),
			'tag_id' => array('type'=>'integer', 'null' => false, 'default' => '0'),
			'indexes' => array('PRIMARY' => array('column' => array('image_id', 'tag_id'), 'unique' => 1), 'imageid' => array('column' => 'image_id', 'unique' => 0), 'tagid' => array('column' => 'tag_id', 'unique' => 0))
		);
	var $locks = array(
			'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
			'image_id' => array('type'=>'integer', 'null' => true, 'default' => NULL),
			'token' => array('type'=>'string', 'null' => false, 'key' => 'index'),
			'expires' => array('type'=>'datetime', 'null' => true, 'default' => NULL, 'key' => 'index'),
			'owner' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 200),
			'recursive' => array('type'=>'integer', 'null' => true, 'default' => '0'),
			'writelock' => array('type'=>'integer', 'null' => true, 'default' => '0'),
			'exclusivelock' => array('type'=>'integer', 'null' => false, 'default' => '0'),
			'created' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'modified' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'token' => array('column' => 'token', 'unique' => 0), 'expires' => array('column' => 'expires', 'unique' => 0))
		);
	var $locations = array(
			'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
			'name' => array('type'=>'string', 'null' => false, 'length' => 64, 'key' => 'index'),
			'type' => array('type'=>'integer', 'null' => true, 'default' => NULL, 'length' => 3),
			'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'name' => array('column' => 'name', 'unique' => 0))
		);
	var $options = array(
			'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
			'user_id' => array('type'=>'integer', 'null' => true, 'default' => NULL),
			'name' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 64),
			'value' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 192),
			'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'user_id' => array('column' => 'user_id', 'unique' => 0))
		);
	var $properties = array(
			'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
			'image_id' => array('type'=>'integer', 'null' => true, 'default' => NULL),
			'ns' => array('type'=>'string', 'null' => false, 'default' => 'DAV:', 'length' => 120),
			'name' => array('type'=>'string', 'null' => false, 'length' => 120),
			'value' => array('type'=>'text', 'null' => true, 'default' => NULL),
			'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
		);
	var $tags = array(
			'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
			'name' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 64, 'key' => 'index'),
			'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'name' => array('column' => 'name', 'unique' => 0))
		);
	var $users = array(
			'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
			'created' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'modified' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'username' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 32),
			'password' => array('type'=>'string', 'null' => false, 'length' => 60),
			'role' => array('type'=>'integer', 'null' => true, 'default' => NULL, 'length' => 3),
			'creator_id' => array('type'=>'integer', 'null' => true, 'default' => '0', 'length' => 10),
			'expires' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'key' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 64),
			'quota' => array('type'=>'float', 'null' => true, 'default' => 0.0),
			'firstname' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 32),
			'lastname' => array('type'=>'string', 'null' => false, 'length' => 32),
			'email' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 64),
			'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'id' => array('column' => 'id', 'unique' => 0))
		);
}
?>
