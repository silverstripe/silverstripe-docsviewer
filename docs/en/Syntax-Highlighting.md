# Syntax Highlighting

The custom Markdown parser can render custom prefixes for code blocks, and 
render it via a [javascript syntax highlighter](http://alexgorbatchev.com/SyntaxHighlighter).

To see examples of the syntax, check out the source of this file in docs/en/

To include the syntax highlighter source, add the following to your `DocumentationViewer->init()`:


```php

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
```

You can overload the `DocumentationViewer` class and add a custom route through `Director::addRule()`
if you prefer not to modify the module file.
