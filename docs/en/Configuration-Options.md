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

	DocumentationPermalinks::add(array(
		'debugging' => 'sapphire/en/topics/debugging',
		'templates' => 'sapphire/en/topics/templates'
	));
	
	

	