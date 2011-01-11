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
		self::$enabled = true;
		
		// include the zend search functionality
		set_include_path(get_include_path() . PATH_SEPARATOR . dirname(dirname(__FILE__)) . '/thirdparty/');
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
		try {
			$index = Zend_Search_Lucene::open(self::get_index_location());
		
			Zend_Search_Lucene::setResultSetLimit(200);
		
			$this->results = $index->find($query);
			$this->totalResults = $index->numDocs();
		}
		catch(Zend_Search_Lucene_Exception $e) {
			// the reindexing task has not been run
			user_error('DocumentationSearch::performSearch() could not perform search as index does not exist. 
				Please run /dev/tasks/RebuildLuceneDocsIndex', E_USER_ERROR);
		}
	}
	
	/**
	 * @return DataObjectSet
	 */
	public function getDataArrayFromHits($request) {
		$data = array(
			'Results' => null,
			'Query' => null,
			'Title' => _t('DocumentationSearch.SEARCHRESULTS', 'Search Results'),
			'TotalResults' => null,
			'TotalPages' => null,
			'ThisPage' => null,
			'StartResult' => null,
			'EndResult' => null,
			'PrevUrl' => DBField::create('Text', 'false'),
			'NextUrl' => DBField::create('Text', 'false'),
			'SearchPages' => new DataObjectSet()
		);
	
		$start = ($request->requestVar('start')) ? (int)$request->requestVar('start') : 0;
		$query = ($request->requestVar('Search')) ? $request->requestVar('Search') : '';
		
		$pageLength = 10;
		$currentPage = floor( $start / $pageLength ) + 1;
		
		$totalPages = ceil(count($this->results) / $pageLength );
		
		if ($totalPages == 0) $totalPages = 1;
		if ($currentPage > $totalPages) $currentPage = $totalPages;

		$results = new DataObjectSet();
		
		foreach($this->results as $k => $hit) {
			if($k < ($currentPage-1)*$pageLength || $k >= ($currentPage*$pageLength)) continue;
			
			$doc = $hit->getDocument();
			
			$content = $hit->content;
			
			// do a simple markdown parse of the file
			$obj = new ArrayData(array(
				'Title' => DBField::create('Varchar', $doc->getFieldValue('Title')),
				'Link' => DBField::create('Varchar',$doc->getFieldValue('Link')),
				'Language' => DBField::create('Varchar',$doc->getFieldValue('Language')),
				'Version' => DBField::create('Varchar',$doc->getFieldValue('Version')),
				'Content' => DBField::create('HTMLText', $content),
				'Score' => $hit->score,
				'Number' => $k + 1
			));

			$results->push($obj);
		}

		$data['Results'] = $results;
		$data['Query']   = DBField::create('Text', $query);
		$data['TotalResults'] = DBField::create('Text', count($this->results));
		$data['TotalPages'] = DBField::create('Text', $totalPages);
		$data['ThisPage'] = DBField::create('Text', $currentPage);
		$data['StartResult'] = $start + 1;
		$data['EndResult'] = $start + count($results);

		// Pagination links
		if($currentPage > 1) {
			$data['PrevUrl'] = DBField::create('Text', 
				$this->buildQueryUrl(array('start' => ($currentPage - 2) * $pageLength))
			);
		}

		if($currentPage < $totalPages) {
			$data['NextUrl'] = DBField::create('Text', 
				$this->buildQueryUrl(array('start' => $currentPage * $pageLength))
			);
		}
		
		if($totalPages > 1) {
			// Always show a certain number of pages at the start
			for ( $i = 1; $i <= $totalPages; $i++ ) {
				$obj = new DataObject();
				$obj->IsEllipsis = false;
				$obj->PageNumber = $i;
				$obj->Link = $this->buildQueryUrl(array(
					'start' => ($i - 1) * $pageLength
				));
				
				$obj->Current = false;
				if ( $i == $currentPage ) $obj->Current = true;
				$data['SearchPages']->push($obj);
			}
		}

		return $data;
	}
	
	/**
	 * @return string
	 */
	private function buildQueryUrl($params) {
		$url = parse_url($_SERVER['REQUEST_URI']);
		if ( ! array_key_exists('query', $url) ) $url['query'] = '';
		parse_str($url['query'], $url['query']);
		if ( ! is_array($url['query']) ) $url['query'] = array();
		// Remove 'start parameter if it exists
		if ( array_key_exists('start', $url['query']) ) unset( $url['query']['start'] );
		// Add extra parameters from argument
		$url['query'] = array_merge($url['query'], $params);
		$url['query'] = http_build_query($url['query']);
		$url = $url['path'] . ($url['query'] ? '?'.$url['query'] : '');
		
		return $url;
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