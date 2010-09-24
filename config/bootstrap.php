<?php
/**
 * This file is loaded automatically by the app/webroot/index.php file after the core bootstrap.php
 *
 * This is an application wide file to load any function that is not used within a class
 * define. You can also use this to include or require any files in your application.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app.config
 * @since         CakePHP(tm) v 0.10.8.2117
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * The settings below can be used to set additional paths to models, views and controllers.
 * This is related to Ticket #470 (https://trac.cakephp.org/ticket/470)
 *
 * App::build(array(
 *     'plugins' => array('/full/path/to/plugins/', '/next/full/path/to/plugins/'),
 *     'models' =>  array('/full/path/to/models/', '/next/full/path/to/models/'),
 *     'views' => array('/full/path/to/views/', '/next/full/path/to/views/'),
 *     'controllers' => array('/full/path/to/controllers/', '/next/full/path/to/controllers/'),
 *     'datasources' => array('/full/path/to/datasources/', '/next/full/path/to/datasources/'),
 *     'behaviors' => array('/full/path/to/behaviors/', '/next/full/path/to/behaviors/'),
 *     'components' => array('/full/path/to/components/', '/next/full/path/to/components/'),
 *     'helpers' => array('/full/path/to/helpers/', '/next/full/path/to/helpers/'),
 *     'vendors' => array('/full/path/to/vendors/', '/next/full/path/to/vendors/'),
 *     'shells' => array('/full/path/to/shells/', '/next/full/path/to/shells/'),
 *     'locales' => array('/full/path/to/locale/', '/next/full/path/to/locale/')
 * ));
 *
 */

/**
 * As of 1.3, additional rules for the inflector are added below
 *
 * Inflector::rules('singular', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 * Inflector::rules('plural', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 *
 */
Inflector::rules('plural', array('uninflected' => array('[Bb]rowser')));

define('ROLE_NOBODY', 0);
define('ROLE_GUEST', 1);
define('ROLE_USER', 2);
define('ROLE_SYSOP', 3);
define('ROLE_ADMIN', 4);

define('OUTPUT_TYPE_MINI', 1);
define('OUTPUT_TYPE_THUMB', 2);
define('OUTPUT_TYPE_PREVIEW', 3);
define('OUTPUT_TYPE_HIGH', 4);
define('OUTPUT_TYPE_VIDEO', 5);
/** Quality between 0 (worsest) and 100 (best) */
define('OUTPUT_QUALITY', 75);
/** Dimension size of output */
define('OUTPUT_SIZE_MINI', 75);
define('OUTPUT_SIZE_THUMB', 220);
define('OUTPUT_SIZE_PREVIEW', 600);
define('OUTPUT_SIZE_HIGH', 1280);
define('OUTPUT_SIZE_HD', 1600);
define('OUTPUT_SIZE_VIDEO', 480);
// ffmpeg option fo '-b'. Size could be suffixed by k or m
define('OUTPUT_BITRATE_VIDEO', '786k');

// ACL constants
// Reading bits are the three highest bits
define("ACL_READ_MASK", 0xe0);
define("ACL_READ_ORIGINAL", 0x60);
define("ACL_READ_HIGH", 0x40);
define("ACL_READ_PREVIEW", 0x20);

define("ACL_WRITE_MASK", 0x07);
define("ACL_WRITE_CAPTION", 0x03);
define("ACL_WRITE_META", 0x02);
define("ACL_WRITE_TAG", 0x01);

define("ACL_LEVEL_UNKNOWN",-1);
define("ACL_LEVEL_KEEP",    0);
define("ACL_LEVEL_PRIVATE", 1);
define("ACL_LEVEL_GROUP",   2);
define("ACL_LEVEL_USER",  3);
define("ACL_LEVEL_OTHER",  4);

define("COMMENT_AUTH_NONE",     0);
define("COMMENT_AUTH_NAME",     1);
define("COMMENT_AUTH_CAPTCHA",  2);

define("MEDIA_FLAG_ACTIVE",   1);
define("MEDIA_FLAG_DIRTY",    4);

define("MEDIA_TYPE_IMAGE", 1);
define("MEDIA_TYPE_VIDEO", 2);
define("MEDIA_TYPE_IMAGE_WITH_SOUND", 3);

define("FILE_FLAG_DIRECTORY", 1);
define("FILE_FLAG_EXTERNAL",  2);
define("FILE_FLAG_DEPENDENT", 4);
define("FILE_FLAG_READ",      8);

define("FILE_TYPE_UNKNOWN",     0);
define("FILE_TYPE_DIRECTORY",   1);
define("FILE_TYPE_TEXT",        2);
define("FILE_TYPE_IMAGE",       3);
define("FILE_TYPE_SOUND",       4);
define("FILE_TYPE_VIDEO",       5);
define("FILE_TYPE_VIDEOTHUMB",  6);
define("FILE_TYPE_GPS",         7);

define("LOCATION_ANY", 0x00);
define("LOCATION_CITY", 0x01);
define("LOCATION_SUBLOCATION", 0x02);
define("LOCATION_STATE", 0x03);
define("LOCATION_COUNTRY", 0x04);

//EOF
?>
