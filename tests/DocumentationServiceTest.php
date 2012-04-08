<?php

/**
 * @package docsviewer
 * @subpackage tests
 */

class DocumentationServiceTest extends SapphireTest {

	function testGetPagesFromFolder() {
		$entity = DocumentationService::register('testdocs', DOCSVIEWER_PATH . '/tests/docs/');
		$pages = DocumentationService::get_pages_from_folder($entity);
		
		$this->assertContains('index.md', $pages->column('Filename'), 'The tests/docs/en folder should contain a index file');
		$this->assertContains('subfolder/', $pages->column('Filename'), 'The tests/docs/en folder should contain a subfolder called subfolder');
		$this->assertContains('test.md', $pages->column('Filename'), 'The tests/docs/en folder should contain a test file');
		$this->assertNotContains('_images', $pages->column('Filename'), 'It should not include hidden files');
		$this->assertNotContains('.svn', $pages->column('Filename'), 'It should not include hidden files');
		
		// test the order of pages
		$pages = DocumentationService::get_pages_from_folder($entity, 'sort');

		$this->assertEquals(
			array('Basic', 'Intermediate', 'Advanced', 'Some page', 'Another page'),
			$pages->column('Title')
		);
	}
	
	
	function testGetPagesFromFolderRecursive() {	
		$entity = DocumentationService::register('testdocsrecursive', DOCSVIEWER_PATH . '/tests/docs-recursive/');
		
		$pages = DocumentationService::get_pages_from_folder($entity, null, true);
		
		// check to see all the pages are found, we don't care about order
		$this->assertEquals($pages->Count(), 9);
		
		$pages = $pages->column('Title');
		
		foreach(array('Index', 'SubFolder TestFile', 'SubSubFolder TestFile', 'TestFile') as $expected) {
			$this->assertContains($expected, $pages);
		}
	}
	
	function testFindPath() {
		DocumentationService::register("DocumentationViewerTests", DOCSVIEWER_PATH . "/tests/docs/");
		
		// file
		$path = DocumentationService::find_page('DocumentationViewerTests', array('test'));
		$this->assertEquals(DOCSVIEWER_PATH . "/tests/docs/en/test.md", $path);

		// the home page. The path finder should go to the index.md file in the default language
		$path = DocumentationService::find_page('DocumentationViewerTests', array(''));
		$this->assertEquals(DOCSVIEWER_PATH . "/tests/docs/en/index.md", $path);

		// second level
		$path = DocumentationService::find_page('DocumentationViewerTests', array('subfolder', 'subpage'));
		$this->assertEquals(DOCSVIEWER_PATH . "/tests/docs/en/subfolder/subpage.md", $path);
		
		// subsubfolder has no index file. It should fail instead the viewer should pick up on this
		// and display the listing of the folder
		$path = DocumentationService::find_page('DocumentationViewerTests', array('subfolder', 'subsubfolder'));
		$this->assertFalse($path);
		
		// third level
		$path = DocumentationService::find_page('DocumentationViewerTests', array('subfolder', 'subsubfolder', 'subsubpage'));
		$this->assertEquals(DOCSVIEWER_PATH . "/tests/docs/en/subfolder/subsubfolder/subsubpage.md", $path);

		// with trailing slash
		$path = DocumentationService::find_page('DocumentationViewerTests', array('subfolder', 'subsubfolder', 'subsubpage'));
		$this->assertEquals(DOCSVIEWER_PATH . "/tests/docs/en/subfolder/subsubfolder/subsubpage.md", $path);
	}
	
	
	function testCleanPageNames() {
		$names = array(
			'documentation-Page',
			'documentation_Page',
			'documentation.md',
			'documentation.pdf',
			'documentation.file.txt',
			'.hidden'
		);
		
		$should = array(
			'Documentation Page',
			'Documentation Page',
			'Documentation',
			'Documentation.pdf', // do not remove an extension we don't know
			'Documentation.file', // .txt we do know about
			'.hidden' // don't display something without a title
		);
		
		foreach($names as $key => $value) {
			$this->assertEquals(DocumentationService::clean_page_name($value), $should[$key]);
		}
	}
	
	function testIsValidExtension() {
		$this->assertTrue(DocumentationService::is_valid_extension('md'));
		$this->assertTrue(DocumentationService::is_valid_extension('markdown'));
		$this->assertTrue(DocumentationService::is_valid_extension('MD'));
		$this->assertTrue(DocumentationService::is_valid_extension('MARKDOWN'));
		
		$this->assertFalse(DocumentationService::is_valid_extension('.markd'));
		$this->assertFalse(DocumentationService::is_valid_extension('.exe'));
		
		// require an extension as internally we check for extension, not using
		// one could cause issues.
		$this->assertFalse(DocumentationService::is_valid_extension(''));
	}
}