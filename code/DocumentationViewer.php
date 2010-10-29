<?php

/**
 * Documentation Viewer.
 *
 * Reads the bundled markdown files from documentation folders and displays the output (either
 * via markdown or plain text)
 *
 * For more documentation on how to use this class see the documentation in /sapphiredocs/docs folder
 *
 * To view the documentation in the browser use:
 * 	
 * 	http://yoursite.com/dev/docs/ Which is locked to ADMIN only
 *
 * @todo 	- Add ability to have docs on the front end as the main site.
 * 			- Fix Language Selector (enabling it troubles the handleRequest when submitting)
 *			- SS_HTTPRequest when we ask for 10 params it gives us 10. Could be 10 blank ones.
 *				It would mean I could save alot of code if it only gave back an array of size X
 * 				up to a maximum of 10...
 *
 * @package sapphiredocs
 */

class DocumentationViewer extends Controller {

	static $allowed_actions = array(
		'LanguageForm',
		'doLanguageForm',
		'handleRequest',
		'DocumentationSearchForm'
	);
	
	/**
	 * @var string
	 */
	public $version = "current";
	
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
		
		$canAccess = (Director::isDev() || Director::is_cli() || !self::$check_permission || Permission::check(self::$check_permission));

		if(!$canAccess) return Security::permissionFailure($this);

		Requirements::javascript(THIRDPARTY_DIR .'/jquery/jquery.js');
		Requirements::javascript('sapphiredocs/javascript/DocumentationViewer.js');
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
		if(!$request->isGET()) return parent::handleRequest($request);
		
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
		
				$this->redirect($link, 301); // permanent redirect
				

				return $this->response;
				
			}
			else if(is_numeric($firstParam) || $firstParam == "current") {
				// its a version number first in the form 2.4/en/sapphire
				$this->version = $firstParam;
				$this->lang = $secondParam;
				$this->module = $thirdParam;
			}
			else {
				// we have a language first in the form /en/sapphire
				array_unshift($this->Remaining, $thirdParam);

				$this->lang = $firstParam;
				$this->module = $secondParam;
			}
		}
		
		// 'current' version mapping
		$module = DocumentationService::is_registered_module($this->module, null, $this->getLang());

		if($module && $this->getVersion()) {
			$current = $module->getCurrentVersion();
			
			$version = $this->getVersion();

			if($version == 'current') {
				$this->version = $current;
			} else if($current == $version) {
				$this->version = 'current';
				$link = $this->Link($this->Remaining);
				$this->response = new SS_HTTPResponse();

				$this->redirect($link, 301); // permanent redirect
			
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
				$params = $this->Remaining;
				
				$path = implode('/', array_unique($params));
				
				if(is_dir($module->getPath() . $path)) return parent::getViewer('folder');
			}
		}
		else {
			return parent::getViewer('home');
		}
		
		return parent::getViewer($action);
	}
	
	/**
	 * Returns the current version
	 *
	 * @return String
	 */
	function getVersion() {
		return $this->version;
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
		$versions = DocumentationService::get_registered_versions($module);
		$output = new DataObjectSet();
		
		foreach($versions as $key => $version) {
			// work out the link to this version of the documentation. 
			// 
			// @todo Keep the user on their given page rather than redirecting to module.
			// @todo Get links working
			$linkingMode = ($this->Version == $version) ? 'current' : 'link';
			
			if(!$version) $version = 'Current';
			$major = (in_array($version, DocumentationService::get_major_versions())) ? true : false;
			
			$output->push(new ArrayData(array(
				'Title' => $version,
				'Link' => $_SERVER['REQUEST_URI'],
				'LinkingMode' => $linkingMode,
				'MajorRelease' => $major
			)));
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
		if(!$version) $version = $this->Version;
		if(!$lang) $lang = $this->Lang;
		
		$modules = DocumentationService::get_registered_modules($version, $lang);
		$output = new DataObjectSet();

		if($modules) {
			foreach($modules as $module) {
				$absFilepath = $module->getPath() . '/index.md';
				$relativeFilePath = str_replace($module->getPath(), '', $absFilepath);
				if(file_exists($absFilepath)) {
					$page = new DocumentationPage();
					$page->setRelativePath($relativeFilePath);
					$page->setEntity($module);
					$page->setLang($this->Lang);
					$page->setVersion($this->Version);
					
					$content = DocumentationParser::parse($page, $this->Link(array_slice($this->Remaining, -1, -1)));
				} else {
					$content = '';
				}
				
				// build the dataset. Load the $Content from an index.md
				$output->push(new ArrayData(array(
					'Title' 	=> $module->getTitle(),
					'Code'		=> $module,
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
			return DocumentationService::is_registered_module($this->module, $this->version, $this->lang);
		}

		return false;
	}
	
	/**
	 * @return DocumentationPage
	 */
	function getPage() {
		$module = $this->getModule();
		if(!$module) return false;
		
		$absFilepath = DocumentationParser::find_page($module->getPath(), $this->Remaining);
		
		if($absFilepath) {
			$relativeFilePath = str_replace($module->getPath(), '', $absFilepath);
			
			$page = new DocumentationPage();
			$page->setRelativePath($relativeFilePath);
			$page->setEntity($module);
			$page->setLang($this->Lang);
			$page->setVersion($this->Version);
			
			return $page;
		} else {
			return false;
		}
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
			$pages = DocumentationParser::get_pages_from_folder($module->getPath());
			
			if($pages) {
				foreach($pages as $page) {
					if(strtolower($page->Title) == "index") {
						$pages->remove($page);
						
						continue;
					}
					
					$linkParts = array();
					
					// don't include the 'index in the url
					if(strtolower($page->Title) != "index") $linkParts[] = $page->Filename;

					$page->Link = $this->Link($linkParts);
					$page->LinkingMode = 'link';
					
					$page->Children = $this->_getModulePagesNested($page);
				}
			}
			
			return $pages;
		}
		
		return false;
	}
	
	/**
	 * Get the module pages under a given page. Recursive call for {@link getModulePages()}
	 *
	 * @param ArrayData CurrentPage
	 * @param int Depth of page in the tree
	 *
	 * @return DataObjectSet|false
	 */
	private function _getModulePagesNested(&$page, $level = 0) {
		// only support 2 more levels
		if(isset($this->Remaining[$level])) {

			if(strtolower($this->Remaining[$level]) == $page->Filename) {
				
				// its either in this section or is the actual link
				$page->LinkingMode = (isset($this->Remaining[$level + 1])) ? 'section' : 'current';
				
				if(is_dir($page->Path)) {
					$children = DocumentationParser::get_pages_from_folder($page->Path);
					
					$segments = array();
					for($x = 0; $x <= $level; $x++) {
						$segments[] = $this->Remaining[$x];
					}
					
					foreach($children as $child) {
						if(strtolower($child->Title) == "index") {
							$children->remove($child);
							
							continue;
						}
						
						$child->Link = $this->Link(array_merge($segments, array($child->Filename)));
						$child->LinkingMode = 'link';
						$child->Children = $this->_getModulePagesNested($child, $level + 1);
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
		if($page = $this->getPage()) {
			// Remove last portion of path (filename), we want a link to the folder base
			$html = DocumentationParser::parse($page, $this->Link(array_slice($this->Remaining, -1, -1)));
			return DBField::create("HTMLText", $html);
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
		
		// $output->push(new ArrayData(array(
		// 	'Title' => ($this->Version) ? $this->Version : _t('DocumentationViewer.DOCUMENTATION', 'Documentation'),
		// 	'Link' => $this->Link()
		// )));
		if($pages) {
			$path = array();
			
			foreach($pages as $i => $title) {
				if($title) {
					// Don't add module name, already present in Link()
					if($i > 0) $path[] = $title;

					$output->push(new ArrayData(array(
						'Title' => DocumentationParser::clean_page_name($title),
						'Link' => $this->Link($path)
					)));
				}
			}
		}
		
		return $output;
	}
	
	/**
	 * Return the base link to this documentation location
	 *
	 * @todo Make this work on non /dev/ 
	 * @return String
	 */
	public function Link($path = false) {
		$base = Director::absoluteBaseURL();
		
		$version = ($this->version) ? $this->version . '/' : false;
		$lang = ($this->language) ? $this->language  .'/' : false;
		$module = ($this->module) ? $this->module .'/' : false;
		
		$action = '';
		
		if(is_string($path)) {
			$action = $path . '/';
		}
		else if(is_array($path)) {
			foreach($path as $key => $value) {
				if($value) {
					$action .= $value .'/';
				}
			}
		}
		
		return $base . self::$link_base . $version . $lang . $module . $action;
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
		
		$fields = new FieldSet(
			new TextField('Search')
		);
		
		$actions = new FieldSet(
			new FormAction('doDocumentationSearchForm', 'Search')
		);
		
		return new Form($this, 'DocumentationSearchForm', $fields, $actions);
	}
	
	/**
	 * Past straight to results, display and encode the query
	 */
	function doDocumentationSearchForm($data, $form) {
		$query = (isset($data['Search'])) ? urlencode($data['Search']) : "";
		
		$this->redirect('DocumentationSearch/search/'. $query);
	}
}