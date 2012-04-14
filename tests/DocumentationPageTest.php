<?php

/**
 * @package docsviewer
 * @subpackage tests
 */

class DocumentationPageTest extends SapphireTest {
	
	function testGetLink() {
		$entity = new DocumentationEntity('testmodule', null, DOCSVIEWER_PATH .'/tests/docs/');
		
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity($entity);
		
		// single layer
		$this->assertStringEndsWith('testmodule/en/test', $page->Link, 'The page link should have no extension and have a language');
		
		$folder = new DocumentationPage();
		$folder->setRelativePath('sort');
		$folder->setEntity($entity);
		
		// folder, should have a trailing slash
		$this->assertStringEndsWith('testmodule/en/sort/', $folder->Link);
		
		// second 
		$nested = new DocumentationPage();
		$nested->setRelativePath('subfolder/subpage.md');
		$nested->setEntity($entity);
		
		$this->assertStringEndsWith('testmodule/en/subfolder/subpage', $nested->Link);
		
		// test with version.
		$entity = DocumentationService::register("versionlinks", DOCSVIEWER_PATH ."/tests/docs-v2.4/", '1');
		$entity->addVersion('2', DOCSVIEWER_PATH ."/tests/docs-v3.0/");
		$entity->setStableVersion('2');
		
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity($entity);
		$page->setVersion('1');
		$this->assertStringEndsWith('versionlinks/en/1/test', $page->Link);
	}
	
	
	function testGetRelativePath() {
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity(new DocumentationEntity('mymodule', null, DOCSVIEWER_PATH . '/tests/docs/'));
		
		$this->assertEquals('test.md', $page->getRelativePath());
		
		$page = new DocumentationPage();
		$page->setRelativePath('subfolder/subpage.md');
		$page->setEntity(new DocumentationEntity('mymodule', null, DOCSVIEWER_PATH . '/tests/docs/'));
		
		$this->assertEquals('subfolder/subpage.md', $page->getRelativePath());
	}
	
	function testGetPath() {
		$absPath = DOCSVIEWER_PATH .'/tests/docs/';
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity(new DocumentationEntity('mymodule', null, $absPath));
		
		$this->assertEquals($absPath . 'en/test.md', $page->getPath());
		
		$page = new DocumentationPage();
		$page->setRelativePath('subfolder/subpage.md');
		$page->setEntity(new DocumentationEntity('mymodule', null, $absPath));
		
		$this->assertEquals($absPath . 'en/subfolder/subpage.md', $page->getPath());
	}
	
	function testGetBreadcrumbTitle() {
		$entity = new DocumentationEntity('testmodule', null, DOCSVIEWER_PATH . '/tests/docs/');
		
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity($entity);
		
		$this->assertEquals("Testmodule - Test", $page->getBreadcrumbTitle());
		
		$page = new DocumentationPage();
		$page->setRelativePath('subfolder/subpage.md');
		$page->setEntity(new DocumentationEntity('mymodule', null, DOCSVIEWER_PATH . '/tests/docs/'));
		
		$this->assertEquals('Mymodule - Subfolder - Subpage', $page->getBreadcrumbTitle());
	}
	
}