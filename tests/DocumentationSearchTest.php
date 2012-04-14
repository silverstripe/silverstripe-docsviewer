<?php

/**
 * @package docsviewer
 * @subpackage tests
 */

class DocumentationSearchTest extends FunctionalTest {
	
	function setUp() {
		parent::setUp();
		
		if(!DocumentationSearch::enabled()) return;
		
		DocumentationService::set_automatic_registration(false);
		DocumentationService::register('docs-search', DOCSVIEWER_PATH . '/tests/docs-search/');
	}
	
	function testGetAllPages() {
		if(!DocumentationSearch::enabled()) return;
		
		DocumentationService::set_automatic_registration(false);
		DocumentationService::register('docs-search', DOCSVIEWER_PATH . '/tests/docs-search/');
		
		$search = DocumentationSearch::get_all_documentation_pages();
		
		$this->assertEquals(7, $search->Count(), '5 pages. 5 pages in entire folder');
	}
	
	function testOpenSearchControllerAccessible() {
		$c = new DocumentationOpenSearchController();

		$response = $c->handleRequest(new SS_HTTPRequest('GET', ''), DataModel::inst());
		$this->assertEquals(404, $response->getStatusCode());
		
		// test accessing it when the search isn't active
		DocumentationSearch::enable(false);
		$response = $c->handleRequest(new SS_HTTPRequest('GET', 'description/'), DataModel::inst());
		$this->assertEquals(404, $response->getStatusCode());
		
		// test we get a response to the description. The meta data test will check
		// that the individual fields are valid but we should check urls are there
		DocumentationSearch::enable(true);
		$response = $c->handleRequest(new SS_HTTPRequest('GET', 'description'), DataModel::inst());
		$this->assertEquals(200, $response->getStatusCode());
		
		$desc = new SimpleXMLElement($response->getBody());
		$this->assertEquals(2, count($desc->Url));
	}
}