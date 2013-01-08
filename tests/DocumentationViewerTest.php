<?php

/**
 * Some of these tests are simply checking that pages load. They should not assume
 * somethings working.
 *
 * @package docsviewer
 * @subpackage tests
 */

class DocumentationViewerTest extends FunctionalTest {

	protected $autoFollowRedirection = false;
	
	function setUpOnce() {
		parent::setUpOnce();
		
		$this->origEnabled = DocumentationService::automatic_registration_enabled();
		DocumentationService::set_automatic_registration(false);
		$this->origModules = DocumentationService::get_registered_entities();
		$this->origLinkBase = DocumentationViewer::get_link_base();
		DocumentationViewer::set_link_base('dev/docs/');
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
	
	function tearDownOnce() {
		parent::tearDownOnce();
		
		DocumentationService::unregister("DocumentationViewerTests");
		DocumentationService::set_automatic_registration($this->origEnabled);
		DocumentationViewer::set_link_base($this->origLinkBase);
	}
	
	/**
	 * This tests that all the locations will exist if we access it via the urls.
	 */
	function testLocationsExists() {
		$response = $this->get('dev/docs/DocumentationViewerTests/en/2.3/subfolder');
		$this->assertEquals($response->getStatusCode(), 200, 'Existing base folder');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/2.4');
		$this->assertEquals($response->getStatusCode(), 200, 'Existing base folder');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/2.4/');		
		$this->assertEquals($response->getStatusCode(), 200, 'Existing base folder');		
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/2.3/nonexistant-subfolder');
		$this->assertEquals($response->getStatusCode(), 404, 'Nonexistant subfolder');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/2.3/nonexistant-file.txt');
		$this->assertEquals($response->getStatusCode(), 404, 'Nonexistant file');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/2.3/test');
		$this->assertEquals($response->getStatusCode(), 200, 'Existing file');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/3.0/empty?foo');
		$this->assertEquals($response->getStatusCode(), 200, 'Existing page');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/3.0/empty.md');
		$this->assertEquals($response->getStatusCode(), 200, 'Existing page');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/3.0/empty/');
		$this->assertEquals($response->getStatusCode(), 200, 'Existing page');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/3.0/test');
		$this->assertEquals($response->getStatusCode(), 404, 'Missing page');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/3.0/test.md');
		$this->assertEquals($response->getStatusCode(), 404, 'Missing page');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/3.0/test/');
		$this->assertEquals($response->getStatusCode(), 404, 'Missing page');
		
		$response = $this->get('dev/docs/en');
		$this->assertEquals($response->getStatusCode(), 404, 'Must include a module');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/dk/');;
		$this->assertEquals($response->getStatusCode(), 404, 'Access a language that doesn\'t exist');
	}
	
	function testRouting() {
		$response = $this->get('dev/docs/DocumentationViewerTests/en/2.4');
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertContains('english test', $response->getBody(), 'Toplevel content page');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/2.4/');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertContains('english test', $response->getBody(), 'Toplevel content page');
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/2.4/index.md');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertContains('english test', $response->getBody(), 'Toplevel content page');
	}
	
	function testGetModulePagesShort() {
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.3/subfolder/'), DataModel::inst());
		$pages = $v->getEntityPages();

		$arr = $pages->toArray();
		
		$page = $arr[2];
		
		$this->assertEquals('Subfolder', $page->Title);
	}
	
	function testGetEntityPages() {
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.3/subfolder/'), DataModel::inst());
		$pages = $v->getEntityPages();
		$this->assertEquals(
			array('sort/', 'subfolder/', 'test.md'),
			$pages->column('Filename')
		);
		$this->assertEquals(
			array('link','current', 'link'),
			$pages->column('LinkingMode')
		);
		
		foreach($pages as $page) {
			$page->setVersion('2.3');
		}
		
		$links = $pages->column('Link');
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.3/sort/', $links[0]);
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.3/subfolder/', $links[1]);
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.3/test', $links[2]);
		
		// Children
		$pagesArr = $pages->toArray();
		$child1 = $pagesArr[1];
	
		$this->assertFalse($child1->Children);
		$child2 = $pagesArr[2];
		
		$this->assertEquals(
			array('subfolder/subpage.md', 'subfolder/subsubfolder/'),
			$child2->Children->column('Filename')
		);
	
		$children = $child2->Children;
		
		foreach($children as $child) {
			$child->setVersion('2.3');
		}
		
		$child2Links = $children->column('Link');
		$subpage = $children->First();
	
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.3/subfolder/subpage', $child2Links[0]);
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.3/subfolder/subsubfolder/', $child2Links[1]);
	}
	
	function testUrlParsing() {
		// Module index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.3/test'), DataModel::inst());
		$this->assertEquals('2.3', $v->getVersion());
		$this->assertEquals('en', $v->getLang());
		$this->assertEquals('DocumentationViewerTests', $v->getEntity()->getTitle());
		$this->assertEquals(array('test'), $v->Remaining);
	
		// Module index without version and language. Should pick up the defaults
		$v2 = new DocumentationViewer();
		$response = $v2->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/test'), DataModel::inst());
	
		$this->assertEquals('2.4', $v2->getVersion());
		$this->assertEquals('en', $v2->getLang());
		$this->assertEquals('DocumentationViewerTests', $v2->getEntity()->getTitle());
		$this->assertEquals(array('test'), $v2->Remaining);
	
		// Overall index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', ''), DataModel::inst());
		$this->assertEquals('', $v->getVersion());
		$this->assertEquals('en', $v->getLang());
		$this->assertEquals('', $v->module);
		$this->assertEquals(array(), $v->Remaining);
	}
	
	function testBreadcrumbs() {
		// Module index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.4'), DataModel::inst());
		$crumbs = $v->getBreadcrumbs();
		$this->assertEquals(1, $crumbs->Count());
		
		// Subfolder index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.4/subfolder/'), DataModel::inst());
		$crumbs = $v->getBreadcrumbs();
		$this->assertEquals(2, $crumbs->Count());
		
		// Subfolder page
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.4/subfolder/subpage'), DataModel::inst());
		$crumbs = $v->getBreadcrumbs();
		$this->assertEquals(3, $crumbs->Count());
	}
	
	function testGetVersion() {
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.4'), DataModel::inst());
		$this->assertEquals('2.4', $v->getVersion());

		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/1'), DataModel::inst());
		$this->assertEquals('1', $v->getVersion());
		
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/3.0'), DataModel::inst());
		$this->assertEquals('3.0', $v->getVersion());		
	}
	
	function testGetEntities() {
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.4'), DataModel::inst());
		
		$pages = $v->getEntities();
		
		$this->assertEquals(3, $pages->Count(), 'Registered 3 entities');
		
		// check to see the links don't have version or pages in them
		foreach($pages as $page) {
			$expected = Controller::join_links('docs', $page->Title, 'en');
			
			$this->assertStringEndsWith($expected, $page->Link);
		}
	}
	
	function testVersionWarning() {
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

	/**
	 * Test that the pages comes back sorted by filename
	 */
	function testGetEntityPagesSortedByFilename() {
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/3.0/'), DataModel::inst());
		$pages = $v->getEntityPages();
		$links = $pages->column('Link');
		$this->assertStringEndsWith('DocumentationViewerTests/en/3.0/ChangeLog', $links[0]);
		$this->assertStringEndsWith('DocumentationViewerTests/en/3.0/Tutorials', $links[1]);
		$this->assertStringEndsWith('DocumentationViewerTests/en/3.0/empty', $links[2]);
	}
}