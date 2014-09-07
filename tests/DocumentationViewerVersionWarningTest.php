<?php

class DocumentationViewerVersionWarningTest extends SapphireTest {
	
	protected $autoFollowRedirection = false;
	
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
		DocumentationService::register("DocumentationViewerTests", DOCSVIEWER_PATH . "/tests/docs/", '2.3');
		DocumentationService::register("DocumentationViewerTests", DOCSVIEWER_PATH . "/tests/docs-v2.4/", '2.4', 'Doc Test', true);
		DocumentationService::register("DocumentationViewerTests", DOCSVIEWER_PATH . "/tests/docs-v3.0/", '3.0', 'Doc Test');
		
		DocumentationService::register("DocumentationViewerAltModule1", DOCSVIEWER_PATH . "/tests/docs-parser/", '1.0');
		DocumentationService::register("DocumentationViewerAltModule2", DOCSVIEWER_PATH . "/tests/docs-search/", '1.0');
	}
	
	public function tearDownOnce() {
		parent::tearDownOnce();
		
		DocumentationService::unregister("DocumentationViewerTests");
		DocumentationService::set_automatic_registration($this->origEnabled);

		Config::inst()->update('DocumentationViewer', 'link_base', $this->origLinkBase);
	}

	public function testVersionWarning() {
		$v = new DocumentationViewer();
		
		// the current version is set to 2.4, no notice should be shown on that page
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.4'), DataModel::inst());
		$this->assertFalse($v->VersionWarning());
		
		// 2.3 is an older release, hitting that should return us an outdated flag
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.3'), DataModel::inst());
		$warn = $v->VersionWarning();
		
		$this->assertTrue($warn->OutdatedRelease);
		$this->assertNull($warn->FutureRelease);
		
		// 3.0 is a future release
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/3.0'), DataModel::inst());
		$warn = $v->VersionWarning();
		
		$this->assertNull($warn->OutdatedRelease);
		$this->assertTrue($warn->FutureRelease);
	}
}