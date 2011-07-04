<?php

/**
 * Documentation Viewer.
 *
 * Reads the bundled markdown files from documentation folders and displays the output (either
 * via markdown or plain text)
 *
 * For more documentation on how to use this class see the documentation in /sapphiredocs/docs folder
 *
 * @package sapphiredocs
 */

class DocumentationViewer extends Controller {

	static $allowed_actions = array(
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
	 * @var string
	 */
	public $entity = '';
	
	/**
	 * @var array
	 */
	public $remaining = array();
	
	/**
	 * @var String Same as the routing pattern set through Director::addRules().
	 */
	protected static $link_base = 'dev/docs/';
	
	/**
	 * @var String|array Optional permission check
	 */
	static $check_permission = 'ADMIN';
	
	function init() {
		parent::init();

		if(!$this->canView()) return Security::permissionFailure($this);

		// javascript
		Requirements::javascript(THIRDPARTY_DIR .'/jquery/jquery.js');
		
		Requirements::combine_files(
			'syntaxhighlighter.js',
			array(
				'sapphiredocs/thirdparty/syntaxhighlighter/scripts/shCore.js',
				'sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushJScript.js',
				'sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushPhp.js',
				'sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushXml.js',
				'sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushCss.js',
				'sapphiredocs/javascript/shBrushSS.js'
			)
		);
		
		Requirements::javascript('sapphiredocs/javascript/DocumentationViewer.js');

		// css
		Requirements::css('sapphiredocs/thirdparty/syntaxhighlighter/styles/shCore.css');
		Requirements::css('sapphiredocs/thirdparty/syntaxhighlighter/styles/shCoreRDark.css');
		Requirements::css('sapphiredocs/thirdparty/syntaxhighlighter/styles/shThemeRDark.css');
		
		Requirements::customScript('jQuery(document).ready(function() {SyntaxHighlighter.all();});');
	}
	
	/**
	 * Can the user view this documentation. Hides all functionality for private wikis
	 *
	 * @return bool
	 */
	public function canView() {
		return (Director::isDev() || Director::is_cli() || !self::$check_permission || Permission::check(self::$check_permission));
	}

	/**
	 * Overloaded to avoid "action doesn't exist" errors - all URL parts in this
	 * controller are virtual and handled through handleRequest(), not controller methods.
	 */
	public function handleAction($request) {
		try {
			$response = parent::handleAction($request);
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
	public function handleRequest(SS_HTTPRequest $request) {
		// if we submitted a form, let that pass
		if(!$request->isGET() || isset($_GET['action_results'])) return parent::handleRequest($request);

		$firstParam = ($request->param('Action')) ? $request->param('Action') : $request->shift();		
		$secondParam = $request->shift();
		$thirdParam = $request->shift();
		
		$this->Remaining = $request->shift(10);
		DocumentationService::load_automatic_registration();
		
		// if no params passed at all then it's the homepage
		if(!$firstParam && !$secondParam && !$thirdParam) {
			return parent::handleRequest($request);
		}
		
		if($firstParam) {
			// allow assets
			if($firstParam == "assets") {
				return parent::handleRequest($request);
			}
			
			// check for permalinks
			if($link = DocumentationPermalinks::map($firstParam)) {
				// the first param is a shortcode for a page so redirect the user to
				// the short code.
				$this->response = new SS_HTTPResponse();
				$this->redirect($link, 301); // 301 permanent redirect
			
				return $this->response;
				
			}

			// check to see if the module is a valid module. If it isn't, then we
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
			$current = $entity->getLatestVersion();
			$version = $this->getVersion();
			
			if(!$version) {
				$this->version = $current;
			}
			
			// Check if page exists, otherwise return 404
			if(!$this->locationExists()) {
				return $this->throw404();
			}
			
			
			return parent::handleRequest($request);
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
	 * set version so need to pull that from the module.
	 *
	 * @return String
	 */
	function getVersion() {
		if($this->version) return $this->version;
		
		if($entity = $this->getEntity()) {
			$this->version = $entity->getLatestVersion();
			
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
	 * Return all the available languages for the module.
	 *
	 * @param String - The name of the module
	 * @return DataObjectSet
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
	 * @param String $entity name of module to limit it to eg sapphire
	 * @return DataObjectSet
	 */
	function getVersions($entity = false) {
		if(!$entity) $entity = $this->entity;
		
		$entity = DocumentationService::is_registered_entity($entity);
		if(!$entity) return false;
		
		$versions = $entity->getVersions();
		$output = new DataObjectSet();
				
		if($versions) {
			$lang = $this->getLang();
			$currentVersion = $this->getVersion();
			
			foreach($versions as $key => $version) {
				// work out the link to this version of the documentation.  
				// @todo Keep the user on their given page rather than redirecting to module.
				$linkingMode = ($currentVersion == $version) ? 'current' : 'link';
			
				if(!$version) $version = 'Current';
				$output->push(new ArrayData(array(
					'Title' => $version,
					'Link' => $this->Link(implode('/',$this->Remaining), $entity->getFolder(), $version),
					'LinkingMode' => $linkingMode
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
	function getEntities($version = false, $lang = false) {
		$entities = DocumentationService::get_registered_entities($version, $lang);
		$output = new DataObjectSet();
		
		$currentEntity = $this->getEntity();

		if($entities) {
			foreach($entities as $entity) {
				$mode = ($entity === $currentEntity) ? 'current' : 'link';
				$folder = $entity->getFolder();
				
				$link = $this->Link(array(), $folder, false, $lang);
				
				$content = false;
				if($page = $entity->getIndexPage($version, $lang)) {
					$content = DBField::create('HTMLText', DocumentationParser::parse($page, $link));
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
	function getEntity() {
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
	function locationExists() {
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
	 * @return false|DataObjectSet
	 */
	function getEntityPages() {
		if($entity = $this->getEntity()) {
			$pages = DocumentationService::get_pages_from_folder($entity, null, false, $this->getVersion(), $this->getLang());

			if($pages) {
				foreach($pages as $page) {
					if(strtolower($page->Title) == "index") {
						$pages->remove($page);
						
						continue;
					}
					
					$page->LinkingMode = 'link';
					$page->Children = $this->_getEntityPagesNested($page, $entity);
				}
			}

			return $pages;
		}

		return false;
	}
	
	/**
	 * Get the module pages under a given page. Recursive call for {@link getEntityPages()}
	 *
	 * @todo Need to rethink how to support pages which are pulling content from their children
	 *		i.e if a folder doesn't have 2 then it will load the first file in the folder
	 *		however it doesn't yet pass the highlighting to it.
	 *
	 * @param ArrayData CurrentPage
	 * @param DocumentationEntity 
	 * @param int Depth of page in the tree
	 *
	 * @return DataObjectSet|false
	 */
	private function _getEntityPagesNested(&$page, $entity, $level = 0) {
		if(isset($this->Remaining[$level])) {
			// compare segment successively, e.g. with "changelogs/alpha/2.4.0-alpha",
			// first comparison on $level=0 is against "changelogs",
			// second comparison on $level=1 is against "changelogs/alpha", etc.
			$segments = array_slice($this->Remaining, 0, $level+1);
			
			if(strtolower(implode('/', $segments)) == trim($page->getRelativeLink(), '/')) {
				
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
						false, 
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
			}
		}
		
		return false;
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
			return DBField::create("HTMLText", $page->getHTML($this->getVersion(), $this->getLang()));
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
	 * @return DataObjectSet
	 */
	function getBreadcrumbs() {
		if(!$this->Remaining) $this->Remaining = array();
		
		$pages = array_merge(array($this->entity), $this->Remaining);
		
		$output = new DataObjectSet();
		
		if($pages) {
			$path = array();
			$version = $this->getVersion();
			$lang = $this->getLang();
			
			foreach($pages as $i => $title) {
				if($title) {
					// Don't add module name, already present in Link()
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
	 * @return String
	 */
	function getPageTitle() {
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
		$base = Director::absoluteBaseURL();
		
		// only include the version. Version is optional after all
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
		
		$link = Controller::join_links($base, self::get_link_base(), $entity, $lang, $version, $action);

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
		if($entity = $this->getEntity()) {
			$langs = DocumentationService::get_registered_languages($entity->getFolder());
		}
		else {
			$langs = DocumentationService::get_registered_languages();
		}
		
		$fields = new FieldSet(
			$dropdown = new DropdownField(
				'LangCode', 
				_t('DocumentationViewer.LANGUAGE', 'Language'),
				$langs,
				$this->Lang
			)
		);
		
		$actions = new FieldSet(
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
	 * Documentation Basic Search Form
	 *
	 * Integrates with sphinx
	 * @return Form
	 */
	function DocumentationSearchForm() {
		if(!DocumentationSearch::enabled()) return false;
		
		$query = (isset($_REQUEST['Search'])) ? Convert::raw2xml($_REQUEST['Search']) : "";
		
		$fields = new FieldSet(
			new TextField('Search', _t('DocumentationViewer.SEARCH', 'Search'), $query)
		);
		
		$actions = new FieldSet(
			new FormAction('results', 'Search')
		);
		
		$form = new Form($this, 'DocumentationSearchForm', $fields, $actions);
		$form->disableSecurityToken();
		$form->setFormMethod('get');
		$form->setFormAction('home/DocumentationSearchForm');
		
		return $form;
	}
	
	/**
	 * Past straight to results, display and encode the query
	 */
	function results($data, $form = false) {

		$query = (isset($_REQUEST['Search'])) ? $_REQUEST['Search'] : false;
		
		if(!$query) return $this->httpError('404');
		
		$search = new DocumentationSearch();
		$search->setQuery($query);
		$search->setOutputController($this);
		
		return $search->renderResults();
	}
}