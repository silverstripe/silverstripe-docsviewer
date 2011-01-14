<?php

/**
 * @package sapphiredocs
 * @subpackage tests
 */

class DocumentationPageTest extends SapphireTest {
	
	function testGetLink() {
		$entity = new DocumentationEntity('testmodule', null, BASE_PATH . '/sapphiredocs/tests/docs/');
		
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity($entity);
		
		// single layer
		$this->assertStringEndsWith('testmodule/en/test', $page->Link(), 'The page link should have no extension and have a language');
		
		$folder = new DocumentationPage();
		$folder->setRelativePath('sort');
		$folder->setEntity($entity);
		
		// folder, should have a trailing slash
		$this->assertStringEndsWith('testmodule/en/sort/', $folder->Link());
		
		// second 
		$nested = new DocumentationPage();
		$nested->setRelativePath('subfolder/subpage.md');
		$nested->setEntity($entity);
		
		$this->assertStringEndsWith('testmodule/en/subfolder/subpage', $nested->Link());
		
		// test with version.
		$entity = DocumentationService::register("versionlinks", BASE_PATH . "/sapphiredocs/tests/docs-2/", '1');
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity($entity);
		$page->setVersion('1');
		$this->assertStringEndsWith('versionlinks/en/1/test', $page->Link());
	}
	
	
	function testGetRelativePath() {
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity(new DocumentationEntity('mymodule', null, BASE_PATH . '/sapphiredocs/tests/docs/'));
		
		$this->assertEquals('test.md', $page->getRelativePath());
		
		$page = new DocumentationPage();
		$page->setRelativePath('subfolder/subpage.md');
		$page->setEntity(new DocumentationEntity('mymodule', null, BASE_PATH . '/sapphiredocs/tests/docs/'));
		
		$this->assertEquals('subfolder/subpage.md', $page->getRelativePath());
	}
	
	function testGetPath() {
		$absPath = BASE_PATH . '/sapphiredocs/tests/docs/';
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity(new DocumentationEntity('mymodule', null, $absPath));
		
		$this->assertEquals($absPath . 'en/test.md', $page->getPath());
		
		$page = new DocumentationPage();
		$page->setRelativePath('subfolder/subpage.md');
		$page->setEntity(new DocumentationEntity('mymodule', null, $absPath));
		
		$this->assertEquals($absPath . 'en/subfolder/subpage.md', $page->getPath());
	}
	
}