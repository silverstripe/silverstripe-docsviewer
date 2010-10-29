<?php

/**
 * Some of these tests are simply checking that pages load. They should not assume
 * somethings working.
 *
 * @package sapphiredocs
 */

class DocumentationViewerTests extends FunctionalTest {

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
		// $this->origModules = Documentation::get_registered_modules();
		// foreach($this->origModules as $name => $module) {
		// 	DocumentationService::register($name);
		// }
	}
	
	function testCurrentRedirection() {
		$response = $this->get('dev/docs/3.0/en/DocumentationViewerTests/test');

		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals(
			Director::absoluteBaseURL() . 'dev/docs/current/en/DocumentationViewerTests/test/',
			$response->getHeader('Location'),
			'Redirection to current on page'
		);
		
		$response = $this->get('dev/docs/3.0/en/DocumentationViewerTests/');
		$this->assertEquals(301, $response->getStatusCode());
		$this->assertEquals(
			Director::absoluteBaseURL() . 'dev/docs/current/en/DocumentationViewerTests/',
			$response->getHeader('Location'),
			'Redirection to current on index'
		);
		
		$response = $this->get('dev/docs/2.3/en/DocumentationViewerTests/');
		$this->assertEquals(200, $response->getStatusCode(), 'No redirection on older versions');
	}
	
	function testUrlParsing() {
		// Module index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', '2.3/en/DocumentationViewerTests/test'));
		$this->assertEquals('2.3', $v->getVersion());
		$this->assertEquals('en', $v->getLang());
		$this->assertEquals('DocumentationViewerTests', $v->module);
		$this->assertEquals(array('test'), $v->Remaining);
		
		// Module index without version and language. Should pick up the defaults
		$v2 = new DocumentationViewer();
		$response = $v2->handleRequest(new SS_HTTPRequest('GET', 'en/DocumentationViewerTests/test'));

		$this->assertEquals('3.0', $v2->getVersion());
		$this->assertEquals('en', $v2->getLang());
		$this->assertEquals('DocumentationViewerTests', $v2->module);
		$this->assertEquals(array('test'), $v2->Remaining);

		// Overall index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', ''));
		$this->assertEquals('current', $v->getVersion());
		$this->assertEquals('en', $v->getLang());
		$this->assertEquals('', $v->module);
		$this->assertEquals(array(), $v->Remaining);
	}
	
	function testBreadcrumbs() {
		// Module index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', '2.4/en/DocumentationViewerTests/'));
		$crumbs = $v->getBreadcrumbs();
		
		$this->assertEquals(1, $crumbs->Count());
		$crumbLinks = $crumbs->column('Link');
		$this->assertStringEndsWith('DocumentationViewerTests/', $crumbLinks[0]);
		
		// Subfolder index
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', '2.4/en/DocumentationViewerTests/subfolder/'));
		$crumbs = $v->getBreadcrumbs();
		$this->assertEquals(2, $crumbs->Count());
		$crumbLinks = $crumbs->column('Link');
		$this->assertStringEndsWith('DocumentationViewerTests/', $crumbLinks[0]);
		$this->assertStringEndsWith('DocumentationViewerTests/subfolder/', $crumbLinks[1]);
		
		// Subfolder page
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', '2.4/en/DocumentationViewerTests/subfolder/subpage'));
		$crumbs = $v->getBreadcrumbs();
		$this->assertEquals(3, $crumbs->Count());
		$crumbLinks = $crumbs->column('Link');
		$this->assertStringEndsWith('DocumentationViewerTests/', $crumbLinks[0]);
		$this->assertStringEndsWith('DocumentationViewerTests/subfolder/', $crumbLinks[1]);
		$this->assertStringEndsWith('DocumentationViewerTests/subfolder/subpage/', $crumbLinks[2]);
	}
	
	function testGetModulePages() {
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', '2.4/en/DocumentationViewerTests/subfolder/'));
		$pages = $v->getModulePages();
		$this->assertEquals(
			array('sort', 'subfolder', 'test'),
			$pages->column('Filename')
		);
		$this->assertEquals(
			array('link','current', 'link'),
			$pages->column('LinkingMode')
		);
		$links = $pages->column('Link');

		$this->assertStringEndsWith('2.4/en/DocumentationViewerTests/sort/', $links[0]);
		$this->assertStringEndsWith('2.4/en/DocumentationViewerTests/subfolder/', $links[1]);
		$this->assertStringEndsWith('2.4/en/DocumentationViewerTests/test/', $links[2]);
		
		// Children
		$pagesArr = $pages->toArray();
		
		$child1 = $pagesArr[1];

		$this->assertFalse($child1->Children);
		
		$child2 = $pagesArr[2];

		$this->assertType('DataObjectSet', $child2->Children);
		$this->assertEquals(
			array('subpage', 'subsubfolder'),
			$child2->Children->column('Filename')
		);
		$child2Links = $child2->Children->column('Link');
		$this->assertStringEndsWith('2.4/en/DocumentationViewerTests/subfolder/subpage/', $child2Links[0]);
		$this->assertStringEndsWith('2.4/en/DocumentationViewerTests/subfolder/subsubfolder/', $child2Links[1]);
	}
	
	function testRouting() {
		$response = $this->get('dev/docs/2.4/en/DocumentationViewerTests/test');
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