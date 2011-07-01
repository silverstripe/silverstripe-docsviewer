<?php

/**
 * DocumentationService
 *
 * Handles the management of the documentation services delivered by the module.
 * Includes registering which components to document and handles the entities being
 * documented
 *
 * @todo - unregistering a lang / version from site does not update the registered_* arrays
 *		 - handle modules (rather than core) differently
 * @package sapphiredocs
 */

class DocumentationService {
	
	/**
	 * A mapping of know / popular languages to nice titles.
	 *
	 * @var Array
	 */
	private static $language_mapping = array(
		'en' => 'English',
		'fr' => 'French',
		'de' => 'German'
	);
	
	/**
	 * Files to ignore from any documentation listing.
	 *
	 * @var array
	 */
	private static $ignored_files = array('.', '..', '.DS_Store', '.svn', '.git', 'assets', 'themes', '_images');

	/**
	 * Case insenstive values to use as extensions on markdown pages.
	 *
	 * @var array
	 */
	public static $valid_markdown_extensions = array('.md', '.txt', '.markdown');
	
	/**
	 * Registered modules to include in the documentation. Either pre-filled by the
	 * automatic filesystem parser or via {@link DocumentationService::register()}. Stores
	 * {@link DocumentEntity} objects which contain the languages and versions of each module.
	 *
	 * You can remove registered modules using {@link DocumentationService::unregister()}
	 *
	 * @var array
	 */
	private static $registered_modules = array();
	
	/**
	 * Major Versions store. We don't want to register all versions of every module in
	 * the documentation but for sapphire/cms and overall we need to register major
	 * versions via {@link DocumentationService::register}
	 *
	 * @var array
	 */
	private static $major_versions = array();
	
	/**
	 * Return the major versions
	 *
	 * @return array
	 */
	public static function get_major_versions() {
		return self::$major_versions;
	}
	
	/**
	 * Return the allowed extensions
	 *
	 * @return array
	 */
	public static function get_valid_extensions() {
		return self::$valid_markdown_extensions;
	}
	
	/**
	 * Set the ignored files list
	 *
	 * @param array
	 */
	public function set_ignored_files($files) {
		self::$ignored_files = $files;
	}
	
	/**
	 * Return the list of files which are ignored
	 *
	 * @return array
	 */
	public function get_ignored_files() {
		return self::$ignored_files;
	}
	
	/**
	 * Check to see if a given language is registered in the system
	 *
	 * @param string
	 * @return bool
	 */
	public static function is_registered_language($lang) {
		$langs = self::get_registered_languages();
		
		return (isset($langs[$lang]));
	}
	
	/**
	 * Get all the registered languages. Optionally limited to a module. Includes
	 * the nice titles
	 *
	 * @return Array
	 */
	public static function get_registered_languages($module = false) {
		$langs = array();
		
		if($module) {
			if(isset(self::$registered_modules[$module])) {
				$langs = self::$registered_modules[$module]->getLanguages();
			}
		}
		else if($modules = self::get_registered_modules()) {
			
			foreach($modules as $module) {
				$langs = array_unique(array_merge($langs, $module->getLanguages()));
			}
		}
		
		$output = array();
		foreach($langs as $lang) {
			$output[$lang] = self::get_language_title($lang);
		}
		
		return $output;
	}
	
	/**
	 * Returns all the registered versions in the system. Optionally only 
	 * include versions from a module.
	 *
	 * @param String $module module to check for versions
	 * @return array
	 */
	public static function get_registered_versions($module = false) {
		if($module) {
			if(isset(self::$registered_modules[$module])) {
				return self::$registered_modules[$module]->getVersions();
			}
			else {
				return false;
			}
		}

		return self::$major_versions;
	}
	
	/**
	 * Should generation of documentation categories be automatic. If this
	 * is set to true then it will generate documentation sections (modules) from
	 * the filesystem. This can be slow and also some projects may want to restrict
	 * to specific project folders (rather than everything).
	 *
	 * You can also disable or remove a given folder from registration using 
	 * {@link DocumentationService::unregister()}
	 *
	 * @see DocumentationService::$registered_modules
	 * @see DocumentationService::set_automatic_registration();
	 *
	 * @var bool
	 */
	private static $automatic_registration = true;
	
	/**
	 * Set automatic registration of modules and documentation folders
	 *
	 * @see DocumentationService::$automatic_registration
	 * @param bool
	 */
	public static function set_automatic_registration($bool = true) {
		self::$automatic_registration = $bool;
		
		if(!$bool) {
			// remove current registed modules when disabling automatic registration
			// needed to avoid caching issues when running all the tests
			self::$registered_modules = array();
		}
	}
	
	/**
	 * Is automatic registration of modules enabled.
	 *
	 * @return bool
	 */
	public static function automatic_registration_enabled() {
		return self::$automatic_registration;
	}
	
	/**
	 * Return the modules which are listed for documentation. Optionally only get
	 * modules which have a version or language given
	 *
	 * @return array
	 */
	public static function get_registered_modules($version = false, $lang = false) {
		$output = array();
		
		if($modules = self::$registered_modules) {
			if($version || $lang) {
				foreach($modules as $module) {
					if(self::is_registered_module($module->getModuleFolder(), $version, $lang)) {
						$output[] = $module;
					}
				}
			}
			else {
				$output = $modules;
			}
		}
		
		return $output;
	}
	
	/**
	 * Check to see if a module is registered with the documenter
	 *
	 * @param String $module module name
	 * @param String $version version
	 * @param String $lang language
	 *
	 * @return DocumentationEntity $module the registered module
	 */
	public static function is_registered_module($module, $version = false, $lang = false) {
		$check = ($module instanceof DocumentationEntity) ? $module->getModuleFolder() : (string) $module;

		if(isset(self::$registered_modules[$check])) {
			$module = self::$registered_modules[$check];
	
			if(($lang && !$module->hasLanguage($lang)) || ($version && !$module->hasVersion($version))) {
				return false;
			}
			
			return $module;
		}

		return false;		
	}
	
	/**
	 * Register a module to be included in the documentation. To unregister a module
	 * use {@link DocumentationService::unregister()}. Must include the trailing slash
	 *
	 * @param String $module Name of module to register
	 * @param String $path Path to documentation root.
	 * @param Float $version Version of module.
	 * @param String $title Nice title to use
	 * @param bool $major is this a major release
	 * @param bool $latest - return is this the latest release.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return DocumentationEntity
	 */
	public static function register($module, $path, $version = '', $title = false, $major = false, $latest = false) {
		if(!file_exists($path)) throw new InvalidArgumentException(sprintf('Path "%s" doesn\'t exist', $path));

		// add the module to the registered array
		if(!isset(self::$registered_modules[$module])) {
			// module is completely new
			$entity = new DocumentationEntity($module, $version, $path, $title);
			
			self::$registered_modules[$module] = $entity;
		}
		else {
			// module exists so add the version to it
			$entity = self::$registered_modules[$module];
			$entity->addVersion($version, $path);
		}
		
		if($major) {
			if(!$version) $version = '';
			
			if(!in_array($version, self::$major_versions)) {	
				self::$major_versions[] = $version;
			}
		}
		
		if($latest) {
			$entity->setLatestVersion($version);
		}

		
		return $entity;
	}
	
	/**
	 * Unregister a module from being included in the documentation. Useful
	 * for keeping {@link DocumentationService::$automatic_registration} enabled
	 * but disabling modules which you do not want to show. Combined with a 
	 * {@link Director::isLive()} you can hide modules you don't want a client to see.
	 *
	 * If no version or lang specified then the whole module is removed. Otherwise only
	 * the specified version of the documentation.
	 *
	 * @param String $module
	 * @param String $version
	 *
	 * @return bool
	 */
	public static function unregister($moduleName, $version = '') {
		if(isset(self::$registered_modules[$moduleName])) {
			$module = self::$registered_modules[$moduleName];
			
			if($version) {
				$module->removeVersion($version);
			}
			else {
				// only given a module so unset the whole module
				unset(self::$registered_modules[$moduleName]);	
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Register the docs from off a file system if automatic registration is turned on.
	 */
	public static function load_automatic_registration() {
		if(self::automatic_registration_enabled()) {
			$modules = scandir(BASE_PATH);

			if($modules) {
				foreach($modules as $key => $module) {
					if(is_dir(BASE_PATH .'/'. $module) && !in_array($module, self::get_ignored_files(), true)) {
						// check to see if it has docs
						$docs = BASE_PATH .'/'. $module .'/docs/';
	
						if(is_dir($docs)) {
							self::register($module, $docs, '', $module, true);
						}
					}
				}
			}
		}
	}
	
	/**
	 * Convert a language code to a 'nice' text string. Uses the 
	 * {@link self::$language_mapping} array combined with translatable.
	 *
	 * @param String $code code
	 */
	public static function get_language_title($lang) {
		return (isset(self::$language_mapping[$lang])) ? _t("DOCUMENTATIONSERVICE.LANG-$lang", self::$language_mapping[$lang]) : $lang;
	}
	
	
	/**
	 * Find a documentation page given a path and a file name. It ignores the extensions
	 * and simply compares the title.
	 *
	 * Name may also be a path /install/foo/bar.
	 *
	 * @param DocumentationEntity 
	 * @param array exploded url string
	 * @param string version number
	 * @param string lang code
	 *
	 * @return String|false - File path
	 */
	static function find_page($module, $path, $version = '', $lang = 'en') {	
		if($module = self::is_registered_module($module, $version, $lang)) {
			return self::find_page_recursive($module->getPath($version, $lang), $path);
		}
		
		return false;
	}
	
	/**
	 * Recursive function for finding the goal of a path to a documentation
	 * page
	 *
	 * @return string
	 */
	private static function find_page_recursive($base, $goal) {
		$handle = opendir($base);

		$name = strtolower(array_shift($goal));
		if(!$name || $name == '/') $name = 'index';

		if($handle) {
			$extensions = DocumentationService::get_valid_extensions();

			// ensure we end with a slash
			$base = rtrim($base, '/') .'/';
			
			while (false !== ($file = readdir($handle))) {
				if(in_array($file, DocumentationService::get_valid_extensions())) continue;
				
				$formatted = strtolower($file);
				
				// if the name has a . then take the substr 
				$formatted = ($pos = strrpos($formatted, '.')) ? substr($formatted, 0, $pos) : $formatted;

				if($dot = strrpos($name, '.')) {
					if(in_array(substr($name, $dot), self::get_valid_extensions())) {
						$name = substr($name, 0, $dot);
					}
				}
				
				// the folder is the one that we are looking for.
				if(strtolower($name) == strtolower($formatted)) {
					
					// if this file is a directory we could be displaying that
					// or simply moving towards the goal.
					if(is_dir($base . $file)) {
						
						$base = $base . trim($file, '/') .'/';
						
						// if this is a directory check that there is any more states to get
						// to in the goal. If none then what we want is the 'index.md' file
						if(count($goal) > 0) {
							return self::find_page_recursive($base, $goal);
						}
						else {
							// recurse but check for an index.md file next time around
							return self::find_page_recursive($base, array('index'));
						}
					}
					else {
						// goal state. End of recursion.
						// tidy up the URLs with single trailing slashes
						$result =  $base . ltrim($file, '/');

						if(is_dir($result)) $result = (rtrim($result, '/') . '/');

						return $result;
					}
				}
			}
		}
		
		closedir($handle);
		
		return false;
	}
	
	/**
	 * String helper for cleaning a file name to a readable version. 
	 *
	 * @param String $name to convert
	 *
	 * @return String $name output
	 */
	public static function clean_page_name($name) {
		// remove dashs and _
		$name = str_replace(array('-', '_'), ' ', $name);
		
		// remove extension
		$name = self::trim_extension_off($name);
		
		// if it starts with a number strip and contains a space strip it off
		if(strpos($name, ' ') !== false) {
			$space = strpos($name, ' ');
			$short = substr($name, 0, $space);

			if(is_numeric($short)) {
				$name = substr($name, $space);
			}
		}
		
		// convert first letter
		return ucfirst(trim($name));
	}
	
	/**
	 * Helper function to strip the extension off.
	 * Warning: Doesn't work if the filename includes dots,
	 * but no extension, e.g. "2.4.0-alpha" will return "2.4".
	 *
	 * @param string
	 *
	 * @return string
	 */
	public static function trim_extension_off($name) {
		$hasExtension = strrpos($name, '.');

		if($hasExtension !== false && $hasExtension > 0) {
			$shorted = substr($name, $hasExtension);
			
			// can remove the extension only if we know how
			// to read it again
			if(in_array(rtrim($shorted, '/'), self::get_valid_extensions())) {
				$name = substr($name, 0, $hasExtension);
			}
		}
		
		return $name;
	}
	
	
	/**
	 * Return the children from a given module sorted by Title using natural ordering. 
	 * It is used for building the tree of the page.
	 *
	 * @param DocumentationEntity path
	 * @param string - an optional path within a module
	 * @param bool enable several recursive calls (more than 1 level)
	 * @param string - version to use
	 * @param string - lang to use
	 *
	 * @throws Exception
	 * @return DataObjectSet
	 */
	public static function get_pages_from_folder($module, $relativePath = false, $recursive = true, $version = 'trunk', $lang = 'en') {
		$output = new DataObjectSet();
		$pages = array();
		
		if(!$module instanceof DocumentationEntity) 
			user_error("get_pages_from_folder must be passed a module", E_USER_ERROR);
		
		$path = $module->getPath($version, $lang);

		
		if(self::is_registered_module($module)) {
			self::get_pages_from_folder_recursive($path, $relativePath, $recursive, $pages);
		}
		else {
			return user_error("$module is not registered", E_USER_WARNING);
		}

		if(count($pages) > 0) {
			natsort($pages);
			
			foreach($pages as $key => $pagePath) {
				
				// get file name from the path
				$file = ($pos = strrpos($pagePath, '/')) ? substr($pagePath, $pos + 1) : $pagePath;
				
				$page = new DocumentationPage();
				$page->setTitle(self::clean_page_name($file));
				$relative = str_replace($path, '', $pagePath);
				
				// if no extension, put a slash on it
				if(strpos($relative, '.') === false) $relative .= '/';

				$page->setEntity($module);
				$page->setRelativePath($relative);
				$page->setVersion($version);
				$page->setLang($lang);
				
				$output->push($page);
			}
		}
		
		return $output;
	}
	
	/**
	 * Recursively search through $folder
	 *
	 */ 
	private static function get_pages_from_folder_recursive($base, $relative, $recusive, &$pages) {
		if(!is_dir($base)) throw new Exception(sprintf('%s is not a folder', $folder));

		$folder = Controller::join_links($base, $relative);
		
		if(!is_dir($folder)) return false;
			
		$handle = opendir($folder);

		if($handle) {
			$extensions = self::get_valid_extensions();
			$ignore = self::get_ignored_files();
			$files = array();
			
			while (false !== ($file = readdir($handle))) {	
				if(!in_array($file, $ignore)) {
					
					$path = Controller::join_links($folder, $file);
					$relativeFilePath = Controller::join_links($relative, $file);

					if(is_dir($path)) {
						$pages[] = $relativeFilePath;
						
						if($recusive) self::get_pages_from_folder_recursive($base, $relativeFilePath, $recusive, $pages);
					} 
					else if(in_array(substr($file, (strrpos($file, '.'))), $extensions)) {
						$pages[] = $relativeFilePath;
					}
				}
			}
		}

		closedir($handle);
	}
}