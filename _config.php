<?php
/**
 * Documentation Configuration
 *
 * Please override any of these options in your own projects _config.php file.
 * For more information and documentation see docviewer/docs/en
 */

if(!defined('DOCVIEWER_PATH')) {
	define('DOCVIEWER_PATH', dirname(__FILE__));
}

if(!defined('DOCVIEWER_DIR')) {
	define('DOCVIEWER_DIR', array_pop(explode(DIRECTORY_SEPARATOR, DOCVIEWER_PATH)));
}


// default location for documentation. If you want this under a custom url
// define your own rule in your mysite/_config.php
Director::addRules(100, array(
	'dev/docs' => 'DocumentationViewer'
));