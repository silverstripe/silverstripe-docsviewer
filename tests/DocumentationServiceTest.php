<?php

/**
 * @package sapphiredocs
 * @subpackage tests
 */

class DocumentationServiceTest extends SapphireTest {

	function testFindPath() {
		DocumentationService::register("DocumentationViewerTests", BASE_PATH . "/sapphiredocs/tests/docs/");
		
		// file
		$path = DocumentationService::find_page('DocumentationViewerTests', array('test'));
		$this->assertEquals(BASE_PATH . "/sapphiredocs/tests/docs/en/test.md", $path);

		// the home page. The path finder should go to the index.md file in the default language
		$path = DocumentationService::find_page('DocumentationViewerTests', array(''));
		$this->assertEquals(BASE_PATH . "/sapphiredocs/tests/docs/en/index.md", $path);

		// second level
		$path = DocumentationService::find_page('DocumentationViewerTests', array('subfolder', 'subpage'));
		$this->assertEquals(BASE_PATH . "/sapphiredocs/tests/docs/en/subfolder/subpage.md", $path);
			
		$path = DocumentationService::find_page('DocumentationViewerTests', array('subfolder', 'subsubfolder'));
		$this->assertEquals(BASE_PATH . "/sapphiredocs/tests/docs/en/subfolder/subsubfolder/", $path);
		
		// third level
		$path = DocumentationService::find_page('DocumentationViewerTests', array('subfolder', 'subsubfolder', 'subsubpage'));
		$this->assertEquals(BASE_PATH . "/sapphiredocs/tests/docs/en/subfolder/subsubfolder/subsubpage.md", $path);

		// with trailing slash
		$path = DocumentationService::find_page('DocumentationViewerTests', array('subfolder', 'subsubfolder', 'subsubpage'));
		$this->assertEquals(BASE_PATH . "/sapphiredocs/tests/docs/en/subfolder/subsubfolder/subsubpage.md", $path);
	}
	
	function testGetPagesFromFolder() {
		$pages = DocumentationService::get_pages_from_folder(BASE_PATH . '/sapphiredocs/tests/docs/en/');

		$this->assertContains('index', $pages->column('Filename'), 'The tests/docs/en folder should contain a index file');
		$this->assertContains('subfolder', $pages->column('Filename'), 'The tests/docs/en folder should contain a subfolder called subfolder');
		$this->assertContains('test', $pages->column('Filename'), 'The tests/docs/en folder should contain a test file');
		$this->assertNotContains('_images', $pages->column('Filename'), 'It should not include hidden files');
		$this->assertNotContains('.svn', $pages->column('Filename'), 'It should not include hidden files');
		
		// test the order of pages
		$pages = DocumentationService::get_pages_from_folder(BASE_PATH . '/sapphiredocs/tests/docs/en/sort');

		$this->assertEquals(
			array('1 basic', '2 intermediate', '3 advanced', '10 some page', '21 another page'),
			$pages->column('Title')
		);
	}

	
	function testGetPagesFromFolderRecursive() {
		$pages = DocumentationService::get_pages_from_folder(BASE_PATH . '/sapphiredocs/tests/docs-recursive/en/');
		
		// check to see all the pages are found, we don't care about order
		$this->assertEquals($pages->Count(), 9);

		$pages = $pages->column('Title');
		
		foreach(array('Index', 'Subfolder testfile', 'Subsubfolder testfile', 'Testfile') as $expected) {
			$this->assertContains($expected, $pages);
		}
	}
}