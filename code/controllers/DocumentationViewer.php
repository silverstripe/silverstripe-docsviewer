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
	 * @var string
	 */
	public $module = '';
	
	/**
	 * @var array
	 */
	public $remaining = array();
	
	/**
	 * @var String Same as the routing pattern set through Director::addRules().
	 */
	protected static $link_base = 'dev/docs/';
	
	/**
	 * @var String|array Optional permssion check
	 */
	static $check_permission = 'ADMIN';
	
	function init() {
		parent::init();

		if(!$this->canView()) return Security::permissionFailure($this);

		// javascript
		Requirements::javascript(THIRDPARTY_DIR .'/jquery/jquery.js');
		Requirements::javascript('sapphiredocs/thirdparty/syntaxhighlighter/scripts/shCore.js');
		Requirements::javascript('sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushJScript.js');
		Requirements::javascript('sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushPhp.js');
		Requirements::javascript('sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushXml.js');
		Requirements::javascript('sapphiredocs/thirdparty/syntaxhighlighter/scripts/shBrushCss.js');
		Requirements::javascript('sapphiredocs/javascript/shBrushSS.js');
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
	 * Overloaded to avoid "action doesnt exist" errors - all URL parts in this
	 * controller are virtual and handled through handleRequest(), not controller methods.
	 */
	public function handleAction($request) {
		try{
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
		
		if(isset($firstParam)) {
			// allow assets
			if($firstParam == "assets") return parent::handleRequest($request);
			
			if($link = DocumentationPermalinks::map($firstParam)) {
				// the first param is a shortcode for a page so redirect the user to
				// the short code.
				$this->response = new SS_HTTPResponse();
				$this->redirect($link, 301); // 301 permanent redirect
			
				return $this->response;
				
			}

			$this->module = $firstParam;
			$this->lang = $secondParam;
			
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
		$module = DocumentationService::is_registered_module($this->module, null, $this->getLang());
		
		if($module) {
			$current = $module->getLatestVersion();
			$version = $this->getVersion();
			
			if(!$version || $version == '') {
				$this->version = $current;
			}
			
			// Check if page exists, otherwise return 404
			if(!$this->locationExists()) {
				$body = $this->renderWith(get_class($this));
				$this->response = new SS_HTTPResponse($body, 404);
				
				return $this->response;
			}
		}

		return parent::handleRequest($request);
	}
		
	/**
	 * Custom templates for each of the sections. 
	 */
	function getViewer($action) {
		// count the number of parameters after the language, version are taken
		// into account. This automatically includes ' ' so all the counts
		// are 1 more than what you would expect

		if($this->module || $this->Remaining) {

			$paramCount = count($this->Remaining);
			
			if($paramCount == 0) {
				return parent::getViewer('folder');
			}
			else if($module = $this->getModule()) {
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
		
		if($module = $this->getModule()) {
			$this->version = $module->getLatestVersion();
			
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
	 * Return all the available languages. Optionally the languages which are
	 * available for a given module
	 *
	 * @param String - The name of the module
	 * @return DataObjectSet
	 */
	function getLanguages($module = false) {
		$output = new DataObjectSet();
		
		if($module) {
			// lookup the module for the available languages
			
			// @todo
		}
		else {
			$languages = DocumentationService::get_registered_languages();

			if($languages) {
				foreach($languages as $key => $lang) {
	
					if(stripos($_SERVER['REQUEST_URI'], '/'. $this->Lang .'/') === false) {
						// no language is in the URL currently. It needs to insert the language 
						// into the url like /sapphire/install to /en/sapphire/install
						//
						// @todo
					} 
					
					$link = str_ireplace('/'.$this->Lang .'/', '/'. $lang .'/', $_SERVER['REQUEST_URI']);

					$output->push(new ArrayData(array(
						'Title' => $lang,
						'Link' => $link
					)));
				}
			}
		}
			
		return $output;
	}

	/**
	 * Get all the versions loaded into the module. If the project is only displaying from 
	 * the filesystem then they are loaded under the 'Current' namespace.
	 *
	 * @todo Only show 'core' versions (2.3, 2.4) versions of the modules are going
	 *		to spam this
	 *
	 * @param String $module name of module to limit it to eg sapphire
	 * @return DataObjectSet
	 */
	function getVersions($module = false) {
		if(!$module) $module = $this->module;
		
		$entity = DocumentationService::is_registered_module($module);
		if(!$entity) return false;
		
		$versions = DocumentationService::get_registered_versions($module);
		
		$output = new DataObjectSet();
		$currentVersion = $this->getVersion();
				
		if($versions) {
			$lang = $this->getLang();
			
			foreach($versions as $key => $version) {
				// work out the link to this version of the documentation.  
				// @todo Keep the user on their given page rather than redirecting to module.
				$linkingMode = ($currentVersion == $version) ? 'current' : 'link';
			
				if(!$version) $version = 'Current';
				$major = (in_array($version, DocumentationService::get_major_versions())) ? true : false;

				
				$output->push(new ArrayData(array(
					'Title' => $version,
					'Link' => $entity->Link($version, $lang),
					'LinkingMode' => $linkingMode,
					'MajorRelease' => $major
				)));
			}
		}
		
		return $output;
	}
	
	/**
	 * Generate the module which are to be documented. It filters
	 * the list based on the current head version. It displays the contents
	 * from the index.md file on the page to use.
	 *
	 * @return DataObject
	 */ 
	function getModules($version = false, $lang = false) {
		if(!$version) $version = $this->getVersion();
		if(!$lang) $lang = $this->getLang();
		
		$modules = DocumentationService::get_registered_modules($version, $lang);
		$output = new DataObjectSet();

		if($modules) {
			foreach($modules as $module) {
				$path = $module->getPath($version, $lang);
				$absFilepath = $path . '/index.md';
				$relativeFilePath = str_replace($path, '', $absFilepath);
	
				if(file_exists($absFilepath)) {
					$page = new DocumentationPage();
					$page->setRelativePath($relativeFilePath);
					$page->setEntity($module);
					$page->setLang($lang);
					$page->setVersion($version);
					
					$content = DocumentationParser::parse($page, $this->Link(array_slice($this->Remaining, -1, -1)));
				} else {
					$content = '';
				}
				
				// build the dataset. Load the $Content from an index.md
				$output->push(new ArrayData(array(
					'Title' 	=> $module->getTitle(),
					'Code'		=> $module,
					'Link'		=> $this->Link(array_slice($this->Remaining, -1, -1), $module->moduleFolder),
					'Content' 	=> DBField::create("HTMLText", $content)
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
	function getModule() {
		if($this->module) {
			return DocumentationService::is_registered_module(
				$this->module, 
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
		$module = $this->getModule();
		
		if($module) {
			$has_dir = is_dir(Controller::join_links(
				$module->getPath($this->getVersion(), $this->getLang()), 
				implode('/', $this->Remaining)
			));
			
			if($has_dir) return 2;
			
			$has_page = DocumentationService::find_page(
				$module, 
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
		$module = $this->getModule();

		if(!$module) return false;

		$version = $this->getVersion();
		$lang = $this->getLang();
		
		$absFilepath = DocumentationService::find_page(
			$module, 
			$this->Remaining, 
			$version,
			$lang
		);
		
		if($absFilepath) {
			$relativeFilePath = str_replace(
				$module->getPath($version, $lang),
				'', 
				$absFilepath
			);
			
			$page = new DocumentationPage();
			$page->setRelativePath($relativeFilePath);
			$page->setEntity($module);
			$page->setLang($lang);
			$page->setVersion($version);

			return $page;
		}

		return false;
	}
	
	/**
	 * Get the related pages to this module and the children to those pages
	 *
	 * @todo this only handles 2 levels. Could make it recursive
	 *
	 * @return false|DataObjectSet
	 */
	function getModulePages() {
		if($module = $this->getModule()) {
			$pages = DocumentationService::get_pages_from_folder($module, null, false, $this->getVersion(), $this->getLang());

			if($pages) {
				foreach($pages as $page) {
					if(strtolower($page->Title) == "index") {
						$pages->remove($page);
						
						continue;
					}
					
					$page->LinkingMode = 'link';
					$page->Children = $this->_getModulePagesNested($page, $module);
				}
			}

			return $pages;
		}

		return false;
	}
	
	/**
	 * Get the module pages under a given page. Recursive call for {@link getModulePages()}
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
	private function _getModulePagesNested(&$page, $module, $level = 0) {
		if(isset($this->Remaining[$level])) {
			// compare segment successively, e.g. with "changelogs/alpha/2.4.0-alpha",
			// first comparison on $level=0 is against "changelogs",
			// second comparison on $level=1 is against "changelogs/alpha", etc.
			$segments = array_slice($this->Remaining, 0, $level+1);
			
			if(strtolower(implode('/', $segments)) == trim($page->getRelativeLink(), '/')) {
				
				// its either in this section or is the actual link
				$page->LinkingMode = (isset($this->Remaining[$level + 1])) ? 'section' : 'current';
				
				$relativePath = Controller::join_links(
					$module->getPath($this->getVersion(), $this->getLang()),
					$page->getRelativePath()
				);

				if(is_dir($relativePath)) {
					$children = DocumentationService::get_pages_from_folder(
						$module, 
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
						$child->Children = $this->_getModulePagesNested($child, $module, $level + 1);
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
		else {
			// If no page found then we may want to get the listing of the folder.
			// In case no folder exists, show a "not found" page.
			$module = $this->getModule();
			$url = $this->Remaining;
			
			if($url && $module) {
				$pages = DocumentationService::get_pages_from_folder(
					$module, 
					implode('/', $url), 
					false,
					$this->getVersion(),
					$this->getLang()
				);
				
				// If no pages are found, the 404 is handled in the same template
				return $this->customise(array(
					'Title' => DocumentationService::clean_page_name(array_pop($url)),
					'Pages' => $pages
				))->renderWith('DocFolderListing');
			}
			else {
				// get all available modules and show a table of contents.
				
				return $this->customise(array(
					'Title' => _t('DocumentationViewer.MODULES', 'Modules'),
					'Pages' => $this->getModules()
				))->renderWith('DocFolderListing');
			}
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
		
		$pages = array_merge(array($this->module), $this->Remaining);
		
		$output = new DataObjectSet();
		
		if($pages) {
			$path = array();
			
			foreach($pages as $i => $title) {
				if($title) {
					// Don't add module name, already present in Link()
					if($i > 0) $path[] = $title;
					
					$output->push(new ArrayData(array(
						'Title' => DocumentationService::clean_page_name($title),
						'Link' => rtrim($this->Link($path), "/"). "/"
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
	 * @return String
	 */
	public function Link($path = false, $module = false) {
		$base = Director::absoluteBaseURL();
		
		$version = $this->getVersion();
		$lang = $this->getLang();
		
		$module = (!$module && $this->module) ? $this->module : $module;
		
		$action = '';
		
		if(is_string($path)) $action = $path;
		else if(is_array($path)) {
			$action = implode('/', $path);
		}
		
		$link = Controller::join_links($base, self::get_link_base(), $module, $lang, $version, $action);

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
		if($module = $this->getModule()) {
			$langs = DocumentationService::get_registered_languages($module->getModuleFolder());
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