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
	
	protected $lang = 'en';
	
	protected $version;
	
	function __construct($relativePath, $entity, $lang = null, $version = null) {
		$this->entity = $entity;
		$this->relativePath = $relativePath;
		if($lang) $this->lang = $lang;
		if($version) $this->version = $version;
		
		if(!file_exists($this->getPath())) {
			throw new InvalidArgumentException(sprintf(
				'Path could not be found. Module path: %s, file path: %s', 
				$this->entity->getPath(),
				$this->relativePath
			));
		}
		
		parent::__construct();
	}
	
	/**
	 * @return DocumentationEntity
	 */
	function getEntity() {
		return $this->entity;
	}
		
	/**
	 * @return String Relative path to file or folder within the entity (including file extension),
	 * but excluding version or language folders.
	 */
	function getRelativePath() {
		return $this->relativePath;
	}
	
	/**
	 * Absolute path including version and lang folder.
	 * 
	 *  @return String 
	 */
	function getPath() {
		$path = rtrim($this->entity->getPath($this->version, $this->lang), '/') . '/' . $this->getRelativePath();
		return realpath($path);
	}
		
	function getLang() {
		return $this->lang;
	}
	
	function getVersion() {
		return $this->version;
	}
		
	/**
	 * @return String
	 */
	function getMarkdown() {
		return file_get_contents($this->getPath());
	}
	
	/**
	 * @param String $baselink 
	 * @return String
	 */
	function getHTML($baselink = null) {
		return DocumentationParser::parse($this, $baselink);
	}
	
}