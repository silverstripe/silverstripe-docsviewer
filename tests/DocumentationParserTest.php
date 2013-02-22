<?php
/**
 * @package docsviewer
 */
class DocumentationParserTest extends SapphireTest {
	
	function testGenerateHtmlId() {
		$this->assertEquals('title-one', DocumentationParser::generate_html_id('title one'));
		$this->assertEquals('title-one', DocumentationParser::generate_html_id('Title one'));
		$this->assertEquals('title-and-one', DocumentationParser::generate_html_id('Title &amp; One'));
		$this->assertEquals('title-and-one', DocumentationParser::generate_html_id('Title & One'));
		$this->assertEquals('title-one', DocumentationParser::generate_html_id(' Title one '));
		$this->assertEquals('title-one', DocumentationParser::generate_html_id('Title--one'));
	}

	function testRewriteCodeBlocks() {
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity(new DocumentationEntity('mymodule', '2.4', DOCSVIEWER_PATH . '/tests/docs/'));
		$page->setLang('en');
		$page->setVersion('2.4');
		$result = DocumentationParser::rewrite_code_blocks($page->getMarkdown());
		$expected = <<<HTML
<pre class="brush: php">
code block
with multiple
lines
	and tab indent
	and escaped &lt; brackets</pre>

Normal text after code block
HTML;


		$this->assertContains($expected, $result, 'Custom code blocks with ::: prefix');		
		
		$expected = <<<HTML
<pre>
code block
without formatting prefix</pre>
HTML;
		$this->assertContains($expected, $result, 'Traditional markdown code blocks');

		$expected = <<<HTML
<pre class="brush: ">
Fenced code block
</pre>
HTML;
		$this->assertContains($expected, $result, 'Backtick code blocks');
		
		$expected = <<<HTML
<pre class="brush: php">
Fenced box with

new lines in

between

content
</pre>
HTML;
		$this->assertContains($expected, $result, 'Backtick with newlines');
	}
	
	function testImageRewrites() {
		// Page on toplevel
		$page = new DocumentationPage();
		$page->setRelativePath('subfolder/subpage.md');
		$page->setEntity(new DocumentationEntity('mymodule', '2.4', DOCSVIEWER_PATH . '/tests/docs/'));
		$page->setLang('en');
		$page->setVersion('2.4');
		
		$result = DocumentationParser::rewrite_image_links($page->getMarkdown(), $page, 'mycontroller/cms/2.4/en/');

		$this->assertContains(
			'[relative image link](' . Director::absoluteBaseURL() .'/'. DOCSVIEWER_DIR . '/tests/docs/en/subfolder/_images/image.png)',
			$result
		);
		$this->assertContains(
			'[parent image link](' . Director::absoluteBaseURL() . '/'. DOCSVIEWER_DIR. '/tests/docs/en/_images/image.png)',
			$result
		);
		
		// $this->assertContains(
		//	'[absolute image link](' . Director::absoluteBaseURL() . '/'. DOCSVIEWER_DIR. '/tests/docs/en/_images/image.png)',
		//	$result
		// );
	}
	
	function testApiLinks() {
		// Page on toplevel
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity(new DocumentationEntity('mymodule', '2.4', DOCSVIEWER_PATH .'/tests/docs/'));
		$page->setLang('en');
		$page->setVersion('2.4');
		
		
		$result = DocumentationParser::rewrite_api_links($page->getMarkdown(), $page, 'mycontroller/cms/2.4/en/');
		$this->assertContains(
			'[link: api](http://api.silverstripe.org/search/lookup/?q=DataObject&version=2.4&module=mymodule)',
			$result
		);
		$this->assertContains(	'[DataObject::$has_one](http://api.silverstripe.org/search/lookup/?q=DataObject::$has_one&version=2.4&module=mymodule)',
			$result
		);
	}
	
	function testHeadlineAnchors() {
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity(new DocumentationEntity('mymodule', '2.4', DOCSVIEWER_PATH . '/tests/docs/'));
		$page->setLang('en');
		$page->setVersion('2.4');
		
		$result = DocumentationParser::rewrite_heading_anchors($page->getMarkdown(), $page);
		
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
		
	function testRelativeLinks() {
		// Page on toplevel
		$page = new DocumentationPage();
		$page->setRelativePath('test.md');
		$page->setEntity(new DocumentationEntity('mymodule', '2.4', DOCSVIEWER_PATH . '/tests/docs/'));

		$result = DocumentationParser::rewrite_relative_links($page->getMarkdown(), $page, 'mycontroller/cms/2.4/en/');

		$this->assertContains(
			'[link: subfolder index](mycontroller/cms/2.4/en/subfolder/)',
			$result
		);
		$this->assertContains(
			'[link: subfolder page](mycontroller/cms/2.4/en/subfolder/subpage)',
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
		$this->assertContains(
			'[link: relative](mycontroller/cms/2.4/a-relative-file.md)',
			$result
		);	
		
		// Page in subfolder
		$page = new DocumentationPage();
		$page->setRelativePath('subfolder/subpage.md');
		$page->setEntity(new DocumentationEntity('mymodule', '2.4', DOCSVIEWER_PATH . '/tests/docs/'));
		
		$result = DocumentationParser::rewrite_relative_links($page->getMarkdown(), $page, 'mycontroller/cms/2.4/en/');

		$this->assertContains(
			'[link: relative](mycontroller/cms/2.4/en/subfolder/subpage.md)',
			$result
		);
		
		$this->assertContains(
			'[link: absolute index](mycontroller/cms/2.4/en/)',
			$result
		);
		$this->assertContains(
			'[link: absolute index with name](mycontroller/cms/2.4/en/index)',
			$result
		);
		$this->assertContains(
			'[link: relative index](mycontroller/cms/2.4/en/)',
			$result
		);
		$this->assertContains(
			'[link: relative parent page](mycontroller/cms/2.4/en/test)',
			$result
		);
		$this->assertContains(
			'[link: absolute parent page](mycontroller/cms/2.4/en/test)',
			$result
		);
		
		// Page in nested subfolder
		$page = new DocumentationPage();
		$page->setRelativePath('subfolder/subsubfolder/subsubpage.md');
		$page->setEntity(new DocumentationEntity('mymodule', '2.4', DOCSVIEWER_PATH . '/tests/docs/'));
		
		$result = DocumentationParser::rewrite_relative_links($page->getMarkdown(), $page, 'mycontroller/cms/2.4/en/');
		
		$this->assertContains(
			'[link: absolute index](mycontroller/cms/2.4/en/)',
			$result
		);
		$this->assertContains(
			'[link: relative index](mycontroller/cms/2.4/en/subfolder/)',
			$result
		);
		$this->assertContains(
			'[link: relative parent page](mycontroller/cms/2.4/en/subfolder/subpage)',
			$result
		);
		$this->assertContains(
			'[link: relative grandparent page](mycontroller/cms/2.4/en/test)',
			$result
		);
		$this->assertContains(
			'[link: absolute page](mycontroller/cms/2.4/en/test)',
			$result
		);
	}

	function testRetrieveMetaData() {
		$page = new DocumentationPage();
		$page->setRelativePath('MetaDataTest.md');
		$page->setEntity(new DocumentationEntity('parser', '2.4', DOCSVIEWER_PATH . '/tests/docs-parser/'));
		
		DocumentationParser::retrieve_meta_data($page);
		
		$this->assertEquals('Dr. Foo Bar.', $page->Author);
		$this->assertEquals("Foo Bar's Test page.", $page->getTitle());
		$this->assertEquals("Foo Bar's Test page.", $page->Title);
	}
	
	function testParserConvertsSpecialCharacters() {
		$page = new DocumentationPage();
		$page->setRelativePath('CodeSnippets.md');
		$page->setEntity(new DocumentationEntity('parser', '2.4', DOCSVIEWER_PATH . '/tests/docs-parser/'));

		$parsed = DocumentationParser::parse($page, '/'.DOCSVIEWER_DIR .'/tests/docs-parser/');
		
		// header elements parsed
		$this->assertContains(
			'&lt;% control Foo %&gt;',
			$parsed
		);
		
		// paragraphs
		$this->assertContains(
			'&lt;% foo %&gt;',
			$parsed
		);
	}
	
	function testRewritingRelativeLinksToFiles() {
		$folder = DOCSVIEWER_PATH . '/tests/docs-parser/';
		
		$page = new DocumentationPage();
		$page->setRelativePath('file-download.md');
		$page->setEntity(new DocumentationEntity('parser', '2.4', $folder));
		
		$parsed = DocumentationParser::parse($page, $folder);

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