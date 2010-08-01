<?php
/**
 * @package sapphiredocs
 */
class DocumentationParserTest extends SapphireTest {
		
	function testGetPagesFromFolder() {
		$pages = DocumentationParser::get_pages_from_folder(BASE_PATH . '/sapphiredocs/tests/docs/en/');
		$this->assertContains('index', $pages->column('Filename'), 'Index');
		$this->assertContains('subfolder', $pages->column('Filename'), 'Foldername');
		$this->assertContains('test', $pages->column('Filename'), 'Filename');
		$this->assertNotContains('_images', $pages->column('Filename'), 'Ignored files');
	}
	
}