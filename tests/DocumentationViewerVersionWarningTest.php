<?php
namespace SilverStripe\DocsViewer\Tests;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\DocsViewer\DocumentationManifest;
use SilverStripe\DocsViewer\Controllers\DocumentationViewer;


/**
 * @package docsviewer
 * @subpackage tests
 */
class DocumentationViewerVersionWarningTest extends SapphireTest
{
    protected $autoFollowRedirection = false;
    
    private $manifest;

    public function setUp()
    {
        parent::setUp();

        Config::nest();

        // explicitly use dev/docs. Custom paths should be tested separately
        Config::inst()->update(
            DocumentationViewer::class,
            'link_base',
            'dev/docs'
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
                    'Key' => 'testdocs',
                    'Version' => '2.3'
                ),
                array(
                    'Path' => dirname(__FILE__) ."/docs-v2.4/",
                    'Title' => 'Doc Test',
                    'Version' => '2.4',
                    'Key' => 'testdocs',
                    'Stable' => true
                ),
                array(
                    'Path' => dirname(__FILE__) ."/docs-v3.0/",
                    'Title' => 'Doc Test',
                    'Key' => 'testdocs',
                    'Version' => '3.0'
                )
            )
        );

        $this->manifest = new DocumentationManifest(true);
    }
    
    public function tearDown()
    {
        parent::tearDown();
        
        @Config::unnest();
    }

    public function testVersionWarning()
    {
        $v = new DocumentationViewer();
        $session = Injector::inst()->create(Session::class, array());
        
        // the current version is set to 2.4, no notice should be shown on that page
        $request = new HTTPRequest('GET', 'en/testdocs/');
        $request->setSession($session);
        $response = $v->handleRequest($request);
        //        $this->assertFalse($v->VersionWarning());

        
        // 2.3 is an older release, hitting that should return us an outdated flag
        $request = new HTTPRequest('GET', 'en/testdocs/2.3/');
        $request->setSession($session);
        $response = $v->handleRequest($request);
        $warn = $v->VersionWarning();
        
        //       $this->assertTrue($warn->OutdatedRelease);
        //       $this->assertNull($warn->FutureRelease);
        
        // 3.0 is a future release
        $request = new HTTPRequest('GET', 'en/testdocs/3.0/');
        $request->setSession($session);
        $response = $v->handleRequest($request);
        $warn = $v->VersionWarning();
        
        //        $this->assertNull($warn->OutdatedRelease);
        //        $this->assertTrue($warn->FutureRelease);
    }
}
