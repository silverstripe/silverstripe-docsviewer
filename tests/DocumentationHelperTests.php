<?php

class DocumentationHelperTests extends SapphireTest {

	public function testGetAllPages() {
		if(!DocumentationSearch::enabled()) {
			return;
		}
		
		DocumentationService::set_automatic_registration(false);
		DocumentationService::register('docs-search', DOCSVIEWER_PATH . '/tests/docs-search/');
		
		$search = DocumentationSearch::get_all_documentation_pages();
		
		$this->assertEquals(7, $search->Count(), '5 pages. 5 pages in entire folder');
	}
}