# Writing Documentation

The files have to end with the __.md__ or __.markdown__ extension. The 
documentation viewer will automatically replace hyphens (-) with spaces.

	my-documentation-file.md
	
Translates to:

	My documentation file
	
The module also support number prefixing for specifying the order of pages in
the index pages and navigation trees.

	03-foo.md
	1-bar.md
	4-baz.md
	
Will be output as the following in the listing views.

	Bar
	Foo
	Baz

## Localization

All documentation folder should be localized. Even if you do not plan on supporting 
multiple languages you need to write your documentation in a 'en' subfolder

	/module/docs/en/
	

## Syntax

Documentation should be written in markdown with an `.md` extension attached.
To view the syntax for page formatting check out [Daring Fireball](http://daringfireball.net/projects/markdown/syntax).

To see how to use the documentation from examples, I recommend opening up this 
file in your text editor and playing around. As these files are plain text, any
text editor will be able to open and write markdown files.


## Creating Hierarchy

The document viewer supports a hierarchical folder structure so you can categorize 
documentation and create topics.

## Directory Listing

Each folder you create should also contain a __index.md__ file which contains 
an overview of the module and related links. If no index is available, the 
default behaviour is to display an ordered list of links.

## Table of Contents

The table of contents on each module page is generated based on where and what 
headers you use. 

## Images and Files

If you want to attach images and other assets to a page you need to bundle those
in a directory called _images at the same level as your documentation.