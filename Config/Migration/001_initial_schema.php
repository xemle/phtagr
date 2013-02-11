<?php
class M4d1f861d09384b5d91c01828cbdd56cb extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = 'Initial schema';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'create_table' => array(
				'categories' => array(
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'name' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 64, 'key' => 'index', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'name_index' => array('column' => 'name', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'categories_media' => array(
					'media_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'category_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'indexes' => array(
						'PRIMARY' => array('column' => array('media_id', 'category_id'), 'unique' => 1),
						'category_id_index' => array('column' => 'category_id', 'unique' => 0),
						'media_id_index' => array('column' => 'media_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'comments' => array(
					'media_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
					'name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 32, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'email' => array('type' => 'string', 'null' => false, 'length' => 64, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'date' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'user_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'reply' => array('type' => 'integer', 'null' => true, 'default' => '0'),
					'notify' => array('type' => 'integer', 'null' => true, 'default' => '0', 'length' => 3),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'url' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 254, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'text' => array('type' => 'text', 'null' => false, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'files' => array(
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'user_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'key' => 'index'),
					'media_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'key' => 'index'),
					'flag' => array('type' => 'integer', 'null' => true, 'default' => '0', 'length' => 3),
					'type' => array('type' => 'integer', 'null' => true, 'default' => '0', 'length' => 3),
					'path' => array('type' => 'text', 'null' => false, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'file' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 254, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'size' => array('type' => 'integer', 'null' => false, 'default' => NULL),
					'time' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
					'readed' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'user_id_index' => array('column' => 'user_id', 'unique' => 0),
						'media_id_index' => array('column' => 'media_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'groups' => array(
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'user_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
					'name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 32, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'description' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 512, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'is_moderated' => array('type' => 'integer', 'null' => false, 'default' => '1', 'length' => 3),
					'is_hidden' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 3),
					'is_shared' => array('type' => 'integer', 'null' => false, 'default' => '1', 'length' => 3),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'groups_users' => array(
					'user_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'group_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'indexes' => array(
						'PRIMARY' => array('column' => array('user_id', 'group_id'), 'unique' => 1),
						'user_id_index' => array('column' => 'user_id', 'unique' => 0),
						'group_id_index' => array('column' => 'group_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'locations' => array(
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 64, 'key' => 'index', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'type' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 3),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'name_index' => array('column' => 'name', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'locations_media' => array(
					'media_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'location_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'indexes' => array(
						'PRIMARY' => array('column' => array('media_id', 'location_id'), 'unique' => 1),
						'location_id_index' => array('column' => 'location_id', 'unique' => 0),
						'media_id_index' => array('column' => 'media_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'locks' => array(
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'token' => array('type' => 'string', 'null' => false, 'key' => 'index', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'expires' => array('type' => 'datetime', 'null' => true, 'default' => NULL, 'key' => 'index'),
					'owner' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 200, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'recursive' => array('type' => 'integer', 'null' => true, 'default' => '0'),
					'writelock' => array('type' => 'integer', 'null' => true, 'default' => '0'),
					'exclusivelock' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'file_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'token_index' => array('column' => 'token', 'unique' => 0),
						'expires_index' => array('column' => 'expires', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'media' => array(
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'user_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
					'group_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'flag' => array('type' => 'integer', 'null' => true, 'default' => '0', 'length' => 3),
					'gacl' => array('type' => 'integer', 'null' => true, 'default' => '0', 'length' => 3),
					'uacl' => array('type' => 'integer', 'null' => true, 'default' => '0', 'length' => 3),
					'oacl' => array('type' => 'integer', 'null' => true, 'default' => '0', 'length' => 3, 'key' => 'index'),
					'name' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 128, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'date' => array('type' => 'datetime', 'null' => true, 'default' => NULL, 'key' => 'index'),
					'width' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 10),
					'height' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 10),
					'orientation' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 4),
					'caption' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'clicks' => array('type' => 'integer', 'null' => true, 'default' => '0'),
					'lastview' => array('type' => 'datetime', 'null' => false, 'default' => '2006-01-08 11:00:00'),
					'ranking' => array('type' => 'float', 'null' => true, 'default' => '0', 'key' => 'index'),
					'voting' => array('type' => 'float', 'null' => true, 'default' => '0'),
					'votes' => array('type' => 'integer', 'null' => true, 'default' => '0'),
					'latitude' => array('type' => 'float', 'null' => true, 'default' => NULL),
					'longitude' => array('type' => 'float', 'null' => true, 'default' => NULL),
					'duration' => array('type' => 'integer', 'null' => true, 'default' => '-1'),
					'aperture' => array('type' => 'float', 'null' => true, 'default' => NULL),
					'shutter' => array('type' => 'float', 'null' => true, 'default' => NULL),
					'model' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 128, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'type' => array('type' => 'integer', 'null' => true, 'default' => '0', 'length' => 3),
					'iso' => array('type' => 'integer', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'date_index' => array('column' => 'date', 'unique' => 0),
						'ranking_index' => array('column' => 'ranking', 'unique' => 0),
						'oacl_index' => array('column' => 'oacl', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'media_tags' => array(
					'media_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'tag_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'indexes' => array(
						'PRIMARY' => array('column' => array('media_id', 'tag_id'), 'unique' => 1),
						'media_id_index' => array('column' => 'media_id', 'unique' => 0),
						'tag_id_index' => array('column' => 'tag_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'options' => array(
					'user_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'key' => 'index'),
					'name' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 64, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'value' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 192, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'user_id_index' => array('column' => 'user_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'properties' => array(
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'name' => array('type' => 'string', 'null' => false, 'length' => 120, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'ns' => array('type' => 'string', 'null' => false, 'default' => 'DAV:', 'length' => 120, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'value' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'file_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'tags' => array(
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'name' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 64, 'key' => 'index', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'name_index' => array('column' => 'name', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'users' => array(
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'username' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 32, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'password' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 60, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'firstname' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 32, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'lastname' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 32, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'email' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 64, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'quota' => array('type' => 'float', 'null' => true, 'default' => '0'),
					'expires' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'role' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 3),
					'creator_id' => array('type' => 'integer', 'null' => true, 'default' => '0', 'length' => 10),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'key' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 64, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'visible_level' => array('type' => 'integer', 'null' => false, 'default' => '3', 'length' => 3),
					'last_login' => array('type' => 'datetime', 'null' => false, 'default' => '2010-09-04 09:27:34'),
					'notify_interval' => array('type' => 'integer', 'null' => false, 'default' => '86400'),
					'last_notify' => array('type' => 'datetime', 'null' => false, 'default' => '2010-09-04 09:27:34'),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
			),
		),
		'down' => array(
			'drop_table' => array(
				'categories', 'categories_media', 'comments', 'files', 'groups', 'groups_users', 'locations', 'locations_media', 'locks', 'media', 'media_tags', 'options', 'properties', 'tags', 'users'
			),
		),
	);

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function after($direction) {
		return true;
	}
}
?>
