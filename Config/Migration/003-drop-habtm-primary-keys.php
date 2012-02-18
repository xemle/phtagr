<?php
class Mc15b41fcc828e7dbb7b8e3fd541d79d6 extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = 'Remove HABTM multi column primary keys';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'drop_field' => array(
				'categories_media' => array(
					'indexes' => array('PRIMARY')
					),
				'groups_users' => array(
					'indexes' => array('PRIMARY')
					),
				'locations_media' => array(
					'indexes' => array('PRIMARY')
					),
				'media_tags' => array(
					'indexes' => array('PRIMARY')
					),
			),
		),
		'down' => array(
			'add_field' => array(
				'categories_media' => array(
					'indexes' => array(
						'PRIMARY' => array('column' => array('media_id', 'category_id'), 'unique' => 1)
					)
				),
				'groups_users' => array(
					'indexes' => array(
						'PRIMARY' => array('column' => array('user_id', 'group_id'), 'unique' => 1)
					)
				),
				'locations_media' => array(
					'indexes' => array(
						'PRIMARY' => array('column' => array('media_id', 'location_id'), 'unique' => 1)
					)
				),
				'media_tags' => array(
					'indexes' => array(
						'PRIMARY' => array('column' => array('media_id', 'tag_id'), 'unique' => 1)
					)
				),
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
