<?php
namespace SilverStripe\DocsViewer\Tests;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\DocsViewer\DocumentationManifest;
use SilverStripe\DocsViewer\Controllers\DocumentationViewer;
use SilverStripe\View\SSViewer;


/**
 * Some of these tests are simply checking that pages load. They should not assume
 * somethings working.
 *
 * @package    docsviewer
 * @subpackage tests
 */

class DocumentationViewerTest extends FunctionalTest
{
    protected $autoFollowRedirection = false;

    protected $manifest;

    public function setUp()
    {
        parent::setUp();

        Config::nest();

        // explicitly use dev/docs. Custom paths should be tested separately
        Config::inst()->update(
            DocumentationViewer::class,
            'link_base',
            'dev/docs/'
        );

        // disable automatic module registration so modules don't interfere.
        Config::inst()->update(
            DocumentationManifest::class,
            'automatic_registration',
            false
        );

        Config::inst()->remove(DocumentationManifest::class, 'register_entities');

        Config::inst()->update(
            DocumentationManifest::class,
            'register_entities',
            array(
                array(
                    'Path' => dirname(__FILE__) ."/docs/",
                    'Title' => 'Doc Test',
                    'Version' => '2.3'
                ),
                array(
                    'Path' => dirname(__FILE__) ."/docs-v2.4/",
                    'Title' => 'Doc Test',
                    'Version' => '2.4',
                    'Stable' => true
                ),
                array(
                    'Path' => dirname(__FILE__) ."/docs-v3.0/",
                    'Title' => 'Doc Test',
                    'Version' => '3.0'
                ),
                array(
                    'Path' => dirname(__FILE__) ."/docs-parser/",
                    'Title' => 'DocumentationViewerAltModule1'
                ),
                array(
                    'Path' => dirname(__FILE__) ."/docs-manifest/",
                    'Title' => 'DocumentationViewerAltModule2'
                )
            )
        );

        Config::inst()->update(SSViewer::class, 'theme_enabled', false);

        $this->manifest = new DocumentationManifest(true);
    }

    public function tearDown()
    {
        parent::tearDown();

        @Config::unnest();
    }

    /**
     * This tests that all the locations will exist if we access it via the urls.
     */
    public function testLocationsExists()
    {
        $this->autoFollowRedirection = false;

        $response = $this->get('dev/docs/en/doc_test/2.3/subfolder/');
        $this->assertEquals($response->getStatusCode(), 200, 'Existing base folder');

        $response = $this->get('dev/docs/en/doc_test/2.3/subfolder/subsubfolder/');
        $this->assertEquals($response->getStatusCode(), 200, 'Existing base folder');

        $response = $this->get('dev/docs/en/doc_test/2.3/subfolder/subsubfolder/subsubpage/');
        $this->assertEquals($response->getStatusCode(), 200, 'Existing base folder');

        $response = $this->get('dev/docs/en/');
        $this->assertEquals($response->getStatusCode(), 200, 'Lists the home index');

        $response = $this->get('dev/docs/');
        $this->assertEquals($response->getStatusCode(), 302, 'Go to english view');


        $response = $this->get('dev/docs/en/doc_test/3.0/empty.md');
        $this->assertEquals(301, $response->getStatusCode(), 'Direct markdown links also work. They should redirect to /empty/');


        // 2.4 is the stable release. Not in the URL
        $response = $this->get('dev/docs/en/doc_test/2.4');
        $this->assertEquals($response->getStatusCode(), 200, 'Existing base folder');
        $this->assertContains('english test', $response->getBody(), 'Toplevel content page');

        // accessing base redirects to the version with the version number.
        $response = $this->get('dev/docs/en/doc_test/');
        $this->assertEquals($response->getStatusCode(), 301, 'Existing base folder redirects to with version');

        $response = $this->get('dev/docs/en/doc_test/3.0/');
        $this->assertEquals($response->getStatusCode(), 200, 'Existing base folder');

        $response = $this->get('dev/docs/en/doc_test/2.3/nonexistant-subfolder');
        $this->assertEquals($response->getStatusCode(), 404, 'Nonexistant subfolder');

        $response = $this->get('dev/docs/en/doc_test/2.3/nonexistant-file.txt');
        $this->assertEquals($response->getStatusCode(), 301, 'Nonexistant file');

        $response = $this->get('dev/docs/en/doc_test/2.3/nonexistant-file/');
        $this->assertEquals($response->getStatusCode(), 404, 'Nonexistant file');

        $response = $this->get('dev/docs/en/doc_test/2.3/test');
        $this->assertEquals($response->getStatusCode(), 200, 'Existing file');

        $response = $this->get('dev/docs/en/doc_test/3.0/empty?foo');
        $this->assertEquals(200, $response->getStatusCode(), 'Existing page');

        $response = $this->get('dev/docs/en/doc_test/3.0/empty/');
        $this->assertEquals($response->getStatusCode(), 200, 'Existing page');

        $response = $this->get('dev/docs/en/doc_test/3.0/test');
        $this->assertEquals($response->getStatusCode(), 404, 'Missing page');

        $response = $this->get('dev/docs/en/doc_test/3.0/test.md');
        $this->assertEquals($response->getStatusCode(), 301, 'Missing page');

        $response = $this->get('dev/docs/en/doc_test/3.0/test/');
        $this->assertEquals($response->getStatusCode(), 404, 'Missing page');

        $response = $this->get('dev/docs/dk/');
        $this->assertEquals($response->getStatusCode(), 404, 'Access a language that doesn\'t exist');
    }


    public function testGetMenu()
    {
        $v = new DocumentationViewer();
        $session = Injector::inst()->create(Session::class, array());
        // check with children
        $request = new HTTPRequest('GET', 'en/doc_test/2.3/');
        $request->setSession($session);
        $response = $v->handleRequest($request);

        $expected = array(
            Director::baseURL() . 'dev/docs/en/doc_test/2.3/sort/' => 'Sort',
            Director::baseURL() . 'dev/docs/en/doc_test/2.3/subfolder/' => 'Subfolder',
            Director::baseURL() . 'dev/docs/en/doc_test/2.3/test/' => 'Test'
        );

        $actual = $v->getMenu()->first()->Children->map('Link', 'Title')->toArray();
        $this->assertEquals($expected, $actual);


        $request = new HTTPRequest('GET', 'en/doc_test/2.4/');
        $request->setSession($session);
        $response = $v->handleRequest($request);
        $this->assertEquals('current', $v->getMenu()->first()->LinkingMode);

        // 2.4 stable release has 1 child page (not including index)
        $this->assertEquals(1, $v->getMenu()->first()->Children->count());

        // menu should contain all the english entities
        $expected = array(
            Director::baseURL() . 'dev/docs/en/doc_test/2.4/' => 'Doc Test',
            Director::baseURL() . 'dev/docs/en/documentationvieweraltmodule1/' => 'DocumentationViewerAltModule1',
            Director::baseURL() . 'dev/docs/en/documentationvieweraltmodule2/' => 'DocumentationViewerAltModule2'
        );

        $this->assertEquals($expected, $v->getMenu()->map('Link', 'Title')->toArray());
    }



    public function testGetLanguage()
    {
        $v = new DocumentationViewer();
        $session = Injector::inst()->create(Session::class, array());
        $request = new HTTPRequest('GET', 'en/doc_test/2.3/');
        $request->setSession($session);
        $response = $v->handleRequest($request);

        $this->assertEquals('en', $v->getLanguage());

        $request = new HTTPRequest('GET', 'en/doc_test/2.3/subfolder/subsubfolder/subsubpage/');
        $request->setSession($session);
        $response = $v->handleRequest($request);
        $this->assertEquals('en', $v->getLanguage());
    }


    public function testAccessingAll()
    {
        $response = $this->get('dev/docs/en/all/');

        // should response with the documentation index
        $this->assertEquals(200, $response->getStatusCode());

        $items = $this->cssParser()->getBySelector('#documentation_index');
        $this->assertNotEmpty($items);

        // should also have a DE version of the page
        $response = $this->get('dev/docs/de/all/');

        // should response with the documentation index
        $this->assertEquals(200, $response->getStatusCode());

        $items = $this->cssParser()->getBySelector('#documentation_index');
        $this->assertNotEmpty($items);

        // accessing a language that doesn't exist should throw a 404
        $response = $this->get('dev/docs/fu/all/');
        $this->assertEquals(404, $response->getStatusCode());

        // accessing all without a language should fail
        $response = $this->get('dev/docs/all/');
        $this->assertEquals(404, $response->getStatusCode());
    }


    public function testRedirectStripExtension()
    {
        // get url with .md extension
        $response = $this->get('dev/docs/en/doc_test/3.0/tutorials.md');

        // response should be a 301 redirect
        $this->assertEquals(301, $response->getStatusCode());

        // redirect should have been to the absolute url minus the .md extension
        $this->assertEquals(Director::absoluteURL('dev/docs/en/doc_test/3.0/tutorials/'), $response->getHeader('Location'));
    }

    public function testCanonicalUrlIsIncludedInLayout()
    {
        $response = $this->get('dev/docs/en/doc_test/2.3/subfolder/subsubfolder/subsubpage');

        $this->assertEquals(200, $response->getStatusCode());

        $expectedUrl = Director::absoluteURL('dev/docs/en/subfolder/subsubfolder/subsubpage/');
        $this->assertContains('<link rel="canonical" href="' . $expectedUrl . '" />', (string) $response->getBody());
    }

    public function testCanonicalUrlIsEmptyWhenNoPageExists()
    {
        $viewer = new DocumentationViewer;
        $this->assertSame('', $viewer->getCanonicalUrl());
    }
}
