<?php

/**
 * @package docsviewer
 */
class DocumentationAdvancedSearchForm extends Form {

	public function __construct($controller) {
		$versions = $controller->getManifest()->getAllVersions();
		$entities = $controller->getManifest()->getEntities();

		$q = ($q = $controller->getSearchQuery()) ? $q->NoHTML() : "";

		// klude to take an array of objects down to a simple map
		$entities = $entities->map('Key', 'Title');

		// if we haven't gone any search limit then we're searching everything
		$searchedEntities = $controller->getSearchedEntities();

		if(count($searchedEntities) < 1) {
			$searchedEntities = $entities;
		}
		
		$searchedVersions = $controller->getSearchedVersions();
		
		if(count($searchedVersions) < 1) {
			$searchedVersions = $versions;
		}

		$fields = FieldList::create(
			TextField::create('q', _t('DocumentationViewer.KEYWORDS', 'Keywords'), $q),
			//CheckboxSetField::create('Entities', _t('DocumentationViewer.MODULES', 'Modules'), $entities, $searchedEntities),
			CheckboxSetField::create(
				'Versions', 
				_t('DocumentationViewer.VERSIONS', 'Versions'),
			 	$versions, $searchedVersions
			)
		);

		$actions = FieldList::create(
			FormAction::create('results', _t('DocumentationViewer.SEARCH', 'Search'))
		);
		
		$required = RequiredFields::create(array('Search'));
		
		parent::__construct(
			$controller, 
			'AdvancedSearchForm', 
			$fields, 
			$actions, 
			$required
		);
		
		$this->disableSecurityToken();
		$this->setFormMethod('GET');
		$this->setFormAction($controller->Link('results'));
	}
}