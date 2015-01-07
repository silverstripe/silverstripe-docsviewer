<?php

/**
 * A {@link DocumentationEntity} represents a module or folder with stored
 * documentation files. An entity not an individual page but a `section` of
 * documentation arranged by version and language.
 *
 * Each entity has a version assigned to it (i.e master) and folders can be
 * labeled with a specific version. For instance, doc.silverstripe.org has three
 * DocumentEntities for Framework - versions 2.4, 3.0 and 3.1. In addition an
 * entity can have a language attached to it. So for an instance with en, de and
 * fr documentation you may have three {@link DocumentationEntities} registered.
 *
 *
 * @package docsviewer
 * @subpackage models
 */

class DocumentationEntity extends ViewableData {

	/**
	 * The key to match entities with that is not localized. For instance, you
	 * may have three entities (en, de, fr) that you want to display a nice
	 * title for, but matching needs to occur on a specific key.
	 *
	 * @var string $key
	 */
	protected $key;

	/**
	 * The human readable title of this entity. Set when the module is
	 * registered.
	 *
	 * @var string $title
	 */
	protected $title;

	/**
	 * If the system is setup to only document one entity then you may only
	 * want to show a single entity in the URL and the sidebar. Set this when
	 * you register the entity with the key `DefaultEntity` and the URL will
	 * not include any version or language information.
	 *
	 * @var boolean $default_entity
	 */
	protected $defaultEntity;

	/**
	 * @var mixed
	 */
	protected $path;

	/**
	 * @see {@link http://php.net/manual/en/function.version-compare.php}
	 * @var float $version
	 */
	protected $version;

	/**
	 * The repository branch name (allows for $version to be an alias on development branches).
	 *
	 * @var string $branch
	 */
	protected $branch;

	/**
	 * If this entity is a stable release or not. If it is not stable (i.e it
	 * could be a past or future release) then a warning message will be shown.
	 *
	 * @var boolean $stable
	 */
	protected $stable;

	/**
	 * @var string
	 */
	protected $language;

	/**
	 *
	 */
	public function __construct($key) {
		$this->key = DocumentationHelper::clean_page_url($key);
	}


	/**
	 * Get the title of this module.
	 *
	 * @return string
	 */
	public function getTitle() {
		if(!$this->title) {
			$this->title = DocumentationHelper::clean_page_name($this->key);
		}

		return $this->title;
	}

	/**
	 * @param string $title
	 * @return this
	 */
	public function setTitle($title) {
		$this->title = $title;

		return $this;
	}

	/**
	 * Returns the web accessible link to this entity.
	 *
	 * Includes the version information
	 *
	 * @return string
	 */
	public function Link() {
		if($this->getIsDefaultEntity()) {
			$base = Controller::join_links(
				Config::inst()->get('DocumentationViewer', 'link_base'),
				$this->getLanguage(),
				'/'
			);
		} else {
			$base = Controller::join_links(
				Config::inst()->get('DocumentationViewer', 'link_base'),
				$this->getLanguage(),
				$this->getKey(),
				'/'
			);
		}

		$base = ltrim(str_replace('//', '/', $base), '/');

		if($this->stable) {
			return $base;
		}

		return Controller::join_links(
			$base,
			$this->getVersion(),
			'/'
		);
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return sprintf('DocumentationEntity: %s)', $this->getPath());
	}

	/**
	 * @param DocumentationPage $page
	 *
	 * @return boolean
	 */
	public function hasRecord($page) {
		if(!$page) {
			return false;
		}

		return strstr($page->getPath(), $this->getPath()) !== false;
	}

	/**
	 * @param boolean $bool
	 */
	public function setIsDefaultEntity($bool) {
		$this->defaultEntity = $bool;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getIsDefaultEntity() {
		return $this->defaultEntity;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @return string
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * @param string
	 *
	 * @return this
	 */
	public function setLanguage($language) {
		$this->language = $language;

		return $this;
	}

	/**
	 * @param string
	 */
	public function setVersion($version) {
		$this->version = $version;

		return $this;
	}

	/**
	 * @return float
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @param string
	 */
	public function setBranch($branch) {
		$this->branch = $branch;

		return $this;
	}

	/**
	 * @return float
	 */
	public function getBranch() {
		return $this->branch;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @param string $path
	 *
	 * @return this
	 */
	public function setPath($path) {
		$this->path = $path;

		return $this;
	}

	/**
	 * @param boolean
	 */
	public function setIsStable($stable) {
		$this->stable = $stable;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getIsStable() {
		return $this->stable;
	}



	/**
	 * Returns an integer value based on if a given version is the latest
	 * version. Will return -1 for if the version is older, 0 if versions are
	 * the same and 1 if the version is greater than.
	 *
	 * @param string $version
	 * @return int
	 */
	public function compare(DocumentationEntity $other) {
		return version_compare($this->getVersion(), $other->getVersion());
	}

	/**
	 * @return array
	 */
	public function toMap() {
		return array(
			'Key' => $this->key,
			'Path' => $this->getPath(),
			'Version' => $this->getVersion(),
			'Branch' => $this->getBranch(),
			'IsStable' => $this->getIsStable(),
			'Language' => $this->getLanguage()
		);
	}
}
