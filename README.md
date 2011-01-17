# Documentation Viewer Module

## Maintainer Contact

* Will Rossiter (Nickname: willr, wrossiter) 
 <will@silverstripe.com>

## Requirements

* SilverStripe 2.4

## Summary

Reads markdown documentation files from the /docs/ folder in . To read documentation go to yoursite.com/dev/docs/.

For more documentation on how to use the module please read /docs/Writing-Documentation.md 
(or via this in /dev/docs/sapphiredocs/Writing-Documentation in your webbrowser)

**Note** This module assumes you are using numeric values for your versions.

## Search ##

Search needs to be indexed manually with `dev/tasks/RebuildLuceneDocsIndex`.

## Syntax Highlighting ##

The custom Markdown parser can render custom prefixes for code blocks,
and render it via a [javascript syntax highlighter](http://alexgorbatchev.com/SyntaxHighlighter).

In:

	:::php
	my sourcecode
	
Out:

	<pre class="brush: php">
	my sourcecode
	</pre>
	
To include the syntax highlighter source, add the following to your `DocumentationViewer->init()`:

	Requirements::javascript(THIRDPARTY_DIR .'/jquery/jquery.js');
	Requirements::javascript('sapphiredocs/thirdparty/syntaxhighlighter/scripts/shCore.js');
	Requirements::javascript('sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushJScript.js');
	Requirements::javascript('sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushPHP.js');
	Requirements::javascript('sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushXML.js');
	// ... any additional syntaxes you want to support
	Requirements::combine_files(
		'syntaxhighlighter.js',
		array(
			'sapphiredocs/thirdparty/syntaxhighlighter/scripts/shCore.js',
			'sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushJScript.js',
			'sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushPHP.js',
			'sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushXML.js'
		)
	);
	
	Requirements::javascript('sapphiredocs/javascript/DocumentationViewer.js');

	// css
	Requirements::css('sapphiredocs/thirdparty/syntaxhighlighter/styles/shCore.css');
	Requirements::css('sapphiredocs/thirdparty/syntaxhighlighter/styles/shCoreDefault.css');
	Requirements::css('sapphiredocs/thirdparty/syntaxhighlighter/styles/shThemeRDark.css');
	
You can overload the `DocumentationViewer` class and add a custom route through `Director::addRule()`
if you prefer not to modify the module file.
