<?php

/**
 * A more specific instance of a {@link DocumentationEntity}. Each instance of
 * a entity will have at least one of these objects attached to encapsulate 
 * linking to a particular URL.
 *
 * Versions are assumed to be in numeric format (e.g. '2.4'),
 *
 * They're also parsed through version_compare() in {@link getStableVersion()} 
 * which assumes a certain format:
 *
 * @see http://php.net/manual/en/function.version-compare.php
 *
 * Each {@link DocumentationEntityVersion} has a list of supported language 
 * instances. All documentation in the docs folder must sit under a supported
 * language {@link DocumentationEntityLanguage}.
 *
 * @package docsviewer
 */

class DocumentationEntityVersion extends ViewableData {

	/**
	 * @var array
	 */
	protected $supportedLanguages = array();

	/**
	 * @var DocumentationEntity
	 */
	protected $entity;

	/**
	 * @var mixed
	 */
	protected $path, $version, $stable;

	/**
	 * @param DocumentationEntity $entity
	 * @param string $path
	 * @param float $version
	 * @param boolean $stable
	 */
	public function __construct($entity, $path, $version, $stable) {
		$this->entity = $entity;
		$this->path = $path;
		$this->version = $version;
		$this->stable = $stable;

		// check what languages that this instance will support.
		$langs = scandir($path);
		$available = array();
		
		if($langs) {
			$possible = i18n::get_common_languages(true);
			$possible['en'] = true;
	
			foreach($langs as $key => $lang) {
				if(isset($possible[$lang])) {
					$this->supportedLanguages[$lang] = Injector::inst()->create(
						'DocumentationEntityLanguage',
						$this,
						$lang
					);
				} else {
					
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public function Link() {
		if($this->stable) {
			return $this->entity->Link();
		}

		return Controller::join_links($this->entity->Link(), $this->version);
	}

	/**
	 * Return the languages which are available for this version of the entity.
	 *
	 * @return array
	 */
	public function getSupportedLanguages() {
		return $this->supportedLanguages;

	}

	/**
	 * Return whether this entity has a given language.
	 *
	 * @return bool
	 */
	public function hasLanguageSupport($lang) {
		return (in_array($lang, $this->getSupportedLanguages()));
	}

	/**
	 * @return float
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getBaseFolder() {
		return $this->entity->getFolder();
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->entity->getTitle();
	}


	/**
	 * Returns an integer value based on if a given version is the latest 
	 * version. Will return -1 for if the version is older, 0 if versions are 
	 * the same and 1 if the version is greater than.
	 *
	 * @param string $version
	 * @return int
	 */
	public function compare(DocumentationEntityVersion $other) {
		return version_compare($this->getVersion(), $other->getVersion());
	}
}