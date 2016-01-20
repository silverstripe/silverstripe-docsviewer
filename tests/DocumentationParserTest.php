<?php

/**
 * @package docsviewer
 * @subpackage tests
 */
class DocumentationParserTest extends SapphireTest
{
    protected $entity, $entityAlt, $page, $subPage, $subSubPage, $filePage, $metaDataPage, $indexPage;

    public function tearDown()
    {
        parent::tearDown();
        
        Config::unnest();
    }

    public function setUp()
    {
        parent::setUp();

        Config::nest();

        // explicitly use dev/docs. Custom paths should be tested separately 
        Config::inst()->update(
            'DocumentationViewer', 'link_base', 'dev/docs/'
        );

        $this->entity = new DocumentationEntity('DocumentationParserTest');
        $this->entity->setPath(DOCSVIEWER_PATH . '/tests/docs/en/');
        $this->entity->setVersion('2.4');
        $this->entity->setLanguage('en');


        $this->entityAlt = new DocumentationEntity('DocumentationParserParserTest');
        $this->entityAlt->setPath(DOCSVIEWER_PATH . '/tests/docs-parser/en/');
        $this->entityAlt->setVersion('2.4');
        $this->entityAlt->setLanguage('en');

        $this->page = new DocumentationPage(
            $this->entity,
            'test.md',
            DOCSVIEWER_PATH . '/tests/docs/en/test.md'
        );

        $this->subPage = new DocumentationPage(
            $this->entity,
            'subpage.md',
            DOCSVIEWER_PATH. '/tests/docs/en/subfolder/subpage.md'
        );
            
        $this->subSubPage = new DocumentationPage(
            $this->entity,
            'subsubpage.md',
            DOCSVIEWER_PATH. '/tests/docs/en/subfolder/subsubfolder/subsubpage.md'
        );

        $this->filePage =  new DocumentationPage(
            $this->entityAlt,
            'file-download.md',
            DOCSVIEWER_PATH . '/tests/docs-parser/en/file-download.md'
        );

        $this->metaDataPage = new DocumentationPage(
            $this->entityAlt,
            'MetaDataTest.md',
            DOCSVIEWER_PATH . '/tests/docs-parser/en/MetaDataTest.md'
        );

        $this->indexPage = new DocumentationPage(
            $this->entity,
            'index.md',
            DOCSVIEWER_PATH. '/tests/docs/en/index.md'
        );

        $manifest = new DocumentationManifest(true);
    }
    public function testRewriteCodeBlocks()
    {
        $codePage = new DocumentationPage(
            $this->entityAlt,
            'CodeSnippets.md',
            DOCSVIEWER_PATH . '/tests/docs-parser/en/CodeSnippets.md'
        );

        $result = DocumentationParser::rewrite_code_blocks(
            $codePage->getMarkdown()
        );

        $expected = <<<HTML
#### <% control Foo %>
```
code block
<% without formatting prefix %>
```
Paragraph with a segment of <% foo %>
```
code block

that has a line in it
```
This is a yaml block

```yaml
foo: bar

baz: qux
```
This is a yaml block with tab in that new line

```yaml
foo: bar

baz: qux
```
HTML;

        $this->assertEquals($expected, $result, 'Code blocks support line breaks');

        $result = DocumentationParser::rewrite_code_blocks(
            $this->page->getMarkdown()
        );

        $expected = <<<HTML
```php
code block
with multiple
lines
	and tab indent
	and escaped < brackets
	
```
Normal text after code block
HTML;

        $this->assertContains($expected, $result, 'Custom code blocks with ::: prefix');
        
        $expected = <<<HTML
```
code block
without formatting prefix
```
HTML;
        $this->assertContains($expected, $result, 'Traditional markdown code blocks');

        $expected = <<<HTML
```
Fenced code block
```
HTML;
        $this->assertContains($expected, $result, 'Backtick code blocks');
        
        $expected = <<<HTML
```php
Fenced box with

new lines in

between

content
```
HTML;
        $this->assertContains($expected, $result, 'Backtick with newlines');
    }

    public function testRelativeLinks()
    {
        // index.md
        $result = DocumentationParser::rewrite_relative_links(
            $this->indexPage->getMarkdown(),
            $this->indexPage
        );
        
        $this->assertContains(
            '[link: subfolder index](dev/docs/en/documentationparsertest/2.4/subfolder/)',
            $result
        );

        // test.md

        $result = DocumentationParser::rewrite_relative_links(
            $this->page->getMarkdown(),
            $this->page
        );
        
        $this->assertContains(
            '[link: subfolder index](dev/docs/en/documentationparsertest/2.4/subfolder/)',
            $result
        );
        $this->assertContains(
            '[link: subfolder page](dev/docs/en/documentationparsertest/2.4/subfolder/subpage/)',
            $result
        );
        $this->assertContains(
            '[link: http](http://silverstripe.org)',
            $result
        );
        $this->assertContains(
            '[link: api](api:DataObject)',
            $result
        );

        
        $result = DocumentationParser::rewrite_relative_links(
            $this->subPage->getMarkdown(),
            $this->subPage
        );

        # @todo this should redirect to /subpage/
        $this->assertContains(
            '[link: relative](dev/docs/en/documentationparsertest/2.4/subfolder/subpage.md/)',
            $result
        );
        
        $this->assertContains(
            '[link: absolute index](dev/docs/en/documentationparsertest/2.4/)',
            $result
        );

        # @todo this should redirect to /
        $this->assertContains(
            '[link: absolute index with name](dev/docs/en/documentationparsertest/2.4/index/)',
            $result
        );

        $this->assertContains(
            '[link: relative index](dev/docs/en/documentationparsertest/2.4/)',
            $result
        );
        
        $this->assertContains(
            '[link: relative parent page](dev/docs/en/documentationparsertest/2.4/test/)',
            $result
        );
        
        $this->assertContains(
            '[link: absolute parent page](dev/docs/en/documentationparsertest/2.4/test/)',
            $result
        );
        
        $result = DocumentationParser::rewrite_relative_links(
            $this->subSubPage->getMarkdown(),
            $this->subSubPage
        );
        
        $this->assertContains(
            '[link: absolute index](dev/docs/en/documentationparsertest/2.4/)',
            $result
        );

        $this->assertContains(
            '[link: relative index](dev/docs/en/documentationparsertest/2.4/subfolder/)',
            $result
        );

        $this->assertContains(
            '[link: relative parent page](dev/docs/en/documentationparsertest/2.4/subfolder/subpage/)',
            $result
        );

        $this->assertContains(
            '[link: relative grandparent page](dev/docs/en/documentationparsertest/2.4/test/)',
            $result
        );

        $this->assertContains(
            '[link: absolute page](dev/docs/en/documentationparsertest/2.4/test/)',
            $result
        );
    }

    public function testGenerateHtmlId()
    {
        $this->assertEquals('title-one', DocumentationParser::generate_html_id('title one'));
        $this->assertEquals('title-one', DocumentationParser::generate_html_id('Title one'));
        $this->assertEquals('title-and-one', DocumentationParser::generate_html_id('Title &amp; One'));
        $this->assertEquals('title-and-one', DocumentationParser::generate_html_id('Title & One'));
        $this->assertEquals('title-one', DocumentationParser::generate_html_id(' Title one '));
        $this->assertEquals('title-one', DocumentationParser::generate_html_id('Title--one'));
    }


    
    public function testImageRewrites()
    {
        $result = DocumentationParser::rewrite_image_links(
            $this->subPage->getMarkdown(),
            $this->subPage
        );

        $expected = Controller::join_links(
            Director::absoluteBaseURL(), DOCSVIEWER_DIR, '/tests/docs/en/subfolder/_images/image.png'
        );

        $this->assertContains(
            sprintf('[relative image link](%s)', $expected),
            $result
        );

        $this->assertContains(
            sprintf('[parent image link](%s)', Controller::join_links(
                Director::absoluteBaseURL(), DOCSVIEWER_DIR, '/tests/docs/en/_images/image.png'
            )),
            $result
        );
        
        $expected = Controller::join_links(
            Director::absoluteBaseURL(), DOCSVIEWER_DIR, '/tests/docs/en/_images/image.png'
        );

        $this->assertContains(
            sprintf('[absolute image link](%s)', $expected),
            $result
        );
    }
    
    public function testApiLinks()
    {
        // test.md
        $result = DocumentationParser::rewrite_api_links(
            $this->page->getMarkdown(),
            $this->page
        );
        // [api:DataObject]
        $this->assertContains(
            '<a href="http://api.silverstripe.org/search/lookup/?q=DataObject&version=2.4&module=documentationparsertest">DataObject</a>',
            $result
        );
        // [api:DataObject::$defaults]
        $this->assertContains(
            '<a href="http://api.silverstripe.org/search/lookup/?q=DataObject::$defaults&version=2.4&module=documentationparsertest">DataObject::$defaults</a>',
            $result
        );
        // [api:DataObject::populateDefaults()]
        $this->assertContains(
            '<a href="http://api.silverstripe.org/search/lookup/?q=DataObject::populateDefaults()&version=2.4&module=documentationparsertest">DataObject::populateDefaults()</a>',
            $result
        );
        // [Title](api:DataObject)
        $this->assertContains(
            '<a href="http://api.silverstripe.org/search/lookup/?q=DataObject&version=2.4&module=documentationparsertest">Title</a>',
            $result
        );
        // [Title](api:DataObject::$defaults)
        $this->assertContains(
            '<a href="http://api.silverstripe.org/search/lookup/?q=DataObject::$defaults&version=2.4&module=documentationparsertest">Title</a>',
            $result
        );
        // [Title](api:DataObject::populateDefaults())
        $this->assertContains(
            '<a href="http://api.silverstripe.org/search/lookup/?q=DataObject::populateDefaults()&version=2.4&module=documentationparsertest">Title</a>',
            $result
        );
    }
    
    public function testHeadlineAnchors()
    {
        $result = DocumentationParser::rewrite_heading_anchors(
            $this->page->getMarkdown(),
            $this->page
        );
        
        /*
        # Heading one {#Heading-one}

        # Heading with custom anchor {#custom-anchor} {#Heading-with-custom-anchor-custom-anchor}

        ## Heading two {#Heading-two}

        ### Heading three {#Heading-three}

        ## Heading duplicate {#Heading-duplicate}

        ## Heading duplicate {#Heading-duplicate-2}

        ## Heading duplicate {#Heading-duplicate-3}
        
        */

        $this->assertContains('# Heading one {#heading-one}', $result);
        $this->assertContains('# Heading with custom anchor {#custom-anchor}', $result);
        $this->assertNotContains('# Heading with custom anchor {#custom-anchor} {#heading', $result);
        $this->assertContains('# Heading two {#heading-two}', $result);
        $this->assertContains('# Heading three {#heading-three}', $result);
        $this->assertContains('## Heading duplicate {#heading-duplicate}', $result);
        $this->assertContains('## Heading duplicate {#heading-duplicate-2}', $result);
        $this->assertContains('## Heading duplicate {#heading-duplicate-3}', $result);
    }


    public function testRetrieveMetaData()
    {
        DocumentationParser::retrieve_meta_data($this->metaDataPage);
        
        $this->assertEquals('Dr. Foo Bar.', $this->metaDataPage->author);
        $this->assertEquals("Foo Bar's Test page.", $this->metaDataPage->getTitle());
    }
    
    public function testRewritingRelativeLinksToFiles()
    {
        $parsed = DocumentationParser::parse($this->filePage);

        $this->assertContains(
            DOCSVIEWER_DIR .'/tests/docs-parser/en/_images/external_link.png',
            $parsed
        );
        
        $this->assertContains(
            DOCSVIEWER_DIR .'/tests/docs-parser/en/_images/test.tar.gz',
            $parsed
        );
    }
}
