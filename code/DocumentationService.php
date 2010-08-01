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
	 * @var Array
	 */
	private static $ignored_files = array('.', '..', '.DS_Store', '.svn', '.git', 'assets', 'themes', '_images');
	
	/**
	 * Set the ignored files list
	 *
	 * @param Array
	 */
	public function set_ignored_files($files) {
		self::$ignored_files = $files;
	}
	
	/**
	 * Return the list of files which are ignored
	 *
	 * @return Array
	 */
	public function get_ignored_files() {
		return self::$ignored_files;
	} 
	
	/**
	 * Case insenstive values to use as extensions on markdown pages.
	 *
	 * @var Array
	 */
	public static $valid_markdown_extensions = array('.md', '.txt', '.markdown');

	/**
	 * Return the allowed extensions
	 *
	 * @return Array
	 */
	public static function get_valid_extensions() {
		return self::$valid_markdown_extensions;
	}
	
	/**
	 * Registered modules to include in the documentation. Either pre-filled by the
	 * automatic filesystem parser or via {@link DocumentationService::register()}. Stores
	 * {@link DocumentEntity} objects which contain the languages and versions of each module.
	 *
	 * You can remove registered modules using {@link DocumentationService::unregister()}
	 *
	 * @var Array
	 */
	private static $registered_modules = array();
	
	/**
	 * Major Versions store. We don't want to register all versions of every module in
	 * the documentation but for sapphire/cms and overall we need to register major
	 * versions via {@link DocumentationService::register}
	 *
	 * @var Array
	 */
	private static $major_versions = array();
	
	/**
	 * Return the major versions
	 *
	 * @return Array
	 */
	public static function get_major_versions() {
		return self::$major_versions;
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
			if(isset($registered_modules[$module])) {
				return $registered_modules[$module]->getVersions();
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
		if(isset(self::$registered_modules[$module])) {
			$module = self::$registered_modules[$module];
			if($lang && !$module->hasLanguage($lang)) return false;
			if($version && !$module->hasVersion($version)) return false;
			
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
	 */
	public static function register($module, $path, $version = '', $title = false, $major = false) {
		
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
					if(is_dir(BASE_PATH .'/'. $module) && !in_array($module, self::$ignored_files, true)) {
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
}