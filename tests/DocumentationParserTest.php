<?php
namespace SilverStripe\DocsViewer\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\DocsViewer\DocumentationManifest;
use SilverStripe\DocsViewer\DocumentationParser;
use SilverStripe\DocsViewer\Controllers\DocumentationViewer;
use SilverStripe\DocsViewer\Models\DocumentationEntity;
use SilverStripe\DocsViewer\Models\DocumentationPage;


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

        @Config::unnest();
    }

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

        $this->entity = new DocumentationEntity('DocumentationParserTest');
        $this->entity->setPath(dirname(__FILE__) .'/docs/en/');
        $this->entity->setVersion('2.4');
        $this->entity->setLanguage('en');


        $this->entityAlt = new DocumentationEntity('DocumentationParserParserTest');
        $this->entityAlt->setPath(dirname(__FILE__) .'/docs-parser/en/');
        $this->entityAlt->setVersion('2.4');
        $this->entityAlt->setLanguage('en');

        $this->page = new DocumentationPage(
            $this->entity,
            'test.md',
            dirname(__FILE__) .'/docs/en/test.md'
        );

        $this->subPage = new DocumentationPage(
            $this->entity,
            'subpage.md',
            dirname(__FILE__) .'/docs/en/subfolder/subpage.md'
        );

        $this->subSubPage = new DocumentationPage(
            $this->entity,
            'subsubpage.md',
            dirname(__FILE__) .'/docs/en/subfolder/subsubfolder/subsubpage.md'
        );

        $this->filePage =  new DocumentationPage(
            $this->entityAlt,
            'file-download.md',
            dirname(__FILE__) .'/docs-parser/en/file-download.md'
        );

        $this->metaDataPage = new DocumentationPage(
            $this->entityAlt,
            'MetaDataTest.md',
            dirname(__FILE__) .'/docs-parser/en/MetaDataTest.md'
        );

        $this->indexPage = new DocumentationPage(
            $this->entity,
            'index.md',
            dirname(__FILE__) .'/docs/en/index.md'
        );

        $manifest = new DocumentationManifest(true);
    }
    public function testRewriteCodeBlocks()
    {
        $codePage = new DocumentationPage(
            $this->entityAlt,
            'CodeSnippets.md',
            dirname(__FILE__) .'/docs-parser/en/CodeSnippets.md'
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
            '[link: subfolder index](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/subfolder/)',
            $result
        );

        // test.md

        $result = DocumentationParser::rewrite_relative_links(
            $this->page->getMarkdown(),
            $this->page
        );

        $this->assertContains(
            '[link: subfolder index](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/subfolder/)',
            $result
        );
        $this->assertContains(
            '[link: subfolder page](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/subfolder/subpage/)',
            $result
        );
        $this->assertContains(
            '[link: http](http://silverstripe.org)',
            $result
        );

        $this->assertContains(
            '[link: with anchor](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/test/#anchor)',
            $result
        );

        $this->assertContains(
            '[link: relative anchor](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/test/#relative-anchor)',
            $result
        );

        $result = DocumentationParser::rewrite_relative_links(
            $this->subPage->getMarkdown(),
            $this->subPage
        );

        // @todo this should redirect to /subpage/
        $this->assertContains(
            '[link: relative](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/subfolder/subpage.md/)',
            $result
        );

        $this->assertContains(
            '[link: absolute index](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/)',
            $result
        );

        // @todo this should redirect to /
        $this->assertContains(
            '[link: absolute index with name](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/index/)',
            $result
        );

        $this->assertContains(
            '[link: relative index](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/)',
            $result
        );

        $this->assertContains(
            '[link: relative parent page](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/test/)',
            $result
        );

        $this->assertContains(
            '[link: absolute parent page](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/test/)',
            $result
        );

        $result = DocumentationParser::rewrite_relative_links(
            $this->subSubPage->getMarkdown(),
            $this->subSubPage
        );

        $this->assertContains(
            '[link: absolute index](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/)',
            $result
        );

        $this->assertContains(
            '[link: relative index](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/subfolder/)',
            $result
        );

        $this->assertContains(
            '[link: relative parent page](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/subfolder/subpage/)',
            $result
        );

        $this->assertContains(
            '[link: relative grandparent page](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/test/)',
            $result
        );

        $this->assertContains(
            '[link: absolute page](' . Director::baseURL() . 'dev/docs/en/documentationparsertest/2.4/test/)',
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
        
        $absoluteBaseURL = preg_quote(trim(Director::absoluteBaseURL(), '/'), '/');

        $expected = $absoluteBaseURL .'\/resources\/((vendor\/silverstripe\/docsviewer\/)?)tests\/docs\/en\/subfolder\/_images\/image\.png';

        $this->assertRegExp(
            '/' . sprintf('\[relative image link\]\(%s\)', $expected) . '/',
            $result
        );

        $expected = $absoluteBaseURL .'\/resources\/((vendor\/silverstripe\/docsviewer\/)?)tests\/docs\/en\/_images\/image\.png';

        $this->assertRegExp(
            '/' . sprintf('\[parent image link\]\(%s\)', $expected) . '/',
            $result
        );

        $expected = $absoluteBaseURL .'\/resources\/((vendor\/silverstripe\/docsviewer\/)?)tests\/docs\/en\/_images\/image\.png';

        $this->assertRegExp(
            '/' . sprintf('\[absolute image link\]\(%s\)', $expected) . '/',
            $result
        );
    }

    public function testApiLinks()
    {

        // $this->page is test.md, the documentation page being parsed by rewrite_api_links
        $parsed_page = DocumentationParser::rewrite_api_links($this->page->getMarkdown(), $this->page);

        // version of documentation page
        $page_version = $this->page->getVersion();

        // expected url format resulting from rewriting api shortcode links
        $html_format = '<a href="http://api.silverstripe.org/search/lookup/?q=%s&version='.$page_version.'&module=documentationparsertest">%s</a>';

        // test cases: non-backtick enclosed api links and the expected html resulting from rewriting them
        //             note that api links enclosed in backticks are left unchanged
        $test_cases = array(
            array('`[api:DataObject]`','`[api:DataObject]`'),
            array('`[api:DataObject::$defaults]`','`[api:DataObject::$defaults]`'),
            array('`[api:DataObject::populateDefaults()]`','`[api:DataObject::populateDefaults()]`'),
            array('`[Title](api:DataObject)`','`[Title](api:DataObject)`'),
            array('`[Title](api:DataObject::$defaults)`','`[Title](api:DataObject::$defaults)`'),
            array('`[Title](api:DataObject::populateDefaults())`','`[Title](api:DataObject::populateDefaults())`'),
            array('[api:DataObject]', sprintf($html_format, 'DataObject', 'DataObject')),
            array('[api:DataObject::$defaults]',sprintf($html_format, 'DataObject::$defaults', 'DataObject::$defaults')),
            array('[api:DataObject::populateDefaults()]',sprintf($html_format, 'DataObject::populateDefaults()', 'DataObject::populateDefaults()')),
            array('[Title](api:DataObject)',sprintf($html_format, 'DataObject', 'Title')),
            array('[Title](api:DataObject::$defaults)',sprintf($html_format, 'DataObject::$defaults', 'Title')),
            array('[Title](api:DataObject::populateDefaults())',sprintf($html_format, 'DataObject::populateDefaults()', 'Title'))
        );

        foreach ($test_cases as $test_case) {
            $expected_html = $test_case[1];
            $this->assertContains($expected_html, $parsed_page);
        }
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
        $this->metaDataPage->getMarkdown(true);
        $this->assertEquals('Foo Bar\'s Test page.', $this->metaDataPage->getTitle());
        $this->assertEquals('A long intro that splits over many lines', $this->metaDataPage->getIntroduction());
        $this->assertEquals('Foo Bar Test page description', $this->metaDataPage->getSummary());

        $parsed = DocumentationParser::parse($this->metaDataPage);
        $expected = <<<HTML
<h1 id="content">Content</h1>
HTML;
        $this->assertEquals($parsed, $expected, 'Metadata block removed, parsed correctly');
    }

    public function testRetrieveMetaDataYamlBlock()
    {
        $page = new DocumentationPage(
            $this->entityAlt,
            'MetaDataYamlBlockTest.md',
            dirname(__FILE__) .'/docs-parser/en/MetaDataYamlBlockTest.md'
        );
        $page->getMarkdown(true);

        $this->assertEquals('Foo Bar\'s Test page.', $page->getTitle());
        $this->assertEquals('This is the page\'s description', $page->getSummary());

        $parsed = DocumentationParser::parse($page);
        $expected = <<<HTML
<h2 id="content-2">Content</h2>
<p>Content goes here.</p>
<hr />
<h2>randomblock: ignored</h2>
HTML;

        $this->assertEquals($parsed, $expected, 'YAML metadata block removed, parsed correctly');
    }

    public function testRewritingRelativeLinksToFiles()
    {
        $parsed = DocumentationParser::parse($this->filePage);

        $expected = '/resources\/((vendor\/silverstripe\/docsviewer\/)?)tests\/docs-parser\/en\/_images\/external_link\.png/';
        $this->assertRegExp($expected, $parsed);

        $expected = '/resources\/((vendor\/silverstripe\/docsviewer\/)?)tests\/docs-parser\/en\/_images\/test\.tar\.gz/';
        $this->assertRegExp($expected, $parsed);
    }
}
