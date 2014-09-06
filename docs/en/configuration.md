# Configuration Options

## Registering what to document
	
By default the documentation system will parse all the directories in your 
project and include the documentation. If you want to only specify a few folders 
you can disable it and register your paths manually

	:::php
	// turns off automatic parsing of filesystem
	DocumentationService::set_automatic_registration(false);
	
	// registers module 'sapphire'
	try {	
		DocumentationService::register("sapphire", BASE_PATH ."/sapphire/docs/", 'trunk');
		
	} catch(InvalidArgumentException $e) {
		
	} 
		
	
If you only want to disable documentation for one module you can correspondingly 
call unregister()

	:::php
	DocumentationService::unregister($module, $version = false, $lang = false)

Unregister a module. You can specify the module, the version and the lang. If 
no version is specified then all folders of that lang are removed. If you do 
not specify a version or lang the whole module will be removed from the 
documentation.


## Hiding files from listing

If you want to ignore (hide) certain file types from being included in the 
listings. By default this is the list of hidden files

	:::php
	$files = array(
		'.', '..', '.DS_Store', '.svn', '.git', 'assets', 'themes', '_images'
	);
	
	DocumentationService::set_ignored_files($files);

## Permalinks 

Permalinks can be setup to make nicer urls or to help redirect older urls
to new structures.

	DocumentationPermalinks::add(array(
		'debugging' => 'sapphire/en/topics/debugging',
		'templates' => 'sapphire/en/topics/templates'
	));
	
	
## Custom metadata and pagesorting

Custom metadata can be added to the head of the MarkDown file like this:  

	pagenumber: 1
	title: A custom title
	

Make sure to add an empty line to separate the metadata from the content of
the file. 

**Note:** SilverStripe needs to read the contents of each page to retrieve the 
metadata. This is expensive, so if you do not plan to use custom sorting, 
do not enable this feature:

### Custom page sorting

By default pages in the lefthand menu are sorted alphabetically. Adding a 
pagenumber to the metadata, like in the example above, allows for custom 
pagenumbering.

**Note:** although folders appear in the menu as 'pages', you obviously can't  
number them, so you need to number their index.php page instead.

Pages that have no custom pagenumber, keep their original 
order, but for them not to interfere with custom sort, they also receive a 
pagenumber, starting at 10.000. 

You can change this starting point for default pagenumbers:

	```php
	DocumentationService:: start_pagenumbers_at(80);
	```

### Other key-value pairs

Basically all DocumentationPage properties can be added to the metadata comment 
block. Beware that the outcome isn't always predictable. Adding a title 
property to the block will change the menu title, but the breadcrumbs 
are at this time not yet supported.
	

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