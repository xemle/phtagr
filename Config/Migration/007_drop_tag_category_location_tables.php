<?php
class M9d052303c09a794e405d658ada760960 extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = 'Drop obsolete tables for tags, categories and locations';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'drop_table' => array(
				'categories',
				'categories_media',
				'locations',
				'locations_media',
				'tags',
				'media_tags',
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
    if ($direction != 'up') {
      throw new Exception("Downmigration is not supported");
    }
    return true;
	}

}
