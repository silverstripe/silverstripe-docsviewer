<?php

class DocumentationSearchExtension extends Extension {
	
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

		return $versions;
	}
	
	/**
	 * Return the current search query.
	 *
	 * @return HTMLText|null
	 */
	public function getSearchQuery() {
		if(isset($_REQUEST['Search'])) {
			return DBField::create_field('HTMLText', $_REQUEST['Search']);
		} else if(isset($_REQUEST['q'])) {
			return DBField::create_field('HTMLText', $_REQUEST['q']);
		}
	}

	/**
	 * Past straight to results, display and encode the query.
	 */
	public function getSearchResults() {
		$query = $this->getSearchQuery();

		$search = new DocumentationSearch();
		$search->setQuery($query);
		$search->setVersions($this->getSearchedVersions());
		$search->setModules($this->getSearchedEntities());
		$search->setOutputController($this->owner);
		
		return $search->renderResults();
	}
	
	/**
	 * Returns an search form which allows people to express more complex rules
	 * and options than the plain search form.
	 *
	 * @return Form
	 */
	public function AdvancedSearchForm() {
		return new DocumentationAdvancedSearchForm($this->owner);
	}

	/**
	 * @return bool
	 */
	public function getAdvancedSearchEnabled() {
		return Config::inst()->get("DocumentationSearch", 'advanced_search_enabled'); 
	}
	
}