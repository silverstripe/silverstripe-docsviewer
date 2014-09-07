<?php

/**
 * @package docsviewer
 */
class DocumentationEntityLanguage extends ViewableData {
	
	/**
	 * @var string
	 */
	protected $language;

	/**
	 * @var DocumentationEntityVersion
	 */
	protected $entity;

	/**
	 * @param DocumentationEntityVersion $version
	 * @param string $language
	 */ 
	public function __construct(DocumentationEntityVersion $version, $language) {
		$this->entity = $version;
		$this->language = $language;
	}

	/**
	 * @return string
	 */
	public function Link() {
		return Controller::join_links(
			$this->entity->Link(), 
			$this->language,
			'/'
		);
	}


	/**
	 * @return DocumentationEntityVersion
	 */
	public function getVersion() {
		return $this->entity;
	}

	/**
	 * @return array
	 */
	public function getVersions() {
		return $this->entity->getEntity()->getVersions();
	}

	/**
	 * @return 
	 */
	public function getStableVersion() {
		return $this->entity->getEntity()->getStableVersion();
	}

	/**
	 * @return string
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return Controller::join_links(
			$this->entity->getPath(), 
			$this->language
		);
	}

	/**
	 * @return string
	 */
	public function getBasePath() {
		return $this->entity->getPath();
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->entity->getTitle();
	}

	/**
	 * @return string
	 */
	public function getBaseFolder() {
		return $this->entity->getBaseFolder();
	}

	/**
	 * @return array
	 */
	public function getSupportedLanguages() {
		return $this->entity->getSupportedLanguages();
	}
}