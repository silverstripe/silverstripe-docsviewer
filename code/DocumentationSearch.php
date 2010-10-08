<?php

/**
 * @todo caching?
 */

class DocumentationSearch extends Controller {
	
	/**
	 * Generates the XML tree for {@link Sphinx} XML Pipes
	 *
	 * @uses DomDocument
	 */
	function sphinxxml() {
		DocumentationService::load_automatic_registration();
		
		
		// generate the head of the document
		$dom = new DomDocument('1.0');
		$dom->encoding = "utf-8";
		$dom->formatOutput = true;
 		$root = $dom->appendChild($dom->createElement('sphinx:docset'));

		$schema = $dom->createElement('sphinx:schema');

		$field = $dom->createElement('sphinx:field');
	    $attr  = $dom->createElement('sphinx:attr');

		foreach(array('Title','Content', 'Language', 'Version', 'Module') as $field) {
			$node = $dom->createElement('sphinx:field');
			$node->setAttribute('name', $field);
			
			$schema->appendChild($node);
	    }

		$root->appendChild($schema);

		// go through each documentation page and add it to index
		$pages = $this->getAllDocumentationPages();
		
		if($pages) {
			foreach($pages as $doc) {
				$node = $dom->createElement('sphinx:document');
			
				$node->setAttribute('ID', $doc->ID);
				
				foreach($doc->getArray() as $key => $value) {
					if($key == 'ID') continue;
				
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
					$pages = DocumentationParser::get_pages_from_folder($module->getPath(false, $language));
					
					if($pages) {
						foreach($pages as $page) {
							$output->push(new ArrayData(array(
								'Title' => $page->Title,
								'Content' => file_get_contents($page->Path),
								'ID' => base_convert(substr(md5($page->Path), -8), 16, 10)
							)));
						}
					}
				}
			}
		}
		
		return $output;
	}
}