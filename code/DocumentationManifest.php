<?php

/**
 * A class which builds a manifest of all documentation present in a project.
 *
 * The manifest is required to map the provided documentation URL rules to a 
 * file path on the server. The stored cache looks similar to the following:
 *
 * <code>
 * array(
 *     'en/someniceurl/' => array(
 *       'filepath' => '/path/to/docs/en/SomeniceFile.md',
 *		 'title' => 'Some nice URL',
 *		 'summary' => 'Summary Text',
 *		 'basename' => 'SomeniceFile.md',
 *		 'type' => 'DocumentationPage'
 *     )
 *   )
 * </code>
 *
 * URL format is in the following structures:
 *
 *  {lang}/{path}
 *	{lang}/{module}/{path}
 *  {lang}/{module}/{version}/{/path}
 *
 * @package framework
 * @subpackage manifest
 */
class DocumentationManifest {

	const TEMPLATES_DIR = 'documentation';

	protected $base;
	protected $cache;
	protected $cacheKey;
	protected $inited;
	protected $forceRegen;
	protected $pages = array();

	private $entity;

	/**
	 * Constructs a new template manifest. The manifest is not actually built
	 * or loaded from cache until needed.
	 *
	 * @param bool $includeTests Include tests in the manifest.
	 * @param bool $forceRegen Force the manifest to be regenerated.
	 */
	public function __construct($forceRegen = false) {
		$this->cacheKey   = 'manifest';
		$this->forceRegen = $forceRegen;

		$this->cache = SS_Cache::factory('DocumentationManifest', 'Core', array(
			'automatic_serialization' => true,
			'lifetime' => null
		));
	}


	/**
	 *
	 */
	protected function init() {
		if (!$this->forceRegen && $data = $this->cache->load($this->cacheKey)) {
			$this->pages = $data;
			$this->inited    = true;
		} else {
			$this->regenerate();
		}
	}


	/**
	 * Returns a map of all documentation pages.
	 *
	 * @return array
	 */
	public function getPages() {
		if (!$this->inited) {
			$this->init();
		}

		return $this->pages;
	}

	/**
	 * Returns a particular page for the requested URL. 
	 *
	 * @return DocumentationPage
	 */
	public function getPage($url) {
		$pages = $this->getPages();

		if(!isset($pages[$url])) {
			return null;
		}

		$record = $pages[$url];

		DocumentationService::load_automatic_registration();

		foreach(DocumentationService::get_registered_entities() as $entity) {
			foreach($entity->getVersions() as $version) {
				foreach($version->getSupportedLanguages() as $language) {
					if(strpos($record['filepath'], $language->getPath()) !== false) {
						$page =  Injector::inst()->create(
							$record['type'], 
							$language,
							$record['basename'],
							$record['filepath']
						);

						return $page;
					}
				}
			}
		}
	}

	/**
	 * Regenerates the manifest by scanning the base path.
	 *
	 * @param bool $cache
	 */
	public function regenerate($cache = true) {
		$finder = new DocumentationManifestFileFinder();
		$finder->setOptions(array(
			'dir_callback' => array($this, 'handleFolder'),
			'file_callback'  => array($this, 'handleFile')
		));

		DocumentationService::load_automatic_registration();
		foreach(DocumentationService::get_registered_entities() as $entity) {
			foreach($entity->getVersions() as $version) {

				foreach($version->getSupportedLanguages() as $k => $v) {
					$this->entity = $v;
					$this->handleFolder('', $this->entity->getPath(), 0);
					
					$finder->find($this->entity->getPath());
				}
			}
		}
		
		if ($cache) {
			$this->cache->save($this->pages, $this->cacheKey);
		}

		$this->inited = true;
	}

	/**
	 *
	 */
	public function handleFolder($basename, $path, $depth) {
		$folder = Injector::inst()->create(
			'DocumentationFolder', $this->entity, $basename, $path
		);

		$this->pages[$folder->Link()] = array(
			'title' => $folder->getTitle(),
			'basename' => $basename,
			'filepath' => $path,
			'type' => 'DocumentationFolder'
		);
	}

	/**
	 * Individual files can optionally provide a nice title and a better URL 
	 * through the use of markdown meta data. This creates a new 
	 * {@link DocumentationPage} instance for the file. 
	 *
	 * If the markdown does not specify the title in the meta data it falls back
	 * to using the file name.
	 *
	 * @param string $basename
	 * @param string $path
	 * @param int $depth
	 */
	public function handleFile($basename, $path, $depth) {
		$page = Injector::inst()->create(
			'DocumentationPage', 
			$this->entity, $basename, $path
		);

		$this->pages[$page->Link()] = array(
			'title' => $page->getTitle(),
			'filepath' => $path,
			'basename' => $basename,
			'type' => 'DocumentationPage',
			'summary' => $page->getSummary()
		);
	}

	/**
	 * Generate an {@link ArrayList} of the pages to the given page.
	 *
	 * @return ArrayList
	 */
	public function generateBreadcrumbs($record) {
		$output = new ArrayList();

		// @todo

		return $output;
	}

	/**
	 * Determine the next page from the given page.
	 *
	 * Relies on the fact when the manifest was built, it was generated in 
	 * order.
	 *
	 * @param string
	 *
	 * @return ArrayData
	 */
	public function getNextPage($filepath) {
		$grabNext = false;

		foreach($this->getPages() as $url => $page) {
			if($grabNext) {
				return new ArrayData(array(
					'Link' => $url,
					'Title' => $page['title']
				));
			}

			if($filepath == $page['filepath']) {
				$grabNext = true;
			}
		}

		return null;
	}

	/**
	 * Determine the previous page from the given page.
	 *
	 * Relies on the fact when the manifest was built, it was generated in 
	 * order.
	 *
	 * @param string
	 *
	 * @return ArrayData
	 */
	public function getPreviousPage($filepath) {
		$previousUrl = $previousPage = null;

		foreach($this->getPages() as $url => $page) {
			if($filepath == $page['filepath']) {
				if($previousUrl) {
					return new ArrayData(array(
						'Link' => $previousUrl,
						'Title' => $previousPage['title']
					));
				}
			}

			$previousUrl = $url;
			$previousPage = $page;
		}

		return null;
	}
}
