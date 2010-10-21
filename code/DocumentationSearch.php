<?php

/**
 * @todo caching?
 */

class DocumentationSearch extends DocumentationViewer {
	
	static $casting = array(
		'Query' => 'Text'
	);
	
	static $allowed_actions = array('xml', 'search');
	
	/**
	 * @var array Cached search results
	 */
	private $searchCache = array();
	
	/**
	 * @var Int Page Length
	 */
	private $pageLength = 10;
	
	/**
	 * Generates the XML tree for {@link Sphinx} XML Pipes
	 *
	 * @uses DomDocument
	 */
	function xml() {
		DocumentationService::load_automatic_registration();
		
		$dom = new DomDocument('1.0');
		$dom->encoding = "utf-8";
		$dom->formatOutput = true;
 		$root = $dom->appendChild($dom->createElementNS('http://sphinxsearch.com', 'sphinx:docset'));

		$schema = $dom->createElement('sphinx:schema');

		$field = $dom->createElement('sphinx:field');
	    $attr  = $dom->createElement('sphinx:attr');

		foreach(array('Title','Content', 'Language', 'Module', 'Path') as $field) {
			$node = $dom->createElement('sphinx:field');
			$node->setAttribute('name', strtolower($field));
			
			$schema->appendChild($node);
	    }

		$root->appendChild($schema);

		// go through each documentation page and add it to index
		$pages = $this->getAllDocumentationPages();
		
		if($pages) {
			foreach($pages as $doc) {
				$node = $dom->createElement('sphinx:document');
			
				$node->setAttribute('id', $doc->ID);
				
				foreach($doc->getArray() as $key => $value) {
					$key = strtolower($key);
					if($key == 'id') continue;
				
					$tmp = $dom->createElement($key);
					$tmp->appendChild($dom->createTextNode($value));

					$node->appendChild($tmp);
				}

				$root->appendChild($node);
			}
		}
		
		return $dom->saveXML();
	}
	
	/**
	 * Generate an array of every single documentation page installed on the system. 
	 *
	 * @todo Add version support
	 *
	 * @return array 
	 */
	private function getAllDocumentationPages() {
		$modules = DocumentationService::get_registered_modules();
		$output = new DataObjectSet();

		
		if($modules) {
			foreach($modules as $module) {
				foreach($module->getLanguages() as $language) {
					try {
						$pages = DocumentationParser::get_pages_from_folder($module->getPath(false, $language));
					
						if($pages) {
							foreach($pages as $page) {
								$output->push(new ArrayData(array(
									'Title' => $page->Title,
									'Content' => file_get_contents($page->Path),
									'Path' => $page->Path,
									'Language' => $language,
									'ID' => base_convert(substr(md5($page->Path), -8), 16, 10)
								)));
							}
						}
					}
					catch(Exception $e) {}
				}
			}
		}
		
		return $output;
	}
	
	/**
	 * Takes a search from the URL, performs a sphinx search and displays a search results
	 * template.
	 *
	 * @todo Add additional language / version filtering
	 */
	function search() {
		$query = (isset($this->urlParams['ID'])) ? $this->urlParams['ID'] : false;
		$results = false;
		$keywords = "";
		
		if($query) {
			$keywords = urldecode($query);

			$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;

			$cachekey = $query.':'.$start;
			
			if(!isset($this->searchCache[$cachekey])) {
				$this->searchCache[$cachekey] = SphinxSearch::search('DocumentationPage', $keywords, array_merge_recursive(array(
					'start' => $start,
					'pagesize' => $this->pageLength
				)));
			}

			$results = $this->searchCache[$cachekey];
		}
		
		return array(
			'Query' => DBField::create('Text', $keywords),
			'Results' => $results
		);
	}
}