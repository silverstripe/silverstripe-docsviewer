# docsviewer Module

This module has been developed to read and display content from markdown and 
plain text files in web browser. It provides an easy way to bundle end user 
documentation within a SilverStripe installation or module.


	:::bash
	$> composer require


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

## Using a URL other than /dev/docs/

By default, the documentation is available in `dev/docs`. If you want it to 
live on the webroot instead of a subfolder or on another url address, add the 
following configuration to your _config.php file:

	Config::inst()->update('DocumentationViewer', 'link_base', '');
	
	Director::addRules(1, array(
		'$Action' => 'DocumentationViewer',
		'' => 'DocumentationViewer'
	));
