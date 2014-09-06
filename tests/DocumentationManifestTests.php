<?php

/**
 *
 */
class DocumentationManifestTests extends SapphireTest {

	private $manifest, $pages;

	public function setUpOnce() {
		parent::setUpOnce();
		
		$this->origEnabled = DocumentationService::automatic_registration_enabled();
		DocumentationService::set_automatic_registration(false);
		$this->origModules = DocumentationService::get_registered_entities();
		
		$this->origLinkBase = Config::inst()->get('DocumentationViewer', 'link_base');
		Config::inst()->update('DocumentationViewer', 'link_base', 'dev/docs/');

		foreach($this->origModules as $module) {
			DocumentationService::unregister($module->getFolder());
		}

		// We set 3.0 as current, and test most assertions against 2.4 - to avoid 'current' rewriting issues
		DocumentationService::register("testdocs", DOCSVIEWER_PATH . "/tests/docs/", '2.3');
		DocumentationService::register("testdocs", DOCSVIEWER_PATH . "/tests/docs-v2.4/", '2.4', 'Doc Test', true);
		DocumentationService::register("testdocs", DOCSVIEWER_PATH . "/tests/docs-v3.0/", '3.0', 'Doc Test');


		$this->manifest = new DocumentationManifest(true);
		$this->pages = $this->manifest->getPages();
	}
	
	public function tearDownOnce() {
		parent::tearDownOnce();
		
		DocumentationService::unregister("testdocs");
		DocumentationService::set_automatic_registration($this->origEnabled);

		Config::inst()->update('DocumentationViewer', 'link_base', $this->origLinkBase);
	}

	/**
	 * Check that the manifest matches what we'd expect.
	 */
	public function testRegenerate() {
		$match = array(
			'dev/docs/testdocs/2.3/de/',
			'dev/docs/testdocs/2.3/de/german/',
			'dev/docs/testdocs/2.3/de/test/',
			'dev/docs/testdocs/2.3/en/',
			'dev/docs/testdocs/2.3/en/sort/',
			'dev/docs/testdocs/2.3/en/subfolder/',
			'dev/docs/testdocs/2.3/en/test/',
			'dev/docs/testdocs/2.3/en/sort/basic/',
			'dev/docs/testdocs/2.3/en/sort/some-page/',
			'dev/docs/testdocs/2.3/en/sort/intermediate/',
			'dev/docs/testdocs/2.3/en/sort/another-page/',
			'dev/docs/testdocs/2.3/en/sort/advanced/',
			'dev/docs/testdocs/2.3/en/subfolder/subpage/',
			'dev/docs/testdocs/2.3/en/subfolder/subsubfolder/',
			'dev/docs/testdocs/2.3/en/subfolder/subsubfolder/subsubpage/',
			'dev/docs/testdocs/en/',
			'dev/docs/testdocs/en/test/',
			'dev/docs/testdocs/3.0/en/',
			'dev/docs/testdocs/3.0/en/changelog/',
			'dev/docs/testdocs/3.0/en/tutorials/',
			'dev/docs/testdocs/3.0/en/empty/'
		);

		$this->assertEquals($match, array_keys($this->pages));
	}

	public function testGetNextPage() {

	}

	public function testGetPreviousPage() {

	}

	public function testGetPage() {

	}
}