<?php
class Md506e1993bdc0017e4f11fd8e3210ec7 extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = 'Fix incorrect path from zip extraction';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
    'up' => array()
  );

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function before($direction) {
    if ($direction == 'up') {
      App::import('Model', 'MyFile');
      $MyFile = new MyFile();
      $files = $MyFile->find('all', array('conditions' => array('path LIKE' => '%/./')));
      CakeLog::debug("Found " . count($files) . " to migrate");
      $errors = 0;
      foreach ($files as $file) {
        $file['File']['path'] = substr($file['File']['path'], 0, strlen($file['File']['path']) - 2);
        if (!$MyFile->save($file, true, array('path'))) {
          CakeLog::error("Could not save file {$file['id']}");
          $errors++;
        }
      }
      if (count($files)) {
        CakeLog::info("Fix upload path of " . count($files) . " files ($errors errors)");
      }
    }
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
