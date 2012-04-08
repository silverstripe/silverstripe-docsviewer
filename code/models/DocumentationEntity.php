<?php

/**
 * A {@link DocumentationEntity} is created when registering a module path 
 * with {@link DocumentationService::register()}. A {@link DocumentationEntity} 
 * represents a module or folder with documentation rather than a specific 
 * page. Individual pages are handled by {@link DocumentationPage}
 *
 * Each folder must have at least one language subfolder, which is automatically
 * determined through {@link addVersion()} and should not be included in the 
 * $path argument.
 * 
 * Versions are assumed to be in numeric format (e.g. '2.4'),
 *
 * They're also parsed through version_compare() in {@link getStableVersion()} 
 * which assumes a certain format:
 *
 * @see http://php.net/manual/en/function.version-compare.php
 *
 * @package docsviewer
 * @subpackage models
 */

class DocumentationEntity extends ViewableData {
	
	/**
	 * @var array
	 */
	static $casting = array(
		'Name' => 'Text'
	);
	
	/**
	 * @var string $folder folder name
	 */
	private $folder;
	
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
	private $stableVersion;
	
	/**
	 * @var Array $langs a list of available langauges
	 */
	private $langs = array();
	
	/**
	 * Constructor. You do not need to pass the langs to this as
	 * it will work out the languages from the filesystem
	 *
	 * @param string $folder folder name
	 * @param string $version version of this module
	 * @param string $path Absolute path to this module (excluding language folders)
	 * @param string $title
	 */
	function __construct($folder, $version, $path, $title = false) {
		$this->addVersion($version, $path);
		$this->title = (!$title) ? $folder : $title;
		$this->folder = $folder;
	}
	
	/**
	 * Return the languages which are available
	 *
	 * @return array
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
	public function getFolder() {
		return $this->folder;
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
	 * Return the versions which have been registered for this entity.
	 *
	 * @return array
	 */
	public function getVersions() {
		return array_keys($this->versions);
	}
	
	/**
	 * @return string|boo
	 */
	public function getStableVersion() {
		if(!$this->hasVersions()) return false;
		
		if($this->stableVersion) {
			return $this->stableVersion;
		} else {
			$sortedVersions = $this->getVersions();
			usort($sortedVersions, create_function('$a,$b', 'return version_compare($a,$b);'));
			
			return array_pop($sortedVersions);
		}
	}
	
	/**
	 * @param String $version
	 */
	public function setStableVersion($version) {
		if(!$this->hasVersion($version)) throw new InvalidArgumentException(sprintf('Version "%s" does not exist', $version));
		$this->stableVersion = $version;
	}
	
	/**
	 * Returns an integer value based on if a given version is the latest 
	 * version. Will return -1 for if the version is older, 0 if versions are 
	 * the same and 1 if the version is greater than.
	 *
	 * @param string $version
	 * @return int
	 */
	public function compare($version) {
		$latest = $this->getStableVersion();
		
		return version_compare($version, $latest);
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
	 */
	public function addVersion($version = '', $path) {

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
		if(!$version) $version = $this->getStableVersion();
		if(!$lang) $lang = 'en';
		
		if($this->hasVersion($version)) {
			$path = $this->versions[$version];
		}	
		else {
			$versions = $this->getVersions();
			$path = $this->versions[$versions[0]]; 
		}
		
		return Controller::join_links($path, $lang);
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
		if(!$lang) $lang = 'en';
		if($version == $this->getStableVersion()) $version = false;
		
		return Controller::join_links(
			DocumentationViewer::get_link_base(), 
			$this->getFolder(),
			$lang,
			$version
		);
	}
	
	/**
	 * Return the summary / index text for this entity. Either pulled
	 * from an index file or some other summary field
	 *
	 * @return DocumentationPage
	 */
	function getIndexPage($version, $lang = 'en') {
		$path = $this->getPath($version, $lang);
		$absFilepath = Controller::join_links($path, 'index.md');
		
		if(file_exists($absFilepath)) {
			$relativeFilePath = str_replace($path, '', $absFilepath);
			
			$page = new DocumentationPage();
			$page->setRelativePath($relativeFilePath);
			$page->setEntity($this);
			$page->setLang($lang);
			$page->setVersion($version);
			
			return $page;
		}
		
		return false;
	}
	
	/**
	 * @return string
	 */
	function __toString() {
		return sprintf('DocumentationEntity: %s)', $this->getPath());
	}
}