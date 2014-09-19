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
	 * @var string
	 */
	protected $title, $summary, $introduction;

	/**
	 * @var DocumentationEntity
	 */
	protected $entity;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * Filename
	 *
	 * @var string
	 */
	protected $filename;

	protected $read = false;

	/**
	 * @param DocumentationEntity $entity
	 * @param string $filename
	 * @param string $path
	 */
	public function __construct(DocumentationEntity $entity, $filename, $path) {
		$this->filename = $filename;
		$this->path = $path;
		$this->entity = $entity;
	}

	/**
	 * @return string
	 */
	public function getExtension() {
		return DocumentationHelper::get_extension($this->filename);
	}
	
	/**
	 * @param string - has to be plain text for open search compatibility.
	 *
	 * @return string
	 */
	public function getBreadcrumbTitle($divider = ' - ') {
		$pathParts = explode('/', trim($this->getRelativePath(), '/'));
		
		// from the page from this
		array_pop($pathParts);

		// add the module to the breadcrumb trail.
		$pathParts[] = $this->entity->getTitle();
		
		$titleParts = array_map(array(
			'DocumentationHelper', 'clean_page_name'
		), $pathParts);

		$titleParts = array_filter($titleParts, function($val) {
			if($val) {
				return $val;
			}
		});

		if($this->getTitle()) {
			array_unshift($titleParts, $this->getTitle());
		}

		return implode($divider, $titleParts);
	}
	
	/**
	 * @return DocumentationEntity
	 */
	public function getEntity() {
		return $this->entity;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		if($this->title) {
			return $this->title;
		}

		$page = DocumentationHelper::clean_page_name($this->filename);

		if($page == "Index") {
			// look at the folder name
			$parts = explode("/", $this->getPath());
			array_pop($parts); // name

			$page = DocumentationHelper::clean_page_name(
				array_pop($parts)
			);
		}

		return $page;
	}
	
	/**
	 * @return string
	 */
	public function getSummary() {
		return $this->summary;
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
			if ($md = file_get_contents($this->getPath())) {
				$this->populateMetaDataFromText($md, $removeMetaData);
			
				return $md;
			}

			$this->read = true;
		}
		catch(InvalidArgumentException $e) {

		}
		
		return false;
	}

	public function setMetaData($key, $value) {
		$key = strtolower($key);

		$this->$key = $value;
	}

	public function getIntroduction() {
		if(!$this->read) {
			$this->getMarkdown();
		}
		
		return $this->introduction;
	}
	
	/**
	 * Parse a file and return the parsed HTML version.
	 *
	 * @param string $baselink 
	 *
	 * @return string
	 */
	public function getHTML() {
		return DocumentationParser::parse(
			$this, 
			$this->entity->Link()
		);
	}
	
	/**
	 * This should return the link from the entity root to the page. The link
	 * value has the cleaned version of the folder names. See 
	 * {@link getRelativePath()} for the actual file path.
	 *
	 * @return string
	 */
	public function getRelativeLink() {
		$path = $this->getRelativePath();
		$url = explode('/', $path);
		$url = implode('/', array_map(function($a) {
			return DocumentationHelper::clean_page_url($a);
		}, $url));

		$url = trim($url, '/') . '/';

		return $url;
	}

	/**
	 * This should return the link from the entity root to the page. For the url
	 * polished version, see {@link getRelativeLink()}.
	 *
	 * @return string
	 */
	public function getRelativePath() {
		return str_replace($this->entity->getPath(), '', $this->getPath());
		
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Returns the URL that will be required for the user to hit to view the 
	 * given document base name.
	 *
	 * @param string $file
	 * @param string $path
	 *
	 * @return string
	 */
	public function Link() {
		return ltrim(Controller::join_links(
			$this->entity->Link(),
			$this->getRelativeLink()
		), '/');
	}
	
	/**
	 * Return metadata from the first html block in the page, then remove the 
	 * block on request
	 * 
	 * @param DocumentationPage $md
	 * @param bool $remove
	 */
	public function populateMetaDataFromText(&$md, $removeMetaData = false) {
		if($md) {
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
							$this->$key = $meta['value'][$index];
							$metaDataFound = true;
						}  
					}
				}

				// optionally remove the metadata block (only on the page that 
				// is displayed)
				if ($metaDataFound && $removeMetaData) {
					$md = preg_replace($extPattern, '', $md);
				}
			}
		}
	} 

	public function getVersion() {
		return $this->entity->getVersion();
	}

	public function __toString() {
		return sprintf(get_class($this) .': %s)', $this->getPath());
	}
}