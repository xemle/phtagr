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
 * MediaFixture
 *
 */
class MediaFixture extends CakeTestFixture {
/**
 * Import
 *
 * @var array
 */
	public $import = array('model' => 'Media');


/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 1,
			'created' => '2012-02-12 23:24:27',
			'modified' => '2012-02-12 23:24:27',
			'user_id' => 1,
			'group_id' => 1,
			'flag' => 1,
			'type' => 1,
			'gacl' => 1,
			'uacl' => 1,
			'oacl' => 1,
			'date' => '2012-02-12 23:24:27',
			'width' => 1,
			'height' => 1,
			'name' => 'Lorem ipsum dolor sit amet',
			'orientation' => 1,
			'aperture' => 1,
			'shutter' => 1,
			'iso' => 1,
			'model' => 'Lorem ipsum dolor sit amet',
			'duration' => 1,
			'latitude' => 1,
			'longitude' => 1,
			'caption' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'clicks' => 1,
			'lastview' => '2012-02-12 23:24:27',
			'ranking' => 1,
			'voting' => 1,
			'votes' => 1
		),
	);
}
