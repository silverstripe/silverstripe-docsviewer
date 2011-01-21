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

// the default meta data for the OpenSearch library. More descriptive values
// can be set in your mysite file
DocumentationSearch::set_meta_data(array(
	'ShortName' => _t('DocumentationViewer.OPENSEARCHNAME', 'Documentation Search'),
	'Description' => _t('DocumentationViewer.OPENSEARCHDESC', 'Search the documentation'),
	'Contact' => Email::getAdminEmail(),
	'Tags' => _t('DocumentationViewer.OPENSEARCHTAGS', 'Documentation')
));
