<?php
namespace SilverStripe\DocsViewer\Tests;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\DocsViewer\DocumentationManifest;
use SilverStripe\DocsViewer\Controllers\DocumentationViewer;
use SilverStripe\DocsViewer\Models\DocumentationEntity;
use SilverStripe\DocsViewer\Models\DocumentationFolder;
use SilverStripe\DocsViewer\Models\DocumentationPage;


/**
 * @package docsviewer
 * @subpackage tests
 */
class DocumentationPageTest extends SapphireTest
{
    protected $entity;

    public function setUp()
    {
        parent::setUp();

        Config::nest();

        $this->entity = new DocumentationEntity('doctest');
        $this->entity->setPath(dirname(__FILE__) .'/docs/en/');
        $this->entity->setVersion('2.4');
        $this->entity->setLanguage('en');

        // explicitly use dev/docs. Custom paths should be tested separately
        Config::inst()->update(DocumentationViewer::class, 'link_base', 'dev/docs/');

        $manifest = new DocumentationManifest(true);
    }

    public function tearDown()
    {
        parent::tearDown();

        @Config::unnest();
    }

    public function testGetLink()
    {
        $page = new DocumentationPage(
            $this->entity,
            'test.md',
            dirname(__FILE__) .'/docs/en/test.md'
        );

        // single layer
        $this->assertEquals(
            Director::baseURL() . 'dev/docs/en/doctest/2.4/test/',
            $page->Link(),
            'The page link should have no extension and have a language'
        );

        $page = new DocumentationFolder(
            $this->entity,
            'sort',
            dirname(__FILE__) .'/docs/en/sort/'
        );

        $this->assertEquals(Director::baseURL() . 'dev/docs/en/doctest/2.4/sort/', $page->Link());

        $page = new DocumentationFolder(
            $this->entity,
            '1-basic.md',
            dirname(__FILE__) .'/docs/en/sort/1-basic.md'
        );

        $this->assertEquals(Director::baseURL() . 'dev/docs/en/doctest/2.4/sort/basic/', $page->Link());
    }

    public function testGetBreadcrumbTitle()
    {
        $page = new DocumentationPage(
            $this->entity,
            'test.md',
            dirname(__FILE__) .'/docs/en/test.md'
        );

        $this->assertEquals("Test - Doctest", $page->getBreadcrumbTitle());

        $page = new DocumentationFolder(
            $this->entity,
            '1-basic.md',
            dirname(__FILE__) .'/docs/en/sort/1-basic.md'
        );

        $this->assertEquals('Basic - Sort - Doctest', $page->getBreadcrumbTitle());

        $page = new DocumentationFolder(
            $this->entity,
            '',
            dirname(__FILE__) .'/docs/en/sort/'
        );

        $this->assertEquals('Sort - Doctest', $page->getBreadcrumbTitle());
    }

    public function testGetCanonicalUrl()
    {
        $page = new DocumentationPage(
            $this->entity,
            'file.md',
            dirname(__FILE__) .'/docs/en/test/file.md'
        );

        $this->assertContains(
            'dev/docs/en/test/file/',
            $page->getCanonicalUrl(),
            'Canonical URL is determined, set and returned'
        );

        $page->setCanonicalUrl('some-other-url');
        $this->assertSame('some-other-url', $page->getCanonicalUrl(), 'Canonical URL can be adjusted via public API');
    }
}
