<?php

class DocumentationSearchForm extends Form {

	public function __construct($controller) {
 		$q = ($q = $controller->getSearchQuery()) ? $q->NoHTML() : "";

		$entities = $controller->getSearchedEntities();
		$versions = $controller->getSearchedVersions();
		
		$fields = new FieldList(
			new TextField('Search', _t('DocumentationViewer.SEARCH', 'Search'), $q)
		);
		
		if ($entities) $fields->push(
			new HiddenField('Entities', '', implode(',', array_keys($entities)))
		);
		
		if ($versions) $fields->push(
			new HiddenField('Versions', '', implode(',', $versions))
		);

		$actions = new FieldList(
			new FormAction('results', 'Search')
		);

		parent::__construct($controller, 'DocumentationSearchForm', $fields, $actions);

		$this->disableSecurityToken();
		$this->setFormMethod('GET');
		$this->setFormAction($controller->Link('DocumentationSearchForm'));
	}