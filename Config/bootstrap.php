<?php
/**
 * This file is loaded automatically by the app/webroot/index.php file after core.php
 *
 * This file should load/create any application wide configuration settings, such as
 * Caching, Logging, loading additional configuration files.
 *
 * You should also use this file to include any files that provide global functions/constants
 * that your application uses.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.10.8.2117
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

// Setup a 'default' cache configuration for use in the application.
Cache::config('default', array('engine' => 'File'));

/**
 * The settings below can be used to set additional paths to models, views and controllers.
 *
 * App::build(array(
 *     'Model'                     => array('/path/to/models/', '/next/path/to/models/'),
 *     'Model/Behavior'            => array('/path/to/behaviors/', '/next/path/to/behaviors/'),
 *     'Model/Datasource'          => array('/path/to/datasources/', '/next/path/to/datasources/'),
 *     'Model/Datasource/Database' => array('/path/to/databases/', '/next/path/to/database/'),
 *     'Model/Datasource/Session'  => array('/path/to/sessions/', '/next/path/to/sessions/'),
 *     'Controller'                => array('/path/to/controllers/', '/next/path/to/controllers/'),
 *     'Controller/Component'      => array('/path/to/components/', '/next/path/to/components/'),
 *     'Controller/Component/Auth' => array('/path/to/auths/', '/next/path/to/auths/'),
 *     'Controller/Component/Acl'  => array('/path/to/acls/', '/next/path/to/acls/'),
 *     'View'                      => array('/path/to/views/', '/next/path/to/views/'),
 *     'View/Helper'               => array('/path/to/helpers/', '/next/path/to/helpers/'),
 *     'Console'                   => array('/path/to/consoles/', '/next/path/to/consoles/'),
 *     'Console/Command'           => array('/path/to/commands/', '/next/path/to/commands/'),
 *     'Console/Command/Task'      => array('/path/to/tasks/', '/next/path/to/tasks/'),
 *     'Lib'                       => array('/path/to/libs/', '/next/path/to/libs/'),
 *     'Locale'                    => array('/path/to/locales/', '/next/path/to/locales/'),
 *     'Vendor'                    => array('/path/to/vendors/', '/next/path/to/vendors/'),
 *     'Plugin'                    => array('/path/to/plugins/', '/next/path/to/plugins/'),
 * ));
 *
 */

/**
 * Custom Inflector rules, can be set to correctly pluralize or singularize table, model, controller names or whatever other
 * string is passed to the inflection functions
 *
 * Inflector::rules('singular', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 * Inflector::rules('plural', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 *
 */
Inflector::rules('plural', array('uninflected' => array('[Bb]rowser')));
Inflector::rules('singular', array('uninflected' => array('pos')));

/**
 * Plugins need to be loaded manually, you can either load them one by one or all of them in a single call
 * Uncomment one of the lines below, as you need. make sure you read the documentation on CakePlugin to use more
 * advanced ways of loading plugins
 *
 * CakePlugin::loadAll(); // Loads all plugins at once
 * CakePlugin::load('DebugKit'); //Loads a single plugin named DebugKit
 *
 */

/**
 * You can attach event listeners to the request lifecycle as Dispatcher Filter . By Default CakePHP bundles two filters:
 *
 * - AssetDispatcher filter will serve your asset files (css, images, js, etc) from your themes and plugins
 * - CacheDispatcher filter will read the Cache.check configure variable and try to serve cached content generated from controllers
 *
 * Feel free to remove or add filters as you see fit for your application. A few examples:
 *
 * Configure::write('Dispatcher.filters', array(
 *		'MyCacheFilter', //  will use MyCacheFilter class from the Routing/Filter package in your app.
 *		'MyPlugin.MyFilter', // will use MyFilter class from the Routing/Filter package in MyPlugin plugin.
 * 		array('callable' => $aFunction, 'on' => 'before', 'priority' => 9), // A valid PHP callback type to be called on beforeDispatch
 *		array('callable' => $anotherMethod, 'on' => 'after'), // A valid PHP callback type to be called on afterDispatch
 *
 * ));
 */
Configure::write('Dispatcher.filters', array(
	'AssetDispatcher',
	'CacheDispatcher'
));

/**
 * Configures default file logging options
 */
App::uses('CakeLog', 'Log');
CakeLog::config('debug', array(
	'engine' => 'File',
	'types' => array('notice', 'info', 'debug'),
	'file' => 'debug',
));
CakeLog::config('error', array(
	'engine' => 'File',
	'types' => array('warning', 'error', 'critical', 'alert', 'emergency'),
	'file' => 'error',
));
CakeLog::config('full', array(
	'engine' => 'File',
	'types' => array('debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'),
	'file' => 'logger',
));

/**
 * Configuration directory
 */
if (!defined('CONFIGS')) {
  define('CONFIGS', APP . 'Config' . DS);
}

define('ROLE_NOBODY', 0);
define('ROLE_GUEST',  1);
define('ROLE_USER',   2);
define('ROLE_SYSOP',  3);
define('ROLE_ADMIN',  4);

define('PROFILE_LEVEL_PRIVATE', 1);
define('PROFILE_LEVEL_GROUP',   2);
define('PROFILE_LEVEL_USER',    3);
define('PROFILE_LEVEL_PUBLIC',  4);

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
define('OUTPUT_SIZE_PREVIEW', 960);
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
define("FILE_TYPE_SIDECAR",     8);

define("LOCATION_ANY", 0x00);
define("LOCATION_CITY", 0x01);
define("LOCATION_SUBLOCATION", 0x02);
define("LOCATION_STATE", 0x03);
define("LOCATION_COUNTRY", 0x04);

define("BULK_DOWNLOAD_FILE_COUNT_ANONYMOUS", 12);
define("BULK_DOWNLOAD_FILE_COUNT_USER", 240);
define("BULK_DOWNLOAD_TOTAL_MB_LIMIT", 250);

define("EXPLORER_DEFAULT_SHOW", 12);
define("EXPLORER_DEFAULT_SORT", 'date');
define("EXPLORER_DEFAULT_VIEW", 'full');

/**
 * Add pear path within vendor path to the include_path
 */
if (function_exists('ini_set') && function_exists('ini_get')) {
  $path = ini_get('include_path');
  $vendorPearPath = APP . 'Vendor' . DS . 'Pear' . DS;
  ini_set('include_path', $vendorPearPath . PATH_SEPARATOR . ini_get('include_path'));
}

App::uses('CakeEventManager', 'Event');
App::uses('UserSessionListener', 'Event');
CakeEventManager::instance()->attach(new UserSessionListener());
