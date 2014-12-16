<?php

/**
 * @package docsviewer
 * @subpackage tests
 */
class DocumentationPermalinksTest extends FunctionalTest {

	public function testSavingAndAccessingMapping() {
		// basic test
		DocumentationPermalinks::add(array(
			'foo' => 'en/framework/subfolder/foo',
			'bar' => 'en/cms/bar'
		));
		
		$this->assertEquals('en/framework/subfolder/foo', 
			DocumentationPermalinks::map('foo')
		);

		$this->assertEquals('en/cms/bar', 
			DocumentationPermalinks::map('bar')
		);
	}
	
	/**
	 * Tests to make sure short codes get translated to full paths.
	 *
	 */
	public function testRedirectingMapping() {
		DocumentationPermalinks::add(array(
			'foo' => 'en/framework/subfolder/foo',
			'bar' => 'en/cms/bar'
		));
		
		$this->autoFollowRedirection = false;
		
		$v = new DocumentationViewer();
		$response = $v->handleRequest(new SS_HTTPRequest('GET', 'foo'), DataModel::inst());
		
		$this->assertEquals('301', $response->getStatusCode());
		$this->assertContains('en/framework/subfolder/foo', $response->getHeader('Location'));
	}
}
