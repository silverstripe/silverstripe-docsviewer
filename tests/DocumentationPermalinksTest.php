<?php

class DocumentationPermalinksTest extends FunctionalTest {

	function testSavingAndAccessingMapping() {
		// basic test
		DocumentationPermalinks::add(array(
			'foo' => 'current/en/sapphire/subfolder/foo',
			'bar' => 'current/en/cms/bar'
		));
		
		$this->assertEquals('current/en/sapphire/subfolder/foo', DocumentationPermalinks::map('foo'));
		$this->assertEquals('current/en/cms/bar', DocumentationPermalinks::map('bar'));
	}
	
	/**
	 * Tests to make sure short codes get translated to full paths
	 */
	function testRedirectingMapping() {
		// testing the viewer class but clearer here
		DocumentationPermalinks::add(array(
			'foo' => 'current/en/sapphire/subfolder/foo',
			'bar' => 'current/en/cms/bar'
		));
		
		$this->autoFollowRedirection = false;
		
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'foo'), DataModel::inst());
		
		$this->assertEquals('301', $response->getStatusCode());
		$this->assertContains('current/en/sapphire/subfolder/foo', $response->getHeader('Location'));
	}
}
