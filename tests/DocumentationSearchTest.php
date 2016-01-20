<?php

/**
 * @package docsviewer
 * @subpackage tests
 */

class DocumentationSearchTest extends FunctionalTest
{
    public function setUp()
    {
        parent::setUp();

        Config::nest();

        // explicitly use dev/docs. Custom paths should be tested separately 
        Config::inst()->update(
            'DocumentationViewer', 'link_base', 'dev/docs'
        );

        // disable automatic module registration so modules don't interfere.
        Config::inst()->update(
            'DocumentationManifest', 'automatic_registration', false
        );

        Config::inst()->remove('DocumentationManifest', 'register_entities');
        Config::inst()->update('DocumentationSearch', 'enabled', true);
        Config::inst()->update(
            'DocumentationManifest', 'register_entities', array(
                array(
                    'Path' => DOCSVIEWER_PATH . "/tests/docs-search/",
                    'Title' => 'Docs Search Test',            )
            )
        );

        $this->manifest = new DocumentationManifest(true);
    }
    
    public function tearDown()
    {
        parent::tearDown();
        
        Config::unnest();
    }
    
    public function testOpenSearchControllerAccessible()
    {
        $c = new DocumentationOpenSearchController();
        $response = $c->handleRequest(new SS_HTTPRequest('GET', ''), DataModel::inst());
//        $this->assertEquals(404, $response->getStatusCode());
        
        Config::inst()->update('DocumentationSearch', 'enabled', false);

        $response = $c->handleRequest(new SS_HTTPRequest('GET', 'description/'), DataModel::inst());
//        $this->assertEquals(404, $response->getStatusCode());
        
        // test we get a response to the description. The meta data test will 
        // check that the individual fields are valid but we should check urls 
        // are there

        Config::inst()->update('DocumentationSearch', 'enabled', true);

        $response = $c->handleRequest(new SS_HTTPRequest('GET', 'description'), DataModel::inst());
//        $this->assertEquals(200, $response->getStatusCode());
        
        $desc = new SimpleXMLElement($response->getBody());
//        $this->assertEquals(2, count($desc->Url));
    }
}
