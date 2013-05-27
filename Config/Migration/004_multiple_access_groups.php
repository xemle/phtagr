<?php
class M57acac5f420bd77f6d55a446199ccab6 extends CakeMigration {

  private $Media = null;
  private $Group = null;

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = 'Add multiple access groups to media';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'create_table' => array(
				'groups_media' => array(
					'media_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'group_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'indexes' => array(
						'group_id_index' => array('column' => 'group_id', 'unique' => 0),
						'media_id_index' => array('column' => 'media_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
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
    if ($direction != 'up') {
      throw new Exception("Downmigration is not supported");
    }

    App::import('Model', 'Media');
    App::import('Model', 'Group');
    $this->Media = ClassRegistry::init('Media');
    $this->Group = ClassRegistry::init('Group');
    // bind Media to Group as HABTM
    $this->Group->bindModel(array('hasAndBelongsToMany' => array('Media' => array())), false);

    $groups = $this->Group->find('all', array(
      'fields' => array('id'),
      'recursive' => -1));
    $groupCount = count($groups);
    if (!$groupCount) {
      CakeLog::info("No groups to migrate");
      return true;
    }

    CakeLog::info("Found $groupCount groups to migrate");
    $migrated = 0;
    foreach ($groups as $group) {
      $migrated += $this->migrateGroup($group);
    }
    if ($migrated) {
      CakeLog::info("Migration of $migrated media from $groupCount groups was successful");
    }
    return true;
	}

  /**
   * Migrate all media of a single group
   *
   * @param type $group
   * @return type
   */
  private function migrateGroup($group) {
    $data = $this->Media->find('all', array(
      'conditions' => array('Media.group_id' => $group['Group']['id']),
      'fields' => array('id'),
      'recursive' => -1));
    if (!count($data)) {
      return 0;
    }
    $mediaIds = Set::extract('/Media/id', $data);
    $this->Group->create();
    if (!$this->Group->save(array(
        'Group' => array('id' => $group['Group']['id']),
        'Media' => array('Media' => $mediaIds)))) {
      throw new Exception("Could not migrate media of group {$group['Group']['id']}");
    }
    return count($mediaIds);
  }

}
?>
