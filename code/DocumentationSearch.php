<?php

/**
 * Documentation Search powered by Lucene. You will need Zend_Lucene installed 
 * on your path.
 *
 * To rebuild the indexes run the {@link RebuildLuceneDocsIndex} task. You may 
 * wish to setup a cron job to remake the indexes on a regular basis.
 *
 * This class has the ability to generate an OpenSearch RSS formatted feeds 
 * simply by using the URL:
 *
 * <code>
 * yoursite.com/search/?q=Foo&format=rss. // Format can either be specified as rss or left off.
 * </code>
 *
 * To get a specific amount of results you can also use the modifiers start and 
 * limit:
 *
 * <code>
 * yoursite.com/search/?q=Foo&start=10&limit=10
 * </code>
 *
 * @package docsviewer
 */

class DocumentationSearch {
	
	/**
	 * @var bool - Is search enabled
	 */
	private static $enabled = false;

	/**
	 * @var bool - Is advanced search enabled
	 */
	private static $advanced_search_enabled = true;	
	
	/**
	 * @var string - OpenSearch metadata. Please use {@link DocumentationSearch::set_meta_data()}
	 */
	private static $meta_data = array();
	
	/**
	 * @var Array Regular expression mapped to a "boost factor" for the searched document.
	 * Defaults to 1.0, lower to decrease relevancy. Requires reindex.
	 * Uses {@link DocumentationPage->getRelativePath()} for comparison.
	 */
	static $boost_by_path = array();
	
	/**
	 * @var ArrayList - Results
	 */
	private $results;
	
	/**
	 * @var int
	 */
	private $totalResults;
	
	/**
	 * @var string
	 */
	private $query;
	
	/**
	 * @var Controller
	 */
	private $outputController;
	
	/**
	 * Optionally filter by module and version
	 *
	 * @var array
	 */
	private $modules, $versions;
	
	public function setModules($modules) {
		$this->modules = $modules;
	}
	
	public function setVersions($versions) {
		$this->versions = $versions;
	}
	
	/**
	 * Set the current search query
	 *
	 * @param string
	 */
	public function setQuery($query) {
		$this->query = $query;
	}
	
	/**
	 * Returns the current search query
	 *
	 * @return string
	 */
	public function getQuery() {
		return $this->query;
	}
	
	/**
	 * Sets the {@link DocumentationViewer} or {@link DocumentationSearch} instance which this search is rendering
	 * on based on whether it is the results display or RSS feed
	 *
	 * @param Controller
	 */
	public function setOutputController($controller) {
		$this->outputController = $controller;
	}
	
	/**
	 * Folder name for indexes (in the temp folder). You can override it using
	 * {@link DocumentationSearch::set_index_location($)}
	 *
	 * @var string 
	 */
	private static $index_location;
	
	/**
	 * Generate an array of every single documentation page installed on the system. 
	 *
	 * @return ArrayList
	 */
	public static function get_all_documentation_pages() {
		DocumentationService::load_automatic_registration();
		
		$modules = DocumentationService::get_registered_entities();
		$output = new ArrayList();

		if($modules) {
			foreach($modules as $module) {
				foreach($module->getVersions() as $version) {
					try {
						$pages = DocumentationService::get_pages_from_folder($module, false, true, $version);
						
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
	public static function enable($enabled = true) {
		self::$enabled = $enabled;
		
		if($enabled) {
			// include the zend search functionality
			set_include_path(
		 		dirname(dirname(__FILE__)) . '/thirdparty/'. PATH_SEPARATOR .
				get_include_path()
			);
		
			require_once 'Zend/Search/Lucene.php';
		}
	}

	/**
	 * @return bool
	 */
	public static function enabled() {
		return self::$enabled;
	}

	/**
	 * Enable advanced documentation search 
	 */	
	public static function enable_advanced_search($enabled = true) {
		self::$advanced_search_enabled = ($enabled)? true: false;
	}
	
	/**
	 * @return bool
	 */
	public static function advanced_search_enabled() {
		return self::$advanced_search_enabled;
	}
	
	/**
	 * @param string
	 */
	public static function set_index($index) {
		self::$index_location = $index;
	}
	
	/**
	 * @return string
	 */
	public static function get_index_location() {
		if(!self::$index_location) {
			self::$index_location = DOCSVIEWER_DIR;
		}
		
		return Controller::join_links(
			TEMP_FOLDER, 
			trim(self::$index_location, '/')
		);
	}
	
	/**
	 * Perform a search query on the index
	 */
	public function performSearch() {	
		try {
			$index = Zend_Search_Lucene::open(self::get_index_location());

			Zend_Search_Lucene::setResultSetLimit(100);
			
			$query = new Zend_Search_Lucene_Search_Query_Boolean();
			$term = Zend_Search_Lucene_Search_QueryParser::parse($this->getQuery());
			$query->addSubquery($term, true);
			
			if($this->modules) {
				$moduleQuery = new Zend_Search_Lucene_Search_Query_MultiTerm();
				
				foreach($this->modules as $module) {
					$moduleQuery->addTerm(new Zend_Search_Lucene_Index_Term($module, 'Entity'));
				}
				
				$query->addSubquery($moduleQuery, true);
			}

			if($this->versions) {
				$versionQuery = new Zend_Search_Lucene_Search_Query_MultiTerm();
				
				foreach($this->versions as $version) {
					$versionQuery->addTerm(new Zend_Search_Lucene_Index_Term($version, 'Version'));
				}
				
				$query->addSubquery($versionQuery, true);
			}
			
			$er = error_reporting();
			error_reporting('E_ALL ^ E_NOTICE');
			$this->results = $index->find($query);
			error_reporting($er);
			$this->totalResults = $index->numDocs();
		}
		catch(Zend_Search_Lucene_Exception $e) {
			user_error($e .'. Ensure you have run the rebuld task (/dev/tasks/RebuildLuceneDocsIndex)', E_USER_ERROR);
		}
	}
	
	/**
	 * @return ArrayData
	 */
	public function getSearchResults($request) {
		$pageLength = (isset($_GET['length'])) ? (int) $_GET['length'] : 10;

		$data = array(
			'Results' => null,
			'Query' => null,
			'Versions' => DBField::create_field('Text', implode(', ', $this->versions)),
			'Modules' => DBField::create_field('Text', implode(', ', $this->modules)),
			'Title' => _t('DocumentationSearch.SEARCHRESULTS', 'Search Results'),
			'TotalResults' => null,
			'TotalPages' => null,
			'ThisPage' => null,
			'StartResult' => null,
			'PageLength' => $pageLength,
			'EndResult' => null,
			'PrevUrl' => DBField::create_field('Text', 'false'),
			'NextUrl' => DBField::create_field('Text', 'false'),
			'SearchPages' => new ArrayList()
		);
	
		$start = ($request->requestVar('start')) ? (int)$request->requestVar('start') : 0;
		$query = ($request->requestVar('Search')) ? $request->requestVar('Search') : '';

		$currentPage = floor( $start / $pageLength ) + 1;
		
		$totalPages = ceil(count($this->results) / $pageLength );
		
		if ($totalPages == 0) $totalPages = 1;
		if ($currentPage > $totalPages) $currentPage = $totalPages;

		$results = new ArrayList();
		
		if($this->results) {
			foreach($this->results as $k => $hit) {
				if($k < ($currentPage-1)*$pageLength || $k >= ($currentPage*$pageLength)) continue;
			
				$doc = $hit->getDocument();
			
				$content = $hit->content;
				
				$obj = new ArrayData(array(
					'Title' => DBField::create_field('Varchar', $doc->getFieldValue('Title')),
					'BreadcrumbTitle' => DBField::create_field('HTMLText', $doc->getFieldValue('BreadcrumbTitle')),
					'Link' => DBField::create_field('Varchar',$doc->getFieldValue('Link')),
					'Language' => DBField::create_field('Varchar',$doc->getFieldValue('Language')),
					'Version' => DBField::create_field('Varchar',$doc->getFieldValue('Version')),
					'Entity' => DBField::create_field('Varchar', $doc->getFieldValue('Entity')),
					'Content' => DBField::create_field('HTMLText', $content),
					'Score' => $hit->score,
					'Number' => $k + 1,
					'ID' => md5($doc->getFieldValue('Link'))
				));

				$results->push($obj);
			}
		}

		$data['Results'] = $results;
		$data['Query'] = DBField::create_field('Text', $query);
		$data['TotalResults'] = DBField::create_field('Text', count($this->results));
		$data['TotalPages'] = DBField::create_field('Text', $totalPages);
		$data['ThisPage'] = DBField::create_field('Text', $currentPage);
		$data['StartResult'] = $start + 1;
		$data['EndResult'] = $start + count($results);

		// Pagination links
		if($currentPage > 1) {
			$data['PrevUrl'] = DBField::create_field('Text', 
				$this->buildQueryUrl(array('start' => ($currentPage - 2) * $pageLength))
			);
		}

		if($currentPage < $totalPages) {
			$data['NextUrl'] = DBField::create_field('Text', 
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

		return new ArrayData($data);
	}
	
	/**
	 * Build a nice query string for the results
	 *
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

	/**
	 * Optimizes the search indexes on the File System
	 *
	 * @return void
	 */
	public function optimizeIndex() {
		$index = Zend_Search_Lucene::open(self::get_index_location());

		if($index) $index->optimize();
	}
	
	/**
	 * @return String
	 */
	public function getTitle() {
		return ($this->outputController) ? $this->outputController->Title : _t('DocumentationSearch.SEARCH', 'Search');
	}
	
	/**
	 * OpenSearch MetaData fields. For a list of fields consult 
	 * {@link self::get_meta_data()}
	 *
	 * @param array
	 */
	public static function set_meta_data($data) {
		if(is_array($data)) {
			foreach($data as $key => $value) {
				self::$meta_data[strtolower($key)] = $value;
			}
		}
		else {
			user_error("set_meta_data must be passed an array", E_USER_ERROR);
		}
	}
	
	/**
	 * Returns the meta data needed by opensearch.
	 *
	 * @return array
	 */
	public static function get_meta_data() {
		$data = self::$meta_data;
		
		$defaults = array(
			'Description' => _t('DocumentationViewer.OPENSEARCHDESC', 'Search the documentation'),
			'Tags' => _t('DocumentationViewer.OPENSEARCHTAGS', 'documentation'),
			'Contact' => Email::getAdminEmail(),
			'ShortName' => _t('DocumentationViewer.OPENSEARCHNAME', 'Documentation Search'),
			'Author' => 'SilverStripe'
		);
		
		foreach($defaults as $key => $value) {
			if(isset($data[$key])) $defaults[$key] = $data[$key];
		}
		
		return $defaults;
	}
	
	/**
	 * Renders the search results into a template. Either
	 * the search results template or the Atom feed
	 */
	public function renderResults() {
		if(!$this->results && $this->query) $this->performSearch();
		if(!$this->outputController) return user_error('Call renderResults() on a DocumentationViewer instance.', E_USER_ERROR);
		
		$request = $this->outputController->getRequest();

		$data = $this->getSearchResults($request);
		$templates = array('DocumentationViewer_results', 'DocumentationViewer');

		if($request->requestVar('format') && $request->requestVar('format') == "atom") {
			// alter the fields for the opensearch xml.
			$title = ($title = $this->getTitle()) ? ' - '. $title : "";
			
			$link = Controller::join_links($this->outputController->Link(), 'DocumentationOpenSearchController/description/');
			
			$data->setField('Title', $data->Title . $title);
			$data->setField('DescriptionURL', $link);
			
			array_unshift($templates, 'OpenSearchResults');
		}
		
		return $this->outputController->customise($data)->renderWith($templates);
	}
}
