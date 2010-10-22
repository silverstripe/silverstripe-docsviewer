<?php

/**
 * A specific page within a {@link DocumentationEntity}.
 * Has to represent an actual file, please use {@link DocumentationViewer}
 * to generate "virtual" index views.
 * 
 * @package sapphiredocs
 */
class DocumentationPage extends ViewableData {
	
	/**
	 * @var DocumentationEntity
	 */
	protected $entity;
	
	/**
	 * @var String 
	 */
	protected $relativePath;
	
	/**
	 * @var String
	 */
	protected $lang = 'en';
	
	/**
	 * @var String
	 */
	protected $version;
	
	/**
	 * @return DocumentationEntity
	 */
	function getEntity() {
		return $this->entity;
	}
	
	function setEntity($entity) {
		$this->entity = $entity;
	}
		
	/**
	 * @return String Relative path to file or folder within the entity (including file extension),
	 * but excluding version or language folders.
	 */
	function getRelativePath() {
		return $this->relativePath;
	}
	
	function setRelativePath($path) {
		$this->relativePath = $path;
	}
	
	/**
	 * Absolute path including version and lang folder.
	 * 
	 *  @return String 
	 */
	function getPath() {
		$path = realpath(rtrim($this->entity->getPath($this->version, $this->lang), '/') . '/' . trim($this->getRelativePath(), '/'));
		
		if(!file_exists($path)) {
			throw new InvalidArgumentException(sprintf(
				'Path could not be found. Module path: %s, file path: %s', 
				$this->entity->getPath(),
				$this->relativePath
			));
		}
		
		return $path;
	}
		
	function getLang() {
		return $this->lang;
	}
	
	function setLang($lang) {
		$this->lang = $lang;
	}
	
	function getVersion() {
		return $this->version;
	}
	
	function setVersion($version) {
		$this->version = $version;
	}
		
	/**
	 * @return String
	 */
	function getMarkdown() {
		try {
			$path = $this->getPath();
			
			return file_get_contents($path);
		}
		catch(InvalidArgumentException $e) {}
		
		return null;
	}
	
	/**
	 * @param String $baselink 
	 * @return String
	 */
	function getHTML($baselink = null) {
		return DocumentationParser::parse($this, $baselink);
	}
}