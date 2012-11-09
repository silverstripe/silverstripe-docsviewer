<?php
/**
 * Documentation Configuration
 *
 * Please override any of these options in your own projects _config.php file.
 * For more information and documentation see docsviewer/docs/en
 */

if(!defined('DOCSVIEWER_PATH')) {
	define('DOCSVIEWER_PATH', dirname(__FILE__));
}

if(!defined('DOCSVIEWER_DIR')) {
	$dir = explode(DIRECTORY_SEPARATOR, DOCSVIEWER_PATH);

	define('DOCSVIEWER_DIR', array_pop($dir));
}


// default location for documentation. If you want this under a custom url
// define your own rule in your mysite/_config.php
Director::addRules(100, array(
	'dev/docs' => 'DocumentationViewer'
));

// define filetypes to ignore
DocumentationService::set_ignored_files(array(
	'.', '..', '.DS_Store', 
	'.svn', '.git', 'assets', 'themes', '_images', '_resources'    
));
