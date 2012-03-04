<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

/**
 * FileFixture
 *
 */
class FileFixture extends CakeTestFixture {
/**
 * Import
 *
 * @var array
 */
	public $import = array('model' => 'File');


/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 1,
			'created' => '2012-02-12 23:25:59',
			'modified' => '2012-02-12 23:25:59',
			'user_id' => 1,
			'media_id' => 1,
			'flag' => 1,
			'type' => 1,
			'path' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'file' => 'Lorem ipsum dolor sit amet',
			'size' => 1,
			'time' => '2012-02-12 23:25:59',
			'readed' => '2012-02-12 23:25:59'
		),
	);
}
