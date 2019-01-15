<?php
namespace SilverStripe\DocsViewer\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\DocsViewer\DocumentationManifest;
use SilverStripe\DocsViewer\Controllers\DocumentationViewer;


/**
 * @package docsviewer
 * @subpackage tests
 */
class DocumentationManifestTests extends SapphireTest
{
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
                ),
                array(
                    'Path' => dirname(__FILE__) ."/docs-manifest/",
                    'Title' => 'Manifest',
                    'Key' => 'manifest'
                )
            )
        );

        $this->manifest = new DocumentationManifest(true);
    }

    public function tearDown()
    {
        parent::tearDown();

        Config::unnest();
    }


    /**
     * Check that the manifest matches what we'd expect.
     */
    public function testRegenerate()
    {
        $match = array(
            'de/testdocs/2.3/',
            'de/testdocs/2.3/german/',
            'de/testdocs/2.3/test/',
            'en/testdocs/2.3/',
            'en/testdocs/2.3/sort/',
            'en/testdocs/2.3/sort/basic/',
            'en/testdocs/2.3/sort/intermediate/',
            'en/testdocs/2.3/sort/advanced/',
            'en/testdocs/2.3/sort/some-page/',
            'en/testdocs/2.3/sort/another-page/',
            'en/testdocs/2.3/subfolder/',
            'en/testdocs/2.3/subfolder/subpage/',
            'en/testdocs/2.3/subfolder/subsubfolder/',
            'en/testdocs/2.3/subfolder/subsubfolder/subsubpage/',
            'en/testdocs/2.3/test/',
            'en/testdocs/2.4/',
            'en/testdocs/2.4/test/',
            'en/testdocs/3.0/',
            'en/testdocs/3.0/changelog/',
            'en/testdocs/3.0/tutorials/',
            'en/testdocs/3.0/empty/',
            'en/manifest/',
            'en/manifest/guide/',
            'en/manifest/guide/test/',
            'en/manifest/second-guide/',
            'en/manifest/second-guide/afile/'
        );

        $this->assertEquals($match, array_keys($this->manifest->getPages()));
    }

    public function testGetNextPage()
    {
        // get next page at the end of one subfolder goes back up to the top
        // most directory
        $this->assertStringEndsWith(
            '2.3/test/',
            $this->manifest->getNextPage(
                dirname(__FILE__) .'/docs/en/subfolder/subsubfolder/subsubpage.md',
                dirname(__FILE__) .'/docs/en/'
            )->Link
        );

        // after sorting, 2 is shown.
        $this->assertContains(
            '/intermediate/',
            $this->manifest->getNextPage(
                dirname(__FILE__) .'/docs/en/sort/01-basic.md',
                dirname(__FILE__) .'/docs/en/'
            )->Link
        );


        // next gets the following URL
        $this->assertContains(
            '/test/',
            $this->manifest->getNextPage(
                dirname(__FILE__) .'/docs-v2.4/en/index.md',
                dirname(__FILE__) .'/docs-v2.4/en/'
            )->Link
        );


        // last folder in a entity does not leak
        $this->assertNull(
            $this->manifest->getNextPage(
                dirname(__FILE__) .'/docs/en/test.md',
                dirname(__FILE__) .'/docs/en/'
            )
        );
    }

    public function testGetPreviousPage()
    {
        // goes right into subfolders
        $this->assertContains(
            'subfolder/subsubfolder/subsubpage',
            $this->manifest->getPreviousPage(
                dirname(__FILE__) .'/docs/en/test.md',
                dirname(__FILE__) .'/docs/en/'
            )->Link
        );

        // does not leak between entities
        $this->assertNull(
            $this->manifest->getPreviousPage(
                dirname(__FILE__) .'/docs/en/index.md',
                dirname(__FILE__) .'/docs/en/'
            )
        );

        // does not leak between entities
        $this->assertNull(
            $this->manifest->getPreviousPage(
                dirname(__FILE__) . '/docs/en/index.md',
                dirname(__FILE__) .'/docs/en/'
            )
        );
    }

    public function testGetPage()
    {
        $this->markTestIncomplete();
    }

    public function testGenerateBreadcrumbs()
    {
        $this->markTestIncomplete();
    }

    public function testGetChildrenFor()
    {
        $expected = array(
            array('Title' => 'Test', 'LinkingMode' => 'link')
        );

        $this->assertDOSContains(
            $expected,
            $this->manifest->getChildrenFor(
                dirname(__FILE__) .'/docs/en/'
            )
        );

        $expected = array(
            array('Title' => 'ChangeLog', 'LinkingMode' => 'current'),
            array('Title' => 'Tutorials'),
            array('Title' => 'Empty')
        );

        $this->assertDOSContains(
            $expected,
            $this->manifest->getChildrenFor(
                dirname(__FILE__) .'/docs-v3.0/en/',
                dirname(__FILE__) .'/docs-v3.0/en/ChangeLog.md'
            )
        );
    }

    public function testGetAllVersions()
    {
        $expected = array(
            '2.3' => '2.3',
            '2.4' => '2.4',
            '3.0' => '3.0',
            '0.0' => 'Master'
        );

        $this->assertEquals($expected, $this->manifest->getAllVersions());
    }

    public function testGetAllEntityVersions()
    {
        $expected = array(
            'Version' => '2.3',
            'Version' => '2.4',
            'Version' => '3.0'
        );

        $entity = $this->manifest->getEntities()->find('Language', 'en');

        $this->assertEquals(3, $this->manifest->getAllVersionsOfEntity($entity)->count());

        $entity = $this->manifest->getEntities()->find('Language', 'de');

        $this->assertEquals(1, $this->manifest->getAllVersionsOfEntity($entity)->count());
    }

    public function testGetStableVersion()
    {
        $this->markTestIncomplete();
    }
}
