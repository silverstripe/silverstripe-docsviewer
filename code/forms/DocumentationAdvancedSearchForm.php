<?php

/**
 * @package docsviewer
 */
class DocumentationAdvancedSearchForm extends Form {

	public function __construct($controller) {
		$entities = DocumentationService::get_registered_entities();
		$versions = array();
		
		foreach($entities as $entity) {
			$versions[$entity->getFolder()] = $entity->getVersions();
		}
		
		// get a list of all the unique versions
		$uniqueVersions = array_unique(ArrayLib::flatten(array_values($versions)));
		asort($uniqueVersions);
		$uniqueVersions = array_combine($uniqueVersions,$uniqueVersions);
		
		$q = ($q = $this->getSearchQuery()) ? $q->NoHTML() : "";
		
		// klude to take an array of objects down to a simple map
		$entities = new ArrayList($entities);
		$entities = $entities->map('Folder', 'Title');
		
		// if we haven't gone any search limit then we're searching everything
		$searchedEntities = $controller->getSearchedEntities();

		if(count($searchedEntities) < 1) {
			$searchedEntities = $entities;
		}
		
		$searchedVersions = $controller->getSearchedVersions();
		
		if(count($searchedVersions) < 1) {
			$searchedVersions = $uniqueVersions;
		}

		$fields = new FieldList(
			new TextField('Search', _t('DocumentationViewer.KEYWORDS', 'Keywords'), $q),
			new CheckboxSetField('Entities', _t('DocumentationViewer.MODULES', 'Modules'), $entities, $searchedEntities),
			new CheckboxSetField('Versions', _t('DocumentationViewer.VERSIONS', 'Versions'),
			 	$uniqueVersions, $searchedVersions
			)
		);
		
		$actions = new FieldList(
			new FormAction('results', _t('DocumentationViewer.SEARCH', 'Search'))
		);
		
		$required = new RequiredFields(array('Search'));
		
		parent::__construct(
			$controller, 
			'AdvancedSearchForm', 
			$fields, 
			$actions, 
			$required
		);
		
		$this->disableSecurityToken();
		$this->setFormMethod('GET');
		$this->setFormAction(self::$link_base . 'DocumentationSearchForm');
	
	}