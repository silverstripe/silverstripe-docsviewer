# Configuration Options

## Registering what to document
	
By default the documentation system will parse all the directories in your 
project and include the documentation from those modules `docs` directory. 

If you want to only specify a few folders or have documentation in a non 
standard location you can disable the autoload behaviour and register your 
folders manually through the `Config` API.

In YAML this looks like:

`mysite/_config/docsviewer.yml`

	:::yaml
	---
	name: docsviewer
	after: docsviewer#docsviewer
	---
	DocumentationManifest:
	  automatic_registration: false
	  register_entities:
	    - 
	      Path: "framework/docs/"
	      Title: "Framework Documentation"

###Branch aliases for the edit link (optional)
When using entities with multiple versions, one of the branches of documentation may be a development version. For example the 'master' branch. You may have an internally assigned version number for this registered in your .yml configuration.

If this version number is not the same as the branch name on the git repository the `getEditLinks` method will return an incorrect link to go and edit the documentation. In this case you can simply set an optional `branch` property on the entity which will be used in the edit link instead.

Example:

	:::yml
	DocumentationManifest:
	  register_entities:
	    - 
	      Path: "framework/docs/"
	      Title: "Framework Documentation"
	      Version: "1.0"
	      Branch: "master"

## Permalinks 

Permalinks can be setup to make nicer urls or to help redirect older urls
to new structures.

	DocumentationPermalinks::add(array(
		'debugging' => 'sapphire/en/topics/debugging',
		'templates' => 'sapphire/en/topics/templates'
	));
	
	
## Custom metadata and pagesorting

Custom metadata can be added to the head of the MarkDown file like this:  

	title: A custom title

Make sure to add an empty line to separate the metadata from the content of
the file. 

The currently utilized metadata tags for the module are

	title: 'A custom title for menus, breadcrumbs'
	summary: 'A custom introduction text'

### Custom page sorting

By default pages in the left hand menu are sorted as how they appear in the file
system. You can manually set the order by prefixing filenames with numbers. For
example:
	
	00_file-first.md
	01_second-file.md

The leading numbers will be scrubbed from the URL and page link.
	

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
