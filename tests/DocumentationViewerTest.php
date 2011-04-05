<?php

/**
 * Some of these tests are simply checking that pages load. They should not assume
 * somethings working.
 *
 * @package sapphiredocs
 */

class DocumentationViewerTest extends FunctionalTest {

	static $fixture_file = 'sapphiredocs/tests/DocumentTests.yml';

	protected $autoFollowRedirection = false;
	
	function setUpOnce() {
		parent::setUpOnce();
		
		$this->origEnabled = DocumentationService::automatic_registration_enabled();
		DocumentationService::set_automatic_registration(false);
		$this->origModules = DocumentationService::get_registered_modules();
		$this->origLinkBase = DocumentationViewer::get_link_base();
		DocumentationViewer::set_link_base('dev/docs/');
		foreach($this->origModules as $module) {
			DocumentationService::unregister($module->getModuleFolder());
		}
		
		// We set 3.0 as current, and test most assertions against 2.4 - to avoid 'current' rewriting issues
		DocumentationService::register("DocumentationViewerTests", BASE_PATH . "/sapphiredocs/tests/docs/", '2.4');
		DocumentationService::register("DocumentationViewerTests", BASE_PATH . "/sapphiredocs/tests/docs-2/", '2.3');
		DocumentationService::register("DocumentationViewerTests", BASE_PATH . "/sapphiredocs/tests/docs-3/", '3.0');
	}
	
	function tearDownOnce() {
		parent::tearDownOnce();
		
		DocumentationService::unregister("DocumentationViewerTests");
		DocumentationService::set_automatic_registration($this->origEnabled);
		DocumentationViewer::set_link_base($this->origLinkBase);
	}
	
	// TODO Works with phpunit executable, but not with sake. 
	// Also works in actual URL routing, just not in tests...
	// function testLocationExists() {
	// 	$response = $this->get('DocumentationViewerTests/en/2.4/');
	// 	$this->assertEquals($response->getStatusCode(), 200, 'Existing base folder');
	// 	
	// 	$response = $this->get('DocumentationViewerTests/en/2.4/subfolder');
	// 	$this->assertEquals($response->getStatusCode(), 200, 'Existing subfolder');
	// 	
	// 	$response = $this->get('DocumentationViewerTests/en/2.4/nonexistant-subfolder');
	// 	$this->assertEquals($response->getStatusCode(), 404, 'Nonexistant subfolder');
	// 	
	// 	$response = $this->get('DocumentationViewerTests/en/2.4/nonexistant-file.txt');
	// 	$this->assertEquals($response->getStatusCode(), 404, 'Nonexistant file');
	// 	
	// 	$response = $this->get('DocumentationViewerTests/en/2.4/test');
	// 	$this->assertEquals($response->getStatusCode(), 200, 'Existing file');
	// }
	
	function testGetModulePagesShort() {
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.4/subfolder/'));
		$pages = $v->getModulePages();
		
		$arr = $pages->toArray();
		$page = $arr[2];
		
		$this->assertEquals('Subfolder', $page->Title);
	}
	
	function testGetModulePages() {
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.4/subfolder/'));
		$pages = $v->getModulePages();
		$this->assertEquals(
			array('sort/', 'subfolder/', 'test.md'),
			$pages->column('Filename')
		);
		$this->assertEquals(
			array('link','current', 'link'),
			$pages->column('LinkingMode')
		);
		
		foreach($pages as $page) {
			$page->setVersion('2.4');
		}
		
		$links = $pages->column('Link');
		
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.4/sort/', $links[0]);
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.4/subfolder/', $links[1]);
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.4/test', $links[2]);
		
		// Children
		$pagesArr = $pages->toArray();
		$child1 = $pagesArr[1];
	
		$this->assertFalse($child1->Children);
		$child2 = $pagesArr[2];
		
		$this->assertInstanceOf('DataObjectSet', $child2->Children);
	
		$this->assertEquals(
			array('subfolder/subpage.md', 'subfolder/subsubfolder/'),
			$child2->Children->column('Filename')
		);
	
		$children = $child2->Children;
		
		foreach($children as $child) {
			$child->setVersion('2.4');
		}
		
		$child2Links = $children->column('Link');
		$subpage = $children->First();
	
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.4/subfolder/subpage', $child2Links[0]);
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.4/subfolder/subsubfolder/', $child2Links[1]);
	}
	
	function testCurrentRedirection() {
		$response = $this->get('dev/docs/DocumentationViewerTests/en/3.0/test');
	
		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals(
			Director::absoluteBaseURL() . 'dev/docs/DocumentationViewerTests/en/test/',
			$response->getHeader('Location'),
			'Redirection to current on page'
		);
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/3.0');
		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals(
			Director::absoluteBaseURL() . 'dev/docs/DocumentationViewerTests/en/',
			$response->getHeader('Location'),
			'Redirection to current on index'
		);
		
		$response = $this->get('dev/docs/DocumentationViewerTests/en/2.3');
		$this->assertEquals(200, $response->getStatusCode(), 'No redirection on older versions');
	}
	
	function testUrlParsing() {
		// Module index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.3/test'));
		$this->assertEquals('2.3', $v->getVersion());
		$this->assertEquals('en', $v->getLang());
		$this->assertEquals('DocumentationViewerTests', $v->module);
		$this->assertEquals(array('test'), $v->Remaining);
	
		// Module index without version and language. Should pick up the defaults
		$v2 = new DocumentationViewer();
		$response = $v2->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/test'));
	
		$this->assertEquals('3.0', $v2->getVersion());
		$this->assertEquals('en', $v2->getLang());
		$this->assertEquals('DocumentationViewerTests', $v2->module);
		$this->assertEquals(array('test'), $v2->Remaining);
	
		// Overall index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', ''));
		$this->assertEquals('', $v->getVersion());
		$this->assertEquals('en', $v->getLang());
		$this->assertEquals('', $v->module);
		$this->assertEquals(array(), $v->Remaining);
	}
	
	function testBreadcrumbs() {
		// Module index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.4'));
		$crumbs = $v->getBreadcrumbs();
		
		$this->assertEquals(1, $crumbs->Count());
		$crumbLinks = $crumbs->column('Link');
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.4/', $crumbLinks[0]);
		
		// Subfolder index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.4/subfolder/'));
		$crumbs = $v->getBreadcrumbs();
		$this->assertEquals(2, $crumbs->Count());
		$crumbLinks = $crumbs->column('Link');
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.4/', $crumbLinks[0]);
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.4/subfolder/', $crumbLinks[1]);
		
		// Subfolder page
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'DocumentationViewerTests/en/2.4/subfolder/subpage'));
		$crumbs = $v->getBreadcrumbs();
		$this->assertEquals(3, $crumbs->Count());
		$crumbLinks = $crumbs->column('Link');
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.4/', $crumbLinks[0]);
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.4/subfolder/', $crumbLinks[1]);
		$this->assertStringEndsWith('DocumentationViewerTests/en/2.4/subfolder/subpage/', $crumbLinks[2]);
	}
	
	function testRouting() {
		$response = $this->get('dev/docs/DocumentationViewerTests/en/2.4/test');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertContains('english test', $response->getBody(), 'Toplevel content page');
	}
	
	// function testGetPage() {
	// 	$v = new DocumentationViewer();
	// 	$v->handleRequest(new SS_HTTPRequest('GET', '2.4/en/cms'));
	// 	$p = $v->getPage();
	// 	$this->assertType('DocumentationPage', $p);
	// 	$this->assertEquals('/', $p->getRelativePath());
	// 	$this->assertEquals('en', $p->getLang());
	// 	$this->assertEquals('2.4', $p->getVersion());
	// }
	
}