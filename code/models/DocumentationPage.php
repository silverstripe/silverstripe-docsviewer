<?php

/**
 * A specific page within a {@link DocumentationEntity}. Maps 1 to 1 to a file on the 
 * filesystem.
 * 
 * @package docsviewer
 * @subpackage model
 */
class DocumentationPage extends ViewableData {
	
	/**
	 * @var DocumentationEntity
	 */
	protected $entity;
	
	/**
	 * Stores the relative path (from the {@link DocumentationEntity} to
	 * this page. The actual file name can be accessed via {@link $this->getFilename()}
	 *
	 * @var String 
	 */
	protected $relativePath;
	
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
	 * @var Boolean
	 */
	protected $isFolder = false;

	/**
	 * @param Boolean
	 */
	public function setIsFolder($isFolder = false) {
		$this->isFolder = $isFolder;
	}

	/**
	 * @return Boolean
	 */
	public function getIsFolder($isFolder = false) {
		return $this->isFolder;
	}

	/**
	 * @return DocumentationEntity
	 */
	public function getEntity() {
		return $this->entity;
	}

	/**
	 * @param DocumentationEntity
	 */
	public function setEntity($entity) {
		$this->entity = $entity;
	}

	/**
	 * @return string
	 */
	public function getRelativePath() {
		return $this->relativePath;
	}

	/**
	 * @param string
	 */
	public function setRelativePath($path) {
		$this->relativePath = $path;
	}

	/**
	 * @return string
	 */
	function getExtension() {
		return DocumentationService::get_extension($this->getRelativePath());
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
		if($this->entity) {
			
			$path = Controller::join_links(
				$this->entity->getPath($this->getVersion(), $this->lang),
				$this->getRelativePath()
			);
			
			if(!is_dir($path)) $path = realpath($path);
			else if($defaultFile) {
				$file = DocumentationService::find_page($this->entity, explode('/', $this->getRelativePath()));

				if($file) $path = $file;
			}
		}
		else {
			$path = $this->getRelativePath();
		}
		if(!file_exists($path)) {
			throw new InvalidArgumentException(sprintf(
				'Path could not be found. Module path: %s, file path: %s', 
				$this->entity->getPath(),
				$this->getRelativePath()
			));
		}
		
		
		return (is_dir($path)) ? rtrim($path, '/') . '/' : $path;
	}
	
	/**
	 * @param string - has to be plain text for open search compatibility.
	 *
	 * @return string
	 */
	function getBreadcrumbTitle($divider = ' - ') {
		$pathParts = explode('/', $this->getRelativePath());
		
		// add the module to the breadcrumb trail.
		array_unshift($pathParts, $this->entity->getTitle());
		
		$titleParts = array_map(array('DocumentationService', 'clean_page_name'), $pathParts);
		
		return implode($divider, $titleParts + array($this->getTitle()));
	}
	
	/**
	 * Returns the public accessible link for this page.
	 *
	 * @return string
	 */
	function getLink() {
		if($entity = $this->getEntity()) {
			$link = Controller::join_links($entity->Link($this->getVersion(), $this->lang), $this->getRelativeLink());

			$link = rtrim(DocumentationService::trim_extension_off($link), '/');
			
			// folders should have a / on them. Looks nicer
			try {
				if(is_dir($this->getPath())) $link .= '/';
			}
			catch (Exception $e) {}
		}
		else {
			$link = $this->getPath(true);
		}

		return $link;
	}
	
	/**
	 * Relative to the module base, not the webroot.
	 * 
	 * @return string
	 */
	function getRelativeLink() {
		$link = rtrim(DocumentationService::trim_extension_off($this->getRelativePath()), '/');
		
		// folders should have a / on them. Looks nicer
		try {
			if(is_dir($this->getPath())) $link .= '/';
		} catch (Exception $e) {};
		
		return $link;
	}
	
	function getLang() {
		return $this->lang;
	}
	
	function setLang($lang) {
		$this->lang = $lang;
	}
	
	function getVersion() {
		return $this->version ? $this->version : $this->entity->getStableVersion();
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
	 * Set a variable from the metadata field on this class
	 *
	 * @param string key
	 * @param mixed value
	 */
	public function setMetaData($key, $value) {
		$this->$key = $value;
	}
	
	/**
	 * @return string
	 */
	function getFilename() {
		$path = rtrim($this->relativePath, '/');
		
		try {
			return (is_dir($this->getPath())) ? $path . '/' : $path;
		}
		catch (Exception $e) {}
		
		return $path;
	}

	/**
	 * Return the raw markdown for a given documentation page. Will throw
	 * an error if the path isn't a file.
	 *
	 * Will return empty if the type is not readable
	 *
	 * @return string
	 */
	function getMarkdown() {
		try {
			$path = $this->getPath(true);

			if($path) {
				$ext = $this->getExtension();
				
				if(DocumentationService::is_valid_extension($ext)) {
					return file_get_contents($path);
				}
			}
		}
		catch(InvalidArgumentException $e) {}
		
		return false;
	}
	
	/**
	 * Parse a file (with a lang and a version).
	 *
	 * @param string $baselink 
	 *
	 * @return string
	 */
	function getHTML($version, $lang = 'en') {
		return DocumentationParser::parse($this, $this->entity->getRelativeLink($version, $lang));
	}
}