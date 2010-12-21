<?php

/**
 * @package sapphiredocs
 * @subpackage tasks
 */

class RebuildLuceneDocsIndex extends BuildTask {
	
	/**
	 * Builds the document index
	 *
	 * Perhaps we run this via a hourly / daily task rather than
	 * based on the user. It's a 
	 */
	function run($request) {

		ini_set("memory_limit", -1);
		ini_set('max_execution_time', 0);

		// only rebuild the index if we have to. Check for either flush or the time write.lock.file
		// was last altered
		$lock = DocumentationSearch::get_index_location() .'/write.lock.file';
		$lockFileFresh = (file_exists($lock) && filemtime($lock) > (time() - (60 * 60 * 24)));

		if($lockFileFresh && !isset($_REQUEST['flush'])) return true;

		try {
			$index = Zend_Search_Lucene::open(DocumentationSearch::get_index_location());
			$index->removeReference();
		}
		catch (Zend_Search_Lucene_Exception $e) {

		}

		try {
			$index = Zend_Search_Lucene::create(DocumentationSearch::get_index_location());
		}
		catch(Zend_Search_Lucene_Exception $c) {
			user_error($c);
		}

		// includes registration
		$pages = DocumentationSearch::get_all_documentation_pages();

		if($pages) {
			$count = 0;
			foreach($pages as $page) {
				$count++;

				// iconv complains about all the markdown formatting
				// turn off notices while we parse
				$error = error_reporting();
				error_reporting('E_ALL ^ E_NOTICE');

				if(!is_dir($page->getPath())) {
					$doc = new Zend_Search_Lucene_Document();
					$doc->addField(Zend_Search_Lucene_Field::Text('content', $page->getMarkdown()));
					$doc->addField(Zend_Search_Lucene_Field::Text('Title', $page->getTitle()));
					$doc->addField(Zend_Search_Lucene_Field::Keyword('Version', $page->getVersion()));
					$doc->addField(Zend_Search_Lucene_Field::Keyword('Language', $page->getLang()));
					$doc->addField(Zend_Search_Lucene_Field::Keyword('Link', $page->getLink()));
					$index->addDocument($doc);
				}

				error_reporting($error);
			}
		}

		$index->commit();
	}
}