<?php

/**
 * A specific page within a {@link DocumentationEntity}. Maps 1 to 1 to a file on the 
 * filesystem.
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
	protected $fullPath; // needed for the search
	
	/**
	 * @var String
	 */
	protected $lang = 'en';
	
	/**
	 * @var string
	 */
	protected $title;
	
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
	
	/**
	 * @param DocumentationEntity
	 */
	function setEntity($entity) {
		$this->entity = $entity;
	}
		
	/**
	 * @return string
	 */
	function getRelativePath() {
		return $this->relativePath;
	}
	
	/**
	 * @param string
	 */
	function setRelativePath($path) {
		$this->relativePath = $path;
	}
	
	/**
	 * Absolute path including version and lang folder.
	 * 
	 * @throws InvalidArgumentException
	 *
	 * @param bool $defaultFile - If this is a folder and this is set to true then getPath
	 *				will return the path of the first file in the folder
	 * @return string 
	 */
	function getPath($defaultFile = false) {
		if($this->fullPath) {
			$path = $this->fullPath;
		}
		elseif($this->entity) {
			$path = realpath(rtrim($this->entity->getPath($this->version, $this->lang), '/') . '/' . trim($this->getRelativePath(), '/'));
		}
		else {
			$path = $this->relativePath;
		}
		
		if(!is_dir($path)) return $path;

		if($defaultFile && ($entity = $this->getEntity())) {
			if($relative = $this->getRelativePath()) {
				return DocumentationService::find_page($entity, explode($relative, '/'));
			}
			else {
				$parts = str_replace($entity->getPath($this->version, $this->lang), '', $this->fullPath);

				return DocumentationService::find_page($entity, explode($parts, '/'));
			}
		}
		
		if(!file_exists($path)) {
			throw new InvalidArgumentException(sprintf(
				'Path could not be found. Module path: %s, file path: %s', 
				$this->entity->getPath(),
				$this->relativePath
			));
		}
		
		return $path;
	}
	
	/**
	 * Returns the link for the webbrowser
	 *
	 * @return string
	 */
	function getLink() {
		$web = Director::absoluteBaseUrl() . DocumentationViewer::get_link_base();
		
		return (str_replace(BASE_PATH, $web , $this->getPath(true)));
	}
	
	function setFullPath($path) {
		$this->fullPath = $path;
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
	
	function setTitle($title) {
		$this->title = $title;
	}
	
	function getTitle() {
		return $this->title;
	}
	
	/**
	 * @return bool
	 */
	function isFolder() {
		return (is_dir($this->getPath()));
	}
	
	/**
	 * @return String
	 */
	function getMarkdown() {
		try {
			$path = $this->getPath(true);

			if($path) {
				return file_get_contents($path);
			}
		}
		catch(InvalidArgumentException $e) {}
		
		return null;
	}
	
	/**
	 * @param String $baselink 
	 * @return String
	 */
	function getHTML($baselink = null) {
		// if this is not a directory then we can to parse the file
		return DocumentationParser::parse($this->getPath(true), $baselink);
	}
}