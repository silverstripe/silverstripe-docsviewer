<?php
namespace SilverStripe\DocsViewer\Tests;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\DocsViewer\DocumentationPermalinks;
use SilverStripe\DocsViewer\Controllers\DocumentationViewer;


/**
 * @package docsviewer
 * @subpackage tests
 */
class DocumentationPermalinksTest extends FunctionalTest
{
    public function testSavingAndAccessingMapping()
    {
        // basic test
        DocumentationPermalinks::add(
            array(
            'foo' => 'en/framework/subfolder/foo',
            'bar' => 'en/cms/bar'
            )
        );
        
        $this->assertEquals(
            'en/framework/subfolder/foo',
            DocumentationPermalinks::map('foo')
        );

        $this->assertEquals(
            'en/cms/bar',
            DocumentationPermalinks::map('bar')
        );
    }
    
    /**
     * Tests to make sure short codes get translated to full paths.
     */
    public function testRedirectingMapping()
    {
        DocumentationPermalinks::add(
            array(
            'foo' => 'en/framework/subfolder/foo',
            'bar' => 'en/cms/bar'
            )
        );
        
        $this->autoFollowRedirection = false;
        
        $v = new DocumentationViewer();
        $request = new HTTPRequest('GET', 'foo');
        $request->setSession(Injector::inst()->create(Session::class, array()));
        $response = $v->handleRequest($request);
        
        $this->assertEquals('301', $response->getStatusCode());
        $this->assertContains('en/framework/subfolder/foo', $response->getHeader('Location'));
    }
}
