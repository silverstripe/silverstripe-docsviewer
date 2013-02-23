<?php

/**
 * DocumentationService
 *
 * Handles the management of the documentation services delivered by the entity.
 * 
 * Includes registering which components to document and handles the entities being
 * documented.
 *
 * @package docsviewer
 */

class DocumentationService {
	
	/**
	 * A mapping of known / popular languages to nice titles. 
	 *
	 * @var Array
	 */
	private static $language_mapping = array(
		'en' => 'English',
		'fr' => 'Français',
		'de' => 'Deutsch'
	);
	
	/**
	 * Files to ignore from any documentation listing.
	 *
	 * @var array
	 */
	private static $ignored_files = array(
		'.', '..', '.DS_Store', 
		'.svn', '.git', 'assets', 'themes', '_images'
	);

	/**
	 * Case insenstive values to use as extensions on markdown pages. The
	 * recommended extension is .md.
	 *
	 * @var array
	 */
	public static $valid_markdown_extensions = array('md', 'txt', 'markdown');
	
	/**
	 * Registered {@link DocumentationEntity} objects to include in the 
	 * documentation. 
	 *
	 * Either pre-filled by the automatic filesystem parser or via 
	 * {@link DocumentationService::register()}. 
	 *
	 * Stores the {@link DocumentEntity} objects which contain the languages 
	 * and versions of each entity.
	 *
	 * You can remove registered {@link DocumentationEntity} objects by using 
	 * {@link DocumentationService::unregister()}
	 *
	 * @var array
	 */
	private static $registered_entities = array();
	
	
	/**
	 * Should generation of documentation categories be automatic? 
	 *
	 * If this is set to true then it will generate {@link DocumentationEntity}
	 * objects from the filesystem. This can be slow and also some projects 
	 * may want to restrict to specific project folders (rather than everything).
	 *
	 * You can also disable or remove a given folder from registration using 
	 * {@link DocumentationService::unregister()}
	 *
	 * @see DocumentationService::$registered_entities
	 * @see DocumentationService::set_automatic_registration();
	 *
	 * @var bool
	 */
	private static $automatic_registration = true;

	/**
	 * Rootpages are non-localized documentation pages that live in the
	 * root of the entity. They are enabled by default.
	 * 
	 * @var bool
	 */
	private static $rootpages_enabled = true;	

	/**
	 * Disables the display of rootpages for single entities. By default
	 * all entities adhere to the $rootpages_enabled setting
	 * 
	 * @var array
	 */
	private static $rootpages_disabled_for = array();
	
	/**
	 * In the proper order: which files can serve as index file
	 * 
	 * @var array
	 */
	private static $valid_index_files = array('index', 'readme');	
	
	/**
	 * by default pagenumbers start high at 10.000
	 * 
	 * @var int 
	 */
	private static $pagenumber_start_at = 10000;
	
	/**
	 * allow the use of key/value pairs in comments <!-- page: 2 -->
	 * @var bool
	 */
	private static $meta_comments_enabled = false;
	
	/**
	 * Return the allowed extensions
	 *
	 * @return array
	 */
	public static function get_valid_extensions() {
		return self::$valid_markdown_extensions;
	}
	
	/**
	 * Check to see if a given extension is a valid extension to be rendered.
	 * Assumes that $ext has a leading dot as that is what $valid_extension uses.
	 *
	 * @return bool
	 */
	public static function is_valid_extension($ext) {
		return in_array(strtolower($ext), self::get_valid_extensions());
	}
	
	/**
	 * Set the ignored files list
	 *
	 * @param array
	 */
	public static function set_ignored_files($files) {
		self::$ignored_files = $files;
	}
	
	/**
	 * Return the list of files which are ignored
	 *
	 * @return array
	 */
	public static function get_ignored_files() {
		return self::$ignored_files;
	}

	/**
	 * Set automatic registration of entities and documentation folders
	 *
	 * @see DocumentationService::$automatic_registration
	 * @param bool
	 */
	public static function set_automatic_registration($bool = true) {
		self::$automatic_registration = $bool;
		
		if(!$bool) {
			// remove current registed entities when disabling automatic registration
			// needed to avoid caching issues when running all the tests
			self::$registered_entities = array();
		}
	}
	
	/**
	 * Is automatic registration of entities enabled.
	 *
	 * @return bool
	 */
	public static function automatic_registration_enabled() {
		return self::$automatic_registration;
	}

	/**
	 * Rootpages are enabled glabally by default, disable by setting
	 * DocumentationService::enable_rootpages(false);  
	 * 
	 * @param bool $enable
	 */
	public static function enable_rootpages($enabled = true) {
		self::$rootpages_enabled = ($enabled)? true: false;
	} 

	/**
	 * Exclude certain entities from displaying their rootpages
	 * 
	 * @param type $entities
	 */
	public static function disable_rootpages_for($entities) {
		if (is_array($entities) && !empty($entities)) {
			self::$rootpages_disabled_for = $entities;
		}
	}

	/**
	 * Return the valid index files (no extensions)
	 *
	 * @return array
	 */	
	public static function get_valid_index_files() {
		return self::$valid_index_files;
	}
	
	/**
	 * Cset an array of valid index file (in order of importance, 
	 * no extensions)
	 * 
	 * @return array
	 */
	public static function set_valid_index_files($indexes) {
		self::$valid_index_files = $indexes;
	}

	/**
	 * Are rootpages enabled for this entity? If no entity provided, 
	 * return the global setting for all entities.
	 * 
	 * @return bool
	 */
	public static function get_rootpages_enabled($entity = '') {
		if (!self::$rootpages_enabled) return false;
		if ($entity && in_array($entity, self::$rootpages_disabled_for)) return false;
		return true;
	}	
	
	/** 
	 * set the number to start default pagenumbering, allowing room for 
	 * custom pagenumbers below.
	 * 
	 * @param int $number
	 */
	public static function start_pagenumbers_at($number = 10000) {
		if (is_int($number)) self::$pagenumber_start_at = $number;
	}

	/**
	 * return the startlevel for default pagenumbering
	 * 
	 * @return int
	 */
	public static function get_pagenumber_start_at() {
		return self::$pagenumber_start_at;
	}
	
	/**
	 * allow the use of key/value pairs incomments?
	 * Example (supported are title and page): <!-- page: 2 --> 
	 * 
	 * @param bool $allow
	 */
	public static function enable_meta_comments($allow = true) {
		self::$meta_comments_enabled = ($allow)? true: false;
	}

	/**
	 * can we use key/value pairs from <!--   --> comments?
	 * 
	 * @return bool
	 */
	public static function meta_comments_enabled() {
		return self::$meta_comments_enabled;
	}
	
	/**
	 * Return the entities which are listed for documentation. Optionally only 
	 * get entities which have a version or language given.
	 *
	 * @return array
	 */
	public static function get_registered_entities($version = false, $lang = false) {
		$output = array();
		
		if($entities = self::$registered_entities) {
			if($version || $lang) {
				foreach($entities as $entity) {
					if(self::is_registered_entity($entity->getFolder(), $version, $lang)) {
						$output[$entity->getFolder()] = $entity;
					}
				}
			}
			else {
				$output = $entities;
			}
		}
		
		return $output;
	}
	
	/**
	 * Check to see if a entity is registered with the documenter.
	 *
	 * @param String $entity entity name
	 * @param String $version version
	 * @param String $lang language
	 *
	 * @return DocumentationEntity $entity the registered entity
	 */
	public static function is_registered_entity($entity, $version = false, $lang = false) {
		$check = ($entity instanceof DocumentationEntity) ? $entity->getFolder() : (string) $entity;

		if(isset(self::$registered_entities[$check])) {
			$entity = self::$registered_entities[$check];
	
			if(($lang && !$entity->hasLanguage($lang)) || ($version && !$entity->hasVersion($version))) {
				return false;
			}
			
			return $entity;
		}

		return false;		
	}
	
	/**
	 * Register a entity to be included in the documentation. To unregister a entity
	 * use {@link DocumentationService::unregister()}. Must include the trailing slash
	 *
	 * @param String $entity Name of entity to register
	 * @param String $path Path to documentation root.
	 * @param Float $version Version of entity.
	 * @param String $title Nice title to use
	 * @param bool $latest - return is this the latest release.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return DocumentationEntity
	 */
	public static function register($entity, $path, $version = 'current', $title = false, $latest = false) {
		if(!file_exists($path)) throw new InvalidArgumentException(sprintf('Path "%s" doesn\'t exist', $path));

		if (!$version) $version = 'current';
		
		$rootPath = '';
		self::configure_paths($entity, $path, $rootPath);
				
		// add the entity to the registered array
		if(!isset(self::$registered_entities[$entity])) {
			// entity is completely new
			$output = new DocumentationEntity($entity, $version, $path, $title);
			
			self::$registered_entities[$entity] = $output;
		}
		else {
			// entity exists so add the version to it
			$output = self::$registered_entities[$entity];
			$output->addVersion($version, $path);
		}
		if (!empty($rootPath)) 
			$output->setRootPath($rootPath);
		
		if($latest)
			$output->setStableVersion($version);
		
		return $output;
	}
	
	/**
	 * Unregister a entity from being included in the documentation. Useful
	 * for keeping {@link DocumentationService::$automatic_registration} enabled
	 * but disabling entities which you do not want to show. Combined with a 
	 * {@link Director::isLive()} you can hide entities you don't want a client to see.
	 *
	 * If no version or lang specified then the whole entity is removed. Otherwise only
	 * the specified version of the documentation.
	 *
	 * @param String $entity
	 * @param String $version
	 *
	 * @return bool
	 */
	public static function unregister($entityName, $version = false) {
		if(isset(self::$registered_entities[$entityName])) {
			$entity = self::$registered_entities[$entityName];
			
			if($version) {
				$entity->removeVersion($version);
			}
			else {
				// only given a entity so unset the whole entity
				unset(self::$registered_entities[$entityName]);	
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Register the docs from off a file system if automatic registration is 
	 * turned on.
	 *
	 * @see {@link DocumentationService::set_automatic_registration()}
	 */
	public static function load_automatic_registration() {
		if(self::automatic_registration_enabled()) {
			$entities = scandir(BASE_PATH);

			if($entities) {
				foreach($entities as $key => $entity) {
					$entityRoot = Controller::join_links(BASE_PATH, $entity);
					$dir = is_dir($entityRoot);
					$ignored = in_array($entity, self::get_ignored_files(), true);
					
					if($dir && !$ignored) {
						$docs = Controller::join_links($entityRoot, 'docs');
						if(is_dir($docs)) {
							self::register($entity, $docs, 'current', $entity, true);
						} elseif (self::get_rootpages_enabled($entity)) {	
							//check if there are files in the root and displaying them is allowed
							$rootPages = array();
							self::get_pages_from_folder_recursive($entityRoot, '', false, $rootPages, true);
							if (count($rootPages) > 0) {
								self::register($entity, $entityRoot, 'current', $entity, true);
							}
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
		$map = self::$language_mapping;
		
		if(isset($map[$lang])) { 
			return _t("DOCUMENTATIONSERVICE.LANG-$lang", $map[$lang]);
		}
		
		return $lang;
	}
	
	
	/**
	 * Find a documentation page given a path and a file name. It ignores the 
	 * extensions and simply compares the title.
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
	static function find_page($entity, $path, $version = '', $lang = 'en') {	
		if($entity = self::is_registered_entity($entity, $version, $lang)) {
			$result = self::find_page_recursive($entity->getPath($version, $lang), $path);
			
			// if nothing is found, this might be a rootpage, so search the root,
			// without recursion!
			if (!$result && $rootPath = $entity->getRootPath()) {
				$result = self::find_page_recursive($rootPath, $path, false);
			}			
			return $result;
		}
	
		return false;
	}
	
	/**
	 * Recursive function for finding the goal of a path to a documentation
	 * page
	 *
	 * @return string
	 */
	private static function find_page_recursive($base, $goal, $recursive=true) {
		$handle = (is_dir($base)) ? opendir($base) : false;

		$name = self::trim_extension_off(strtolower(array_shift($goal)));
		$arrName = (!$name || $name == '/') ? self::$valid_index_files : array($name);


		if($handle) {
			$ignored = self::get_ignored_files();
			
			// ensure we end with a slash
			$base = rtrim($base, '/') .'/';
			
			while (false !== ($file = readdir($handle))) {
				if(in_array($file, $ignored)) continue;
				
				$formatted = self::trim_extension_off(strtolower($file));
				
				foreach ($arrName as $aName) {
					// the folder is the one that we are looking for.
					if(strtolower($aName) == strtolower($formatted)) {

						// if this file is a directory we could be displaying that
						// or simply moving towards the goal.
						if(is_dir(Controller::join_links($base, $file))) {
							if ($recursive) {
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
		}
		
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
	 * Helper function to strip the extension off and return the name without
	 * the extension. If you need the extension see {@link get_extension()}
	 *
	 * @param string
	 *
	 * @return string
	 */
	public static function trim_extension_off($name) {
		$ext = self::get_extension($name);

		if($ext) {
			if(self::is_valid_extension($ext)) {
				return substr($name, 0, strrpos($name,'.'));
			}
		}
		
		return $name;
	}
	
	/**
	 * Returns the extension from a string. If you want to trim the extension
	 * off the end of the string see {@link trim_extension_off()}
	 *
	 * @param string
	 * 
	 * @return string
	 */
	public static function get_extension($name) {
		return substr(strrchr($name,'.'), 1);
	}
	
	/**
	 * Return the children from a given entity sorted by Title using natural ordering. 
	 * It is used for building the tree of the page.
	 *
	 * @param DocumentationEntity path
	 * @param string - an optional path within a entity
	 * @param bool enable several recursive calls (more than 1 level)
	 * @param string - version to use
	 * @param string - lang to use
	 *
	 * @throws Exception
	 * @return ArrayList
	 */
	public static function get_pages_from_folder($entity, $relativePath = false, $recursive = true, $version = 'trunk', $lang = 'en') {
		$output = new ArrayList();
		$metaCommentsEnabled = self::meta_comments_enabled();
		$pages = array();
		
		if(!$entity instanceof DocumentationEntity) 
			user_error("get_pages_from_folder must be passed a entity", E_USER_ERROR);
		
		$path = $entity->getPath($version, $lang);

		
		if(self::is_registered_entity($entity)) {
			self::get_pages_from_folder_recursive($path, $relativePath, $recursive, $pages);
			if(count($pages) > 0) natcasesort($pages);
			if (empty($relativePath) && $rootPath = $entity->getRootPath()) {
				$rootPages = array();
				self::get_pages_from_folder_recursive($rootPath, $relativePath, $recursive, $rootPages, true);
				if(count($rootPages) > 0) {
					natcasesort($rootPages);
					foreach ($rootPages as $page) {
						if (!in_array($page, $pages)) $pages[] = $page;
					}
				}
			}			
		}
		else {
			return user_error("$entity is not registered", E_USER_WARNING);
		}

		if(count($pages) > 0) {
			$pagenumber = self::get_pagenumber_start_at();
			
			foreach($pages as $key => $pagePath) {
				
				// get file name from the path
				$file = ($pos = strrpos($pagePath, '/')) ? substr($pagePath, $pos + 1) : $pagePath;
				
				$page = new DocumentationPage();
				$page->setTitle(self::clean_page_name($file));
				$relative = str_replace($path, '', $pagePath);
				
				// if no extension, put a slash on it
				if(strpos($relative, '.') === false) $relative .= '/';

				$page->setEntity($entity);
				$page->setRelativePath($relative);
				$page->setVersion($version);
				$page->setLang($lang);
				
				// does this page act as a folder?
				$path = $page->getPath();
				if (is_dir($path)) { $page->setIsFolder(true); }
				
				$page->setPagenumber($pagenumber++);
				// we need the markdown to get the comments
				if ($metaCommentsEnabled) $page->getMarkdown();

				$output->push($page);
			}
		}
		
		return ($metaCommentsEnabled)? $output->sort('pagenumber') : $output;
	}
	
	/**
	 * Recursively search through a given folder
	 *
	 * @see {@link DocumentationService::get_pages_from_folder}
	 */ 
	private static function get_pages_from_folder_recursive($base, $relative, $recusive, &$pages, $filesOnly=false) {
		//if(!is_dir($base)) throw new Exception(sprintf('%s is not a folder', $folder));

		$folder = Controller::join_links($base, $relative);
		
		if(!is_dir($folder)) return false;
			
		$handle = opendir($folder);

		if($handle) {
			$ignore = self::get_ignored_files();
			$files = array();
			
			while (false !== ($file = readdir($handle))) {	
				if(!in_array($file, $ignore)) {
					
					$path = Controller::join_links($folder, $file);
					$relativeFilePath = Controller::join_links($relative, $file);

					if(is_dir($path) && !$filesOnly) {
						// dir
						$pages[] = $relativeFilePath;
						
						if($recusive) self::get_pages_from_folder_recursive($base, $relativeFilePath, $recusive, $pages);
					} 
					else if(self::is_valid_extension(self::get_extension($path))) {
						// file we want
						$pages[] = $relativeFilePath;
					}
				}
			}
		}

		closedir($handle);
	}

	/**
	 * On default registration, the path wil point to the root of the 
	 * entity. On manual registration of entities that follow the 
	 * SilverStripe standards having a /docs folder, the path can
	 * still point to the /docs folder (backwards compatibility)
	 * 
	 * For entities without a docs folder, the rootfolder will be equal to 
	 * the path, But the entity can still have separate rootfiles, as all 
	 * localized docs will always live in language/version directories. 
	 * 
	 * @param string reference $path
	 * @param string reference $rootPath
	 */
	private static function configure_paths($entity, &$path, &$rootPath) {
		$path = rtrim($path, '/');	
		$docsPath = Controller::join_links($path, 'docs');
		if (is_dir($docsPath)) {
			$path = $docsPath;
		} 
		
		if (self::get_rootpages_enabled($entity)) {
			$rootPath = $path;
			if ('/docs' == substr($path, -5, 5)) {
				$subPath = substr($path, 0, -5);
				if ($subPath != BASE_PATH) $rootPath = $subPath;				
			} 
		}
	}
	
}
