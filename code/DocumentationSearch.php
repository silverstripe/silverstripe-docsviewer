<?php

/**
 * Documentation Search powered by Lucene. You will need Zend_Lucene installed on your path
 * to rebuild the indexes run the {@link RebuildLuceneDocsIndex} task. You may wish to setup
 * a cron job to remake the indexes on a regular basis
 *
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
	public static function get_all_documentation_pages() {
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
		$index = Zend_Search_Lucene::open(self::get_index_location());
		
		Zend_Search_Lucene::setResultSetLimit(200);
		
		$results = $index->find($query);

		$this->results = new DataObjectSet();
		$this->totalResults = $index->numDocs();
		
		foreach($results as $result) {			
			$data = $result->getDocument();
			
			$this->results->push(new ArrayData(array(
				'Title' => DBField::create('Varchar', $data->Title),
				'Link' => DBField::create('Varchar',$data->Link),
				'Language' => DBField::create('Varchar',$data->Language),
				'Version' => DBField::create('Varchar',$data->Version),
				'Content' => DBField::create('Text', $data->content)
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

	public function optimizeIndex() {
		$index = Zend_Search_Lucene::open(self::get_index_location());

		if($index) $index->optimize();
	}
}