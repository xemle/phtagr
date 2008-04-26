<?php
/* SVN FILE: $Id: bootstrap.php 4410 2007-02-02 13:31:21Z phpnut $ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.app.config
 * @since			CakePHP(tm) v 0.10.8.2117
 * @version			$Revision: 4410 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2007-02-02 14:31:21 +0100 (Fri, 02 Feb 2007) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 *
 * This file is loaded automatically by the app/webroot/index.php file after the core bootstrap.php is loaded
 * This is an application wide file to load any function that is not used within a class define.
 * You can also use this to include or require any files in your application.
 *
 */
/**
 * The settings below can be used to set additional paths to models, views and controllers.
 * This is related to Ticket #470 (https://trac.cakephp.org/ticket/470)
 *
 * $modelPaths = array('full path to models', 'second full path to models', 'etc...');
 * $viewPaths = array('this path to views', 'second full path to views', 'etc...');
 * $controllerPaths = array('this path to controllers', 'second full path to controllers', 'etc...');
 *
 */

define('ROLE_NOBODY', 0);
define('ROLE_GUEST', 1);
define('ROLE_MEMBER', 2);
define('ROLE_ADMIN', 3);

define('OUTPUT_TYPE_MINI', 1);
define('OUTPUT_TYPE_THUMB', 2);
define('OUTPUT_TYPE_PREVIEW', 3);
define('OUTPUT_TYPE_VIDEO', 4);
/** Quality between 0 (worsest) and 100 (best) */
define('OUTPUT_QUALITY', 75);
/** Dimension size of output */
define('OUTPUT_SIZE_MINI', 75);
define('OUTPUT_SIZE_THUMB', 220);
define('OUTPUT_SIZE_PREVIEW', 600);
define('OUTPUT_SIZE_VIDEO', 480);
define('OUTPUT_BITRATE_VIDEO', 350);

// ACL constants
// Reading bits are the three highest bits
define("ACL_READ_MASK", 0xe0);
define("ACL_READ_ORIGINAL", 0x60);
define("ACL_READ_HIGHSOLUTION", 0x40);
define("ACL_READ_PREVIEW", 0x20);

define("ACL_WRITE_MASK", 0x07);
define("ACL_WRITE_CAPTION", 0x03);
define("ACL_WRITE_META", 0x02);
define("ACL_WRITE_TAG", 0x01);

define("ACL_LEVEL_UNKNOWN",-1);
define("ACL_LEVEL_KEEP",    0);
define("ACL_LEVEL_PRIVATE", 1);
define("ACL_LEVEL_GROUP",   2);
define("ACL_LEVEL_MEMBER",  3);
define("ACL_LEVEL_PUBLIC",  4);

define("IMAGE_FLAG_ACTIVE",   1);
define("IMAGE_FLAG_EXTERNAL", 2);
define("IMAGE_FLAG_DIRTY",    4);

define("LOCATION_ANY", 0x00);
define("LOCATION_CITY", 0x01);
define("LOCATION_SUBLOCATION", 0x02);
define("LOCATION_STATE", 0x03);
define("LOCATION_COUNTRY", 0x04);

//EOF
?>
