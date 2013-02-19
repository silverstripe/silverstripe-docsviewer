<?php

/**
 * Documentation Viewer.
 *
 * Reads the bundled markdown files from documentation folders and displays the 
 * output (either via markdown or plain text)
 *
 * For more documentation on how to use this class see the documentation in the
 * docs folder
 *
 * @package docsviewer
 */

class DocumentationViewer extends Controller {

	public static $allowed_actions = array(
		'home',
		'LanguageForm',
		'doLanguageForm',
		'handleRequest',
		'DocumentationSearchForm',
		'results'
	);
	
	/**
	 * @var string
	 */
	public $version = "";
	
	/**
	 * @var string
	 */
	public $language = "en";
	
	/**
	 * The string name of the currently accessed {@link DocumentationEntity}
	 * object. To access the entire object use {@link getEntity()}
	 *
	 * @var string
	 */
	public $entity = '';
	
	/**
	 * @var array
	 */
	public $remaining = array();
	
	/**
	 * @var DocumentationPage
	 */
	public $currentLevelOnePage;

	/**
	 * @var String Same as the routing pattern set through Director::addRules().
	 */
	protected static $link_base = 'dev/docs/';
	
	/**
	 * @var String|array Optional permission check
	 */
	static $check_permission = 'ADMIN';

	/**
	 * @var array map of modules to edit links.
	 * @see {@link getEditLink()}
	 */
	private static $edit_links = array();
	
	/**
	 * @return boolean
	 */
	protected static $separate_submenu = true;

	/**
	 * @return boolean
	 */
	protected static $recursive_submenu = false;

	public static function set_separate_submenu($separate_submenu = true) {
		self::$separate_submenu = $separate_submenu;
	}

	public static function set_recursive_submenu($recursive_submenu = false) {
		self::$recursive_submenu = $recursive_submenu;
	}

	function init() {
		parent::init();

		if(!$this->canView()) return Security::permissionFailure($this);

		Requirements::javascript(THIRDPARTY_DIR .'/jquery/jquery.js');		
		Requirements::combine_files(
			'syntaxhighlighter.js',
			array(
				DOCSVIEWER_DIR .'/thirdparty/syntaxhighlighter/scripts/shCore.js',
				DOCSVIEWER_DIR . '/thirdparty/syntaxhighlighter/scripts/shBrushJScript.js',
				DOCSVIEWER_DIR . '/thirdparty/syntaxhighlighter/scripts/shBrushPhp.js',
				DOCSVIEWER_DIR . '/thirdparty/syntaxhighlighter/scripts/shBrushXml.js',
				DOCSVIEWER_DIR . '/thirdparty/syntaxhighlighter/scripts/shBrushCss.js',
				DOCSVIEWER_DIR . '/thirdparty/syntaxhighlighter/scripts/shBrushYaml.js',
				DOCSVIEWER_DIR . '/javascript/shBrushSS.js'
			)
		);
		
		Requirements::javascript(DOCSVIEWER_DIR .'/javascript/DocumentationViewer.js');
		Requirements::css(DOCSVIEWER_DIR .'/css/shSilverStripeDocs.css');
		Requirements::css(DOCSVIEWER_DIR .'/css/DocumentationViewer.css');
	}
	
	/**
	 * Can the user view this documentation. Hides all functionality for 
	 * private wikis.
	 *
	 * @return bool
	 */
	public function canView() {
		return (Director::isDev() || Director::is_cli() || 
			!self::$check_permission || 
			Permission::check(self::$check_permission)
		);
	}

	/**
	 * Overloaded to avoid "action doesn't exist" errors - all URL parts in 
	 * this controller are virtual and handled through handleRequest(), not 
	 * controller methods.
	 *
	 * @param $request
	 * @param $action
	 * @return SS_HTTPResponse
	 */
	public function handleAction($request, $action) {
		try {
			$response = parent::handleAction($request, $action);
		} catch(SS_HTTPResponse_Exception $e) {
			if(strpos($e->getMessage(), 'does not exist') !== FALSE) {
				return $this;
			} else {
				throw $e;
			}
		}
		
		return $response;
	}
	
	/**
	 * Handle the url parsing for the documentation. In order to make this
	 * user friendly this does some tricky things..
	 *
	 * The urls which should work
	 * / - index page
	 * /en/sapphire - the index page of sapphire (shows versions)
	 * /2.4/en/sapphire - the docs for 2.4 sapphire.
	 * /2.4/en/sapphire/installation/
	 *
	 * @return SS_HTTPResponse
	 */
	public function handleRequest(SS_HTTPRequest $request, DataModel $model) {
		DocumentationService::load_automatic_registration();
		
		// if we submitted a form, let that pass
		if(!$request->isGET() || isset($_GET['action_results'])) {
			return parent::handleRequest($request, $model);
		}

		$firstParam = ($request->param('Action')) ? $request->param('Action') : $request->shift();		
		$secondParam = $request->shift();
		$thirdParam = $request->shift();
		
		$this->Remaining = $request->shift(10);
		
		// if no params passed at all then it's the homepage
		if(!$firstParam && !$secondParam && !$thirdParam) {
			return parent::handleRequest($request, $model);
		}
		
		if($firstParam) {
			// allow assets
			if($firstParam == "assets") {
				return parent::handleRequest($request, $model);
			}
			
			// check for permalinks
			if($link = DocumentationPermalinks::map($firstParam)) {
				// the first param is a shortcode for a page so redirect the user to
				// the short code.
				$this->response = new SS_HTTPResponse();
				$this->redirect($link, 301); // 301 permanent redirect
			
				return $this->response;
				
			}

			// check to see if the request is a valid entity. If it isn't, then we
			// need to throw a 404.
			if(!DocumentationService::is_registered_entity($firstParam)) {
				return $this->throw404();
			}
			
			$this->entity = $firstParam;
			$this->language = $secondParam;
			
			if(isset($thirdParam) && (is_numeric($thirdParam) || in_array($thirdParam, array('master', 'trunk')))) {
				$this->version = $thirdParam;	
			}
			else {
				// current version so store one area para
				array_unshift($this->Remaining, $thirdParam);
				
				$this->version = false;
			}
		}
		
		// 'current' version mapping
		$entity = DocumentationService::is_registered_entity($this->entity, null, $this->getLang());

		if($entity) {
			$current = $entity->getStableVersion();
			$version = $this->getVersion();
			
			if(!$version) {
				$this->version = $current;
			}
			
			// Check if page exists, otherwise return 404
			if(!$this->locationExists()) {
				return $this->throw404();
			}
			
			
			return parent::handleRequest($request, $model);
		}
		
		return $this->throw404();
	}
		
	
	/**
	 * Helper function for throwing a 404 error from the {@link handleRequest}
	 * method.
	 *
	 * @return HttpResponse
	 */
	function throw404() {
		$class = get_class($this);
		
		$body = $this->renderWith(array("{$class}_error", $class));
		$this->response = new SS_HTTPResponse($body, 404);
		
		return $this->response;
	}
	
	/**
	 * Custom templates for each of the sections. 
	 */
	function getViewer($action) {
		// count the number of parameters after the language, version are taken
		// into account. This automatically includes ' ' so all the counts
		// are 1 more than what you would expect

		if($this->entity || $this->Remaining) {

			$paramCount = count($this->Remaining);
			
			if($paramCount == 0) {
				return parent::getViewer('folder');
			}
			else if($entity = $this->getEntity()) {
				// if this is a folder return the folder listing
				if($this->locationExists() == 2) {
					return parent::getViewer('folder');
				}
			}
		}
		else {
			return parent::getViewer('home');
		}
		
		return parent::getViewer($action);
	}
	
	/**
	 * Returns the current version. If no version is set then it is the current
	 * set version so need to pull that from the {@link Entity}.
	 *
	 * @return String
	 */
	function getVersion() {
		if($this->version) return $this->version;
		
		if($entity = $this->getEntity()) {
			$this->version = $entity->getStableVersion();
			
			return $this->version;
		} 
		
		return false;
	}
	
	/**
	 * Returns the current language
	 *
	 * @return String
	 */
	function getLang() {
		return $this->language;
	}
	
	/**
	 * Return all the available languages for the {@link Entity}.
	 *
	 * @return array
	 */
	function getLanguages() {
		$entity = $this->getEntity();
			
		if($entity) {
			return $entity->getLanguages();
		}
		
		return array('en' => 'English');
	}

	/**
	 * Get all the versions loaded for the current {@link DocumentationEntity}. 
	 * the filesystem then they are loaded under the 'Current' namespace.
	 *
	 * @param String $entity name of {@link Entity} to limit it to eg sapphire
	 * @return ArrayList
	 */
	function getVersions($entity = false) {
		if(!$entity) $entity = $this->entity;

		$entity = DocumentationService::is_registered_entity($entity);
		if(!$entity) return false;

		$versions = $entity->getVersions();
		$output = new ArrayList();

		if($versions) {
			$lang = $this->getLang();
			$currentVersion = $this->getVersion();

			foreach($versions as $key => $version) {
				if(!$version) continue;

				$linkingMode = ($currentVersion == $version) ? 'current' : 'link';

				$output->push(new ArrayData(array(
					'Title' => $version,
					'Link' => $this->Link(implode('/',$this->Remaining), $entity->getFolder(), $version),
					'LinkingMode' => $linkingMode,
					'Version' => $version // separate from title, we may want to make title nicer.
				)));
			}
		}
		
		return $output;
	}
	
	/**
	 * Generate a list of entities which have been registered and which can 
	 * be documented. 
	 *
	 * @return DataObject
	 */ 
	public function getEntities($version = false, $lang = false) {
		$entities = DocumentationService::get_registered_entities($version, $lang);
		$output = new ArrayList();
		
		$currentEntity = $this->getEntity();

		if($entities) {
			foreach($entities as $entity) {
				$mode = ($entity === $currentEntity) ? 'current' : 'link';
				$folder = $entity->getFolder();
				
				$link = $this->Link(array(), $folder, false, $lang);
				
				$content = false;
				if($page = $entity->getIndexPage($version, $lang)) {
					$content = DBField::create_field('HTMLText', DocumentationParser::parse($page, $link));
				}
				
				$output->push(new ArrayData(array(
					'Title' 	  => $entity->getTitle(),
					'Link'		  => $link,
					'LinkingMode' => $mode,
					'Content' 	  => $content
				)));
			}
		}

		return $output;
	}
	
	/**
	 * Get the currently accessed entity from the site.
	 *
	 * @return false|DocumentationEntity
	 */
	public function getEntity() {
		if($this->entity) {
			return DocumentationService::is_registered_entity(
				$this->entity, 
				$this->version, 
				$this->language
			);
		}

		return false;
	}
	
	/**
	 * Simple way to check for existence of page of folder
	 * without constructing too much object state. Useful for 
	 * generating 404 pages. Returns 0 for not a page or
	 * folder, returns 1 for a page and 2 for folder
	 *
	 * @return int
	 */
	public function locationExists() {
		$entity = $this->getEntity();
		
		if($entity) {
			
			$has_dir = is_dir(Controller::join_links(
				$entity->getPath($this->getVersion(), $this->getLang()), 
				implode('/', $this->Remaining)
			));
			
			if($has_dir) return 2;
			
			$has_page = DocumentationService::find_page(
				$entity, 
				$this->Remaining, 
				$this->getVersion(), 
				$this->getLang()
			);

			if($has_page) return 1;
		}

		return 0;
	}
	
	/**
	 * @return DocumentationPage
	 */
	function getPage() {
		$entity = $this->getEntity();

		if(!$entity) return false;

		$version = $this->getVersion();
		$lang = $this->getLang();
		
		$absFilepath = DocumentationService::find_page(
			$entity, 
			$this->Remaining, 
			$version,
			$lang
		);
		
		if($absFilepath) {
			$relativeFilePath = str_replace(
				$entity->getPath($version, $lang),
				'', 
				$absFilepath
			);
			
			$page = new DocumentationPage();
			$page->setRelativePath($relativeFilePath);
			$page->setEntity($entity);
			$page->setLang($lang);
			$page->setVersion($version);

			return $page;
		}

		return false;
	}
	
	/**
	 * Get the related pages to the current {@link DocumentationEntity} and 
	 * the children to those pages
	 *
	 * @todo this only handles 2 levels. Could make it recursive
	 *
	 * @return false|ArrayList
	 */
	function getEntityPages() {
		if($entity = $this->getEntity()) {
			$pages = DocumentationService::get_pages_from_folder($entity, null, self::$recursive_submenu, $this->getVersion(), $this->getLang());

			if($pages) {
				foreach($pages as $page) {
					if(strtolower($page->Title) == "index") {
						$pages->remove($page);
						
						continue;
					}
					
					$page->LinkingMode = 'link';
					$page->Children = $this->_getEntityPagesNested($page, $entity);

					if (!empty($page->Children)) {
						$this->currentLevelOnePage = $page;
					}
				}
			}

			return $pages;
		}

		return false;
	}
	
	/**
	 * Get all the pages under a given page. Recursive call for {@link getEntityPages()}
	 *
	 * @todo Need to rethink how to support pages which are pulling content from their children
	 *		i.e if a folder doesn't have 2 then it will load the first file in the folder
	 *		however it doesn't yet pass the highlighting to it.
	 *
	 * @param ArrayData CurrentPage
	 * @param DocumentationEntity 
	 * @param int Depth of page in the tree
	 *
	 * @return ArrayList|false
	 */
	private function _getEntityPagesNested(&$page, $entity, $level = 0) {
		if(isset($this->Remaining[$level])) {
			// compare segment successively, e.g. with "changelogs/alpha/2.4.0-alpha",
			// first comparison on $level=0 is against "changelogs",
			// second comparison on $level=1 is against "changelogs/alpha", etc.
			$segments = array_slice($this->Remaining, 0, $level+1);
			
			if(strtolower(implode('/', $segments)) == strtolower(trim($page->getRelativeLink(), '/'))) {
				
				// its either in this section or is the actual link
				$page->LinkingMode = (isset($this->Remaining[$level + 1])) ? 'section' : 'current';
				
				$relativePath = Controller::join_links(
					$entity->getPath($this->getVersion(), $this->getLang()),
					$page->getRelativePath()
				);

				if(is_dir($relativePath)) {
					$children = DocumentationService::get_pages_from_folder(
						$entity, 
						$page->getRelativePath(), 
						self::$recursive_submenu,
						$this->getVersion(), 
						$this->getLang()
					);

					$segments = array();
					for($x = 0; $x <= $level; $x++) {
						$segments[] = $this->Remaining[$x];
					}
					
					foreach($children as $child) {
						if(strtolower($child->Title) == "index") {
							$children->remove($child);
							
							continue;
						}
						
						$child->LinkingMode = 'link';
						$child->Children = $this->_getEntityPagesNested($child, $entity, $level + 1);
					}
					
					return $children;
				}
			} else {
				if ($page->getRelativeLink() == $this->Remaining[$level]) {
					$page->LinkingMode = 'current';
				}
			}
		}
		
		return false;
	}
	
	/**
	 * @return DocumentationPage
	 */
	public function getCurrentLevelOnePage() {
		return $this->currentLevelOnePage;
	}

	/**
	 * returns 'separate' if the submenu should be displayed in a separate
	 * block, 'nested' otherwise. If no currentDocPage is defined, there is
	 * no submenu, so an empty string is returned.
	 *
	 * @return string
	 */
	public function getSubmenuLocation() {
		if ($this->currentLevelOnePage) {
			if (self::$separate_submenu && !self::$recursive_submenu) {
				return 'separate';
			} else {
				return 'nested';
			}
		}
		return '';
	}

	/**
	 * Return the content for the page. If its an actual documentation page then
	 * display the content from the page, otherwise display the contents from
	 * the index.md file if its a folder
	 *
	 * @return HTMLText
	 */
	function getContent() {
		$page = $this->getPage();
		
		if($page) {
			return DBField::create_field("HTMLText", $page->getHTML($this->getVersion(), $this->getLang()));
		}
		
		// If no page found then we may want to get the listing of the folder.
		// In case no folder exists, show a "not found" page.
		$entity = $this->getEntity();
		$url = $this->Remaining;
		
		if($url && $entity) {
			$pages = DocumentationService::get_pages_from_folder(
				$entity, 
				implode('/', $url), 
				false,
				$this->getVersion(),
				$this->getLang()
			);
			
			return $this->customise(array(
				'Content' => false,
				'Title' => DocumentationService::clean_page_name(array_pop($url)),
				'Pages' => $pages
			))->renderWith('DocFolderListing');
		}
		else {
			return $this->customise(array(
				'Content' => false,
				'Title' => _t('DocumentationViewer.MODULES', 'Modules'),
				'Pages' => $this->getEntities()
			))->renderWith('DocFolderListing');
		}
	
		return false;
	}
	
	/**
	 * Generate a list of breadcrumbs for the user. Based off the remaining params
	 * in the url
	 *
	 * @return ArrayList
	 */
	public function getBreadcrumbs() {
		if(!$this->Remaining) $this->Remaining = array();
		
		$pages = array_merge(array($this->entity), $this->Remaining);
		$output = new ArrayList();
		
		if($pages) {
			$path = array();
			$version = $this->getVersion();
			$lang = $this->getLang();
			
			foreach($pages as $i => $title) {
				if($title) {
					// Don't add entity name, already present in Link()
					if($i > 0) $path[] = $title;
					
					$output->push(new ArrayData(array(
						'Title' => DocumentationService::clean_page_name($title),
						'Link' => rtrim($this->Link($path, false, $version, $lang), "/"). "/"
					)));
				}
			}
		}
		
		return $output;
	}
	
	/**
	 * Generate a string for the title tag in the URL.
	 *
	 * @return string
	 */
	public function getPageTitle() {
		if($pages = $this->getBreadcrumbs()) {
			$output = "";
			
			foreach($pages as $page) {
				$output = $page->Title .' - '. $output;
			}
			
			return $output;
		}
		
		return false;
	}
	
	/**
	 * Return the base link to this documentation location
	 *
	 * @param string $path - subfolder path
	 * @param string $entity - name of entity
	 * @param float $version - optional version
	 * @param string $lang - optional lang
	 *
	 * @return String
	 */
	public function Link($path = false, $entity = false, $version = false, $lang = false) {
		$version = ($version === null) ? $this->getVersion() : $version;
		
		$lang = (!$lang) ? $this->getLang() : $lang;
		
		$entity = (!$entity && $this->entity) ? $this->entity : $entity;
		$action = '';
		
		if(is_string($path)) {
			$action = $path;
		}
		else if(is_array($path)) {
			$action = implode('/', $path);
		}

		// check for stable version: if so, remove version from link
		// (see DocumentationEntity->getRelativeLink() )
		$objEntity = $this->getEntity();
		if ($objEntity && $objEntity->getStableVersion() == $version) $version = '';

		$link = Controller::join_links(
			Director::absoluteBaseURL(), 
			self::get_link_base(), 
			$entity, 
			($entity) ? $lang : "", // only include lang for entity - sapphire/en vs en/
			($entity) ? $version :"",
			$action
		);

		return $link;
	} 
	
	/**
	 * Build the language dropdown.
	 *
	 * @todo do this on a page by page rather than global
	 *
	 * @return Form
	 */
	function LanguageForm() {
		$langs = $this->getLanguages();
		
		$fields = new FieldList(
			$dropdown = new DropdownField(
				'LangCode', 
				_t('DocumentationViewer.LANGUAGE', 'Language'),
				$langs,
				$this->Lang
			)
		);
		
		$actions = new FieldList(
			new FormAction('doLanguageForm', _t('DocumentationViewer.CHANGE', 'Change'))
		);
		
		$dropdown->setDisabled(true);
		
		return new Form($this, 'LanguageForm', $fields, $actions);
	}
	
	/**
	 * Process the language change
	 *
	 */
	function doLanguageForm($data, $form) {
		$this->Lang = (isset($data['LangCode'])) ? $data['LangCode'] : 'en';

		return $this->redirect($this->Link());
	}
	
	/**
	 * @param String
	 */
	static function set_link_base($base) {
		self::$link_base = $base;
	}
	
	/**
	 * @return String
	 */
	static function get_link_base() {
		return self::$link_base;
	}
	
	/**
	 * @see {@link Form::FormObjectLink()}
	 */
	function FormObjectLink($name) {
		return $name;
	}
	
	/**
	 * Documentation Search Form. Allows filtering of the results by many entities
	 * and multiple versions.
	 *
	 * @return Form
	 */
	function DocumentationSearchForm() {
		if(!DocumentationSearch::enabled()) return false;
		$q = ($q = $this->getSearchQuery()) ? $q->NoHTML() : "";

		$entities = $this->getSearchedEntities();
		$versions = $this->getSearchedVersions();
		
		$fields = new FieldList(
			new TextField('Search', _t('DocumentationViewer.SEARCH', 'Search'), $q)
		);
		
		if ($entities) $fields->push(
			new HiddenField('Entities', '', implode(',', array_keys($entities)))
		);
		
		if ($versions) $fields->push(
			new HiddenField('Versions', '', implode(',', $versions))
		);

		$actions = new FieldList(
			new FormAction('results', 'Search')
		);

		$form = new Form($this, 'DocumentationSearchForm', $fields, $actions);
		$form->disableSecurityToken();
		$form->setFormMethod('GET');
		$form->setFormAction(self::$link_base . 'DocumentationSearchForm');
		
		return $form;
	}
	
	/**
	 * Return an array of folders and titles
	 *
	 * @return array
	 */
	function getSearchedEntities() {
		$entities = array();

		if(!empty($_REQUEST['Entities'])) {
			if(is_array($_REQUEST['Entities'])) {
				$entities = Convert::raw2att($_REQUEST['Entities']);
			}
			else {
				$entities = explode(',', Convert::raw2att($_REQUEST['Entities']));
				$entities = array_combine($entities, $entities);
			}
		}
		else if($entity = $this->getEntity()) {
			$entities[$entity->getFolder()] = Convert::raw2att($entity->getTitle());
		}
		
		return $entities;
	}
	
	/**
	 * Return an array of versions that we're allowed to return
	 *
	 * @return array
	 */
	function getSearchedVersions() {
		$versions = array();
		
		if(!empty($_REQUEST['Versions'])) {
			if(is_array($_REQUEST['Versions'])) {
				$versions = Convert::raw2att($_REQUEST['Versions']);
				$versions = array_combine($versions, $versions);
			}
			else {
				$version = Convert::raw2att($_REQUEST['Versions']);
				$versions[$version] = $version;
			}
		}
		else if($version = $this->getVersion()) {
			$version =  Convert::raw2att($version);
			$versions[$version] = $version;
		}

		return $versions;
	}
	
	/**
	 * Return the current search query
	 *
	 * @return HTMLText|null
	 */
	function getSearchQuery() {
		if(isset($_REQUEST['Search'])) {
			return DBField::create_field('HTMLText', $_REQUEST['Search']);
		}
	}
	
	/**
	 * Past straight to results, display and encode the query
	 */
	function results($data, $form = false) {
		$query = (isset($_REQUEST['Search'])) ? $_REQUEST['Search'] : false;

		$search = new DocumentationSearch();
		$search->setQuery($query);
		$search->setVersions($this->getSearchedVersions());
		$search->setModules($this->getSearchedEntities());
		$search->setOutputController($this);
		
		return $search->renderResults();
	}
	
	/**
	 * Returns an search form which allows people to express more complex rules
	 * and options than the plain search form.
	 *
	 * @todo client side filtering of checkable option based on the module selected.
	 *
	 * @return Form
	 */
	function AdvancedSearchForm() {
		$entities = DocumentationService::get_registered_entities();
		$versions = array();
		
		foreach($entities as $entity) {
			$versions[$entity->getFolder()] = $entity->getVersions();
		}
		
		// get a list of all the unique versions
		$uniqueVersions = array_unique(self::array_flatten(array_values($versions)));
		asort($uniqueVersions);
		$uniqueVersions = array_combine($uniqueVersions,$uniqueVersions);
		
		$q = ($q = $this->getSearchQuery()) ? $q->NoHTML() : "";
		
		// klude to take an array of objects down to a simple map
		$entities = new ArrayList($entities);
		$entities = $entities->map('Folder', 'Title');
		
		// if we haven't gone any search limit then we're searching everything
		$searchedEntities = $this->getSearchedEntities();
		if(count($searchedEntities) < 1) $searchedEntities = $entities;
		
		$searchedVersions = $this->getSearchedVersions();
		if(count($searchedVersions) < 1) $searchedVersions = $uniqueVersions;

		$fields = new FieldList(
			new TextField('Search', _t('DocumentationViewer.KEYWORDS', 'Keywords'), $q),
			new CheckboxSetField('Entities', _t('DocumentationViewer.MODULES', 'Modules'), $entities, $searchedEntities),
			new CheckboxSetField('Versions', _t('DocumentationViewer.VERSIONS', 'Versions'),
			 	$uniqueVersions, $searchedVersions
			)
		);
		
		$actions = new FieldList(
			new FormAction('results', _t('DocumentationViewer.SEARCH', 'Search'))
		);
		$required = new RequiredFields(array('Search'));
		
		$form = new Form($this, 'AdvancedSearchForm', $fields, $actions, $required);
		$form->disableSecurityToken();
		$form->setFormMethod('GET');
		$form->setFormAction(self::$link_base . 'DocumentationSearchForm');
	
		return $form;
	}

	/**
	 * check if the Advanced SearchForm can be displayed
	 * enabled by default, to disable use: 
	 * DocumentationSearch::enable_advanced_search(false);
	 * 
	 * @return bool
	 */
	public function getAdvancedSearchEnabled() {
		return 	DocumentationSearch::advanced_search_enabled(); 
	}
	
	/**
	 * Check to see if the currently accessed version is out of date or
	 * perhaps a future version rather than the stable edition
	 *
	 * @return false|ArrayData
	 */
	function VersionWarning() {
		$version = $this->getVersion();
		$entity = $this->getEntity();
		
		if($entity) {
			$compare = $entity->compare($version);
			$stable = $entity->getStableVersion();

			// same
			if($version == $stable) return false;
			
			// check for trunk, if trunk and not the same then it's future
			// also run through compare
			if($version == "trunk" || $compare > 0) {
				return $this->customise(new ArrayData(array(
					'FutureRelease' => true,
					'StableVersion' => DBField::create_field('HTMLText', $stable)
				)));				
			}
			else {
				return $this->customise(new ArrayData(array(
					'OutdatedRelease' => true,
					'StableVersion' => DBField::create_field('HTMLText', $stable)
				)));
			}
		}
		
		return false;
	}

	/**
	 * Sets the mapping between a entity name and the link for the end user
	 * to jump into editing the documentation. 
	 *
	 * Some variables are replaced:
	 *	- %version%
	 *	- %entity%
	 *	- %path%
	 * 	- %lang%
	 *
	 * For example to provide an edit link to the framework module in github:
	 *
	 * <code>
	 * DocumentationViewer::set_edit_link(
	 *	'framework', 
	 *	'https://github.com/silverstripe/%entity%/edit/%version%/docs/%lang%/%path%',
	 * 	$opts
	 * ));
	 * </code>
	 *
	 * @param string module name
	 * @param string link
	 * @param array options ('rewritetrunktomaster')
	 */
	public static function set_edit_link($module, $link, $options = array()) {
		self::$edit_links[$module] = array(
			'url' => $link,
			'options' => $options
		);
	}

	/**
	 * Returns an edit link to the current page (optional).
	 *
	 * @return string
	 */
	public function getEditLink() {
		$page = $this->getPage();

		if($page) {
			$entity = $page->getEntity();

			if($entity && isset(self::$edit_links[$entity->title])) {
				// build the edit link, using the version defined
				$url = self::$edit_links[$entity->title];
				$version = $page->getVersion();

				if($version == "trunk" && (isset($url['options']['rewritetrunktomaster']))) {
					if($url['options']['rewritetrunktomaster']) {
						$version = "master";
					}
				}

				return str_replace(
					array('%entity%', '%lang%', '%version%', '%path%'),
					array(
						$entity->getFolder(), 
						$page->getLang(), 
						$version, 
						ltrim($page->getRelativePath(), '/')
					),

					$url['url']
				);
			}
		}

		return false;
	}
	
	/** 
	 * Flattens an array
	 *
	 * @param array
	 * @return array
	 */ 
	public static function array_flatten($array) { 
		if(!is_array($array)) return false; 
		
		$output = array(); 
		foreach($array as $k => $v) { 
			if(is_array($v)) { 
				$output = array_merge($output, self::array_flatten($v)); 
			} 
			else { 
				$output[$k] = $v; 
			} 
		}
		
		return $output; 
	}
}
