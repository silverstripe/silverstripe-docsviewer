<?php

/**
 * @package docsviewer
 * @subpackage tests
 */
class DocumentationManifestTests extends SapphireTest {

	private $manifest;

	public function setUp() {
		parent::setUp();

		Config::nest();

		// explicitly use dev/docs. Custom paths should be tested separately 
		Config::inst()->update(
			'DocumentationViewer', 'link_base', 'dev/docs'
		);

		// disable automatic module registration so modules don't interfere.
		Config::inst()->update(
			'DocumentationManifest', 'automatic_registration', false
		);

		Config::inst()->remove('DocumentationManifest', 'register_entities');

		Config::inst()->update(
			'DocumentationManifest', 'register_entities', array(
				array(
					'Path' => DOCSVIEWER_PATH . "/tests/docs/",
					'Title' => 'Doc Test',
					'Key' => 'testdocs',
					'Version' => '2.3'
				),
				array(
					'Path' => DOCSVIEWER_PATH . "/tests/docs-v2.4/",
					'Title' => 'Doc Test',
					'Version' => '2.4',
					'Key' => 'testdocs',
					'Stable' => true
				),
				array(
					'Path' => DOCSVIEWER_PATH . "/tests/docs-v3.0/",
					'Title' => 'Doc Test',
					'Key' => 'testdocs',
					'Version' => '3.0'
				)
			)
		);

		$this->manifest = new DocumentationManifest(true);
	}
	
	public function tearDown() {
		parent::tearDown();
		
		Config::unnest();
	}


	/**
	 * Check that the manifest matches what we'd expect.
	 */
	public function testRegenerate() {
		$match = array(
			'de/testdocs/2.3/',
			'de/testdocs/2.3/german/',
			'de/testdocs/2.3/test/',
			'en/testdocs/2.3/',
			'en/testdocs/2.3/sort/',
			'en/testdocs/2.3/subfolder/',
			'en/testdocs/2.3/test/',
			'en/testdocs/2.3/sort/basic/',
			'en/testdocs/2.3/sort/some-page/',
			'en/testdocs/2.3/sort/intermediate/',
			'en/testdocs/2.3/sort/another-page/',
			'en/testdocs/2.3/sort/advanced/',
			'en/testdocs/2.3/subfolder/subpage/',
			'en/testdocs/2.3/subfolder/subsubfolder/',
			'en/testdocs/2.3/subfolder/subsubfolder/subsubpage/',
			'en/testdocs/',
			'en/testdocs/test/',
			'en/testdocs/3.0/',
			'en/testdocs/3.0/changelog/',
			'en/testdocs/3.0/tutorials/',
			'en/testdocs/3.0/empty/'
		);

		$this->assertEquals($match, array_keys($this->manifest->getPages()));
	}

	public function testGetNextPage() {

	}

	public function testGetPreviousPage() {

	}

	public function testGetPage() {

	}

	public function testGenerateBreadcrumbs() {

	}

	public function testGetChildrenFor() {

	}

	public function testGetAllVersions() {

	}

	public function testGetStableVersion() {
		
	}
}
