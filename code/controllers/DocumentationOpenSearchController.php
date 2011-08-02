<?php

/**
 * Public facing controller for handling an opensearch interface based on
 * the standard search form.
 *
 * @package sapphiredocs
 */

class DocumentationOpenSearchController extends ContentController {
	
	static $allowed_actions = array(
		'description'
	);
	
	function index() {
		return $this->httpError(404);
	}
	
	function description() {
		$viewer = new DocumentationViewer();
		
		if(!$viewer->canView()) return Security::permissionFailure($this);
		if(!DocumentationSearch::enabled()) return $this->httpError('404');
		
		$data = DocumentationSearch::get_meta_data();
		$link = Director::absoluteBaseUrl() .
		$data['SearchPageLink'] = Controller::join_links(
			$viewer->Link(),
			'results/?Search={searchTerms}&amp;start={startIndex}&amp;length={count}&amp;action_results=1'
		);
		
		$data['SearchPageAtom'] = $data['SearchPageLink'] . '&amp;format=atom';
		
		return $this->customise(
			new ArrayData($data)
		)->renderWith(array('OpenSearchDescription'));
	}
}