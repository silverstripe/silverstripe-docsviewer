<?php

/**
 * Documentation Configuration
 *
 * Please override any of these options in your own projects _config.php file.
 * For more information and documentation see sapphiredocs/docs/en
 */

// default location for documentation. If you want this under a custom url
// define your own rule in your mysite/_config.php
Director::addRules(100, array(
	'dev/docs' => 'DocumentationViewer'
));