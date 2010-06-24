<?php

/**
 * Some of these tests are simply checking that pages load. They should not assume
 * somethings working.
 *
 * @package sapphiredocs
 */

class DocumentationViewerTests extends FunctionalTest {

	static $fixture_file = 'sapphiredocs/tests/DocumentTests.yml';
	
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
			'Documentation',
			'Documentation',
			'.hidden' // don't display something without a title
		);
		
		foreach($names as $key => $value) {
			$this->assertEquals(DocumentationParser::clean_page_name($value), $should[$key]);
		}
	}
	
	function testDocumentationEntityAccessing() {
		$entity = new DocumentationEntity('docs', '1.0', '../sapphiredocs/tests/docs/', 'My Test');
		
		$this->assertEquals($entity->getTitle(), 'My Test');
		$this->assertEquals($entity->getVersions(), array('1.0'));
		$this->assertEquals($entity->getLanguages(), array('en', 'de'));
		$this->assertEquals($entity->getModuleFolder(), 'docs');
		
		$this->assertTrue($entity->hasVersion('1.0'));
		$this->assertFalse($entity->hasVersion('2.0'));
		
		$this->assertTrue($entity->hasLanguage('en'));
		$this->assertFalse($entity->hasLanguage('fr'));
	}
}