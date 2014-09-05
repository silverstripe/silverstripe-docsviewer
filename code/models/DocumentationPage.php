<?php

/**
 * A specific documentation page within a {@link DocumentationEntity}. 
 *
 * Maps to a file on the file system. Note that the URL to access this page may 
 * not always be the file name. If the file contains meta data with a nicer URL 
 * sthen it will use that. 
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
	 * @var string 
	 */
	protected $relativePath;
	
	/**
	 * @var string
	 */
	protected $lang = 'en';
	
	/**
	 * @var string
	 */
	protected $title;
	
	/**
	 * @var string
	 */
	protected $version;
	
	/**
	 * @var boolean
	 */
	protected $isFolder = false;

	/**
	 * @var integer
	 */
	protected $pagenumber = 0; 	
	
	/**
	 * @param boolean
	 */
	public function setIsFolder($isFolder = false) {
		$this->isFolder = $isFolder;
	}

	/**
	 * @return boolean
	 */
	public function getIsFolder($isFolder = false) {
		return $this->isFolder;
	}

	/**
	 * 
	 * @param int $number
	 */
	public function setPagenumber($number = 0) {
		if (is_int($number )) $this->pagenumber = $number;
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
	public function getExtension() {
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
	public function getPath($defaultFile = false, $realpath = true) {
		if($this->entity) {
			$path = Controller::join_links(
				$this->entity->getPath($this->getVersion(), $this->lang),
				$this->getRelativePath()
			);
			
			if(!is_dir($path) && $realpath) $path = realpath($path);
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
	public function getBreadcrumbTitle($divider = ' - ') {
		$pathParts = explode('/', $this->getRelativePath());
		
		// add the module to the breadcrumb trail.
		array_unshift($pathParts, $this->entity->getTitle());
		
		$titleParts = array_map(array('DocumentationService', 'clean_page_name'), $pathParts);
		
		return implode($divider, $titleParts + array($this->getTitle()));
	}
	
	/**
	 * Returns the public accessible link for this page.
	 *
	 * @param Boolean Absolute URL (incl. domain), or relative to webroot
	 * @return string
	 */
	public function getLink($absolute = true) {
		if($entity = $this->getEntity()) {
			$link = $this->getRelativeLink();
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

		if($absolute) {
			$fullLink = Controller::join_links($entity->Link($this->getVersion(), $this->lang), $link);
		} else {
			$fullLink = Controller::join_links($entity->getRelativeLink($this->getVersion(), $this->lang), $link);
		}

		return $fullLink;
	}
	
	/**
	 * Relative to the module base, not the webroot.
	 * 
	 * @return string
	 */
	public function getRelativeLink() {
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
	public function getFilename() {
		$path = rtrim($this->relativePath, '/');
		
		try {
			return (is_dir($this->getPath())) ? $path . '/' : $path;
		}
		catch (Exception $e) {

		}
		
		return $path;
	}

	/**
	 * Return the raw markdown for a given documentation page. 
	 *
	 * @param boolean $removeMetaData
	 *
	 * @return string
	 */
	public function getMarkdown($removeMetaData = false) {
		try {
			$path = $this->getPath(true);

			if($path) {
				$ext = $this->getExtension();
								
				if(empty($ext) || DocumentationService::is_valid_extension($ext)) {
					if ($md = file_get_contents($path)) {
						if ($this->title != 'Index') $this->getMetadataFromComments($md, $removeMetaData);
					}  
					return $md;
				}   
			}
		}
		catch(InvalidArgumentException $e) {}
		
		return false;
	}
	
	/**
	 * Parse a file and return the parsed HTML version.
	 *
	 * @param string $baselink 
	 *
	 * @return string
	 */
	public function getHTML($version, $lang = 'en') {
		return DocumentationParser::parse($this, $this->entity->getRelativeLink($version, $lang));
	}
	
	/**
	 * get metadata from the first html block in the page, then remove the 
	 * block on request
	 * 
	 * @param DocumentationPage $md
	 * @param bool $remove
	 */
	public function getMetadataFromComments(&$md, $removeMetaData = false) {
		if($md && DocumentationService::meta_comments_enabled()) {
			
			// get the text up to the first whiteline
			$extPattern = "/^(.+)\n(\r)*\n/Uis";
			$matches = preg_match($extPattern, $md, $block);
			if($matches && $block[1]) {
				$metaDataFound = false;
				
				// find the key/value pairs
				$intPattern = '/(?<key>[A-Za-z][A-Za-z0-9_-]+)[\t]*:[\t]*(?<value>[^:\n\r\/]+)/x';
				$matches = preg_match_all($intPattern, $block[1], $meta);
				
				foreach($meta['key'] as $index => $key) {
					if(isset($meta['value'][$index])) {
						
						// check if a property exists for this key
						if (property_exists(get_class(), $key)) {
							$this->setMetaData($key, $meta['value'][$index]);
							$metaDataFound = true;
						}  
					}
				}
				// optionally remove the metadata block (only on the page that is displayed)
				if ($metaDataFound && $removeMetaData) {
					$md = preg_replace($extPattern, '', $md);
				}
			}
		}
	} 
	
	/**
	 * Returns the next page. Either retrieves the sibling of the current page
	 * or return the next sibling of the parent page.
	 *
	 * @return DocumentationPage
	 */
	public function getNextPage() {

	}	

	/**
	 * Returns the previous page. Either returns the previous sibling or the 
	 * parent of this page
	 *
	 * @return DocumentationPage
	 */
	public function getPreviousPage() {

	}
}