# Helpful Configuration Options

	DocumentationService::set_ignored_files(array());
	
If you want to ignore (hide) certain file types from being included.

	DocumentationService::set_automatic_registration(false);
	
By default the documentation system will parse all the directories in your project and
include the documentation. If you want to only specify a few folders you can disable it
with the above.

	DocumentationService::register($module, $path, $version = 'current', $lang = 'en', $major_release = false)

Registers a module to be included in the system (if automatic registration is off or you need
to load a module outside a documentation path).

	DocumentationService::unregister($module, $version = false, $lang = false)
	
Unregister a module (removes from documentation list). You can specify the module, the version
and the lang. If no version is specified then all folders of that lang are removed. If you do
not specify a version or lang the whole module will be removed from the documentation.

## Permalinks 

You can set short names for longer urls so they are easier to remember. Set the following in your mysite/_config.php file:

	DocumentationPermalinks::add(array(
		'debugging' => 'current/en/sapphire/topics/debugging',
		'templates' => 'current/en/sapphire/topics/templates'
	));
	
	

	