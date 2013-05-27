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
    $config = $this->createConfig($name);
    $fieldConfig = $this->createConfig('Field');

    $tableName = Inflector::tableize($name);
    $alias = $name;

    $page = 1;
    $limit = 300;
    $query = "select * from $prefix$tableName $alias order by id limit %d offset %d";

    $db = $this->Field->getDataSource();
    $all = $db->fetchAll(sprintf($query, $limit, ($page - 1) * $limit), array());

    $modelCount = 0;
    $mediaCount = 0;
    while (count($all)) {
      // Create new fields
      $oldIdToFieldId = $this->createFields($name, $all);

      // Create mappings
      $oldIds = join(', ', array_keys($oldIdToFieldId));
      $oldMappings = $db->fetchAll("select * from $prefix{$config['joinTable']} {$config['joinAlias']} where {$config['foreignKey']} in ($oldIds)");
      $newMappings = array();
      foreach ($oldMappings as $map) {
        $mediaId = $map[$config['joinAlias']][$config['associationForeignKey']];

        $oldId = $map[$config['joinAlias']][$config['foreignKey']];
        $newId = $oldIdToFieldId[$oldId];
        $newMappings[] = "({$mediaId}, {$newId})";
      }

      // Insert mappings
      $db->query("insert into {$prefix}{$fieldConfig['joinTable']} ({$fieldConfig['associationForeignKey']}, {$fieldConfig['foreignKey']}) values " . join(', ', $newMappings));
      $mediaCount += count($oldMappings);
      $modelCount += count($all);

      $page++;
      $all = $db->fetchAll(sprintf($query, $limit, ($page - 1) * $limit), array());
    }
    $names = Inflector::pluralize($name);
    CakeLog::info("Migrated $modelCount $names to fields with $mediaCount media");
  }

  /**
   * Create join config for given model
   *
   * @param string $name Model name
   * @return array Configuration
   */
  private function createConfig($name) {
    $tableName = Inflector::tableize($name);
    $habtmTables = array($tableName, 'media');
    sort($habtmTables);
    $habtmNames = array(Inflector::pluralize($name), 'Media');
    sort($habtmNames);

    return array(
        'joinTable' => join("_", $habtmTables),
        'joinAlias' => join('', $habtmNames),
        'foreignKey' => Inflector::underscore($name) . "_id",
        'associationForeignKey' => "media_id"
    );
  }

  /**
   * Create fields for given data
   *
   * @param string $name Model name (Tag, Category, or Location)
   * @param array $all current model data (e.g. of Tag)
   * @return array Old model id to new field id
   */
  private function createFields($name, &$all) {
    $oldIdToFieldId = array();
    foreach ($all as $data) {
      $fieldName = $this->getFieldName($data);
      $field = $this->Field->create(array('name' => $fieldName, 'data' => $data[$name]['name']));
      $field = $this->Field->save($field);
      $oldId = $data[$name]['id'];
      $oldIdToFieldId[$oldId] = $field['Field']['id'];
    }
    return $oldIdToFieldId;
  }

  /**
   * Get the field name of the model data
   *
   * @param array $data Model data
   * @return string Fieldname
   */
  private function getFieldName(&$data) {
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

    $this->migrateModel('Tag');
    $this->migrateModel('Category');
    $this->migrateModel('Location');
    return true;
	}

}