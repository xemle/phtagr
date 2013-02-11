<?php
/* SVN FILE: $Id: sass.php 57 2010-04-14 16:30:58Z chris.l.yates $ */
/**
 * Sass Asset filter.
 * Allows Cake to parse Sass stylesheets using {@link http://phamlp.googlecode.com PHamlP}.
 * If a .sass file is present a .css file will be used.
 * 
 * * To use the Sass parser:
 * * Put {@link http://phamlp.googlecode.com PHamlP} in your vendors directory
 * * Put this file in your webroot directory
 * * Configure the SassParser using
 *   <pre>Configure::write('Sass.<optionName>', <optionValue>);</pre>
 *   See below for a description of options
 * * Add the following line in your core.php
 *   <pre>Configure::write('Asset.filter.css', 'sass.php');</pre>
 * * Create .sass stylesheets in your <pre>webroot/css</pre> directory
 *   Note: The generated links are to the <pre>webroot/ccss</pre> directory,
 *   so URLs in your stylesheets must be relative this.
 * 
 * @author			Chris Yates <chris.l.yates@gmail.com>
 * @copyright 	Copyright (c) 2010 PBM Web Development
 * @license			http://phamlp.googlecode.com/files/license.txt
 * @package			PHamlP
 * @subpackage	Cake
 */
$sassOptions = array('style', 'property_syntax', 'cache', 'always_update', 'template_location', 'css_location', 'cache_location', 'load_paths', 'line', 'line_numbers', 'extensions');
/**
 * @var array options
 * The following options are available:
 *
 * style: string Sets the style of the CSS output. Value can be:
 * nested - Nested is the default Sass style, because it reflects the
 * structure of the document in much the same way Sass does. Each selector
 * and rule has its own line with indentation is based on how deeply the rule
 * is nested. Nested style is very useful when looking at large CSS files for
 * the same reason Sass is useful for making them: it allows you to very
 * easily grasp the structure of the file without actually reading anything.
 * expanded - Expanded is the typical human-made CSS style, with each selector
 * and property taking up one line. Selectors are not indented; properties are
 * indented within the rules.
 * compact - Each CSS rule takes up only one line, with every property defined
 * on that line. Nested rules are placed with each other while groups of rules
 * are separated by a blank line.
 * compressed - Compressed has no whitespace except that necessary to separate
 * selectors and properties. It's not meant to be human-readable.
 *
 * property_syntax: string Forces the document to use one syntax for
 * properties. If the correct syntax isn't used, an error is thrown.
 * Value can be:
 * new - forces the use of a colon or equals sign after the property name.
 * For example	 color: #0f3 or width = !main_width.
 * old -  forces the use of a colon before the property name.
 * For example: :color #0f3 or :width = !main_width.
 * By default, either syntax is valid.
 *
 * cache: boolean Whether parsed Sass files should be cached, allowing greater
 * speed. Defaults to true.
 *
 * always_update: boolean Whether the CSS files should be updated every time,
 * as opposed to only when the template has been modified. Defaults to false.
 *
 * template_location: string Path to the root sass template directory for your
 * application.
 *
 * css_location: string The path where CSS output should be written to.
 * Defaults to "./css".
 *
 * cache_location: string The path where the cached sassc files should be
 * written to. Defaults to "./sass-cache".
 *
 * load_paths: array An array of filesystem paths which should be searched for
 * Sass templates imported with the @import directive.
 * Defaults to
 * "./sass-templates".
 *
 * line: integer The number of the first line of the Sass template. Used for
 * reporting line numbers for errors. This is useful to set if the Sass
 * template is embedded.
 *
 * line_numbers: boolean When set to true, causes the line number and file
 * where a selector is defined to be emitted into the compiled CSS as a
 * comment. Useful for debugging especially when using imports and mixins.
 */
if (!defined('CAKE_CORE_INCLUDE_PATH')) {
	header('HTTP/1.1 404 Not Found');
	exit('File Not Found');
}

if (preg_match('|\.\.|', $url) || !preg_match('|^([\w/]+)?ccss/(.+)(\.[^.]+)?$|iU', $url, $regs)) {
	die('Wrong file name.');
}

$mime = array(
	'txt'  => 'text/plain',
	'htm'  => 'text/html',
	'html' => 'text/html',

	'css'  => 'text/css',

	'png'  => 'image/png',
	'jpeg' => 'image/jpeg',
	'jpg'  => 'image/jpeg',
	'gif'  => 'image/gif',
	'bmp'  => 'image/bmp',
	'ico'  => 'image/vnd.microsoft.icon',
	'tiff' => 'image/tiff',
	'svg'  => 'image/svg+xml');
$ext = strtolower(substr($regs[3], 1));
if (isset($mime[$ext])) {
	header("Content-Type: " . $mime[$ext]);
} else {
	header("Content-Type: application/octet-stream");
}
header("Expires: " . gmdate("D, j M Y H:i:s", time() + DAY) . " GMT");
header("Cache-Control: cache"); // HTTP/1.1
header("Pragma: cache");        // HTTP/1.0

$cssPath = CSS;
if ($regs[1]) {
	$plugin = trim($regs[1], '/');
	$theme = split('/', $plugin);
	if (is_dir('.' . DS . $plugin . DS . 'css')) {
		$cssPath = '.' . DS . $plugin . DS . 'css' . DS;
	} else if (count($theme) > 1 && $theme[0] == 'theme') {
		$path = App::themePath($theme[1]);
		$cssPath = $path . 'webroot' . DS . 'css' . DS;
	} else {
		$path = App::pluginPath(Inflector::camelize($plugin));
		$cssPath = $path . 'webroot' . DS . 'css' . DS;
	}
}

$cssFile = $cssPath . $regs[2] . $regs[3];
$sassFile = false;
if ($ext === 'css') {
	$sassFile = str_replace('.css', '.sass', $cssFile);
}

// Used Sass compiler does not work with PHP 5.4. Following section is a
// work around to provide a precompiled SASS file
$phpVersion = preg_split('/[-\.]/', phpversion());
$requiredPhpVersion = $phpVersion[0] == 5 && $phpVersion[1] < 4;
$useSassCompiler = $requiredPhpVersion && Configure::read('debug') > 0;

if (!$useSassCompiler) {
  if (file_exists($cssFile)) {
    echo file_get_contents($cssFile);
  } else {
    echo "/*\nCan not compile SASS file '$sassFile'!\n\n"
      . "PHP 5.3 with and debug mode 1 or 2 is required. ";
    if ($requiredPhpVersion) {
      echo "Please set Configure::write(\"debug\", 1); in Config/core.php.\n*/";
    } else {
      echo "Please use PHP 5.3. You are running PHP " . phpversion() . "\n*/";
    }
  }
  die;
}

// Parse the Sass file if there is one
if ($sassFile && file_exists($sassFile)) {
	$options = array();
	foreach ($sassOptions as $option) {
		$_option = Configure::read("Sass.$option");
		if (!is_null($_option)) {
			$options[$option] = $_option;
		}
	} // foreach
	
	App::import('Vendor', 'SassParser', array('file'=>'phamlp'.DS.'sass'.DS.'SassParser.php'));
	
	$parser = new SassParser($options);

	echo $parser->toCss($sassFile);
}
// Look for a CSS file if no Sass file
elseif (file_exists($cssFile)) {
	echo file_get_contents($cssFile);
}
// If no Sass or CSS then die
else {
	die('/* No Sass or CSS file found. */');
}
