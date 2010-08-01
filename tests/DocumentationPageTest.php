<?php
class DocumentationPageTest extends SapphireTest {
	
	function testGetRelativePath() {
		$page = new DocumentationPage(
			'test.md',
			new DocumentationEntity('mymodule', null, BASE_PATH . '/sapphiredocs/tests/docs/')
		);
		$this->assertEquals('test.md', $page->getRelativePath());
		
		$page = new DocumentationPage(
			'subfolder/subpage.md',
			new DocumentationEntity('mymodule', null, BASE_PATH . '/sapphiredocs/tests/docs/')
		);
		$this->assertEquals('subfolder/subpage.md', $page->getRelativePath());
	}
	
	function testGetPath() {
		$absPath = BASE_PATH . '/sapphiredocs/tests/docs/';
		$page = new DocumentationPage(
			'test.md',
			new DocumentationEntity('mymodule', null, $absPath)
		);
		$this->assertEquals($absPath . 'en/test.md', $page->getPath());
		
		$page = new DocumentationPage(
			'subfolder/subpage.md',
			new DocumentationEntity('mymodule', null, $absPath)
		);
		$this->assertEquals($absPath . 'en/subfolder/subpage.md', $page->getPath());
	}
	
}