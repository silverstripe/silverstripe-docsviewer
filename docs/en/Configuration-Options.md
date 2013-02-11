# Configuration Options

## Registering what to document

By default the documentation system will parse all the directories in your project 
and include the documentation. If you want to only specify a few folders you can 
disable it and register your paths manually

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

	:::php
	DocumentationPermalinks::add(array(
		'debugging' => 'sapphire/en/topics/debugging',
		'templates' => 'sapphire/en/topics/templates'
	));


## Permissions

By default the permissions for the docsviewer documentation are set to 'ADMIN'.
You can change this to any other permission, or to false, if you want your docs 
to be open to the public:

	:::php
	DocumentationViewer::$check_permission = false;

or:

	:::php
	DocumentationViewer::$check_permission = 'MY_PERMISSION';


## Rootpages (README files)

Rootpages are non-localized pages that live in the root of the entity or
module, as opposite to the localized docs located in the docs/<lang> folder. 
Example: README.md. By default Rootpages are injected into the top level menu 
on the left, where they'll act as if they are part of the localized docs.

If a rootpage is found for which a page with the same name already exists in
the localized documentation, the localized version takes presendence. If a
module has no docs section, the docsviewer will show the rootpages only.

If no index.md page can be found, the README.md page (if present) will serve as
the main entry page. In other situations the default overview page is displayed.


### Disable rootpages globally

	:::php
	DocumentationService::enable_rootpages(false);


### Disable rootpages for individual entities/modules

The following will disable the display of rootpages for the framework and the
cms. Since the cms has no docs section, it will now not be registered by the
docsviewer:

	:::php
	DocumentationService::disable_rootpages_for(array(
		'cms', 'framework'
	));

