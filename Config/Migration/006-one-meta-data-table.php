<?php
class Mffcaea2061011ff9181c79c14fa83622 extends CakeMigration {

  private $Media = null;
  private $Field = null;

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = 'Merge Tag, Category, and Location table to one meta data table fields';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'create_table' => array(
				'fields' => array(
					'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
					'name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 64, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'data' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 255, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'name_index' => array('column' => 'name', 'unique' => 0),
						'value_index' => array('column' => 'data', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM'),
				),
				'fields_media' => array(
					'field_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'media_id' => array('type' => 'integer', 'null' => false, 'default' => '0'),
					'indexes' => array(
						'field_id_index' => array('column' => 'field_id', 'unique' => 0),
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
   * Migrate model data to new fields trable
   *
   * @param string $name Model name
   */
  private function migrateModel($name) {
    $prefix = $this->Field->tablePrefix;
    $tableName = Inflector::tableize($name);
    $habtmTables = array($tableName, 'media');
    sort($habtmTables);
    $habtmTableName = join("_", $habtmTables);
    $habtmForeignKey = Inflector::underscore($name) . "_id";
    $habtmNames = array(Inflector::pluralize($name), 'Media');
    sort($habtmNames);
    $habtmAlias = join('', $habtmNames);

    $page = 1;
    $limit = 20;
    $query = "select * from $prefix$tableName $name order by id limit %d offset %d";
    $mediaQuery = "select * from $prefix$habtmTableName $habtmAlias where $habtmForeignKey = %d";

    $db = $this->Field->getDataSource();
    $all = $db->fetchAll(sprintf($query, $limit, ($page - 1) * $limit), array());

    $modelCount = 0;
    $mediaCount = 0;
    while (count($all)) {
      foreach ($all as $data) {
        $fieldName = $this->getFieldName($data);
        $field = $this->Field->create(array('name' => $fieldName, 'data' => $data[$name]['name']));
        $field = $this->Field->save($field);
        $modelCount++;

        $media = $db->fetchAll(sprintf($mediaQuery, $data[$name]['id']));
        $mediaIds = Set::extract("/$habtmAlias/media_id", $media);
        if (!count($mediaIds)) {
          continue;
        }
        $tmp = array('Field' => array('id' => $field['Field']['id']), 'Media' => array('Media' => $mediaIds));
        $this->Field->save($tmp);
        $mediaCount += count($mediaIds);
      }
      $page++;
      $all = $db->fetchAll(sprintf($query, $limit, ($page - 1) * $limit), array());
    }
    $names = Inflector::pluralize($name);
    Logger::info("Migrated $modelCount $names to fields with $mediaCount media");
  }

  /**
   * Get the field name of the model data
   *
   * @param array $data Model data
   * @return string Fieldname
   */
  private function getFieldName($data) {
    if (!empty($data['Tag'])) {
      return 'keyword';
    } else if (!empty($data['Category'])) {
      return 'category';
    } else if (!empty($data['Location'])) {
      $type = $data['Location']['type'];
      $fieldName = '';
      if ($type == LOCATION_SUBLOCATION) {
        $fieldName = 'sublocation';
      } else if ($type == LOCATION_CITY) {
        $fieldName = 'city';
      } else if ($type == LOCATION_STATE) {
        $fieldName = 'state';
      } else {
        $fieldName = 'country';
      }
      return $fieldName;
    } else {
      throw new Exception('Invalid data');
    }
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
    App::import('Model', 'Field');
    $this->Media = ClassRegistry::init('Media');
    $this->Field = ClassRegistry::init('Field');
    // bind Media to Field as HABTM
    $this->Field->bindModel(array('hasAndBelongsToMany' => array('Media' => array())), false);

    $this->migrateModel('Tag');
    $this->migrateModel('Category');
    $this->migrateModel('Location');
    return true;
	}

}