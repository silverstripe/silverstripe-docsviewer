<?php

/**
 * @package sapphiredocs
 */

class DocumentationSearch {

	private static $enabled = false;

	private $results;
	
	private $totalResults;
	
	
	/**
	 * Folder name for indexes (in the temp folder). You can override it using
	 * {@link DocumentationSearch::set_index_location($)}
	 *
	 * @var string 
	 */
	private static $index_location = 'sapphiredocs';
	
	static $allowed_actions = array(
		'buildindex'
	);
	
	/**
	 * Generate an array of every single documentation page installed on the system. 
	 *
	 * @return DataObjectSet
	 */
	static function get_all_documentation_pages() {
		DocumentationService::load_automatic_registration();
		
		$modules = DocumentationService::get_registered_modules();
		$output = new DataObjectSet();

		if($modules) {
			foreach($modules as $module) {
				
				foreach($module->getLanguages() as $language) {
					try {
						$pages = DocumentationService::get_pages_from_folder($module);
						
						if($pages) {
							foreach($pages as $page) {
								$output->push($page);
							}
						}
					}
					catch(Exception $e) {
						user_error($e, E_USER_WARNING);
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Enable searching documentation 
	 */
	public static function enable() {
		if(!class_exists('ZendSearchLuceneSearchable')) {
			return user_error('DocumentationSearch requires the ZendSearchLucene library', E_ERROR);
		}
		
		self::$enabled = true;

		ZendSearchLuceneSearchable::enable(array());
	}

	/**
	 * @return bool
	 */
	public static function enabled() {
		return self::$enabled;
	}

	/**
	 * @param string
	 */
	public function set_index($index) {
		self::$index_location = $index;
	}
	
	/**
	 * @return string
	 */
	public function get_index_location() {
		return TEMP_FOLDER . '/'. trim(self::$index_location, '/');
	}
	
	/**
	 * Perform a search query on the index
	 *
	 * Rebuilds the index if it out of date
	 */
	public function performSearch($query) {
		$this->buildindex();
		$index = Zend_Search_Lucene::open(self::get_index_location());
		
		Zend_Search_Lucene::setResultSetLimit(200);
		
		$results = $index->find($query);

		$this->results = new DataObjectSet();
		$this->totalResults = $index->numDocs();
		
		foreach($results as $result) {			
			$data = $result->getDocument();
			
			$this->results->push(new ArrayData(array(
				'Title' => DBField::create('Varchar', $data->Title),
				'Link' => DBField::create('Varchar',$data->Path),
				'Language' => DBField::create('Varchar',$data->Language),
				'Version' => DBField::create('Varchar',$data->Version)
			)));
		}
	}
	
	/**
	 * @return DataObjectSet
	 */
	public function getResults($start) {
		return $this->results;
	}
	
	/**
	 * @return int
	 */
	public function getTotalResults() {
		return (int) $this->totalResults;
	}
	
	/**
	 * Builds the document index
	 */
	public function buildIndex() {
		ini_set("memory_limit", -1);
		ini_set('max_execution_time', 0);
		
		// only rebuild the index if we have to. Check for either flush or the time write.lock.file
		// was last altered
		$lock = self::get_index_location() .'/write.lock.file';
		$lockFileFresh = (file_exists($lock) && filemtime($lock) > (time() - (60 * 60 * 24)));
		
		if($lockFileFresh && !isset($_REQUEST['flush'])) return true;
		
		try {
			$index = Zend_Search_Lucene::open(self::get_index_location());
			$index->removeReference();
		}
		catch (Zend_Search_Lucene_Exception $e) {
			
		}

		try {
			$index = Zend_Search_Lucene::create(self::get_index_location());
		}
		catch(Zend_Search_Lucene_Exception $c) {
			user_error($c);
		}
			
		// includes registration
		$pages = self::get_all_documentation_pages();

		if($pages) {
			$count = 0;
			foreach($pages as $page) {
				$count++;
				
				if(!is_dir($page->getPath())) {
					var_dump("Indexing ". $page->getPath());
					$doc = Zend_Search_Lucene_Document_Html::loadHTML($page->getHtml());
					$doc->addField(Zend_Search_Lucene_Field::Text('Title', $page->getTitle()));
					$doc->addField(Zend_Search_Lucene_Field::Keyword('Version', $page->getVersion()));
					$doc->addField(Zend_Search_Lucene_Field::Keyword('Language', $page->getLang()));
					$doc->addField(Zend_Search_Lucene_Field::Keyword('Path', $page->getPath()));
					$index->addDocument($doc);
				}
				else {
					var_dump("Not Indexing ". $page->getPath());
				}
			}
		}
	
		$index->commit();
	}

	public function optimizeIndex() {
		$index = Zend_Search_Lucene::open(self::get_index_location());

		if($index) $index->optimize();
	}
}