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
 * Email configuration class.
 *
 * transport => The name of a supported transport; valid options are as follows:
 *		Mail 		- Send using PHP mail function
 *		Smtp		- Send using SMTP
 *		Debug		- Do not send the email, just return the result
 *
 */
class EmailConfig {

  /** Email delivery configuration. See email.php.default for more details */
	public $default = array(
    /* Standard mail transport using PHP mail function */
		'transport' => 'Mail',
    /* Smtp mail transport */
		// 'transport' => 'Smtp',
		// 'host' => 'localhost',
		// 'port' => 25,
		// 'username' => 'user',
		// 'password' => 'secret',
		'from' => array('no-reply@phtagr.org' => 'phTagr Gallery'),
    // 'replyTo' => array('no-reply@phtagr.org' => 'No Reply phTagr.org'),
    // 'bcc' => null,
		//'charset' => 'utf-8',
		//'headerCharset' => 'utf-8',
	);

}
