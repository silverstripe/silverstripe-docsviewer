<?php

class DocumentationSearchController extends DocumentationViewer {
	
	/**
	 * Return an array of folders and titles
	 *
	 * @return array
	 */
	public function getSearchedEntities() {
		$entities = array();

		if(!empty($_REQUEST['Entities'])) {
			if(is_array($_REQUEST['Entities'])) {
				$entities = Convert::raw2att($_REQUEST['Entities']);
			}
			else {
				$entities = explode(',', Convert::raw2att($_REQUEST['Entities']));
				$entities = array_combine($entities, $entities);
			}
		}
		else if($entity = $this->getEntity()) {
			$entities[$entity->getFolder()] = Convert::raw2att($entity->getTitle());
		}
		
		return $entities;
	}
	
	/**
	 * Return an array of versions that we're allowed to return
	 *
	 * @return array
	 */
	public function getSearchedVersions() {
		$versions = array();
		
		if(!empty($_REQUEST['Versions'])) {
			if(is_array($_REQUEST['Versions'])) {
				$versions = Convert::raw2att($_REQUEST['Versions']);
				$versions = array_combine($versions, $versions);
			}
			else {
				$version = Convert::raw2att($_REQUEST['Versions']);
				$versions[$version] = $version;
			}
		}
		else if($version = $this->getVersion()) {
			$version =  Convert::raw2att($version);
			$versions[$version] = $version;
		}

		return $versions;
	}
	
	/**
	 * Return the current search query
	 *
	 * @return HTMLText|null
	 */
	public function getSearchQuery() {
		if(isset($_REQUEST['Search'])) {
			return DBField::create_field('HTMLText', $_REQUEST['Search']);
		}
	}

	/**
	 * Past straight to results, display and encode the query
	 */
	public function results($data, $form = false) {
		$query = (isset($_REQUEST['Search'])) ? $_REQUEST['Search'] : false;

		$search = new DocumentationSearch();
		$search->setQuery($query);
		$search->setVersions($this->getSearchedVersions());
		$search->setModules($this->getSearchedEntities());
		$search->setOutputController($this);
		
		return $search->renderResults();
	}
	
	/**
	 * Returns an search form which allows people to express more complex rules
	 * and options than the plain search form.
	 *
	 * @todo client side filtering of checkable option based on the module selected.
	 *
	 * @return Form
	 */
	public function AdvancedSearchForm() {
		$entities = DocumentationService::get_registered_entities();

		return new DocumentationAdvancedSearchForm($this);
	}

	/**
	 * Check if the Advanced SearchForm can be displayed. It is enabled by 
	 * default, to disable use: 
	 *
	 * <code>
	 * DocumentationSearch::enable_advanced_search(false);
	 * </code>
	 *
	 * @return bool
	 */
	public function getAdvancedSearchEnabled() {
		return DocumentationSearch::advanced_search_enabled(); 
	}
	
}