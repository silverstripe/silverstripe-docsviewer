# docsviewer Module

This module has been developed to read and display content from markdown and 
plain text files in web browser. It provides an easy way to bundle end user 
documentation within a SilverStripe installation or module.

## Setup

The module includes the ability to read documentation from any folder on your
file system. By standard, documentation should go in a __docs__ folder in the
root of your module or project documentation.

### Standard

If you follow the standard setup create a file in /<<module>>/__docs/_en/index.md__ 
file then include the following in your config file:

	DocumentationService::set_automatic_registration(true);

Now visit yoursite.com/dev/docs you should see your module.

### Custom Folders

If you wish to register specific folders only, or folders in a non standard 
location then you can register paths directly:

	try {	
		DocumentationService::register(
			$name = "sapphire", 
			$path = "/src/sapphire_master/docs/", 
			$version = 'trunk'
		);
	} catch(InvalidArgumentException $e) {
		 // Silence if path is not found (for CI environment)
	}


To configure the documentation system the configuration information is 
available on the [Configurations](configuration-options)
page.

## Writing documentation

See [Writing Documentation](writing-documentation)
for more information on how to write markdown files which are available here. 


## Enabling Search

The module provides automatic search functionality via [Lucene Search](http://lucene.apache.org/java/docs/index.html). 

To enable search you need to add the following to your applications _config.php 
file:

	DocumentationSearch::enable();
	
After adding that line you will also need to build the indexes of the search. 

You can do this either via your web browser by accessing

	http://yoursite.com/dev/tasks/RebuildLuceneDocsIndex?flush=1
	
Or rebuild it via sake. You will want to set this up as a cron job if your 
documentation search needs to be updated on the fly

	sake dev/tasks/RebuildLuceneDocsIndex flush=1

## Advanced Search

Advanced Search is enabled by default on the searchresults page, allowing you to 
extend your search over multiple modules and/or versions. Advanced search can 
be disabled from your _config.php like this:

	DocumentationSearch::enable_advanced_search(false);

## Using a URL other than /dev/docs/

By default, the documentation is available in `dev/docs`. If you want it to 
live on the webroot instead of a subfolder or on another url address, add the 
following configuration to your _config.php file:

	DocumentationViewer::set_link_base('');
	
	Director::addRules(1, array(
		'$Action' => 'DocumentationViewer',
		'' => 'DocumentationViewer'
	));
