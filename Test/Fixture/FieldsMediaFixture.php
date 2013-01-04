<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

/**
 * FieldsMediaFixture
 *
 */
class FieldsMediaFixture extends CakeTestFixture {
	
	public $fields = array(
			'field_id' => array('type' => 'integer', 'null' => false),
			'media_id' => array('type' => 'integer', 'null' => false)
	);


/**
 * Records
 *
 * @var array
 */
	public $records = array();
}
