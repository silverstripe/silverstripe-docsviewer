<?php

/**
 * Rebuilds the search indexes for the documentation pages. 
 *
 * For the hourly cron rebuild use RebuildLuceneDocusIndex_Hourly 
 *
 * @package sapphiredocs
 * @subpackage tasks
 */

class RebuildLuceneDocsIndex extends BuildTask {
	
	protected $title = "Rebuild Documentation Search Indexes";
	
	protected $description = "Rebuilds the indexes used for the search engine in sapphiredocs. Gathers all documentation files from your modules";
	
	function run($request) {
		$this->rebuildIndexes();
	}
	
	function rebuildIndexes($quiet = false) {
		require_once(BASE_PATH . '/sapphiredocs/thirdparty/markdown/markdown.php');

		ini_set("memory_limit", -1);
		ini_set('max_execution_time', 0);
	
		// only rebuild the index if we have to. Check for either flush or the time write.lock.file
		// was last altered
		$lock = DocumentationSearch::get_index_location() .'/write.lock.file';
		$lockFileFresh = (file_exists($lock) && filemtime($lock) > (time() - (60 * 60 * 24)));

		if($lockFileFresh && !isset($_REQUEST['flush'])) { 
			if(!$quiet) {
				echo "Index recently rebuilt. If you want to force reindex use ?flush=1";
			}
			
			return true;
		}

		try {
			$index = Zend_Search_Lucene::open(DocumentationSearch::get_index_location());
			$index->removeReference();
		}
		catch (Zend_Search_Lucene_Exception $e) {
			user_error($e);
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
			
			// iconv complains about all the markdown formatting
			// turn off notices while we parse
			$error = error_reporting();
			error_reporting('E_ALL ^ E_NOTICE');
			
			foreach($pages as $page) {
				$count++;

				if(!is_dir($page->getPath())) {
					$doc = new Zend_Search_Lucene_Document();
					$content = $page->getMarkdown();
					if($content) $content = Markdown($content);

					$doc->addField(Zend_Search_Lucene_Field::Text('content', $content));
					$doc->addField(Zend_Search_Lucene_Field::Text('Title', $page->getTitle()));
					$doc->addField(Zend_Search_Lucene_Field::Keyword('Version', $page->getVersion()));
					$doc->addField(Zend_Search_Lucene_Field::Keyword('Language', $page->getLang()));
					$doc->addField(Zend_Search_Lucene_Field::Keyword('Link', $page->Link()));
					$index->addDocument($doc);
				}
				
				if(!$quiet) echo "adding ". $page->getTitle() ."\n\n";
			}
			
			error_reporting($error);
		}

		$index->commit();
		
		if(!$quiet) echo "complete.";
	}
}

/**
 * @package sapphiredocs
 * @subpackage tasks
 */
class RebuildLuceneDocusIndex_Hourly extends HourlyTask {
	
	function process() {
		$reindex = new RebuildLuceneDocusIndex();
		
		$reindex->rebuildIndexes(true);
	}
}