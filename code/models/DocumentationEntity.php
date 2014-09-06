<?php

/**
 * A {@link DocumentationEntity} represents a module or folder with 
 * documentation not an individual page. Entities are loaded via 
 * {@link DocumentationService::register()} and individual pages are represented 
 * by a {@link DocumentationPage} and are loaded by the manifest.
 * 
 * 
 * @package docsviewer
 * @subpackage models
 */

class DocumentationEntity extends ViewableData {
	
	/**
	 * @var array
	 */
	private static $casting = array(
		'Title' => 'Text'
	);
	
	/**
	 * @var string $title
	 */
	protected $title;

	/**
	 * @var string $folder
	 */
	protected $folder;

	/**
	 * @var ArrayList $versions
	 */
	protected $versions;
	
	/**
	 * Constructor. You do not need to pass the langs to this as
	 * it will work out the languages from the filesystem
	 *
	 * @param string $folder folder name
	 * @param string $title
	 */
	public function __construct($folder, $title = false) {
		$this->versions = new ArrayList();
		$this->folder = $folder;
		$this->title = (!$title) ? $folder : $title;
	}
	
	/**
	 * Get the title of this module.
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
		return $this->versions;
	}
	
	/**
	 * @return string|boo
	 */
	public function getStableVersion() {
		if(!$this->hasVersions()) {
			return false;
		}

		$sortedVersions = $this->getVersions();
			
		usort($sortedVersions, create_function('$a,$b', 'return version_compare($a,$b);'));
			
		return array_pop($sortedVersions);
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
		return $this->versions->find('Version', $version);
	}
	
	/**
	 * Return whether we have any versions at all0
	 *
	 * @return bool
	 */
	public function hasVersions() {
		return $this->versions->count() > 0;
	}
	
	/**
	 * Add another version to this entity
	 *
	 * @param DocumentationEntityVersion
	 */
	public function addVersion($version) {
		$this->versions->push($version);

		return $this;
	}
	
	/**
	 * Remove a version from this entity
	 *
	 * @param float $version
	 *
	 */
	public function removeVersion($version) {
		$this->versions->remove('Version', $version);

		return $this;
	}
	
	/**
	 * Return the absolute path to this documentation entity.
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}
	
	/**
	 * @return string
	 */
	public function getFolder() {
		return $this->folder;
	}

	/**
	 * Returns the web accessible link to this Entity
	 *
	 * @return string
	 */
	public function Link() {
		return Controller::join_links(
			Config::inst()->get('DocumentationViewer', 'link_base'), 
			$this->getFolder()
		);
	}
	
	/**
	 * @return string
	 */
	public function __toString() {
		return sprintf('DocumentationEntity: %s)', $this->getPath());
	}

}