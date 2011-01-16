<?php

/**
 * A wrapper for a documentation entity which is created when registering the
 * path with {@link DocumentationService::register()}. This refers to a whole package
 * rather than a specific page but if we need page options we may need to introduce 
 * a class for that.
 * 
 * Each folder must have at least one language subfolder, which is automatically
 * determined through {@link addVersion()} and should not be included in the $path argument.
 * 
 * Versions are assumed to be in numeric format (e.g. '2.4'),
 * mainly as an easy way to distinguish them from language codes in the routing logic.
 * They're also parsed through version_compare() in {@link getCurrentVersion()} which assumes a certain format. 
 *
 * @package sapphiredocs
 */

class DocumentationEntity extends ViewableData {
	
	static $casting = array(
		'Name' => 'Text'
	);
	
	/**
	 * @var string $module folder name
	 */
	private $moduleFolder;
	
	/**
	 * @var string $title nice title
	 */
	private $title;

	/**
	 * @var array $version version numbers and the paths to each
	 */
	private $versions = array();
	
	/**
	 * @var array
	 */
	private $currentVersion;
	
	/**
	 * @var Array $langs a list of available langauges
	 */
	private $langs = array();
	
	/**
	 * Constructor. You do not need to pass the langs to this as
	 * it will work out the languages from the filesystem
	 *
	 * @param string $module name of module
	 * @param string $version version of this module
	 * @param string $path Absolute path to this module (excluding language folders)
	 * @param string $title
	 */
	function __construct($module, $version, $path, $title = false) {
		$this->addVersion($version, $path);
		$this->title = (!$title) ? $module : $title;
		$this->moduleFolder = $module;
	}
	
	/**
	 * Return the languages which are available
	 *
	 * @return Array
	 */
	public function getLanguages() {
		return $this->langs;
	}
	
	/**
	 * Return whether this entity has a given language
	 *
	 * @return bool
	 */
	public function hasLanguage($lang) {
		return (in_array($lang, $this->langs));
	}
	
	/**
	 * Add a langauge or languages to the entity
	 *
	 * @param Array|String languages
	 */
	public function addLanguage($language) {
		if(is_array($language)) {
			$this->langs = array_unique(array_merge($this->langs, $language));
		}
		else {
			$this->langs[] = $language;
		}
	}
	
	/**
	 * Get the folder name of this module
	 *
	 * @return String
	 */
	public function getModuleFolder() {
		return $this->moduleFolder;
	}
	
	/**
	 * Get the title of this module
	 *
	 * @return String
	 */
	public function getTitle() {
		return $this->title;
	}
	
	/**
	 * Return the versions which are available
	 *
	 * @return Array
	 */
	public function getVersions() {
		return array_keys($this->versions);
	}
	
	/**
	 * @return String|Boolean
	 */
	public function getCurrentVersion() {
		if(!$this->hasVersions()) return false;
		
		if($this->currentVersion) {
			return $this->currentVersion;
		} else {
			$sortedVersions = $this->getVersions();
			usort($sortedVersions, create_function('$a,$b', 'return version_compare($a,$b);'));
			return array_pop($sortedVersions);
		}
	}
	
	/**
	 * @param String $version
	 */
	public function setCurrentVersion($version) {
		if(!$this->hasVersion($version)) throw new InvalidArgumentException(sprintf('Version "%s" does not exist', $version));

		$this->currentVersion = $version;
	}
	
	/**
	 * Return whether we have a given version of this entity
	 *
	 * @return bool
	 */
	public function hasVersion($version) {
		return (isset($this->versions[$version]));
	}
	
	/**
	 * Return whether we have any versions at all0
	 *
	 * @return bool
	 */
	public function hasVersions() {
		return (sizeof($this->versions) > 0);
	}
	
	/**
	 * Add another version to this entity
	 *
	 * @param Float $version Version number
	 * @param String $path path to folder
	 * @param Boolean $current
	 */
	public function addVersion($version = '', $path, $current = false) {
		// determine the langs in this path
		
		$langs = scandir($path);
		
		$available = array();
		
		if($langs) {
			foreach($langs as $key => $lang) {
				if(!is_dir($path . $lang) || strlen($lang) > 2 || in_array($lang, DocumentationService::get_ignored_files(), true)) 
					$lang = 'en';
				
				if(!in_array($lang, $available))
					$available[] = $lang;
			}
		}
		
		$this->addLanguage($available);
		$this->versions[$version] = $path;
		
		if($current) $this->setCurrentVersion($version);
	}
	
	/**
	 * Remove a version from this entity
	 *
	 * @param Float $version
	 */
	public function removeVersion($version = '') {
		if(isset($this->versions[$version])) {
			unset($this->versions[$version]);
		}
	}
	
	/**
	 * Return the absolute path to this documentation entity on the
	 * filesystem
	 *
	 * @return string
	 */
	public function getPath($version = false, $lang = false) {
		
		if(!$version) $version = '';
		if(!$lang) $lang = 'en';
		
		if($this->hasVersion($version)) {
			$path = $this->versions[$version];
		}	
		else {
			$versions = $this->getVersions();
			$path = $this->versions[$versions[0]]; 
		}
		
		return rtrim($path, '/') . '/' . rtrim($lang, '/') .'/';
	}
	
	/**
	 * Returns the web accessible link to this Entity
	 *
	 * @return string
	 */
	public function Link($version = false, $lang = false) {
		return Controller::join_links(
			Director::absoluteBaseURL(),
			$this->getRelativeLink($version, $lang)
		);
	}
	
	function getRelativeLink($version = false, $lang = false) {
		if(!$version) $version = '';
		if(!$lang) $lang = 'en';
		
		return Controller::join_links(
			DocumentationViewer::get_link_base(), 
			$this->moduleFolder,
			$lang,
			$version
		);
	}
	
	/**
	 * @return string
	 */
	function __toString() {
		return sprintf('DocumentationEntity: %s)', $this->getPath());
	}
}